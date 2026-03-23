<?php
session_start();
include '../../config.php'; // Kết nối CSDL

header('Content-Type: application/json');

// --- Helper Functions ---

/**
 * Cập nhật ngày kết thúc dự án dựa trên ngày kết thúc muộn nhất của các công việc
 * @param mysqli $conn Kết nối database
 * @param string $project_code Mã dự án cần cập nhật
 * @return bool Thành công hay thất bại
 */
function updateProjectEndDate($conn, $project_code) {
    // Tìm ngày kết thúc muộn nhất trong tất cả công việc của dự án
    $sql = "SELECT MAX(DSCV_NGAYKETTHUC) as max_end_date 
            FROM danhsachcongviec 
            WHERE DA_MA = ?
            AND DSCV_NGAYKETTHUC IS NOT NULL";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    
    mysqli_stmt_bind_param($stmt, 's', $project_code);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$row || empty($row['max_end_date'])) {
        return false; // Không có công việc nào hoặc không có ngày kết thúc
    }
    
    // Cập nhật ngày kết thúc cho dự án
    $update_sql = "UPDATE duan SET DA_NGAYKETTHUC = ? WHERE DA_MA = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if (!$update_stmt) return false;
    
    mysqli_stmt_bind_param($update_stmt, 'ss', $row['max_end_date'], $project_code);
    $result = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    return $result;
}

/**
 * Hàm tính ngày kết thúc công việc (không tính thứ 7, chủ nhật)
 * @param string $startDate 'Y-m-d' - Ngày bắt đầu (đã bao gồm)
 * @param int $duration Số ngày làm việc (tính cả ngày bắt đầu)
 * @return string 'Y-m-d' - Ngày kết thúc
 */
function calculateEndDate($startDate, $duration) {
    $current_date = new DateTime($startDate);
    $days_added = 0;
    $total_days = 0;
    
    // Trừ 1 vì đã tính ngày bắt đầu
    $working_days_needed = $duration > 0 ? $duration - 1 : 0;
    
    while ($days_added < $working_days_needed) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('N'); // 1 (Monday) to 7 (Sunday)
        
        // Chỉ đếm ngày thứ 2 đến thứ 6
        if ($day_of_week <= 5) {
            $days_added++;
        }
        $total_days++;
        
        // Đảm bảo không bị lặp vô hạn
        if ($total_days > 365) break;
    }
    
    return $current_date->format('Y-m-d');
}

/**
 * Hàm tạo mã công việc mới không trùng lặp
 * @param mysqli $conn
 * @return string
 */
function generateProjectCode($conn)
{
    $datePrefix = date('Ymd');
    $microtime = str_replace('.', '', microtime(true));
    $query = "SELECT MAX(CAST(SUBSTRING(DSCV_MA, 15) AS UNSIGNED)) as max_num FROM danhsachcongviec WHERE DSCV_MA LIKE 'CV" . $datePrefix . $microtime . "%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $taskCounter = ($row['max_num'] !== null) ? $row['max_num'] + 1 : 1;
    $project_code = 'CV' . $datePrefix . substr($microtime, -6) . str_pad($taskCounter, 3, '0', STR_PAD_LEFT);
    return $project_code;
}


