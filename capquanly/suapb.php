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

// Your existing PHP code here...

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Chỉnh sửa phòng ban</title>

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

// Fetch existing department data
if (isset($_GET['id'])) {
    $pbma = $_GET['id']; // Mã phòng ban
    $query = "SELECT * FROM phongban WHERE PB_MA = '$pbma'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $phongban = mysqli_fetch_assoc($result);
    } else {
        echo "Error fetching data: " . mysqli_error($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['btnluu'])) {
    UpdateData();
}

function UpdateData()
{
    include('../config.php');
    $pbma_cu = $_GET['id'];  
    $pbma_moi = $_POST['mapbmoi'];        
    $pbten = $_POST['txtTenPB'];

    if ($pbma_cu !== $pbma_moi) {
        $checkQuery = "SELECT PB_MA FROM phongban WHERE PB_MA = '$pbma_moi'";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            echo "<script>alert('Mã số phòng ban đã tồn tại! Vui lòng chọn mã khác.'); window.history.back();</script>";
            exit();
        }
    }

        // Update query
        $query = "UPDATE `phongban` 
                SET `PB_TEN`='$pbten',
                    `PB_MA`='$pbma_moi'
                WHERE `PB_MA`='$pbma_cu'";
        $result = mysqli_query($conn, $query);

    // Check the result
    if ($result) {
        header('location:danhsachpb.php');
    } else {
        echo "Lỗi khi cập nhật dữ liệu: " . mysqli_error($conn);
    }
}
?>

    <div class="wrapper">
        <!-- Sidebar  -->
        <?php include ("../menu.php"); ?>

        <!-- Page Content  -->
        <div id="content">

            <div class="content-header">
                <div class="container-fluid">
                 <div class="row mb-2">
                        <div class="col-sm-6">
                            <h3 class="m-0" style="color: #d30e0e; font-weight: 700;">Sửa Phòng Ban</h3>
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
                                    <div class="row">
                                        <div class="col-md-12">
                                             <div class="form-group">
                                                <label>Mã Phòng Ban</label>
                                                <input type="text" class="form-control form-control-sm" required name="mapbmoi" value="<?php echo $phongban['PB_MA']; ?>">
                                            </div> 
                                            <div class="form-group">
                                                <label>Tên Phòng Ban :</label>
                                                <input type="text" class="form-control form-control-sm" required name="txtTenPB" value="<?php echo $phongban['PB_TEN']; ?>">
                                            </div>
                                            
                                        </div>
                                    </div>
                                    <br>
                                    <div class="card-footer border-top border-info">
                                        <div class="d-flex w-100 justify-content-center align-items-center">
                                            <button type="submit" id="btnluu" name="btnluu" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px; border:2px solid #ff1e004d">Save</button>
                                            <a href="danhsachpb.php" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px; border:2px solid #ff1e004d">Cancel</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- jQuery CDN - Slim version (=without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
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
</body>

</html>
