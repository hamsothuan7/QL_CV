<?php
header('Content-Type: application/json; charset=utf-8');

// Khởi tạo response mặc định
$response = ['success' => false, 'message' => ''];

try {
    // Include file cấu hình
    $rootPath = dirname(dirname(dirname(__FILE__)));
    $configPath = $rootPath . '/config.php';
    
    if (!file_exists($configPath)) {
        throw new Exception('Không tìm thấy file cấu hình (config.php)');
    }
    include($configPath);
    
    // Kiểm tra session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra đăng nhập
    if (!isset($_SESSION['username']) || !isset($_SESSION['code'])) {
        throw new Exception('Chưa đăng nhập hoặc phiên đăng nhập đã hết hạn');
    }

    // Kiểm tra dữ liệu đầu vào
    if (!isset($_POST['task_id']) || empty($_POST['task_id'])) {
        throw new Exception('Thiếu thông tin công việc');
    }

    $taskId = trim($_POST['task_id']);
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (empty($content)) {
        throw new Exception('Nội dung bình luận không được để trống');
    }

    // Chuẩn bị và thực thi câu lệnh SQL
    $sql = "INSERT INTO binhluan_cv (DSCV_MA, TV_MA, NOI_DUNG, NGAY_BINH_LUAN) 
            VALUES (?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn));
    }
    
    $userId = $_SESSION['code'];
    mysqli_stmt_bind_param($stmt, 'sss', $taskId, $userId, $content);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi khi lưu bình luận: ' . mysqli_stmt_error($stmt));
    }
    
    // Lấy thông tin bình luận vừa thêm
    $commentId = mysqli_insert_id($conn);
    
    $response = [
        'success' => true,
        'message' => 'Đã thêm bình luận thành công',
        'comment_id' => $commentId
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(500);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
