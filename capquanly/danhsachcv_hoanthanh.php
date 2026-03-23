<?php
include('../config.php');
if (!$conn) {
    die("connect error" . mysqli_connect_errno());
}

$sql = "SELECT * FROM `danhsachcongviec` WHERE DSCV_TRANGTHAI = '2'";
$result = mysqli_query($conn, $sql);

$tasks = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tasks[] = $row;
    }
}

echo json_encode($tasks);
?>
