<?php
include('../config.php');
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Quản Lý Công Việc Cá Nhân</title>
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/styleCV.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous"></script>
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['btnluu'])) {
    InsertData();
}

function InsertData()
{
    $tencv = $_POST['txtName'];
    $ngaybd = $_POST['txtNgayBD'];
    $ngaykt = $_POST['txtNgayKT'];
    $tinhtrang = $_POST['tinhtrang'];
    $tv_ma = $_POST['selectThanhVien'];
    $duan_ma = $_POST['selectDuan'];

    if ($ngaybd > $ngaykt) {
        echo "<script type='text/javascript'>";
        echo "alert('Ngày Kết Thúc Phải > Ngày Bắt Đầu');";
        echo "</script>";
    } else {
        $check_query = "SELECT TV_MA, PB_MA FROM thanhvien WHERE TV_MA = '$tv_ma'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) == 0) {
            echo "<script type='text/javascript'>";
            echo "alert('Mã thành viên không tồn tại');";
            echo "</script>";
        } else {
            $row = mysqli_fetch_assoc($check_result);
            $pb_ma = $row['PB_MA'];
            $cv_ma = generateProjectCode($conn);
            $query = "INSERT INTO `danhsachcongviec`(`DSCV_MA`, `DSCV_TEN`, `DSCV_NGAYBATDAU`, `DSCV_NGAYKETTHUC`, `DSCV_TRANGTHAI`, `TV_MA`, `DA_MA`, `PB_MA`) 
                      VALUES ('$cv_ma','$tencv','$ngaybd','$ngaykt','$tinhtrang','$tv_ma','$duan_ma','$pb_ma')";

            $result = mysqli_query($conn, $query);
            if ($result == true) {
                header('location:danhsachcv.php');
            } else {
                echo "Insert data error: " . mysqli_error($conn);
            }
        }
    }
    mysqli_close($conn);
}


function getPhongBan()
{
    $query = "SELECT PB_MA, PB_TEN FROM phongban";
    $result = mysqli_query($conn, $query);
    $phongban_arr = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $phongban_arr[] = $row;
    }
    mysqli_close($conn);
    return $phongban_arr;
}

function generateProjectCode($conn)
{
    $today = date("Ymd");
    $query = "SELECT COUNT(*) AS count FROM `danhsachcongviec` WHERE `DSCV_MA` LIKE '$today%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];
    do {
        $count++;
        $project_code = $today . str_pad($count, 2, "0", STR_PAD_LEFT);
        $check_query = "SELECT DSCV_MA FROM `danhsachcongviec` WHERE `DSCV_MA` = '$project_code'";
        $check_result = mysqli_query($conn, $check_query);
    } while (mysqli_num_rows($check_result) > 0);

    return $project_code;
}

$phongban_arr = getPhongBan();
$sql = "SELECT DA_MA, DA_TEN FROM duan";
$result = mysqli_query($conn, $sql);
$duan_arr = array();
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $duan_arr[] = $row;
    }
}
?>
<div class="wrapper">
<?php include ("../menu.php"); ?>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                </button>
            </div>
        </nav>
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h2 class="m-0">Thêm Công Việc</h2>
                    </div>
                </div>
                <hr class="border-primary">
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="col-lg-12">
                    <div class="card card-outline card-primary" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6 border-right">
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Tên Công Việc :</label>
                                            <input type="text" class="form-control form-control-sm" required name="txtName">
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Ngày Bắt Đầu</label>
                                            <input type="date" class="form-control form-control-sm" autocomplete="off" required name="txtNgayBD" value="<?php echo date("Y-m-d"); ?>">
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Ngày Kết Thúc</label>
                                            <input type="date" class="form-control form-control-sm" autocomplete="off" required name="txtNgayKT" value="<?php echo date("Y-m-d"); ?>">
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Tình Trạng :</label>
                                            <div>
                                                <select id="tinhtrang" name="tinhtrang" class="custom-select mb-3" style="margin: 0px;">
                                                    <option value="1">Đang Tiến Hành</option>
                                                    <option value="2">Hoàn Thành</option>
                                                    <option value="3">Hoãn lại</option>
                                                    <option value="4">Hủy</option>
                                                    <option value="5">Bắt Đầu</option>
                                                    </select>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Dự Án :</label>
                                            <div>
                                                <select id="selectDuan" name="selectDuan" class="custom-select mb-3" style="margin: 0px;">
                                                <option value="" disabled selected>Chọn Dự Án</option>
                                                    <?php
                                                    foreach ($duan_arr as $duan) {
                                                        echo '<option value="' . $duan['DA_MA'] . '">' . $duan['DA_TEN'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Phòng Ban :</label>
                                            <div>
                                                <select id="selectPhongBan" name="selectPhongBan" class="custom-select mb-3" style="margin: 0px;" onchange="fetchThanhVien()">
                                                    <option value="" disabled selected>Chọn Phòng Ban</option>
                                                    <?php
                                                    foreach ($phongban_arr as $phongban) {
                                                        echo '<option value="' . $phongban['PB_MA'] . '">' . $phongban['PB_TEN'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0rem;">
                                            <label>Thành Viên :</label>
                                            <div>
                                                <select id="selectThanhVien" name="selectThanhVien" class="custom-select mb-3" style="margin: 0px;">
                                                    <option value="" disabled selected>Chọn Thành Viên</option>
                                                    <!-- Thành viên sẽ được tải lên đây -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                               <div class="card-footer border-top border-info">
                                        <div class="d-flex w-100 justify-content-center align-items-center">
                                            <button type="submit" id="btnluu" name="btnluu" class="btn btn-flat  bg-gradient-primary mx-2" style="border-radius: 9px;border:2px solid #ff1e004d">Save</button>
                                            <a href="danhsachcv.php" class="btn btn-flat  bg-gradient-primary mx-2" style="border-radius: 9px;border:2px solid #ff1e004d">Cancel</a>
                                        </div>
                            </form>
                        </div>
                    </div>
                </div>
        </section>
    </div>
</div>

<!-- jQuery CDN - Slim version -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<!-- Popper.JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBkTt4K8EXMpbjwFlnusSWv3EZi4zWOzDlh9OJg8" crossorigin="anonymous"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-3cc0Clmo9mzXDmKEfDgf7DaTkYjrrwp4fl8vs4xcpQYTV4C4AfRgtANtLFEhWDI" crossorigin="anonymous"></script>
<!-- jQuery Custom Scroller CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

<script type="text/javascript">
    function fetchThanhVien() {
        var phongBanId = document.getElementById('selectPhongBan').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_thanhvien.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (this.status == 200) {
                document.getElementById('selectThanhVien').innerHTML = this.responseText;
            }
        };
        xhr.send('phongban_id=' + phongBanId);
    }
</script>
</body>
</html>
