<?php
include('../config.php');
session_start(); // Start the session

// Debug: Kiểm tra kết nối database
if (!$conn) {
    echo "Lỗi: Không thể kết nối database!";
    exit();
}

// Kiểm tra session
if (!isset($_SESSION['code'])) {
    echo "Lỗi: Chưa đăng nhập! Vui lòng đăng nhập lại.";
    echo "<br><a href='../index.php'>Quay lại trang đăng nhập</a>";
    exit();
}

// Lấy thông tin người dùng đang đăng nhập
$username = $_SESSION['code']; // Sử dụng mã người dùng thay vì tên

// Debug: Kiểm tra session và username
if (empty($username)) {
    echo "Lỗi: Session code trống!";
    exit();
}

$query = "SELECT tv.*, cv.CV_TEN, nnd.NND_TEN, pb.PB_TEN 
          FROM thanhvien tv 
          LEFT JOIN chucvu cv ON tv.CV_MA = cv.CV_MA 
          LEFT JOIN nhomnguoidung nnd ON tv.NND_MA = nnd.NND_MA 
          LEFT JOIN phongban pb ON tv.PB_MA = pb.PB_MA 
          WHERE tv.TV_MA = '$username'";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Lỗi truy vấn: " . mysqli_error($conn);
    exit();
}

if (mysqli_num_rows($result) > 0) {
    $member = mysqli_fetch_assoc($result);
} else {
    echo "Không tìm thấy thông tin người dùng với username: " . htmlspecialchars($username);
    echo "<br>Vui lòng kiểm tra lại thông tin đăng nhập.";
    exit();
}

// (Đã loại bỏ handler cập nhật cũ để tránh trùng lặp; logic xử lý POST nằm phía dưới)
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Thông Tin Cá Nhân</title>

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
    <div class="wrapper">
        <!-- Sidebar  -->
        <?php include ("../menu.php"); ?>

        <!-- Page Content  -->
        <div id="content">

            <div class="content-header">
                <div class="container-fluid">
                <div class="row mb-2">
                        <div class="col-sm-12">
                            <h3 class="m-0" style="color: #d30e0e; font-weight: 700; text-align: center;">Thông Tin Cá Nhân</h3>
                        </div>
                    </div>
                    <hr class="border-primary">
                </div>
            </div>

            <!-- Main content -->
            <?php
