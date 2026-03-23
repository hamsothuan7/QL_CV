<?php
include('../config.php');
// Bắt đầu session
session_start();

// Kiểm tra xem session đã được tạo hay chưa và nếu tên người dùng không được lưu trữ trong session 'username', chuyển hướng người dùng đến trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: ../login/index.php');
    exit;
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
    <link rel="stylesheet" href="../style/styleBDK.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.css" />
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
    </script>
</head>

<body>
<div class="wrapper">
        <!-- Sidebar  -->
        <?php include ("../menu.php"); ?>


        <!-- Page Content  -->
        <div id="content">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="col-sm-12">
                        <a href="index.php" class="test">Tổng Quan Về Công Việc</a>
                    </div>
                    <hr>
                    <div class="row">
                        <!-- <div class="col-md-1">
                        </div> -->
                        <div class="col-md-3">
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #4b9261">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `danhsachcongviec`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $count++;
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-calendar-check" style="margin-left: 60px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Số Công Việc</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #5190d2">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `danhsachcongviec`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $tinhtrang = $row['DSCV_TRANGTHAI'];
                                                        if ($tinhtrang != "2" && $tinhtrang != "4"){
                                                            $count++;
                                                        }
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-clipboard-list" style="margin-left: 60px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Đang Tiến Hành</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3">
                            <a href="../congvieccanhan/danhsachcv.php">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #6c757d;">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `danhsachcongviec` WHERE DSCV_TRANGTHAI= '2'";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $count++;
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-check-circle" style="margin-left: 60px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Đã Hoàn Thành</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: red;">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            $today = date("Y-m-d");

                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `danhsachcongviec`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $ngaykt = $row['DSCV_NGAYKETTHUC'];
                                                        $tinhtrang = $row['DSCV_TRANGTHAI'];
                                                        if ($tinhtrang != "Hoàn Thành" && $ngaykt < $today) {
                                                            $count++;
                                                        }
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-check-circle" style="margin-left: 60px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Công Việc Chậm Tiến Độ</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-1">
                        </div>
                    </div>
                    <hr class="border-primary">
                    <div>
                        <!-- <h5 style="color: black;">Quản lý công việc cá nhân</h5> -->
                        <div id='calendar' style="width: 900px; margin: 0 auto;"></div>
                        <div class="response" style="height: 60px;"></div>
                    </div>

                </div><!-- /.container-fluid -->
            </div>
        </div>
    </div>
    </script>
    <!-- jQuery CDN - Slim version (=without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous">
    </script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
    </script>
    <!-- jQuery Custom Scroller CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.min.js"></script>

    <script>
        $(document).ready(function() {
            var calendar = $('#calendar').fullCalendar({
                editable: true,
                header: {
                    left: 'prev,next today',
                    // center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: 'lichCVCN.php',
            });
        });
    </script>
</body>

</html>