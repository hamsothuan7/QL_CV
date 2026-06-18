<?php
session_start();
include('../config.php');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['code'])) {
    echo "<script>alert('Vui lòng đăng nhập.'); window.location.href='../index.php';</script>";
    exit;
}
if (!isset($_SESSION['nnd_ma']) || $_SESSION['nnd_ma'] != 1) {
    echo "<script>alert('Bạn không có quyền truy cập chức năng này.'); window.location.href='index.php';</script>";
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    echo "<script>alert('Mã đơn vị không hợp lệ.'); window.history.back();</script>";
    exit;
}

$_ID = trim($_GET['id']);

// Kiểm tra xem đơn vị này có công việc liên quan không (Prepared Statement)
$stmtCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM danhsachcongviec WHERE PH_MA = ?");
$stmtCheck->bind_param("s", $_ID);
$stmtCheck->execute();
$rowCheck = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if ($rowCheck['cnt'] > 0) {
    echo "<script type='text/javascript'>";
    echo "alert('Không thể xóa đơn vị vì còn " . $rowCheck['cnt'] . " công việc liên quan.');";
    echo "window.location.href='donviphoihop.php';";
    echo "</script>";
} else {
    $stmtDel = $conn->prepare("DELETE FROM donviphoihop WHERE PH_MA = ?");
    $stmtDel->bind_param("s", $_ID);
    $result = $stmtDel->execute();
    $affected = $stmtDel->affected_rows;
    $stmtDel->close();

    if ($result && $affected > 0) {
        echo "<script type='text/javascript'>";
        echo "alert('Xóa đơn vị thành công.');";
        echo "window.location.href='donviphoihop.php';";
        echo "</script>";
    } else {
        echo "<script type='text/javascript'>";
        echo "alert('Lỗi khi xóa đơn vị. Vui lòng thử lại.');";
        echo "window.location.href='donviphoihop.php';";
        echo "</script>";
    }
}

mysqli_close($conn);
?>