if (isset($_POST['btnluu'])) {
    $is_admin = (isset($member['NND_MA']) && (int)$member['NND_MA'] === 1);
    $old_tv_ma = $member['TV_MA']; // Lưu lại TV_MA cũ

    // Lấy dữ liệu từ form
    $ten = mysqli_real_escape_string($conn, $_POST['txtName']);
    $ngaysinh = mysqli_real_escape_string($conn, $_POST['txtNgaySinh']);
    $gioitinh = mysqli_real_escape_string($conn, $_POST['gioitinh']);
    $email = mysqli_real_escape_string($conn, $_POST['txtEmail']);
    $quequan = mysqli_real_escape_string($conn, $_POST['txtquequan']);

    // Khởi tạo câu lệnh UPDATE
    $sql_update = "UPDATE thanhvien SET TV_TEN = '$ten', TV_NGAYSINH = '$ngaysinh', TV_GIOITINH = '$gioitinh', TV_EMAIL = '$email', TV_QUEQUAN = '$quequan'";

    // Nếu là admin, thêm các trường cần cập nhật
    if ($is_admin) {
        $new_tv_ma = mysqli_real_escape_string($conn, $_POST['tv_ma']);
        $cv_ma = mysqli_real_escape_string($conn, isset($_POST['selectChucVu']) ? $_POST['selectChucVu'] : '');
        $nnd_ma = mysqli_real_escape_string($conn, isset($_POST['selectNhomNguoiDung']) ? $_POST['selectNhomNguoiDung'] : '');
        $pb_ma = mysqli_real_escape_string($conn, isset($_POST['selectPhongBan']) ? $_POST['selectPhongBan'] : '');

        // Validate TV_MA mới không rỗng và không trùng với tài khoản khác
        if ($new_tv_ma === '') {
            echo "<script>alert('Tên đăng nhập không được để trống.');</script>";
            return;
        }
        if ($new_tv_ma !== $old_tv_ma) {
            $chk_rs = mysqli_query($conn, "SELECT 1 FROM thanhvien WHERE TV_MA = '$new_tv_ma' LIMIT 1");
            if ($chk_rs && mysqli_num_rows($chk_rs) > 0) {
                echo "<script>alert('Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.');</script>";
                return;
            }
        }

        // Thêm các trường vào câu lệnh UPDATE
        $sql_update .= ", TV_MA = '$new_tv_ma', CV_MA = '$cv_ma', NND_MA = '$nnd_ma', PB_MA = '$pb_ma'";
    } else {
        $new_tv_ma = $old_tv_ma; // Người dùng thường không thể đổi TV_MA
    }

    $sql_update .= " WHERE TV_MA = '$old_tv_ma'";
    $result_update = mysqli_query($conn, $sql_update);

    $update_success = $result_update;
    $password_message = '';

    // Cập nhật session nếu TV_MA thay đổi (ứng dụng dùng $_SESSION['code'])
    if ($is_admin && $old_tv_ma != $new_tv_ma) {
        $_SESSION['code'] = $new_tv_ma;
    }

    // Xử lý đổi mật khẩu
    if (!empty($_POST['txtMatKhauCu']) && !empty($_POST['txtMatKhau']) && !empty($_POST['txtMatKhau2'])) {
        $matkhaucu = md5($_POST['txtMatKhauCu']);
        $matkhaumoi = $_POST['txtMatKhau'];
        $matkhaumoi2 = $_POST['txtMatKhau2'];

        if ($matkhaucu == $member['TV_MATKHAU']) {
            if ($matkhaumoi == $matkhaumoi2) {
                $matkhaumoi_md5 = md5($matkhaumoi);
                mysqli_query($conn, "UPDATE thanhvien SET TV_MATKHAU = '$matkhaumoi_md5' WHERE TV_MA = '$new_tv_ma'"); // Dùng new_tv_ma
                $password_message = 'Cập nhật mật khẩu thành công!';
            } else {
                $password_message = 'Mật khẩu mới không trùng khớp!';
                $update_success = false;
            }
        } else {
            $password_message = 'Mật khẩu cũ không đúng!';
            $update_success = false;
        }
    }

    if ($update_success) {
        // Nếu người dùng đang đăng nhập là người vừa được cập nhật, đồng bộ lại session hiển thị
        if (isset($_SESSION['code']) && $_SESSION['code'] === $old_tv_ma) {
            // Cập nhật tên hiển thị trên menu
            $_SESSION['username'] = $ten;
            // Nếu admin cũng thay đổi nhóm người dùng cho chính mình, cập nhật lại quyền trong session
            if ($is_admin && isset($nnd_ma) && $nnd_ma !== '') {
                $_SESSION['nnd_ma'] = (int)$nnd_ma;
            }
        }
        $alert_message = 'Cập nhật thông tin thành công! ' . $password_message;
        echo "<script>alert('{$alert_message}'); window.location.href='thongtincanhan.php';</script>";
    } else {
        $alert_message = 'Cập nhật thất bại! ' . $password_message . ' SQL: ' . addslashes(mysqli_error($conn));
        echo "<script>alert('{$alert_message}');</script>";
    }

    // Tải lại dữ liệu member sau khi cập nhật để hiển thị đúng
    $reload_query = "SELECT tv.*, cv.CV_TEN, nnd.NND_TEN, pb.PB_TEN 
                     FROM thanhvien tv 
                     LEFT JOIN chucvu cv ON tv.CV_MA = cv.CV_MA 
                     LEFT JOIN nhomnguoidung nnd ON tv.NND_MA = nnd.NND_MA 
                     LEFT JOIN phongban pb ON tv.PB_MA = pb.PB_MA 
                     WHERE tv.TV_MA = '$new_tv_ma'";
    $result_member = mysqli_query($conn, $reload_query);
    if ($result_member) {
        $member = mysqli_fetch_assoc($result_member);
    }
}
?>
<section class="content">
                <div class="container-fluid">
                    <div class="col-lg-12">
                        <div class="card card-outline card-primary" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-body">
                                 <form method="post">
                                     <div class="row">
                                         <?php $is_admin = (isset($member['NND_MA']) && (int)$member['NND_MA'] === 1); ?>
                                         <div class="col-md-6 border-right">
                                             <div class="form-group">
                                                 <label for="tv_ma"><strong>Tên đăng nhập:</strong></label>
                                                 <input type="text" id="tv_ma" name="tv_ma" class="form-control form-control-sm" value="<?php echo htmlspecialchars($member['TV_MA']); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>>
                                             </div>
                                             <div class="form-group">
                                                 <label><strong>Họ Và Tên:</strong></label>
                                                 <input type="text" class="form-control form-control-sm" required name="txtName" value="<?php echo htmlspecialchars($member['TV_TEN']); ?>">
                                             </div>
                                             <div class="form-group">
                                                 <label><strong>Ngày Sinh:</strong></label>
                                                 <input type="date" class="form-control form-control-sm" autocomplete="off" name="txtNgaySinh" value="<?php echo $member['TV_NGAYSINH']; ?>">
                                             </div>
                                             <div class="form-group">
                                                 <label><strong>Giới Tính:</strong></label>
                                                 <div style="border-radius: 3px; border:0.5px solid #CED4DA; height: 35px; padding: 5px;">
                                                     <div class="row">
                                                         <div class="form-check" style="padding:0px 35px 0px 50px;">
                                                             <input class="form-check-input" type="radio" name="gioitinh" value="Nam" <?php echo ($member['TV_GIOITINH'] == 'Nam') ? 'checked' : ''; ?>>
                                                             <label class="form-check-label">Nam</label>
                                                         </div>
                                                         <div class="form-check" style="padding-left: 50px;">
                                                             <input class="form-check-input" type="radio" name="gioitinh" value="Nữ" <?php echo ($member['TV_GIOITINH'] == 'Nữ') ? 'checked' : ''; ?>>
                                                             <label class="form-check-label">Nữ</label>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                             <div class="form-group">
                                                 <div class="row">
                                                     <div class="col-md-4">
                                                         <label><strong>Mật Khẩu Hiện Tại:</strong></label>
                                                         <input type="password" class="form-control form-control-sm" name="txtMatKhauCu" placeholder="Mật khẩu hiện tại">
                                                     </div>
                                                     <div class="col-md-4">
                                                         <label><strong>Mật Khẩu Mới:</strong></label>
                                                         <input type="password" class="form-control form-control-sm" name="txtMatKhau" placeholder="Mật khẩu mới">
                                                     </div>
                                                     <div class="col-md-4">
                                                         <label><strong>Xác Nhận:</strong></label>
                                                         <input type="password" class="form-control form-control-sm" name="txtMatKhau2" placeholder="Xác nhận mật khẩu mới">
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                         <div class="col-md-6">
                                             <div class="form-group">
                                                 <label><strong>Gmail:</strong></label>
                                                 <input type="email" class="form-control form-control-sm" name="txtEmail" value="<?php echo htmlspecialchars($member['TV_EMAIL']); ?>">
                                             </div>
                                             <?php if ($is_admin): ?>
                                                 <?php
                                                 $chucvu_list = mysqli_query($conn, "SELECT * FROM chucvu");
                                                 $nnd_list = mysqli_query($conn, "SELECT * FROM nhomnguoidung");
                                                 $phongban_list = mysqli_query($conn, "SELECT * FROM phongban");
                                                 ?>
                                                 <div class="form-group">
                                                     <label><strong>Chức Vụ:</strong></label>
                                                     <select name="selectChucVu" class="form-control form-control-sm">
                                                         <?php while ($cv = mysqli_fetch_assoc($chucvu_list)): ?>
                                                             <option value="<?php echo $cv['CV_MA']; ?>" <?php echo ($cv['CV_MA'] == $member['CV_MA']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cv['CV_TEN']); ?></option>
                                                         <?php endwhile; ?>
                                                     </select>
                                                 </div>
                                                 <div class="form-group">
                                                     <label><strong>Nhóm Người Dùng:</strong></label>
                                                     <select name="selectNhomNguoiDung" class="form-control form-control-sm">
                                                         <?php while ($nnd = mysqli_fetch_assoc($nnd_list)): ?>
                                                             <option value="<?php echo $nnd['NND_MA']; ?>" <?php echo ($nnd['NND_MA'] == $member['NND_MA']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nnd['NND_TEN']); ?></option>
                                                         <?php endwhile; ?>
                                                     </select>
                                                 </div>
                                                 <div class="form-group">
                                                     <label><strong>Phòng Ban:</strong></label>
                                                     <select name="selectPhongBan" class="form-control form-control-sm">
                                                         <?php while ($pb = mysqli_fetch_assoc($phongban_list)): ?>
                                                             <option value="<?php echo $pb['PB_MA']; ?>" <?php echo ($pb['PB_MA'] == $member['PB_MA']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pb['PB_TEN']); ?></option>
                                                         <?php endwhile; ?>
                                                     </select>
                                                 </div>
                                             <?php else: ?>
                                                 <div class="form-group">
                                                     <label class="text-muted"><strong>Chức Vụ:</strong></label>
                                                     <div class="form-control form-control-sm bg-light text-muted" style="border: 1px solid #ced4da;">
                                                         <?php echo htmlspecialchars($member['CV_TEN'] ?: 'Chưa cập nhật'); ?>
                                                     </div>
                                                 </div>
                                                 <div class="form-group">
                                                     <label class="text-muted"><strong>Nhóm Người Dùng:</strong></label>
                                                     <div class="form-control form-control-sm bg-light text-muted" style="border: 1px solid #ced4da;">
                                                         <?php echo htmlspecialchars($member['NND_TEN'] ?: 'Chưa cập nhật'); ?>
                                                     </div>
                                                 </div>
                                                 <div class="form-group">
                                                     <label class="text-muted"><strong>Phòng Ban:</strong></label>
                                                     <div class="form-control form-control-sm bg-light text-muted" style="border: 1px solid #ced4da;">
                                                         <?php echo htmlspecialchars($member['PB_TEN'] ?: 'Chưa cập nhật'); ?>
                                                     </div>
                                                 </div>
                                             <?php endif; ?>
                                             <div class="form-group">
                                                 <label><strong>Địa Chỉ:</strong></label>
                                                 <textarea class="form-control" style="height: 100px" placeholder="Nhập địa chỉ tại đây" name="txtquequan"><?php echo htmlspecialchars($member['TV_QUEQUAN'] ?: ''); ?></textarea>
                                             </div>
                                         </div>
                                     </div>
                                 <br>
                                 <div class="card-footer border-top border-info">
                                     <div class="d-flex w-100 justify-content-center align-items-center">
                                         <button type="submit" id="btnluu" name="btnluu" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px; border:2px solid #ff1e004d">
                                             <i class="fas fa-save"></i> Lưu Thông Tin
                                         </button>
                                         <a href="index.php" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px; border:2px solid #ff1e004d">
                                             <i class="fas fa-home"></i> Về Trang Chủ
                                         </a>
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
