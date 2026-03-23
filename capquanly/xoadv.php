<?php
    include('../config.php');
    $_ID = $_GET['id'];

    // Kiểm tra xem đơn vị này có công việc liên quan không
    $check_query = "SELECT * FROM danhsachcongviec WHERE PH_MA = '$_ID'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Nếu có công việc liên quan, không xóa và thông báo
        echo "<script type='text/javascript'>";
        echo "alert('Không thể xóa đơn vị vì còn công việc liên quan.');";
        echo "window.location.href='donviphoihop.php';";
        echo "</script>";
    } else {
        // Nếu không có công việc, tiến hành xóa
        $query = "DELETE FROM donviphoihop WHERE PH_MA = '$_ID'";
        $result = mysqli_query($conn, $query);

        if ($result > 0) {
            echo "<script type='text/javascript'>";
            echo "alert('Xóa đơn vị thành công.');";
            echo "window.location.href='donviphoihop.php';";
            echo "</script>";
        } else {
            echo "<script type='text/javascript'>";
            echo "alert('Lỗi khi xóa đơn vị: " . mysqli_error($conn) . "');";
            echo "window.location.href='donviphoihop.php';";
            echo "</script>";
        }
    }

    mysqli_close($conn);
?>
