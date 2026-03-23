<?php
include('../config.php');
session_start(); // Start the session
//$tvma = generateTVMA(); // Generate TV_MA before using it

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['btnluu'])) {
    InsertData();
}

/*function generateTVMA()
{
    include('../config.php');
    // Query to get the latest TV_MA
    $query = "SELECT MAX(CAST(SUBSTRING(TV_MA, 5) AS UNSIGNED)) AS max_id FROM thanhvien";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $max_id = $row['max_id'];

    // Increment the max_id for the new entry
    $new_id = $max_id + 1;

    // Generate new TV_MA
    return "MSCB" . str_pad($new_id, 2, "0", STR_PAD_LEFT); // Format: mscb01, mscb02, ...
}*/

function InsertData()
{
    include('../config.php');

    $tvma = $_POST['txtMaSoCanBo']; // Mã cán bộ nhập tay
    $Name = $_POST['txtName'];
    $NgaySinh = $_POST['txtNgaySinh'];
    $GioiTinh = $_POST['gioitinh'];
    $email = $_POST['txtEmail'];
    $matkhau = $_POST['txtMatKhau'];
    $quequan = $_POST['txtquequan'];
    $pb_ma = $_POST['phongban'];
    $cv_ma = $_POST['chucvu'];
    $nnd_ma = $_POST['nhomnguoidung'];

    // Kiểm tra mã cán bộ đã tồn tại chưa
    $check_query = "SELECT * FROM thanhvien WHERE TV_MA = '$tvma'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Mã số cán bộ \"$tvma\" đã tồn tại. Vui lòng nhập mã khác.'); window.history.back();</script>";
        return;
    }

    // Mã hóa mật khẩu
    $hashed_password = md5($matkhau);

    // Câu truy vấn thêm mới
    $query = "INSERT INTO `thanhvien`(`TV_MA`, `TV_TEN`, `TV_NGAYSINH`, `TV_GIOITINH`, `TV_QUEQUAN`, `TV_EMAIL`, `TV_MATKHAU`, `PB_MA`, `CV_MA`, `NND_MA`, `active`) 
              VALUES ('$tvma', '$Name', '$NgaySinh', '$GioiTinh', '$quequan', '$email', '$hashed_password', '$pb_ma', '$cv_ma', '$nnd_ma', 0)";
    
    $result = mysqli_query($conn, $query);

    if ($result) {
        header('Location: danhsachthanhvien.php');
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
    <title>Thêm Thành Viên</title>

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

<body>

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
                                    <h3 class="m-0" style="color: #d30e0e; font-weight: 700; text-align: center;">Thêm Thành Viên</h3>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tên Đăng Nhập:</label>
                                        <!-- Display the generated staff ID here -->
                                        <input type="text" class="form-control form-control-sm" name="txtMaSoCanBo" required placeholder="Nhập mã cán bộ">

                                    </div>
                                    <div class="form-group">
                                        <label>Họ Và Tên :</label>
                                        <input type="text" class="form-control form-control-sm" required name="txtName">
                                    </div>
                                    <div class="form-group">
                                        <label>Ngày Sinh :</label>
                                        <input type="date" class="form-control form-control-sm" autocomplete="off"
                                               required name="txtNgaySinh" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <label>Giới Tính :</label>
                                    <div class="form-group"
                                         style="border-radius: 3px; border:0.5px solid #CED4DA; height: 25px">
                                        <div class="row">
                                            <div class="form-check" style="padding:0px 35px 0px 50px;">
                                                <input class="form-check-input" type="radio" id="gioitinh"
                                                       name="gioitinh" value="Nam" checked>
                                                <label class="form-check-label" for="flexRadioDefault1">Nam</label>
                                            </div>
                                            <div class="form-check" style="padding-left: 50px;">
                                                <input class="form-check-input" type="radio" name="gioitinh" value="Nữ">
                                                <label class="form-check-label" for="flexRadioDefault1">Nữ</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Gmail :</label>
                                        <input type="email" class="form-control form-control-sm"
                                               name="txtEmail">
                                    </div>
                                    <div class="form-group">
                                        <label>Mật Khẩu :</label>
                                        <input type="password" class="form-control form-control-sm" required
                                               name="txtMatKhau">
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Chức Vụ :</label>
                                        <select id="chucvu" name="chucvu" class="custom-select mb-3">
                                            <?php
                                            if (!$conn) {
                                                die("Connection error: " . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `chucvu`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo "<option value='" . $row['CV_MA'] . "'>" . $row['CV_TEN'] . "</option>";
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                     <div class="form-group">
                                        <label>Nhóm Người Dùng :</label>
                                        <select id="nhomnguoidung" name="nhomnguoidung" class="custom-select mb-3">
                                            <?php
                                            $sql = "SELECT * FROM `nhomnguoidung`";
                                            $result = mysqli_query($conn, $sql);
                                            if ($result) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='" . $row['NND_MA'] . "'>" . $row['NND_TEN'] . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Phòng Ban :</label>
                                        <select id="phongban" name="phongban" class="custom-select mb-3">
                                            <?php
                                            $sql = "SELECT * FROM `phongban`";
                                            $result = mysqli_query($conn, $sql);
                                            if ($result) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='" . $row['PB_MA'] . "'>" . $row['PB_TEN'] . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Địa Chỉ : </label>
                                        <textarea class="form-control" id="floatingTextarea2" style="height: 100px"
                                                  placeholder="Nhập tại đây" name="txtquequan"></textarea>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="card-footer border-top border-info">
                                <div class="d-flex w-100 justify-content-center align-items-center">
                                    <button type="submit" id="btnluu" name="btnluu"
                                            class="btn btn-flat bg-gradient-primary mx-2"
                                            style="border-radius: 9px; border:2px solid #ff1e004d">Save
                                    </button>
                                    <a href="danhsachthanhvien.php" class="btn btn-flat bg-gradient-primary mx-2"
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
