<?php
include('../../config.php');
session_start();

// Kiểm tra đăng nhập bằng đúng session key mà dự án sử dụng
if (!isset($_SESSION['code'])) {
    echo json_encode(['status' => false, 'message' => 'Vui lòng đăng nhập để bình luận']);
    exit;
}

try {
    $text   = trim($_POST['text'] ?? '');
    $code   = trim($_POST['code'] ?? '');
    $userId = $_SESSION['code'];

    if (empty($text)) {
        echo json_encode(['status' => false, 'message' => 'Nội dung bình luận không được để trống']);
        return;
    }

    if (empty($code)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    $sql  = "INSERT INTO binhluan_cv(TEXT, DSCV_MA, TV_MA) VALUES(?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param('sss', $text, $code, $userId);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        echo json_encode([
            'status'  => true,
            'data'    => $code,
            'message' => 'Bình luận thành công'
        ]);
    } else {
        echo json_encode([
            'status'  => false,
            'message' => 'Bình luận thất bại'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>