<?php
// Bắt đầu session trước khi bất kỳ output nào
session_start();

// Kiểm tra đăng nhập dựa trên session hiện tại
if (!isset($_SESSION['code'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy user_id từ session
$user_id = $_SESSION['code'];

// Kết nối database
include('../../config.php');

// Kiểm tra kết nối
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'message' => 'Không thể kết nối đến cơ sở dữ liệu',
        'error' => mysqli_connect_error()
    ]);
    exit;
}

// Đặt charset kết nối
mysqli_set_charset($conn, "utf8mb4");

// Lấy dữ liệu từ form
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 1;
// Đã lấy user_id từ session ở trên

// Validate dữ liệu
if (empty($title)) {
    echo json_encode(['status' => false, 'message' => 'Vui lòng nhập tên công việc']);
    exit;
}

if (empty($start_date)) {
    echo json_encode(['status' => false, 'message' => 'Vui lòng chọn ngày bắt đầu']);
    exit;
}

if ($duration < 1) {
    echo json_encode(['status' => false, 'message' => 'Số ngày hoàn thành phải lớn hơn 0']);
    exit;
}

// Tính toán ngày kết thúc không tính thứ 7, chủ nhật
$startDateTime = new DateTime($start_date);
$endDateTime = clone $startDateTime;

// Trừ 1 vì đã tính ngày bắt đầu
$working_days_needed = $duration - 1;
$days_added = 0;
$total_days = 0;

// Bắt đầu từ ngày tiếp theo
if ($working_days_needed > 0) {
    while ($days_added < $working_days_needed) {
        $endDateTime->modify('+1 day');
        $day_of_week = $endDateTime->format('N'); // 1 (Monday) to 7 (Sunday)
        
        // Chỉ đếm ngày thứ 2 đến thứ 6
        if ($day_of_week <= 5) {
            $days_added++;
        }
        $total_days++;
        
        // Đảm bảo không bị lặp vô hạn
        if ($total_days > 365) break;
    }
}

// Đặt thời gian về cuối ngày (23:59:59)
$endDateTime->setTime(23, 59, 59);
$end_date = $endDateTime->format('Y-m-d H:i:s');

// Định dạng lại ngày bắt đầu để bắt đầu từ đầu ngày (00:00:00)
$start_date = $startDateTime->format('Y-m-d') . ' 00:00:00';

// Bắt đầu transaction
mysqli_begin_transaction($conn);

function generateWorkCode($conn) {
    $datePrefix = date('Ymd');
    $microtime = str_replace('.', '', microtime(true));
    $query = "SELECT MAX(CAST(SUBSTRING(DSCV_MA, 15) AS UNSIGNED)) as max_num FROM danhsachcongviec WHERE DSCV_MA LIKE 'CV" . $datePrefix . $microtime . "%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $taskCounter = ($row['max_num'] !== null) ? $row['max_num'] + 1 : 1;
    $new_code = 'CV' . $datePrefix . substr($microtime, -6) . str_pad($taskCounter, 3, '0', STR_PAD_LEFT);
    return $new_code;
}

try {
    $work_code = generateWorkCode($conn);
    
    // Chuẩn bị câu lệnh SQL phù hợp với cấu trúc bảng
    // Thêm với trạng thái 1 (đang tiến hành) và không thuộc dự án nào (DA_MA IS NULL)
    $sql = "INSERT INTO danhsachcongviec 
            (DSCV_MA, DSCV_TEN, DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, 
             DSCV_TRANGTHAI, TV_MA, DA_MA, PH_MA, TIEN_DO) 
            VALUES (?, ?, ?, ?, 1, ?, NULL, 1, 0)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . mysqli_error($conn));
    }
    
    // Bind các tham số
    mysqli_stmt_bind_param($stmt, "sssss", $work_code, $title, $start_date, $end_date, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Commit transaction nếu thành công
        mysqli_commit($conn);
        echo json_encode(['status' => true, 'message' => 'Thêm công việc thành công', 'work_code' => $work_code]);
    } else {
        throw new Exception('Lỗi khi thêm công việc: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
        'error' => $e->getTraceAsString()
    ]);
} finally {
    // Không đóng kết nối ở đây vì nó được tạo từ file config.php
}
?>
