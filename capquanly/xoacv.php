<?php
session_start();
include('../config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['code'])) {
    echo "<script>alert('Vui lòng đăng nhập để thực hiện chức năng này.'); window.location.href='../index.php';</script>";
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    echo "<script>alert('Mã công việc không hợp lệ.'); window.history.back();</script>";
    exit;
}

if ($conn != true) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

$_ID = trim($_GET['id']);

// Sử dụng Prepared Statement để tránh SQL Injection
$stmt = $conn->prepare("DELETE FROM danhsachcongviec WHERE DSCV_MA = ?");
$stmt->bind_param("s", $_ID);
$result = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($result && $affected > 0) {
    echo "<script type='text/javascript'>";
    echo "alert('Xóa công việc thành công!');";
    echo "window.location.href='danhsachcv.php'";
    echo "</script>";
} else {
    echo "<script type='text/javascript'>";
    echo "alert('Xóa công việc thất bại hoặc không tìm thấy công việc.');";
    echo "window.history.back();";
    echo "</script>";
}

mysqli_close($conn);
?>