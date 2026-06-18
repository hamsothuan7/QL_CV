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
    <title>Thành Viên</title>

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

// Fetch existing member data bằng Prepared Statement để tránh SQL Injection
if (isset($_GET['id'])) {
    $tvma = $_GET['id'];
    $query = "SELECT * FROM thanhvien WHERE TV_MA = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $tvma);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $member = $result->fetch_assoc();
        } else {
            echo "Không tìm thấy thông tin thành viên!";
            exit;
        }
        $stmt->close();
    } else {
        echo "Error fetching data: " . $conn->error;
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['btnluu'])) {
    UpdateData();
}

function UpdateData()
{
    include('../config.php');

    $tvma_cu = $_GET['id']; // Mã cán bộ cũ (ban đầu)
    $tvma_moi = trim($_POST['matv']); // Mã cán bộ mới (người dùng nhập)
    $Name = trim($_POST['txtName']);
    $NgaySinh = $_POST['txtNgaySinh'];
    $GioiTinh = $_POST['gioitinh'];
    $email = trim($_POST['txtEmail']);
    $matkhau = $_POST['txtMatKhau'];
    $quequan = trim($_POST['txtquequan']);
    $pb_ma = $_POST['phongban'];
    $cv_ma = $_POST['chucvu'];
    $nnd_ma = $_POST['nhomnguoidung'];

    // Kiểm tra nếu người dùng đổi mã số cán bộ thì phải kiểm tra trùng bằng Prepared Statement
    if ($tvma_cu !== $tvma_moi) {
        $checkQuery = "SELECT TV_MA FROM thanhvien WHERE TV_MA = ?";
        if ($stmt = $conn->prepare($checkQuery)) {
            $stmt->bind_param("s", $tvma_moi);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                echo "<script>alert('Mã số cán bộ đã tồn tại! Vui lòng chọn mã khác.'); window.history.back();</script>";
                $stmt->close();
                exit();
            }
            $stmt->close();
        }
    }

    // Xây dựng câu lệnh UPDATE và danh sách các bind param
    $sql = "UPDATE `thanhvien` 
            SET `TV_MA` = ?, 
                `TV_TEN` = ?, 
                `TV_GIOITINH` = ?, 
                `TV_EMAIL` = ?, 
                `PB_MA` = ?, 
                `CV_MA` = ?, 
                `NND_MA` = ?, 
                `TV_NGAYSINH` = ?,
                `TV_QUEQUAN` = ?";

    $types = "sssssssss";
    $params = [&$tvma_moi, &$Name, &$GioiTinh, &$email, &$pb_ma, &$cv_ma, &$nnd_ma, &$NgaySinh, &$quequan];

    // Chỉ cập nhật mật khẩu nếu người dùng nhập
    if (isset($matkhau) && $matkhau !== '') {
        $hashed_password = password_hash($matkhau, PASSWORD_DEFAULT);
        $sql .= ", `TV_MATKHAU` = ?";
        $types .= "s";
        $params[] = &$hashed_password;
    }

    $sql .= " WHERE `TV_MA` = ?";
    $types .= "s";
    $params[] = &$tvma_cu;

    if ($stmt = $conn->prepare($sql)) {
        // Sử dụng call_user_func_array để bind_param linh hoạt
        $bind_params = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            header('location:danhsachthanhvien.php');
            exit();
        } else {
            echo "Lỗi khi cập nhật dữ liệu: " . $conn->error;
        }
    } else {
        echo "Lỗi chuẩn bị truy vấn: " . $conn->error;
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
                            <h3 class="m-0" style="color: #d30e0e; font-weight: 700;">Sửa Thành Viên</h3>
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
                                        <div class="col-md-6 border-right">
                                            <div class="form-group">
                                                <label>Tên đăng nhập</label>
                                                <input type="text" class="form-control form-control-sm" required name="matv" value="<?php echo $member['TV_MA']; ?>">
                                                <input type="hidden" name="old_matv" value="<?php echo $member['TV_MA']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Họ Và Tên :</label>
                                                <input type="text" class="form-control form-control-sm" required name="txtName" value="<?php echo $member['TV_TEN']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Ngày Sinh :</label>
                                                <input type="date"
                                                class="form-control form-control-sm" autocomplete="off" required name="txtNgaySinh" value="<?php echo $member['TV_NGAYSINH']; ?>">
                                            </div>
                                            <label>Giới Tính :</label>
                                            <div class="form-group" style="border-radius: 3px; border:0.5px solid #CED4DA; height: 25px">
                                                <div class="row">
                                                    <div class="form-check" style="padding:0px 35px 0px 50px;">
                                                        <input class="form-check-input" type="radio" id="gioitinh" name="gioitinh" value="Nam" <?php echo ($member['TV_GIOITINH'] == 'Nam') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="flexRadioDefault1">Nam</label>
                                                    </div>
                                                    <div class="form-check" style="padding-left: 50px;">
                                                        <input class="form-check-input" type="radio"  name="gioitinh" value="Nữ" <?php echo ($member['TV_GIOITINH'] == 'Nữ') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="flexRadioDefault1">Nữ</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Gmail :</label>
                                                <input type="email" class="form-control form-control-sm" required name="txtEmail" value="<?php echo $member['TV_EMAIL']; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Mật Khẩu :</label>
                                                <input type="password" class="form-control form-control-sm" name="txtMatKhau" placeholder="Để trống nếu không đổi">
                                            </div>
                                            
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Chức Vụ :</label>
                                                <select id="chucvu" name="chucvu" class="custom-select mb-3">
                                                    <?php
                                                    $sql = "SELECT * FROM `chucvu`";
                                                    $result = mysqli_query($conn, $sql);
                                                    if ($result) {
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            $selected = ($member['CV_MA'] == $row['CV_MA']) ? 'selected' : '';
                                                            echo "<option value='" . $row['CV_MA'] . "' $selected>" . $row['CV_TEN'] . "</option>";
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
                                                            $selected = ($member['NND_MA'] == $row['NND_MA']) ? 'selected' : '';
                                                            echo "<option value='" . $row['NND_MA'] . "' $selected>" . $row['NND_TEN'] . "</option>";
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
                                                            $selected = ($member['PB_MA'] == $row['PB_MA']) ? 'selected' : '';
                                                            echo "<option value='" . $row['PB_MA'] . "' $selected>" . $row['PB_TEN'] . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>



                                            <div class="form-group">
                                                <label>Địa Chỉ : </label>
                                                <textarea class="form-control" id="floatingTextarea2" style="height: 100px" placeholder="Để trống nếu không đổi" name="txtquequan"><?php echo $member['TV_QUEQUAN']; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="card-footer border-top border-info">
                                        <div class="d-flex w-100 justify-content-center align-items-center">
                                            <button type="submit" id="btnluu" name="btnluu" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px; border:2px solid #ff1e004d">Save</button>
                                            <a href="danhsachthanhvien.php" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px; border:2px solid #ff1e004d">Cancel</a>
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
