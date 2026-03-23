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
    <title>Quản Lý Dự Án</title>
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
    <style>

       

    </style>
</head>

<body>
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
                            <h3 class="m-0">Quản Lý Dự Án</h3>
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
                        <div class="card card-outline card-success" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-header">
                                <!-- form tìm kiếm  -->
                                <div class="row">
                                    <form method="post">
                                        <input type="text" name="txtSearch" style="width: 450px; margin-left:10px" placeholder="Tìm Kiếm Theo Tên Dự Án">
                                    </form>
                                    <!-- button thêm -->
                                    <div class="card-tools" style="margin-left:330px">
                                        <a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="themda.php"><i class="fa fa-plus"></i> Thêm Dự Án</a>
                                    </div>
                                </div>
                            </div>
                            <table class="table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col" style="text-align: center">STT</th>
                                        <th scope="col" style="text-align: center">Tên Dự Án</th>
                                        <th scope="col" style="text-align: center">Ngày Bắt Đầu</th>
                                        <th scope="col" style="text-align: center">Ngày Kết Thúc</th>
                                        <th scope="col" style="text-align: center">Tình Trạng</th>
                                        <th scope="col" style="width: 140px; text-align: center">
                                        Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($conn != true) {
                                        die("connect error" . mysqli_connect_errno());
                                    } else {

                                        //----------------------------------Tìm Kiếm + Phân Trang -----------------------------
                                        if (isset($_POST['txtSearch']) && $_POST['txtSearch'] != '') {
                                            $KeyWord = $_POST['txtSearch'];
                                            $sql = mysqli_query($conn, "SELECT * FROM  duan WHERE DA_TEN LIKE N'%" . $KeyWord . "%' ");
                                            //b1:tính tổng các bản ghi
                                            $total = mysqli_num_rows($sql);
                                            $limit = 10;
                                            //tổng                                            //tổng số trang
                                            $total_page = ceil($total / $limit);
                                            //lấy trang hiện tại
                                            $cr_page = isset($_GET['page']) ? $_GET['page'] : 1;
                                            $start = ($cr_page - 1) * $limit;
                                            $sql = "SELECT * FROM duan WHERE DA_TEN LIKE N'%" . $KeyWord . "%' LIMIT $start,$limit";
                                        } else {
                                            //------------------------------phân trang--------------------------------
                                            $sql = mysqli_query($conn, "select * from `duan`");
                                            //b1:tính tổng các bản ghi
                                            $total = mysqli_num_rows($sql);
                                            $limit = 10;
                                            //tổng số trang
                                            $total_page = ceil($total / $limit);
                                            //lấy trang hiện tại
                                            $cr_page = isset($_GET['page']) ? $_GET['page'] : 1;
                                            $start = ($cr_page - 1) * $limit;
                                            $sql = "SELECT * FROM `duan` LIMIT $start,$limit";
                                        }

                                        // thực hiện câu truy vấn
                                        $result = mysqli_query($conn, $sql);
                                        $idtang = 0;
                                        if ($result) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $idtang++;
                                                $id = $row['DA_MA'];
                                                $tenda = $row['DA_TEN'];
                                                $ngaybd = $row['DA_NGAYBATDAU'];
                                                $chuyennbd = date('d-m-Y', strtotime($ngaybd)); //date chuyển đổi định dạng mông muốn
                                                $ngaykt = $row['DA_NGAYKETTHUC'];
                                                $chuyennkt = date("d-m-Y", strtotime($ngaykt));
                                                $tinhtrang = $row['DA_TRANGTHAI'];
                                                

                                                // Hiển thị tình trạng dự án dưới dạng dropdown
                                                $tinhtrang_text = '';
                                                $row_style = ''; // default row style

                                                switch ($tinhtrang) {
                                                    case 1:
                                                        $tinhtrang_text = 'Đang Tiến Hành';
                                                        $row_style = 'background-color: #5190d2;'; // Light green for "Đang Tiến Hành"
                                                        break;
                                                    case 2:
                                                        $tinhtrang_text = 'Hoàn Thành';
                                                        $row_style = 'background-color: #6c757d;'; // Light blue for "Hoàn Thành"
                                                        break;
                                                    case 3:
                                                        $tinhtrang_text = 'Dời';
                                                        $row_style = 'background-color: lightyellow;'; // Light yellow for "Dời"
                                                        break;
                                                    case 4:
                                                        $tinhtrang_text = 'Hủy';
                                                        $row_style = 'background-color: lightcoral;'; // Light coral for "Hủy" (lighter red)
                                                        break;
                                                    case 5:
                                                        $tinhtrang_text = 'Bắt Đầu';
                                                        $row_style = 'background-color: lightpink;'; // Light pink for "Bắt Đầu"
                                                        break;
                                                    default:
                                                        $tinhtrang_text = 'Không Xác Định';
                                                        $row_style = 'background-color: lightgray;'; // Light gray for "Không Xác Định"
                                                }

                                                echo '<tr>
                                                    <th scope="row" style="text-align: center">' . $idtang . '</th>
                                                    <td style="text-align: center">' . $tenda . '</td>
                                                    <td style="text-align: center">' . $chuyennbd . '</td>
                                                    <td style="text-align: center">' . $chuyennkt . '</td>
                                                    <td style="text-align: center; ' . $row_style . '">' . $tinhtrang_text . '</td>
                                                    <td style="text-align: center">
                                                    <button class="btn btn-primary"><a href="suada.php?id=' . $row['DA_MA'] . '"><i class="fas fa-pencil-alt"></i></a></button>
                                                    <button class="btn btn-danger" onclick="return Del();"><a href="xoada.php?id=' . $row['DA_MA'] . '" ><i class="fas fa-trash-alt"></i></a></button>
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
                                    <a class="page-link" href="index.php?page=<?php echo $cr_page - 1 ?>" aria-label="Previous">
                                        &laquo;
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_page; $i++) { ?>
                                    <li class="<?php echo (($cr_page == $i) ? 'page-item active' : '') ?>" aria-current="page">
                                        <a class="page-link" href="index.php?page=<?php echo $i ?>"><?php echo $i ?></a>
                                    </li>
                                <?php } ?>
                                <li class="<?php echo (($cr_page == $total_page) ? 'ckeck' : '') ?>">
                                    <a class="page-link" href="index.php?page=<?php echo $cr_page + 1 ?>" aria-label="Next">
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
    </script>
    <!-- jQuery CDN - Slim version (=without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>

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
                $('a[ariaexpanded=true]').attr('aria-expanded', 'false');
            });
        });
    </script>
</body>
</html>
