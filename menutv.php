<nav id="sidebar">
            <div class="sidebar-header">
            <h4 style="font-size: 23px;">Quản Lý Công Việc</h4>
            </div>
            <ul class="list-unstyled components" style="margin-bottom: 0px; padding: 0px">
                <li><a href="index.php"><i class="fas fa-home"></i> Bảng Điều Khiển</a></li>
                <li><a href="danhsachcvtv.php"><i class="fas fa-calendar-alt"></i> Công Việc Cá Nhân</a></li>
                <li><a><i class="fas fa-user-tie"></i> Tài Khoản, <?php echo $_SESSION['username']; ?> </a>
                </li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt" ></i> Đăng xuất</a></li>
            </ul>
</nav>