// --- Main Logic ---

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Phương thức không được hỗ trợ.']);
    exit;
}

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['nnd_ma'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này.']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // --- Lấy và xác thực dữ liệu đầu vào ---
    $project_code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $member_id = $_POST['member_id'] ?? '';
    $room_id = $_POST['room_id'] ?? '';
    $ph_id = $_POST['ph_id'] ?? null;
    $duration = filter_var($_POST['duration'] ?? 0, FILTER_VALIDATE_INT);
    $start_date_input = $_POST['start_date'] ?? '';
    $prerequisite_task = $_POST['prerequisite_task'] ?? '';
    $parent_task = $_POST['parent'] ?? '';
    $creator_id = $_SESSION['nnd_ma'];

    // Kiểm tra các trường bắt buộc
    $requiredFields = [
        'code' => $project_code, 
        'name' => $name, 
        'member_id' => $member_id, 
        'duration' => $duration,
        'start_date' => $start_date_input
    ];
    $missingFields = [];
    foreach ($requiredFields as $key => $value) {
        if (empty($value)) {
            $missingFields[] = $key;
        }
    }

    if (!empty($missingFields)) {
        throw new Exception('Vui lòng điền đầy đủ các trường bắt buộc: ' . implode(', ', $missingFields));
    }
    if ($duration <= 0) {
        throw new Exception('Thời gian hoàn thành phải là một số nguyên dương.');
    }

    // --- Tính toán Ngày Bắt Đầu và Kết Thúc ---
    // Sử dụng ngày bắt đầu từ form nếu có, nếu không thì lấy ngày hôm nay
    $start_date = !empty($start_date_input) ? $start_date_input : date('Y-m-d');
    
    // Kiểm tra định dạng ngày tháng
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        throw new Exception('Định dạng ngày bắt đầu không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD');
    }

    // Nếu có công việc tiên quyết, kiểm tra xem ngày bắt đầu có sớm hơn ngày kết thúc của công việc tiên quyết không
    if (!empty($prerequisite_task)) {
        $sql_prereq = "SELECT DSCV_NGAYKETTHUC FROM danhsachcongviec WHERE DSCV_MA = ?";
        $stmt_prereq = mysqli_prepare($conn, $sql_prereq);
        mysqli_stmt_bind_param($stmt_prereq, 's', $prerequisite_task);
        mysqli_stmt_execute($stmt_prereq);
        $result_prereq = mysqli_stmt_get_result($stmt_prereq);
        
        if ($row_prereq = mysqli_fetch_assoc($result_prereq)) {
            $prereq_end_date = new DateTime($row_prereq['DSCV_NGAYKETTHUC']);
            $prereq_end_date->modify('+1 day'); // Ngày bắt đầu ít nhất là ngày tiếp theo sau khi kết thúc công việc tiên quyết
            
            // Bỏ qua cuối tuần cho ngày bắt đầu tối thiểu
            while (in_array($prereq_end_date->format('N'), [6, 7])) {
                $prereq_end_date->modify('+1 day');
            }
            
            $min_start_date = $prereq_end_date->format('Y-m-d');
            
            // Nếu ngày bắt đầu nhập vào sớm hơn ngày tối thiểu, sử dụng ngày tối thiểu
            if (strtotime($start_date) < strtotime($min_start_date)) {
                $start_date = $min_start_date;
            }
        }
    }

    // Tính ngày kết thúc dựa trên ngày bắt đầu và thời gian hoàn thành (bỏ qua cuối tuần)
    $end_date = calculateEndDate($start_date, $duration);

    // --- Tạo mã công việc mới ---
    $work_code = generateProjectCode($conn);
    $status = $_POST['status'] ?? '5'; // Lấy trạng thái từ form, mặc định là 5 nếu không có

    // --- Chèn vào cơ sở dữ liệu ---
    $sql_insert = "INSERT INTO danhsachcongviec 
                    (DSCV_MA, DA_MA, DSCV_TEN, TV_MA, PB_MA, PH_MA, DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, PARENT_ID, DSCV_TIENQUYET, DSCV_TRANGTHAI) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh: " . mysqli_error($conn));
    }

    // Gán giá trị null nếu trống
    $ph_id = !empty($ph_id) ? $ph_id : null;
    $parent_task = !empty($parent_task) ? $parent_task : null;
    
    // Xử lý công việc tiên quyết
    $prerequisite_task_db = null;
    if (!empty($prerequisite_task) && $prerequisite_task !== '--Không có--') {
        // Làm sạch giá trị đầu vào
        $prerequisite_task = trim($prerequisite_task);
        
        // Kiểm tra xem mã công việc tiên quyết có tồn tại không
        $check_sql = "SELECT DSCV_MA FROM danhsachcongviec WHERE DSCV_MA = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, 's', $prerequisite_task);
            if (mysqli_stmt_execute($check_stmt)) {
                $result = mysqli_stmt_get_result($check_stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $prerequisite_task_db = $row['DSCV_MA'];
                } else {
                    // Nếu không tìm thấy với mã đầy đủ, thử tìm với phần đầu của mã
                    $like_sql = "SELECT DSCV_MA FROM danhsachcongviec WHERE DSCV_MA LIKE ? LIMIT 1";
                    $like_stmt = mysqli_prepare($conn, $like_sql);
                    if ($like_stmt) {
                        $like_param = $prerequisite_task . "%";
                        mysqli_stmt_bind_param($like_stmt, 's', $like_param);
                        if (mysqli_stmt_execute($like_stmt)) {
                            $like_result = mysqli_stmt_get_result($like_stmt);
                            if ($like_row = mysqli_fetch_assoc($like_result)) {
                                $prerequisite_task_db = $like_row['DSCV_MA'];
                            }
                        }
                        mysqli_stmt_close($like_stmt);
                    }
                }
            }
            mysqli_stmt_close($check_stmt);
        }
        
        // Nếu vẫn không tìm thấy, đặt lại thành null để tránh lỗi
        if (empty($prerequisite_task_db)) {
            $prerequisite_task_db = null;
        }
    }


    // Cập nhật: chuỗi type là 'sssssssssss' (11 ký tự) và 11 biến, khớp với 11 dấu '?' trong câu lệnh INSERT
    mysqli_stmt_bind_param($stmt_insert, 'sssssssssss', 
        $work_code,
        $project_code,
        $name,
        $member_id,
        $room_id,
        $ph_id,
        $start_date,
        $end_date,
        $parent_task,
        $prerequisite_task_db,
        $status
    );

    if (mysqli_stmt_execute($stmt_insert)) {
        // Cập nhật ngày kết thúc dự án
        updateProjectEndDate($conn, $project_code);
        
        mysqli_commit($conn);
        echo json_encode(['status' => true, 'message' => 'Thêm công việc thành công!']);
    } else {
        throw new Exception("Lỗi khi thêm công việc mới: " . mysqli_stmt_error($stmt_insert));
    }

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt_prereq)) mysqli_stmt_close($stmt_prereq);
    if (isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
    mysqli_close($conn);
}
?>
