<?php
include('../config.php');
session_start(); // Start the session

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['btnluu'])) {
    InsertData();
}

/* function generatePBMA()
{
    include('../config.php');
    // Query to get the latest TV_MA
    $query = "SELECT MAX(CAST(SUBSTRING(PB_MA, 1) AS UNSIGNED)) AS max_id FROM phongban";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $max_id = $row['max_id'];

    // Increment the max_id for the new entry
    $new_id = $max_id + 1;

    // Generate new TV_MA
    return $new_id;

}
    */


function InsertData()
{
    include('../config.php');
    // Continue with other data retrieval
    $pbma = $_POST['txtMaPB'];
    $pbten = $_POST['txtTenPB'];

    // Kiểm tra mã phòng ban đã tồn tại chưa
    $check_query = "SELECT * FROM phongban WHERE PB_MA = '$pbma'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Mã số cán bộ \"$pbma\" đã tồn tại. Vui lòng nhập mã khác.'); window.history.back();</script>";
        return;
    }

    // Insert query
    $query = "INSERT INTO `phongban`(`PB_MA`, `PB_TEN`) 
            VALUES ('$pbma', '$pbten')";
    $result = mysqli_query($conn, $query);

    // Check the result
    if ($result) {
        header('Location: danhsachpb.php');
        exit();
    } else {
        echo "Lỗi khi thêm thành viên: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Thêm Phòng Ban</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
          integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/style2.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"
            integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ"
            crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"
            integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY"
            crossorigin="anonymous"></script>
</head>

<body class="thempb-page">

<div class="wrapper">
    <!-- Sidebar  -->
    <?php include("../menu.php"); ?>
    <!-- Page Content  -->
    <div id="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-primary"
                     style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="m-0" style="color: #d30e0e; font-weight: 700;">Thêm Phòng Ban</h3>
                                </div>
                                <div class="col-md-12">
                                     <div class="form-group">
                                        <label>Mã Số Phòng Ban:</label>
                                        <input type="text" class="form-control form-control-sm" name="txtMaPB" required placeholder="Nhập mã phòng ban">
                                    </div>
                                    <div class="form-group">
                                        <label>Tên Phòng Ban :</label>
                                        <input type="text" class="form-control form-control-sm" required name="txtTenPB" required placeholder="Nhập tên phòng ban">
                                    </div>
                                </div>      
                            <br>
                            <div class="card-footer w-100 border-top border-info">
                                <div class="d-flex justify-content-center align-items-center">
                                    <button type="submit" id="btnluu" name="btnluu"
                                            class="btn btn-flat bg-gradient-primary mx-2"
                                            style="border-radius: 9px; border:2px solid #ff1e004d">Save
                                    </button>
                                    <a href="danhsachpb.php" class="btn btn-flat bg-gradient-primary mx-2"
                                       style="border-radius: 9px; border:2px solid #ff1e004d">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- jQuery CDN - Slim version (=without AJAX) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<!-- Popper.JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"
        integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ"
        crossorigin="anonymous"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"
        integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm"
        crossorigin="anonymous"></script>
<!-- jQuery Custom Scroller CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

</body>

</html>
