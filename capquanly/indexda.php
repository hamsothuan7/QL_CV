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
    <link rel="stylesheet" href="styleBDK.css">
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
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>Quản lý công việc</h3>
            </div>
            <ul class="list-unstyled components" style="margin-bottom: 0px; padding: 0px">
                <li><a href="index.php"><i class="fas fa-home"></i> Bảng Điều Khiển</a></li>
                <li><a href="../LoaiCongViec/index.php"><i class="fas fa-tasks"></i> Loại Công Việc</a></li>
                <li><a href="../LoaiDuAn/index.php"><i class="fas fa-project-diagram"></i> Loại Dự Án</a></li>
                <li><a href="../CongViecCaNhan/index.php"><i class="fas fa-calendar-alt"></i> Công Việc Cá Nhân</a></li>
                <li><a href="../ChucVu/index.php"><i class="fas fa-calendar-check"></i> Chức Vụ</a></li>
                <li><a href="../ThanhVien/index.php"><i class="fas fa-users"></i> Thành Viên</a></li>
                <li><a href="../DuAnLon/index.php"><i class="fas fa-handshake"></i> Dự Án Lớn</a></li>
            </ul>
            <ul style="padding-left: 0px;">
                <li>
                    <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-user-tie"></i> Tài Khoản</a>
                    <ul class="collapse list-unstyled" id="pageSubmenu">
                        <li>
                            <a href="../login/index.php">Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Page Content  -->
        <div id="content">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="col-sm-12">
                        <a href="index.php" class="test">Tổng Quan Về Công Việc cá Nhân /</a>
                        <span><a href="indexda.php" class="test">Tổng Quan Về Dự Án Lớn</a></span>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #205388">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `duanlon`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $count++;
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-briefcase" style="margin-left: 60px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Tổng Số Dự Án</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #c48718">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `pc_congviec_dal`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $count++;
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-calendar-check" style="margin-left: 50px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Tổng Số Công Việc</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #28ae9b;">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `pc_congviec_dal`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $tinhtrang = $row['TinhTrang'];
                                                        if ($tinhtrang != "Hoàn Thành") {
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
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #6c757d;">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `pc_congviec_dal` WHERE TinhTrang= 'Hoàn Thành'";
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
                    </div>
                    <div class="row" style="padding-top: 10px ;">
                        <div class="col-md-3">
                            <a href="">
                                <div class="card card-outline card-success" style="border-left: 3px solid black; border-radius: 5px; background-color: #aa0000">
                                    <div>
                                        <h2>
                                            <?php
                                            $count = 0;
                                            $today = date("Y-m-d");

                                            if ($conn != true) {
                                                die("connect error" . mysqli_connect_errno());
                                            } else {
                                                $sql = "SELECT * FROM `duanlon`";
                                                $result = mysqli_query($conn, $sql);
                                                if ($result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $ngaykt = $row['NgayKetThuc'];
                                                        $tinhtrang = $row['TinhTrang'];
                                                        if ($tinhtrang != "Hoàn Thành" && $ngaykt < $today) {
                                                            $count++;
                                                        }
                                                    }
                                                }
                                            }
                                            echo "$count";
                                            ?>
                                            <i class="fas fa-briefcase" style="margin-left: 60px; font-size: 25px;"></i>
                                        </h2>
                                        <p>Dự Án Quá Hạn</p>
                                    </div>
                                </div>
                            </a>
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
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: 'lichDAL.php',
            });
        });
    </script>
</body>

</html>