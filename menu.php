<button id="sidebarToggle" class="btn btn-primary" style="position: fixed; top: 10px; left: 10px; z-index: 1051; display: none;">
    <i class="fas fa-bars"></i>
</button>
<div id="sidebarOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:1050;"></div>
<div style="position: absolute; right: 2px;">
    <img src="../style/logoy.png" width="40" height="40" alt="Logoy">
</div>
<nav id="sidebar">
    <button id="sidebarClose" style="position: absolute; top: 10px; right: 10px; z-index: 1060; background: none; border: none; color: #fff; font-size: 28px; display: none;">
        <i class="fas fa-times"></i>
    </button>
    <div class="sidebar-header">
        <img src="../style/logo.gif" width="75" height="75" alt="Logo">
        <br>
        <div style="padding:8px; width: 80%; ">
            <a>Xin chào, <?php echo $_SESSION['username']; ?></a>
        </div>
    </div>
    <ul class="list-unstyled components" style="margin-bottom: 0px; padding: 0px" >
        <li><a href="index.php"><i class="fas fa-home"></i> Bảng Điều Khiển</a></li>
        <li><a href="danhsachduan.php"><i class="fas fa-handshake"></i> Danh Sách Dự án</a></li>

        <?php if($_SESSION['nnd_ma'] == 1): ?>
        <li><a href="danhsachthanhvien.php"><i class="fas fa-users"></i> Danh Sách Thành Viên</a></li>
        <li><a href="danhsachpb.php"><i class="fas fa-users"></i> Danh Sách Phòng Ban</a></li>
        <li><a href="donviphoihop.php"><i class="fas fa-users"></i> Đơn Vị Phối Hợp</a></li>
        <?php endif; ?>

        <!-- <?php if($_SESSION['nnd_ma'] == 2): ?>
        <li><a href="danhsachduan.php"><i class="fas fa-handshake"></i> Danh Sách Dự án</a></li>
        <?php endif; ?> -->

        <li><a href="<?php echo ($_SESSION['nnd_ma'] == 1 || $_SESSION['nnd_ma'] == 2 || $_SESSION['nnd_ma'] == 4) ? 'danhsachcv.php': 'danhsachcvtv.php'  ?>"><i class="fas fa-calendar-alt"></i> Danh Sách Công Việc</a></li>

        <li><a href="thongtincanhan.php"><i class="fas fa-user"></i> Thông Tin Cá Nhân</a></li>

        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</nav>
<link rel="stylesheet" href="../style/responsive/responsive.css">
<script src="../style/responsive/responsive.js"></script>