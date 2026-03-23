<?php
include('../config.php');
session_start();
function getStatusText($statusCode) {
    switch ($statusCode) {
        case 1:
            return "Đang Tiến Hành";
        case 2:
            return "Hoàn Thành";
        case 3:
            return "Dời";
        case 4:
            return "Hủy";
        case 5:
            return "Bắt Đầu";
        default:
            return "";
    }
}

// Function to get status code from status text
function getStatusCode($statusText) {
    switch ($statusText) {
        case "Đang Tiến Hành":
            return 1;
        case "Hoàn Thành":
            return 2;
        case "Dời":
            return 3;
        case "Hủy":
            return 4;
        case "Bắt Đầu":
            return 5;
        default:
            return 0;
    }
}

// Check if the ID is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM duan WHERE DA_MA = '$id'";
    $result = mysqli_query($conn, $query);
    if ($result != null && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tenda = $row['DA_TEN'];
            $ngaybd = $row['DA_NGAYBATDAU'];
            $ngaykt = $row['DA_NGAYKETTHUC'];
            $tinhtrang = getStatusText($row['DA_TRANGTHAI']);
        }
    } else {
        echo "Data is empty";
    }
} else {
    echo "ID is not provided in the URL.";
}

// Update data function
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['btnluu'])) {
    UpdateData();
}

function UpdateData() {
    
    $tenda = $_POST['txtName'];
    $tinhtrang = getStatusCode($_POST['tinhtrang']);
    $ngaybd = $_POST['txtNgayBD'];
    $ngaykt = $_POST['txtNgayKT'];

    if (!$conn) {
        die("Connect error: " . mysqli_connect_errno());
    } else {
        if ($ngaybd > $ngaykt) {
            echo "<script type='text/javascript'>";
            echo "alert('Ngày Kết Thúc Phải > Ngày Bắt Đầu');";
            echo "</script>";
        } else {
            $query = "UPDATE duan SET DA_TEN='$tenda', DA_TRANGTHAI='$tinhtrang', DA_NGAYBATDAU='$ngaybd', DA_NGAYKETTHUC='$ngaykt' WHERE DA_MA='$_GET[id]'";
            $result = mysqli_query($conn, $query);

            if ($result) {
                header('Location: index.php');
            } else {
                echo "Insert data error: " . mysqli_error($conn);
            }
        }
        mysqli_close($conn);
    }
}
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
    <link rel="stylesheet" href="../style/style_DAL.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
    </script>
</head>

<body>
    <!-- lấy dữ liệu khi ấn -->

    <div class="wrapper">
        <!-- Sidebar  -->
        <?php include ("../menu.php"); ?>
        <!-- Page Content  -->
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
                            <h2 class="m-0">Quản Lý Dự Án</h2>
                        </div>
                    </div>
                    <hr class="border-primary">
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="col-lg-12">
                        <div class="card card-outline card-primary" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-body">
                                <form method="post">
                                    <div class="row">
                                        <div class="col-md-6 border-right">
                                            <div class="form-group" style="margin-bottom: 0rem;">
                                                <label>Tên Dự Án :</label>
                                                <input type="text" class="form-control form-control-sm" required name="txtName" value="<?php echo $tenda ?>">
                                            </div>

                                            <label>Tình Trạng :</label>
                                            <div>
                                                <div class="form-group">
                                                    <label>Tình Trạng :</label>
                                                    <select id="tinhtrang" name="tinhtrang" class="custom-select mb-3">
                                                        <option value="Đang Tiến Hành" <?php if ($tinhtrang == "Đang Tiến Hành") echo "selected"; ?>>Đang Tiến Hành</option>
                                                        <option value="Hoàn Thành" <?php if ($tinhtrang == "Hoàn Thành") echo "selected"; ?>>Hoàn Thành</option>
                                                        <option value="Dời" <?php if ($tinhtrang == "Dời") echo "selected"; ?>>Dời</option>
                                                        <option value="Hủy" <?php if ($tinhtrang == "Hủy") echo "selected"; ?>>Hủy</option>
                                                        <option value="Bắt Đầu" <?php if ($tinhtrang == "Bắt Đầu") echo "selected"; ?>>Bắt Đầu</option>
                                                    </select>
                                                    </div>
                                        </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group" style="margin-bottom: 0rem;">
                                                <label>Ngày Bắt Đầu</label>
                                                <input type="date" class="form-control form-control-sm" autocomplete="off" required name="txtNgayBD" value="<?php echo $ngaybd ?>">
                                            </div>
                                            <div class="form-group" style="margin-bottom: 0rem;">
                                                <label>Ngày Kết Thúc</label>
                                                <input type="date" class="form-control form-control-sm" autocomplete="off" required name="txtNgayKT" value="<?php echo $ngaykt ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="card-footer border-top border-info">
                                        <div class="d-flex w-100 justify-content-center align-items-center">
                                            <!-- <button class="btn btn-flat  bg-gradient-primary mx-2" form="manage-project" id="btnluu" name="btnluu">Save</button> -->
                                            <button type="submit" id="btnluu" name="btnluu" class="btn btn-flat  bg-gradient-primary mx-2" style="border-radius: 9px;border:2px solid #ff1e004d">Save</button>
                                            <a href="danhsachduan.php" class="btn btn-flat  bg-gradient-primary mx-2" style="border-radius: 9px;border:2px solid #ff1e004d">Cancel</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/. container-fluid -->
            </section>
        </div>
    </div>
    <!-- jQuery CDN - Slim version (=without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <!-- jQuery Custom Scroller CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            $("#sidebar").mCustomScrollbar({
                theme: "minimal"
            });
    
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar, #content').toggleClass('active');
                $('.collapse.in').toggleClass('in');
                $('a[aria-expanded=true]').attr('aria-expanded', 'false');
            });
        });
    </script>
    