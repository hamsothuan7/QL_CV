<?php
include('../../config.php');
// Bắt đầu session
session_start();

// Kiểm tra xem session đã được tạo hay chưa và nếu tên người dùng không được lưu trữ trong session 'username', chuyển hướng người dùng đến trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

try {
    $name = $_SESSION['username'];
    $text = trim($_POST['text']);
    $code = $_POST['code'];
    //Get TV
    $sql = "SELECT TV_MA FROM thanhvien WHERE TV_TEN = '$name' ";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    $userId = $user['TV_MA'] ?? "";


    $sql = "INSERT INTO binhluan(TEXT, DA_MA, TV_MA) VALUES('$text', '$code', '$userId') ";

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