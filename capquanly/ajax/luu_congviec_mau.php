<?php
// Bắt đầu session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kết nối database
require_once('../../config.php');

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['mamau']) || !isset($_POST['congviec'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

// Hàm tính ngày kết thúc không tính thứ 7, chủ nhật
function calculateEndDate($start_date, $working_days) {
    $current_date = new DateTime($start_date);
    $days_added = 0;
    $total_days = 0;
    
    while ($days_added < $working_days) {
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

$mamau = mysqli_real_escape_string($conn, $_POST['mamau']);
$congviecList = json_decode($_POST['congviec'], true);
$tengoithau = isset($_POST['tengoithau']) ? trim($_POST['tengoithau']) : null;

if (!is_array($congviecList)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();
    $savedCount = 0;
    
    // Lấy mã dự án từ POST hoặc từ session
    $mada = isset($_POST['mada']) ? $_POST['mada'] : (isset($_SESSION['mada']) ? $_SESSION['mada'] : null);
    
    if (!$mada) {
        throw new Exception('Không tìm thấy mã dự án');
    }
    
    // Lấy thông tin dự án để lấy ngày bắt đầu
    $sql_duan = "SELECT DA_NGAYBATDAU FROM duan WHERE DA_MA = ?";
    $stmt_duan = $conn->prepare($sql_duan);
    $stmt_duan->bind_param("s", $mada);
    $stmt_duan->execute();
    $result_duan = $stmt_duan->get_result();
    
    if ($result_duan->num_rows === 0) {
        throw new Exception('Không tìm thấy thông tin dự án');
    }
    
    $row_duan = $result_duan->fetch_assoc();
    $ngay_bat_dau_du_an = $row_duan['DA_NGAYBATDAU'];
    
    // Mảng lưu trữ thông tin ngày tháng của các công việc
    $taskDates = [];
    $mada = isset($_POST['mada']) ? $_POST['mada'] : (isset($_SESSION['mada']) ? $_SESSION['mada'] : '');
    
    // Debug: Log session và mada
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Mã dự án (mada): " . $mada);
    
    // Kiểm tra xem DA_MA có tồn tại trong bảng duan không
    $checkDa = $conn->prepare("SELECT COUNT(*) as count FROM duan WHERE DA_MA = ?");
    $checkDa->bind_param("s", $mada);
    $checkDa->execute();
    $result = $checkDa->get_result();
    $row = $result->fetch_assoc();
    $checkDa->close();
    
    if ($row['count'] == 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Mã dự án không tồn tại trong hệ thống.'
        ]);
        exit;
    }
    
    // Lưu từng công việc
    $savedTasks = []; // Lưu trữ thông tin các công việc đã lưu để xử lý tiên quyết
    $datePrefix = date('Ymd'); // Định dạng YYYYMMDD
    
    // Lấy số thứ tự cuối cùng cho ngày hiện tại
    $sql = "SELECT MAX(CAST(SUBSTRING(DSCV_MA, 11) AS UNSIGNED)) as max_num 
            FROM danhsachcongviec 
            WHERE DSCV_MA LIKE 'CV" . $datePrefix . "%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $taskCounter = ($row['max_num'] !== null) ? $row['max_num'] + 1 : 1;
    
    // Xử lý từng công việc
    foreach ($congviecList as $index => $cv) {
        $ten_cv = mysqli_real_escape_string($conn, $cv['ten_cv']);
        
        // Thêm hậu tố "_tengoithau" vào tên công việc nếu có tên gói thầu
        if (!empty($tengoithau)) {
            $ten_cv .= ' _ ' . mysqli_real_escape_string($conn, $tengoithau);
        }
        
        $thoigian_dukien = intval($cv['thoigian_dukien']);
        $congviec_tienquyet = isset($cv['congviec_tienquyet']) ? intval($cv['congviec_tienquyet']) : 0;
        $nguoiphutrach = mysqli_real_escape_string($conn, $cv['nguoiphutrach']);
        
        // Tạo mã công việc mới với định dạng: CV + năm tháng ngày + microtime + số thứ tự
        $microtime = str_replace('.', '', microtime(true));
        $dscv_ma = 'CV' . date('Ymd') . substr($microtime, -6) . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        

        // Tính toán ngày bắt đầu và kết thúc
        if ($congviec_tienquyet > 0 && isset($taskDates[$congviec_tienquyet])) {
            // Nếu có công việc tiên quyết, lấy ngày kết thúc của công việc tiên quyết + 1 ngày
            $ngay_bat_dau = date('Y-m-d', strtotime($taskDates[$congviec_tienquyet]['ngay_ket_thuc'] . ' +1 day'));
            $ngay_ket_thuc = calculateEndDate($ngay_bat_dau, $thoigian_dukien - 1);
        } else {
            // Nếu không có công việc tiên quyết, ưu tiên sử dụng ngày bắt đầu từ người dùng (nếu có)
            // Nếu không có thì mới dùng ngày bắt đầu dự án
            $ngay_bat_dau = isset($cv['ngaybatdau']) ? $cv['ngaybatdau'] : $ngay_bat_dau_du_an;
            $ngay_ket_thuc = calculateEndDate($ngay_bat_dau, $thoigian_dukien - 1);
        }
        
        // Lưu thông tin ngày tháng của công việc hiện tại
        $taskDates[$index + 1] = [
            'ngay_bat_dau' => $ngay_bat_dau,
            'ngay_ket_thuc' => $ngay_ket_thuc
        ];
        
        // Lưu thông tin công việc để xử lý tiên quyết sau
        $savedTasks[$index + 1] = [
            'ma' => $dscv_ma,
            'tienquyet' => $congviec_tienquyet
        ];
        
        // Thêm vào bảng danhsachcongviec
        if (empty($nguoiphutrach)) {
            // Nếu không có người phụ trách, bỏ qua cột TV_MA
            $sql = "INSERT INTO danhsachcongviec (
                DSCV_MA, DSCV_TEN, 
                DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, 
                DSCV_TRANGTHAI, DA_MA, PARENT_ID
            ) VALUES (?, ?, 
                     ?, ?, 
                     5, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", 
                $dscv_ma,           // DSCV_MA
                $ten_cv,            // DSCV_TEN
                $ngay_bat_dau,      // DSCV_NGAYBATDAU
                $ngay_ket_thuc,     // DSCV_NGAYKETTHUC
                $mada,              // DA_MA (mã dự án)
                $mamau              // PARENT_ID
            );
        } else {
            // Nếu có người phụ trách, thêm vào cột TV_MA
            $sql = "INSERT INTO danhsachcongviec (
                DSCV_MA, DSCV_TEN, 
                DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, 
                DSCV_TRANGTHAI, TV_MA, DA_MA, PARENT_ID
            ) VALUES (?, ?, 
                     ?, ?, 
                     5, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", 
                $dscv_ma,           // DSCV_MA
                $ten_cv,            // DSCV_TEN
                $ngay_bat_dau,      // DSCV_NGAYBATDAU
                $ngay_ket_thuc,     // DSCV_NGAYKETTHUC
                $nguoiphutrach,     // TV_MA (người phụ trách)
                $mada,              // DA_MA (mã dự án)
                $mamau              // PARENT_ID
            );
        }
        
        error_log("Thực thi SQL với: " . json_encode([
            'dscv_ma' => $dscv_ma,
            'ten_cv' => $ten_cv,
            'thoigian_dukien' => $thoigian_dukien,
            'nguoiphutrach' => $nguoiphutrach,
            'mada' => $mada,
            'mamau' => $mamau
        ], JSON_UNESCAPED_UNICODE));
        
        if ($stmt->execute()) {
            $savedCount++;
            // Lưu thông tin công việc đã lưu
            $savedTasks[$index + 1]['id'] = $conn->insert_id;
        } else {
            throw new Exception("Lỗi khi lưu công việc: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Cập nhật lại thông tin công việc tiên quyết
    foreach ($savedTasks as $task) {
        if (!empty($task['tienquyet']) && isset($savedTasks[$task['tienquyet']])) {
            $maTienQuyet = $savedTasks[$task['tienquyet']]['ma'];
            $sql = "UPDATE danhsachcongviec SET DSCV_TIENQUYET = ? WHERE DSCV_MA = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $maTienQuyet, $task['ma']);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi cập nhật công việc tiên quyết: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    // Lưu thông tin vào bảng duan_maucv nếu có mã mẫu
    if (!empty($mamau) && !empty($mada)) {
        // Đảm bảo $tengoithau là null nếu rỗng
        if (!isset($tengoithau) || $tengoithau === '' || $tengoithau === 'null') {
            $tengoithau = null;
        }
        // Luôn insert mới, không kiểm tra trùng lặp duan_ma + mamau
        $sql_duan_maucv = "INSERT INTO duan_maucv (duan_ma, mamau, ngay_tao, trangthai, tengoithau) VALUES (?, ?, NOW(), 1, ?)";
        $stmt_duan_maucv = $conn->prepare($sql_duan_maucv);
        $stmt_duan_maucv->bind_param("sis", $mada, $mamau, $tengoithau);
        if (!$stmt_duan_maucv->execute()) {
            throw new Exception("Lỗi khi lưu thông tin mẫu công việc: " . $stmt_duan_maucv->error);
        }
        $stmt_duan_maucv->close();
    }
    
    // Cập nhật ngày kết thúc dự án dựa trên ngày kết thúc của các công việc
    $sql_max_date = "SELECT MAX(DSCV_NGAYKETTHUC) as max_ngay_ket_thuc 
                    FROM danhsachcongviec 
                    WHERE DA_MA = ?";
    $stmt_max_date = $conn->prepare($sql_max_date);
    $stmt_max_date->bind_param("s", $mada);
    $stmt_max_date->execute();
    $result_max_date = $stmt_max_date->get_result();
    
    if ($row_max_date = $result_max_date->fetch_assoc()) {
        if (!empty($row_max_date['max_ngay_ket_thuc'])) {
            $sql_update_duan = "UPDATE duan SET DA_NGAYKETTHUC = ? WHERE DA_MA = ?";
            $stmt_update_duan = $conn->prepare($sql_update_duan);
            $stmt_update_duan->bind_param("ss", $row_max_date['max_ngay_ket_thuc'], $mada);
            if (!$stmt_update_duan->execute()) {
                throw new Exception("Lỗi khi cập nhật ngày kết thúc dự án: " . $stmt_update_duan->error);
            }
            $stmt_update_duan->close();
        }
    }
    $stmt_max_date->close();
    
    // Commit transaction nếu mọi thứ thành công
    $conn->commit();
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'saved' => $savedCount,
        'message' => 'Đã lưu ' . $savedCount . ' công việc và cập nhật ngày kết thúc dự án',
        'ngay_ket_thuc_moi' => $row_max_date['max_ngay_ket_thuc'] ?? null,
        'mada' => $mada,
        'mamau' => $mamau ?? ''
    ]);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    
    // Trả về lỗi
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi khi lưu công việc: ' . $e->getMessage()
    ]);
}

// Đóng kết nối
$conn->close();
?>
