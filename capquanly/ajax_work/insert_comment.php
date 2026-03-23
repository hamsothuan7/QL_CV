<?php
include('../../config.php');
// Bắt đầu session
session_start();

// Kiểm tra xem session đã được tạo hay chưa và nếu tên người dùng không được lưu trữ trong session 'username', chuyển hướng người dùng đến trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: ../login/index.php');
    exit;
}

try {
    $text = trim($_POST['text']);
    $code = $_POST['code'];
    $userId = $_SESSION['code'] ?? "";

    $sql = "INSERT INTO binhluan_cv(TEXT, DSCV_MA, TV_MA) VALUES('$text', '$code', '$userId') ";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Bình luận dự án thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Bình luận dự án thất bại'
        ]);
        return;
    }

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}

?>