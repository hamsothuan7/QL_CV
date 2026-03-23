<?php
// insert_duan_maucv.php
header('Content-Type: application/json; charset=utf-8');

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Kết nối database
include(__DIR__ . '/../../config.php');

// Đặt bộ mã ký tự UTF-8
mysqli_set_charset($conn, 'utf8mb4');

// Lấy dữ liệu từ POST
$duan_ma = isset($_POST['duan_ma']) ? trim($_POST['duan_ma']) : null;
$mamau = isset($_POST['mamau']) ? intval($_POST['mamau']) : null;
$tenmau = isset($_POST['tenmau']) ? trim($_POST['tenmau']) : 'Không tên';
$tengoithau = isset($_POST['tengoithau']) ? trim($_POST['tengoithau']) : null;

// --- Validation ---
if (!$duan_ma || !$mamau) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã dự án hoặc mã mẫu.']);
    exit;
}

// Bắt đầu transaction
mysqli_begin_transaction($conn);

try {
    // 1. Kiểm tra xem dự án đã có liên kết mẫu chưa
    $check_sql = "SELECT COUNT(*) as count FROM duan_maucv WHERE duan_ma = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 's', $duan_ma);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($check_stmt);

    if ($count > 0) {
        throw new Exception('Dự án này đã được liên kết với một mẫu công việc trước đó.');
    }

    // 2. Liên kết dự án với mẫu trong bảng `duan_maucv`
    $link_sql = "INSERT INTO duan_maucv (duan_ma, mamau, tengoithau) VALUES (?, ?, ?)";
    $link_stmt = mysqli_prepare($conn, $link_sql);
    mysqli_stmt_bind_param($link_stmt, 'sis', $duan_ma, $mamau, $tengoithau);
    if (!mysqli_stmt_execute($link_stmt)) {
        throw new Exception('Lỗi khi liên kết mẫu với dự án: ' . mysqli_stmt_error($link_stmt));
    }
    mysqli_stmt_close($link_stmt);

    // 3. Lấy tất cả công việc từ mẫu đã chọn
    $tasks_sql = "SELECT ten_cv, thoi_gian_du_kien FROM cv_mau WHERE ma_mau = ? AND trang_thai = 1 ORDER BY id ASC";
    $tasks_stmt = mysqli_prepare($conn, $tasks_sql);
    mysqli_stmt_bind_param($tasks_stmt, 'i', $mamau);
    mysqli_stmt_execute($tasks_stmt);
    $tasks_result = mysqli_stmt_get_result($tasks_stmt);
    $tasks = mysqli_fetch_all($tasks_result, MYSQLI_ASSOC);
    mysqli_stmt_close($tasks_stmt);

    if (empty($tasks)) {
        // Nếu mẫu không có công việc nào, vẫn coi là thành công vì đã liên kết.
        mysqli_commit($conn);
        echo json_encode(['status' => 'success', 'message' => 'Đã liên kết mẫu thành công (mẫu không có công việc nào).']);
        exit;
    }

    // 4. KHÔNG tự động thêm công việc từ mẫu vào dự án nữa theo yêu cầu người dùng
    // (Đoạn code thêm công việc đã bị loại bỏ)


    // 5. Ghi log hệ thống
    // $log_text = "Đã áp dụng mẫu công việc '" . mysqli_real_escape_string($conn, $tenmau) . "' vào dự án.";
    // $log_sql = "INSERT INTO binhluan (TEXT, DA_MA, TV_MA) VALUES (?, ?, 'system')";
    // $log_stmt = mysqli_prepare($conn, $log_sql);
    // mysqli_stmt_bind_param($log_stmt, 'ss', $log_text, $duan_ma);
    // mysqli_stmt_execute($log_stmt);
    // mysqli_stmt_close($log_stmt);
    
    // Nếu mọi thứ thành công, commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['status' => 'success', 'message' => 'Đã liên kết mẫu thành công, KHÔNG tự động thêm công việc vào dự án.']);

} catch (Exception $e) {
    // Nếu có lỗi, rollback transaction
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    // Đóng kết nối
    mysqli_close($conn);
}

