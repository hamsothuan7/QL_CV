<?php
    include('../config.php');
    $_ID = $_GET['id'];
    if ($conn != true) {
        die("kết nối thất bại " . mysqli_connect_error());
    } else {
        $query = "DELETE FROM danhsachcongviec WHERE DSCV_MA = '$_ID'";
        $result = mysqli_query($conn, $query);
        if ($result >0) {
             echo "<script type ='text/javascript'>";
             echo "alert('Xóa công việc thành công!');";
             echo "window.location.href='danhsachcv.php'";
             echo "</script>";
            } else {
            echo "Data is empty".mysqli_error($conn);
        }
    }
    mysqli_close($conn);
?>