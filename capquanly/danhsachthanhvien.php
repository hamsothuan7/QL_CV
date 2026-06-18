<?php
include('../config.php');
session_start(); // Start the session

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
    <title>Danh Sách Thành Viên</title>
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/style2.css">

    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet"
          type="text/css"/>
    <style type="text/css">
        .ckeck {
            display: none;
        }
          table.table-bordered {
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar  -->
        <?php include ("../menu.php"); ?>
        <!-- Page Content  -->
        <div id="content">
            <div class="top-bar-block center" style="margin-left: 10px;">
                <div class="d-flex justify-content-between align-items-center" style="margin-top: 40px;">
                    <h3 class="m-0" style="color: #d30e0e; font-weight: 700;">Danh Sách Thành Viên</h3>
                    <form method="GET">
                        <div class="d-flex">
                            <input autocomplete="off" class="form-control input-sm" type="text" name="txtSearch" style="width: 300px; margin-left:10px" placeholder="Tìm kiếm theo tên hoặc quê quán" value="<?php echo $_GET['txtSearch'] ?? ''; ?>">
                            <button type="submit" class="btn btn-sm btn-danger px-3">
                                <i class="fal fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="border-primary">
                <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="row" style="margin-right: 10px;">
                    <div class="col-lg-12">
                        <div class="card card-outline card-success" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-header">
                                <div class="row">
                                    <div class="card-tools">
                                        <a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="themtv.php"><i class="fa fa-plus"></i> Thêm Thành Viên</a>
                                    </div>
                                </div>
                            </div>
                            <table class="table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 50px; text-align: center">STT</th>
                                        <th scope="col" style="text-align: center">Mã Số CB</th>
                                        <th scope="col" style="text-align: center">Họ Tên</th>
                                        <th scope="col" style="text-align: center">Ngày Sinh</th>
                                        <th scope="col" style="text-align: center">Giới Tính</th>
                                        <th scope="col" style="text-align: center">Email</th>
                                        <th scope="col" style="text-align: center">Phòng Ban</th>
                                        <th scope="col" style="text-align: center">Chức Vụ</th>
                                        <th scope="col" style="text-align: center">Nhóm Người Dùng</th>
                                        <th scope="col" style="text-align: center">Quê Quán</th>
                                        <th scope="col" style="width: 100px; text-align: center">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($conn != true) {
                                        die("connect error" . mysqli_connect_errno());
                                    } else {
                                        $idtang = 1;
                                        //----------------------------phân trang khi click--------------------------------
                                        $sql = mysqli_query($conn, "SELECT * FROM `thanhvien` WHERE active = 0");
                                        //b1:tính tổng các bản ghi
                                        $total = mysqli_num_rows($sql);
                                        $limit = 5;
                                       
                                        //tổng số trang
                                        $total_page = ceil($total / $limit);
                                        //lấy trang hiện tại
                                        $cr_page = isset($_GET['page']) ? $_GET['page'] : 1;
                                        $start = ($cr_page - 1) * $limit;
                                        $sql = "SELECT * FROM `thanhvien` WHERE active = 0 LIMIT $start,$limit";

                                        //----------------------------------Tìm Kiếm + Phân Trang -----------------------------
                                        if (isset($_GET['txtSearch']) && $_GET['txtSearch'] != '') {
                                            $KeyWord = $_GET['txtSearch'];
                                            $sql = mysqli_query($conn, "SELECT * FROM  thanhvien WHERE active = 0 AND (TV_TEN LIKE N'%" . $KeyWord . "%' or TV_QUEQUAN LIKE N'%" . $KeyWord . "%')");
                                            //b1:tính tổng các bản ghi
                                            $total = mysqli_num_rows($sql);
                                            $limit = 10;
                                            //tổng số trang
                                            $total_page = ceil($total / $limit);
                                            //lấy trang hiện tại
                                            $cr_page = isset($_GET['page']) ? $_GET['page'] : 1;
                                            $start = ($cr_page - 1) * $limit;
                                            $sql = "SELECT * FROM thanhvien WHERE active = 0 AND (TV_TEN LIKE N'%" . $KeyWord . "%' or TV_QUEQUAN LIKE N'%" . $KeyWord . "%') LIMIT $start,$limit";
                                        } else {
                                            //----------------------------phân trang khi click--------------------------------
                                            $sql = mysqli_query($conn, "SELECT * FROM `thanhvien` WHERE active = 0");
                                            //b1:tính tổng các bản ghi
                                            $total = mysqli_num_rows($sql);
                                            $limit = 10;
                                            //tổng số trang
                                            $total_page = ceil($total / $limit);
                                            //lấy trang hiện tại
                                            $cr_page = isset($_GET['page']) ? $_GET['page'] : 1;
                                            $start = ($cr_page - 1) * $limit;
                                            $sql = "SELECT * FROM `thanhvien` WHERE active = 0 LIMIT $start,$limit";
                                        }

                                        $result = mysqli_query($conn, $sql);
                                        if ($result) {
                                            $serial_number = $start + 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $tvma=$row['TV_MA'];
                                                $name = $row['TV_TEN'];
                                                $ngaysinh = $row['TV_NGAYSINH']; // Lấy dữ liệu ngày sinh
                                                $chuyenns = date('d-m-Y', strtotime($ngaysinh));
                                                $gioitinh = $row['TV_GIOITINH'];
                                                $email = $row['TV_EMAIL'];

                                                // Lấy tên phòng ban từ mã phòng ban
                                                $phongban_id = $row['PB_MA'];
                                                $phongban_result = mysqli_query($conn, "SELECT PB_TEN FROM phongban WHERE PB_MA = '$phongban_id'");
                                                $phongban_row = mysqli_fetch_assoc($phongban_result);
                                                $phongban = $phongban_row['PB_TEN'] ?? 'N/A'; // Check if the query returns a result

                                                // Lấy tên chức vụ từ mã chức vụ
                                                $chucvu_id = $row['CV_MA'];
                                                $chucvu_result = mysqli_query($conn, "SELECT CV_TEN FROM chucvu WHERE CV_MA = '$chucvu_id'");
                                                $chucvu_row = mysqli_fetch_assoc($chucvu_result);
                                                $chucvu = $chucvu_row['CV_TEN'] ?? 'N/A'; // Check if the query returns a result

                                                // Lấy tên nhóm người dùng từ mã NND
                                                $nhomnguoidung_id = $row['NND_MA'];
                                                $nhomnguoidung_result = mysqli_query($conn, "SELECT NND_TEN FROM nhomnguoidung WHERE NND_MA = '$nhomnguoidung_id'");
                                                $nhomnguoidung_row = mysqli_fetch_assoc($nhomnguoidung_result);
                                                $nhomnguoidung = $nhomnguoidung_row['NND_TEN'] ?? 'N/A'; // Check if the query returns a result

                                                //Quê quán
                                                $quequan = $row['TV_QUEQUAN'];
                                        
                                                echo '<tr>
                                                        <th scope="row" style="text-align: center">' . $serial_number++ . '</th>
                                                        <td style="text-align: center">' . $tvma . '</td>
                                                        <td style="text-align: center">' . $name . '</td>
                                                        <td style="text-align: center">' . $chuyenns . '</td> <!-- This now displays the date of birth -->
                                                        <td style="text-align: center">' . $gioitinh . '</td>
                                                        <td style="text-align: center">' . $email . '</td>
                                                        <td style="text-align: center">' . $phongban . '</td>
                                                        <td style="text-align: center">' . $chucvu . '</td> <!-- This now displays position names -->
                                                        <td style="text-align: center">' . $nhomnguoidung . '</td>
                                                        <td style="text-align: center">' . $quequan . '</td>
                                                        <td style="text-align: center">
                                                            <button class="btn btn-primary"><a href="suatv.php?id=' . $row['TV_MA'] . '"><i class="fas fa-pencil-alt"></i></a></button>
                                                            <button class="btn btn-danger" onclick="return Del();"><a href="xoatv.php?id=' . $row['TV_MA'] . '" ><i class="fas fa-trash-alt"></i></a></button>                                                      
                                                        </td>
                                                    </tr>';
                                            }
                                        }
                                        
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <!-- trang  -->
                            <ul class="pagination justify-content-end" style="margin: 10px 20px;">
                                <li class="<?php echo (($cr_page - 1 == 0) ? 'ckeck' : '') ?>">
                                    <a class="page-link" href="danhsachthanhvien.php?page=<?php echo $cr_page - 1 ?>" aria-label="Previous">
                                        &laquo;
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_page; $i++) { ?>
                                    <li class="<?php echo (($cr_page == $i) ? 'page-item active' : '') ?>" aria-current="page"><a class="page-link" href="danhsachthanhvien.php?page=<?php echo $i ?>"><?php echo $i ?></a></li>
                                <?php } ?>
                                <li class="<?php echo (($cr_page == $total_page) ? 'ckeck' : '') ?>">
                                    <a class="page-link" href="danhsachthanhvien.php?page=<?php echo $cr_page + 1 ?>" aria-label="Next">
                                        &raquo;
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <!-- hiển thị thông báo xóa -->
    <script>
        function Del() {
            return confirm("Bạn Có Muốn Xóa Không!");
        }
    </script>
    <!-- jQuery CDN - Slim version (=without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
    </script>
    <!-- jQuery Custom Scroller CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js">
    </script>

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
</body>

</html>
