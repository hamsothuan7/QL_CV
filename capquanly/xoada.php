<?php
include('../config.php');
$_ID = $_GET['id'];
// Kiểm tra xem dự án có liên kết với công việc nào không
$query_check = "SELECT * FROM danhsachcongviec WHERE DA_MA = '$_ID'";
$result_check = mysqli_query($conn, $query_check);

if (mysqli_num_rows($result_check) > 0) {
    // Nếu có công việc liên kết với dự án này, không thực hiện xóa và hiển thị thông báo
    $message = "Không thể xóa dự án vì có công việc đang thực hiện!";
} else {
    // Nếu không có công việc liên kết, thực hiện xóa dự án
    $query_delete = "DELETE FROM duan WHERE DA_MA = '$_ID'";
    $result_delete = mysqli_query($conn, $query_delete);
    
    if ($result_delete > 0) {
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
        alert("<?php echo $message; ?>");
        window.location.href = "danhsachduan.php";
    </script>
</body>

</html>
