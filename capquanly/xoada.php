<?php
session_start();
include('../config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['code'])) {
    echo "<script>alert('Vui lòng đăng nhập.'); window.location.href='../index.php';</script>";
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    echo "<script>alert('Mã dự án không hợp lệ.'); window.history.back();</script>";
    exit;
}

$_ID = trim($_GET['id']);

// Kiểm tra xem dự án có liên kết với công việc nào không (Prepared Statement)
$stmtCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM danhsachcongviec WHERE DA_MA = ?");
$stmtCheck->bind_param("s", $_ID);
$stmtCheck->execute();
$rowCheck = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if ($rowCheck['cnt'] > 0) {
    $message = "Không thể xóa dự án vì có " . $rowCheck['cnt'] . " công việc đang liên kết!";
} else {
    $stmtDel = $conn->prepare("DELETE FROM duan WHERE DA_MA = ?");
    $stmtDel->bind_param("s", $_ID);
    $result = $stmtDel->execute();
    $affected = $stmtDel->affected_rows;
    $stmtDel->close();

    if ($result && $affected > 0) {
        $message = "Xóa dự án thành công!";
    } else {
        $message = "Xóa dự án không thành công. Vui lòng thử lại!";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Xóa Dự Án</title>
</head>
<body>
    <script type="text/javascript">
        alert("<?php echo addslashes($message); ?>");
        window.location.href = "danhsachduan.php";
    </script>
</body>
</html>
