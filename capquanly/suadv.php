<?php
include('../config.php');
session_start(); 

if (!isset($_SESSION['code'])) {
    header('Location: ../index.php');
    exit;
}
if (!isset($_SESSION['nnd_ma']) || $_SESSION['nnd_ma'] != 1) {
    echo "<script>alert('Bạn không có quyền truy cập chức năng này.'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Đơn Vị Phối Hợp</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/style2.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous"></script>
</head>

<body>
    <?php
    // Lấy dữ liệu đơn vị cần sửa
    if (isset($_GET['id'])) {
        $dv_ma = $_GET['id'];
        $query = "SELECT * FROM donviphoihop WHERE PH_MA = '$dv_ma'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $dv = mysqli_fetch_assoc($result);
        } else {
            echo "Lỗi truy vấn: " . mysqli_error($conn);
        }
    }

    // Xử lý cập nhật
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnluu'])) {
        $ten = $_POST['txtTen'];
        $diachi = $_POST['txtDiaChi'];

        $update_query = "UPDATE donviphoihop 
                     SET PH_TEN = '$ten', PH_DIACHI = '$diachi' 
                     WHERE PH_MA = '$dv_ma'";

        if (mysqli_query($conn, $update_query)) {
            header('Location: donviphoihop.php');
        } else {
            echo "Lỗi cập nhật: " . mysqli_error($conn);
        }
    }
    ?>

    <div class="wrapper">
        <!-- Sidebar  -->
        <?php include("../menu.php"); ?>

        <!-- Page Content  -->
        <div id="content">

            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h3 class="m-0" style="color: #d30e0e; font-weight: 700;">Sửa Đơn Vị</h3>
                        </div>
                    </div>
                    <hr class="border-primary">
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="col-lg-12">
                        <div class="card card-outline card-primary" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-body">
                                <form method="post">
                                    <div class="container">
                                        <h3 class="mt-4" style="color: #d30e0e; font-weight: 700;">Sửa Đơn Vị Phối Hợp</h3>
                                        <hr class="border-primary">
                                        <form method="post">
                                            <div class="form-group">
                                                <label>Tên Đơn Vị:</label>
                                                <input type="text" class="form-control" name="txtTen" required value="<?php echo $dv['PH_TEN']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Địa Chỉ:</label>
                                                <textarea class="form-control" name="txtDiaChi" required rows="3"><?php echo $dv['PH_DIACHI']; ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Số Dự Án Tiếp Nhận:</label>
                                                <input type="number" class="form-control" readonly value="<?php echo $dv['PH_SLTIEPNHAN']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Số Dự Án Hoàn Thành:</label>
                                                <input type="number" class="form-control" readonly value="<?php echo $dv['PH_SLHOANTHANH']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Số Dự Án Hủy:</label>
                                                <input type="number" class="form-control" readonly value="<?php echo $dv['PH_SLTRE']; ?>">
                                            </div>
                                            <div class="form-group text-center">
                                                <button type="submit" name="btnluu" class="btn btn-success">Lưu</button>
                                                <a href="donviphoihop.php" class="btn btn-secondary">Hủy</a>
                                            </div>
                                        </form>
                                    </div>
                                    <br>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>