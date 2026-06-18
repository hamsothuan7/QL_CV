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

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['btnluu'])) {
    InsertData();
}

function InsertData()
{
    include('../config.php');

    $ten_donvi = $_POST['txtTenDonVi'];
    $diachi = $_POST['txtDiaChi'];

    // Kiểm tra tên đơn vị đã tồn tại chưa
    $check_query = "SELECT * FROM donviphoihop WHERE PH_TEN = '$ten_donvi'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Tên đơn vị \"$ten_donvi\" đã tồn tại. Vui lòng nhập tên khác.'); window.history.back();</script>";
        return;
    }

    $query = "INSERT INTO donviphoihop (PH_TEN, PH_DIACHI) VALUES ('$ten_donvi', '$diachi')";
    $result = mysqli_query($conn, $query);

    if ($result) {
        header('Location: donviphoihop.php');
        exit();
    } else {
        echo "Lỗi khi thêm đơn vị phối hợp: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Đơn Vị Phối Hợp</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="themdonvi-page">
<div class="wrapper">
    <?php include("../menu.php"); ?>

    <div id="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-primary" style="border-top: 3px solid #2e8b57; border-radius: 5px;">
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="m-0 text-primary font-weight-bold">Thêm Đơn Vị Phối Hợp</h3>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tên Đơn Vị:</label>
                                        <input type="text" class="form-control form-control-sm" name="txtTenDonVi" required placeholder="Nhập tên đơn vị phối hợp">
                                    </div>
                                    <div class="form-group">
                                        <label>Địa Chỉ:</label>
                                        <textarea class="form-control" name="txtDiaChi" style="height: 100px;" placeholder="Nhập địa chỉ"></textarea>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="card-footer border-top border-info">
                                <div class="d-flex w-100 justify-content-center align-items-center">
                                    <button type="submit" id="btnluu" name="btnluu"
                                            class="btn btn-success mx-2" style="border-radius: 9px;">Lưu</button>
                                    <a href="donviphoihop.php" class="btn btn-secondary mx-2" style="border-radius: 9px;">Hủy</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS scripts -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
</body>

</html>
