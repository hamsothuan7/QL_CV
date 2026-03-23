<?php
// Bật báo lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// // Bỏ qua kiểm tra ABSPATH và IN_SCRIPT trong môi trường phát triển
// if (!defined('ABSPATH') && !defined('IN_SCRIPT') && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
//     header('Content-Type: application/json');
//     die(json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ']));
// }

// Kết nối database
require_once('../../config.php');

// Kiểm tra kết nối
if (mysqli_connect_errno()) {
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Kết nối database thất bại: ' . mysqli_connect_error()]));
}

// Thiết lập charset
mysqli_set_charset($conn, 'utf8mb4');

// Hàm trả về JSON response
function jsonResponse($status, $message = '', $data = []) {
    header('Content-Type: application/json');
    $response = ['status' => $status];
    if ($message) $response['message'] = $message;
    if (!empty($data)) $response = array_merge($response, $data);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Phương thức không hợp lệ');
}

// Kiểm tra đăng nhập
session_start();
if (!isset($_SESSION['username'])) {
    jsonResponse('error', 'Chưa đăng nhập');
}

// Lấy dữ liệu
$tenMau = isset($_POST['tenmau']) ? trim($_POST['tenmau']) : '';
$tasks = isset($_POST['task_name']) ? $_POST['task_name'] : [];
$durations = isset($_POST['task_duration']) ? $_POST['task_duration'] : [];
$prereqs = isset($_POST['task_prereq']) ? $_POST['task_prereq'] : [];

// Validate
if (empty($tenMau)) {
    jsonResponse('error', 'Vui lòng nhập tên mẫu công việc');
}

if (empty($tasks)) {
    jsonResponse('error', 'Vui lòng thêm ít nhất một công việc');
}

try {
    // Chuẩn bị dữ liệu công việc dạng JSON
    $tasksData = [];
    $taskCount = min(count($tasks), count($durations), count($prereqs));

    // Tạo trước mảng ánh xạ STT => mã công việc
    $sttToMaCv = [];
    for ($i = 0; $i < $taskCount; $i++) {
        $sttToMaCv[$i+1] = 'CV' . str_pad($i+1, 3, '0', STR_PAD_LEFT);
    }

    for ($i = 0; $i < $taskCount; $i++) {
        $taskName = trim($tasks[$i]);
        $duration = intval($durations[$i]);
        $prereqStt = trim($prereqs[$i]);
        $ma_cv_tien_quyet = '';
        if ($prereqStt !== '' && isset($sttToMaCv[$prereqStt])) {
            $ma_cv_tien_quyet = $sttToMaCv[$prereqStt];
        }
        if (!empty($taskName)) {
            $tasksData[] = [
                'name' => mb_substr($taskName, 0, 255, 'UTF-8'),
                'duration' => max(1, min(365, $duration)),
                'ma_cv_tien_quyet' => $ma_cv_tien_quyet
            ];
        }
    }

    if (empty($tasksData)) {
        jsonResponse('error', 'Vui lòng thêm ít nhất một công việc hợp lệ');
    }

    // Tạo mã mẫu duy nhất cho macvmau
    $macvmau = 'MAU_' . date('YmdHis') . rand(100,999);

    // Bắt đầu transaction
    mysqli_begin_transaction($conn);

    // Thêm mẫu vào bảng maucv (lưu vào cột macvmau)
    $sql_mau = "INSERT INTO maucv (tenmau, macvmau, trangthai, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())";
    $stmt_mau = mysqli_prepare($conn, $sql_mau);
    if (!$stmt_mau) throw new Exception('Lỗi chuẩn bị câu lệnh SQL: ' . mysqli_error($conn));
    mysqli_stmt_bind_param($stmt_mau, 'ss', $tenMau, $macvmau);
    if (!mysqli_stmt_execute($stmt_mau)) throw new Exception('Lỗi khi thêm mẫu: ' . mysqli_stmt_error($stmt_mau));
    mysqli_stmt_close($stmt_mau);

    // Lấy id tự tăng của mẫu vừa tạo
    $ma_mau_id = mysqli_insert_id($conn);

    // Thêm từng công việc vào bảng cv_mau, liên kết qua ma_mau = id (int)
    $stt = 1;
    foreach ($tasksData as $task) {
        $ma_cv = 'CV' . str_pad($stt++, 3, '0', STR_PAD_LEFT);
        $sql_cv = "INSERT INTO cv_mau (ma_mau, ma_cv, ten_cv, thoi_gian_du_kien, ma_cv_tien_quyet, trang_thai) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt_cv = mysqli_prepare($conn, $sql_cv);
        if (!$stmt_cv) throw new Exception('Lỗi chuẩn bị SQL công việc: ' . mysqli_error($conn));
        mysqli_stmt_bind_param($stmt_cv, 'issss', $ma_mau_id, $ma_cv, $task['name'], $task['duration'], $task['ma_cv_tien_quyet']);
        if (!mysqli_stmt_execute($stmt_cv)) throw new Exception('Lỗi khi thêm công việc mẫu: ' . mysqli_stmt_error($stmt_cv));
        mysqli_stmt_close($stmt_cv);
    }

    // Commit transaction nếu mọi thứ thành công
    if (!mysqli_commit($conn)) {
        throw new Exception('Lỗi khi commit transaction: ' . mysqli_error($conn));
    }

    jsonResponse('success', 'Lưu mẫu công việc thành công', [
        'macvmau' => $macvmau,
        'tenmau' => $tenMau
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}