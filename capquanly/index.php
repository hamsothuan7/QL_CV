<?php
include('../config.php');


// Bắt đầu session
session_start();

// Kiểm tra xem session đã được tạo hay chưa và nếu tên người dùng không được lưu trữ trong session 'username', chuyển hướng người dùng đến trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

// Lấy danh sách dự án cho dropdown
$userCode = $_SESSION['code'];
$isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1);
$nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý

// Debug: Kiểm tra kết nối CSDL
if (!$conn) {
    die("Kết nối CSDL thất bại: " . mysqli_connect_error());
}


if ($isAdmin || $nndMa == 4) {
    // Admin: lấy tất cả dự án có da_trangthai != 0
    $sql = "SELECT * FROM duan WHERE da_trangthai != 0 ORDER BY DA_MA DESC";
    $result = mysqli_query($conn, $sql);
    
    // Debug: Kiểm tra lỗi truy vấn
    if (!$result) {
        die("Lỗi truy vấn: " . mysqli_error($conn));
    }
    
    $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Debug: Kiểm tra dữ liệu trả về
    error_log("Số dự án tìm thấy (Admin): " . count($projects));
    error_log(print_r($projects, true));
} elseif ($nndMa == 2) {
    // Quản lý: lấy dự án bản thân phụ trách hoặc thuộc phòng ban
    $sql = "SELECT DISTINCT da.* 
            FROM duan da
            LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
            LEFT JOIN thanhvien tv1 ON dt.TV_MA = tv1.TV_MA
            LEFT JOIN thanhvien tv2 ON tv2.TV_MA = ?
            WHERE da.da_trangthai != 0 
            AND (
                da.DA_NGUOIPHUTRACH = ?  -- Dự án do chính quản lý phụ trách
                OR dt.TV_MA = ?  -- Dự án mà quản lý là thành viên
                OR (tv1.PB_MA = tv2.PB_MA)  -- Dự án có thành viên cùng phòng ban
                OR EXISTS (  -- Dự án có người phụ trách cùng phòng ban
                    SELECT 1 
                    FROM thanhvien tv3 
                    WHERE tv3.TV_MA = da.DA_NGUOIPHUTRACH 
                    AND tv3.PB_MA = tv2.PB_MA
                )
            )
            ORDER BY da.DA_MA DESC";
    
    // Debug: Ghi log câu truy vấn
    error_log("Truy vấn dự án cho quản lý: " . $sql);
    error_log("Tham số: userCode=$userCode");
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('sss', $userCode, $userCode, $userCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Debug: Ghi log kết quả
        error_log("Số dự án tìm thấy (Quản lý): " . count($projects));
        error_log(print_r($projects, true));
    } else {
        error_log("Lỗi prepare: " . $conn->error);
        $projects = [];
    }
} else {
    // Thành viên: lấy dự án tham gia, phụ trách hoặc có công việc được giao
    $sql = "SELECT DISTINCT da.* 
            FROM duan da
            LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
            LEFT JOIN danhsachcongviec cv ON da.DA_MA = cv.DA_MA AND cv.TV_MA = ?
            WHERE da.da_trangthai != 0 
            AND (dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ? OR cv.TV_MA IS NOT NULL)
            ORDER BY da.DA_MA DESC";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('sss', $userCode, $userCode, $userCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $projects = [];
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Quản Lý Dự Án</title>

    <!-- Font Awesome JS -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/styleBDK.css">

    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"
        integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"
        integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
    </script>
    <script>
        // Tự động tải lại trang sau 30 phút (30 * 60 * 1000 ms) và giữ nguyên trạng thái lọc
        function reloadWithFilters() {
            // Lấy tất cả các tham số URL hiện tại
            const urlParams = new URLSearchParams(window.location.search);
            
            // Thêm timestamp để tránh cache
            urlParams.set('t', new Date().getTime());
            
            // Tải lại trang với các tham số hiện tại
            window.location.href = window.location.pathname + '?' + urlParams.toString();
        }
        
        // Thiết lập hẹn giờ tải lại
        setTimeout(reloadWithFilters, 30 * 60 * 1000);
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>

        .fn-gantt-hint {
            display: none !important;
        }
        .bar {
            cursor: pointer !important;
        }

        .itemDashboard {
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        /* Custom badge styles for task status */
        .badge-status {
            padding: 0.5em 0.8em;
            font-size: 0.9em;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-status-1 { /* Đang thực hiện */
            background-color: #3B82F6 !important;
            color: white !important;
        }
        
        .badge-status-2 { /* Đã hoàn thành */
            background-color: #10B981 !important;
            color: white !important;
        }
        
        .badge-status-3 { /* Chậm tiến độ */
            background-color: #F43F5E !important;
            color: white !important;
        }
        
        .badge-status-4 { /* Chưa bắt đầu */
            background-color: #78716C !important;
            color: white !important;
        }
        
        .badge-status-5 { /* Chờ xử lý */
            background-color: #64748B !important;
            color: white !important;
        }

        .badge-status-6 { /* Hoàn thành trể */
            background-color: #F59E0B !important;
            color: white !important;
        }
        .chart-container {
            position: relative;
            margin: 20px auto;
            height: 300px;
        }
        
        /* CSS cho bộ lọc Gantt Chart */
        .gantt-filter-card {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .gantt-filter-card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
        }
        
        .gantt-filter-card .card-header h5 {
            color: #5a5c69;
            font-weight: 700;
            margin: 0;
        }
        
        .gantt-filter-card .card-body {
            padding: 1.25rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        #ganttFilterInfo {
            border-left: 4px solid #4e73df;
            background-color: #f8f9fc;
            border-color: #4e73df;
        }
        
        #ganttLoading {
            color: #4e73df;
            font-weight: 600;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* CSS cho thông báo */
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .alert-info .close {
            color: #0c5460;
            opacity: 0.5;
        }
        
        .alert-info .close:hover {
            opacity: 1;
        }
        
        /* CSS cho bộ lọc biểu đồ */
        .chart-filter-card {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .chart-filter-card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
        }
        
        .chart-filter-card .card-header h5 {
            color: #5a5c69;
            font-weight: 700;
            margin: 0;
        }
        
        .chart-filter-card .card-body {
            padding: 1.25rem;
        }
        
        #chartFilterInfo {
            border-left: 4px solid #17a2b8;
            background-color: #f8f9fc;
            border-color: #17a2b8;
        }
        
        #chartLoading {
            color: #17a2b8;
            font-weight: 600;
        }
        
        /* CSS cho thông báo không có dữ liệu */
        .text-muted .fas {
            color: #6c757d;
        }
        
        .text-muted p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        /* CSS cho bộ lọc nhỏ */
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
        
        .form-control-sm {
            height: calc(1.5em + 0.5rem + 2px);
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
        
        .alert-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
        
        .g-2 {
            gap: 0.5rem;
        }
        
        /* CSS cho panel lọc */
        #statusFilterPanel, #progressFilterPanel, #statusFilterPanel2 {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1rem;
        }
        
        /* CSS cho nút filter */
        .btn-outline-primary:hover {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        /* CSS cho card header */
        .card-header .btn-group {
            margin-left: auto;
        }
        
        .card-header h3 {
            margin: 2px;
            font-size: 16px;
        }
        
        /* Tạo hiệu ứng cho tab đang active */
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 5px 15px !important;
        }
        
        .nav-tabs .nav-link:hover {
            color: #007bff;
            border-color: #dee2e6;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 5px 15px !important;
            cursor: pointer;
        }
        
        .nav-tabs .nav-link:hover {
            color: #007bff;
            border-color: #dee2e6;
        }
        
        .nav-tabs .nav-link.active {
            color: #007bff !important;
            background-color: transparent !important;
            border: none !important;
            border-bottom: 3px solid #007bff !important;
            font-weight: 600;
        }
        
        /* Ensure active tab content is visible */
        .tab-content > .tab-pane {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .tab-content > .tab-pane.active {
            display: block;
            opacity: 1;
        }
        
        .tab-content > .tab-pane.show {
            display: block;
            opacity: 1;
        }
        
        /* #statusFilterPanel input,
        #statusFilterPanel button {
          min-width: 0 !important;
          flex: 1 1 100% !important;
          width: 100% !important;
        } */
        .align-items-end {
            align-items: normal !important;
        }
        .btn-group-sm>.btn, .btn-sm {
            padding: 5px 10px;
            font-size: 14px !important;
            line-height: 1.5;
            border-radius: 3px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/x-icon" href="/quanlycongviec/favicon.ico">
</head>
<?php
// 1. Lấy tham số lọc từ URL (mặc định tháng/năm hiện tại)
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// 2. Cấu hình phân trang
$limit = 6; // Số dự án hiển thị trên mỗi trang
$page = isset($_GET['p_page']) ? (int)$_GET['p_page'] : 1;
$offset = ($page - 1) * $limit;

// 3. Truy vấn lấy danh sách dự án có công việc trong tháng đã chọn
// Chúng ta Join bảng duan và danhsachcongviec để lọc theo DSCV_NGAYBATDAU
$where_clause = "WHERE MONTH(cv.DSCV_NGAYBATDAU) = $selected_month 
                 AND YEAR(cv.DSCV_NGAYBATDAU) = $selected_year";

// Đếm tổng số dự án để phân trang
$sql_count = "SELECT COUNT(DISTINCT d.DA_MA) as total 
              FROM duan d 
              JOIN danhsachcongviec cv ON d.DA_MA = cv.DA_MA 
              $where_clause";
$res_count = mysqli_query($conn, $sql_count);
$total_projects = mysqli_fetch_assoc($res_count)['total'];
$total_pages = ceil($total_projects / $limit);

// 4. Lấy dữ liệu dự án và tính % tiến độ trung bình từ cột TIEN_DO
$sql_main = "SELECT d.DA_MA, d.DA_TEN, 
             AVG(cv.TIEN_DO) as tien_do_tb, 
             COUNT(cv.DSCV_MA) as so_cong_viec
             FROM duan d
             JOIN danhsachcongviec cv ON d.DA_MA = cv.DA_MA
             $where_clause
             GROUP BY d.DA_MA
             LIMIT $limit OFFSET $offset";
$result_projects = mysqli_query($conn, $sql_main);
?>

<body>
    <div class="wrapper">
        <!-- Sidebar  -->
        <?php include("../menu.php"); ?>


        <!-- Page Content  -->
        <div id="content">
            <div class="content-header">
                <div class="container-fluid">
                    <hr>
                    
                    <!-- Bộ lọc dùng chung: luôn hiển thị ở đầu trang -->
                    <!-- <div class="row mb-2">
                        <div class="col-12">
                            <div id="statusFilterPanel2" class="mb-2">
                                <div class="row align-items-end">
                                    <?php 
                                    // Lấy danh sách phòng ban
                                    $sql_pb = "SELECT * FROM phongban ORDER BY PB_TEN";
                                    $result_pb = mysqli_query($conn, $sql_pb);
                                    $phongbans = [];
                                    while ($row = mysqli_fetch_assoc($result_pb)) {
                                        $phongbans[] = $row;
                                    }
                                    ?>
                                    
                                    <?php if (isset($_SESSION['active']) && $_SESSION['active'] == 1 || (isset($_SESSION['nnd_ma']) && $_SESSION['nnd_ma'] == 1) || (isset($_SESSION['nnd_ma']) && $_SESSION['nnd_ma'] == 4)): ?>
                                    <div class="col-12 col-md-3">
                                        <select class="form-control form-control-sm status-select-input w-100" id="phongbanFilter">
                                            <option value="">-- Tất cả phòng ban --</option>
                                            <?php foreach ($phongbans as $pb): ?>
                                                <option value="<?php echo $pb['PB_MA']; ?>"><?php echo $pb['PB_TEN']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-12 col-md">
                                        <select class="form-control form-control-sm status-select-input w-100" id="projectFilter2">
                                            <option value="">-- Chọn dự án --</option>
                                            <?php foreach ($projects as $project): ?>
                                                <option value="<?php echo $project['DA_MA']; ?>" data-nam="<?php echo date('Y', strtotime($project['DA_NGAYBATDAU'])) ?? ''; ?>" data-phongban="<?php echo $project['PB_MA'] ?? ''; ?>">[<?php echo $project['DA_MA']; ?>] <?php echo $project['DA_TEN']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-auto mt-2 mt-md-0">
                                        <input type="number" class="form-control form-control-sm status-select-input" id="yearFilter" 
                                               placeholder="Năm" title="Nhập năm" 
                                               value="<?php echo date('Y'); ?>" 
                                               min="1900" max="2100" style="width: 100px;">
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-primary btn-sm status-filter-btn" id="applyStatusFilter2">
                                                        <i class="fas fa-search"></i> Lọc
                                        </button>
                                    </div>
                                    <div class="col-auto" >
                                        <button type="button" class="btn btn-secondary btn-sm status-filter-btn" id="resetStatusFilter2" title="Đặt lại">
                                                        <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <style>
                        .project-card { border: none; border-radius: 15px; transition: transform 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
                        .project-card:hover { transform: translateY(-5px); }
                        .progress { height: 12px; border-radius: 10px; background-color: #e9ecef; overflow: visible; }
                        .progress-bar { border-radius: 10px; position: relative; }
                        .progress-label { position: absolute; right: 0; top: -25px; font-weight: bold; font-size: 12px; color: #333; }
                    </style>

                    <div class="container-fluid mt-4">
                        <div class="row mb-4 align-items-center bg-white p-3 rounded shadow-sm">
                            <div class="col-md-3">
                                <label class="small text-muted">Chọn Tháng</label>
                                <select class="form-select" id="filterMonth">
                                    <?php for($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Chọn Năm</label>
                                <input type="number" class="form-control" id="filterYear" value="<?= $selected_year ?>">
                            </div>
                            <div class="col-md-2 mt-4">
                                <button class="btn btn-primary w-100" onclick="applyFilter()">
                                    <i class="fas fa-filter"></i> Lọc dữ liệu
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (mysqli_num_rows($result_projects) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result_projects)): 
                                    $progress = round($row['tien_do_tb']);
                                    $color = ($progress < 50) ? 'bg-danger' : (($progress < 80) ? 'bg-warning' : 'bg-success');
                                ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card project-card h-100 p-3" onclick="showJobDetails('<?= $row['DA_MA'] ?>', '<?= $selected_month ?>', '<?= $selected_year ?>')">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <span class="badge bg-light text-primary mb-2">ID: <?= $row['DA_MA'] ?></span>
                                                    <h6 class="card-title fw-bold text-dark"><?= $row['DA_TEN'] ?></h6>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted d-block"><?= $row['so_cong_viec'] ?> CV</small>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small>Tiến độ tổng thể</small>
                                                    <small class="fw-bold"><?= $progress ?>%</small>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar <?= $color ?> progress-bar-striped progress-bar-animated" 
                                                        style="width: <?= $progress ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="opacity-50 mb-3">
                                    <p class="text-muted">Không có công việc nào bắt đầu trong tháng <?= $selected_month ?>/<?= $selected_year ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link shadow-sm" href="?p_page=<?= $page-1 ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>">Trước</a>
                                </li>
                                <?php for($i=1; $i<=$total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link shadow-sm" href="?p_page=<?= $i ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link shadow-sm" href="?p_page=<?= $page+1 ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>">Sau</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>

                        <div class="modal fade" id="modalDetails" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="modalTitle">Chi tiết công việc</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="modalBodyDetails">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary" role="status"></div>
                                            <p class="mt-2">Đang tải dữ liệu...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                    function applyFilter() {
                        const month = document.getElementById('filterMonth').value;
                        const year = document.getElementById('filterYear').value;
                        // Chuyển hướng trang và giữ các tham số
                        window.location.href = `?month=${month}&year=${year}&p_page=1`;
                    }

                    function showJobDetails(da_ma, month, year) {
                        // Hiện modal
                        var myModal = new bootstrap.Modal(document.getElementById('modalDetails'));
                        myModal.show();
                        
                        // Đổi tiêu đề modal
                        document.getElementById('modalTitle').innerText = 'Công việc dự án: ' + da_ma;

                        // Gửi AJAX lấy dữ liệu
                        fetch(`chitietduan.php?da_ma=${da_ma}&month=${month}&year=${year}`)
                            .then(response => response.text())
                            .then(data => {
                                document.getElementById('modalBodyDetails').innerHTML = data;
                            })
                            .catch(error => {
                                document.getElementById('modalBodyDetails').innerHTML = '<p class="text-danger">Lỗi tải dữ liệu!</p>';
                            });
                    }
                    </script>
                    
                    <div class="row">
                        <div class="mb-3 w-100">
                            <div class="row">
                                <div class="col-12 col-md-6 pb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <ul class="nav nav-tabs card-header-tabs" id="chartTabs" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" id="disbursement-tab" data-toggle="tab" href="#disbursementTab" role="tab">
                                                        <i class="fas fa-money-bill-wave"></i> Biểu đồ giải ngân
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="workload-tab" data-toggle="tab" href="#workloadTab" role="tab">
                                                        <i class="fas fa-tasks"></i> Khối lượng thực hiện
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="card-body">
                                            <div class="tab-content" id="chartTabContent" style="min-height: 300px; position: relative;">
                                                <div class="tab-pane fade show active in" id="disbursementTab" role="tabpanel" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;">
                                                    <canvas id="disbursementChart" style="width: 100%; height: 100%;"></canvas>
                                                </div>
                                                <div class="tab-pane fade in" id="workloadTab" role="tabpanel" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1;">
                                                    <canvas id="workloadChart" style="width: 100%; height: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 pb-4">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h3 class="card-title">Trạng thái công việc theo dự án</h3>
                                            
                                        </div>
                                        <div class="card-body">
                                            <canvas id="statusChart2" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="card card-outline card-success itemDashboard" data-type="1"
                                style="border-left: 3px solid black; border-radius: 5px; background-color: #4b9261">
                                <div>
                                    <h2 style="display: flex; align-items: center; justify-content: center; position: relative;">
                                        <span id="totalTasksCount" style="flex: 1; text-align: center;">0</span>
                                        <i class="fas fa-tasks" style="font-size: 25px; margin-right: 10px; position: absolute; right: 0; top: 50%; transform: translateY(-50%);"></i>
                                    </h2>
                                    <p>Số Công Việc trong Dự Án</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="card card-outline card-success itemDashboard" data-type="2"
                                style="border-left: 3px solid black; border-radius: 5px; background-color: #18dcff">
                                <div>
                                    <h2 style="display: flex; align-items: center; justify-content: center; position: relative;">
                                        <span id="pendingTasksCount" style="flex: 1; text-align: center;">0</span>
                                        <i class="fas fa-clock" style="font-size: 25px; margin-right: 10px; position: absolute; right: 0; top: 50%; transform: translateY(-50%);"></i>
                                    </h2>
                                    <p>Chưa tiếp nhận</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="card card-outline card-success itemDashboard" data-type="3"
                                style="border-left: 3px solid black; border-radius: 5px; background-color: #5190d2">
                                <div>
                                    <h2 style="display: flex; align-items: center; justify-content: center; position: relative;">
                                        <span id="inProgressTasksCount" style="flex: 1; text-align: center;">0</span>
                                        <i class="fas fa-spinner" style="font-size: 25px; margin-right: 10px; position: absolute; right: 0; top: 50%; transform: translateY(-50%);"></i>
                                    </h2>
                                    <p>Đang Tiến Hành</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="card card-outline card-success itemDashboard" data-type="4"
                                style="border-left: 3px solid black; border-radius: 5px; background-color: #32ff7e;">
                                <div>
                                    <h2 style="display: flex; align-items: center; justify-content: center; position: relative;">
                                        <span id="completedTasksCount" style="flex: 1; text-align: center;">0</span>
                                        <i class="fas fa-check-circle" style="font-size: 25px; margin-right: 10px; position: absolute; right: 0; top: 50%; transform: translateY(-50%);"></i>
                                    </h2>
                                    <p>Đã Hoàn Thành</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="card card-outline card-success itemDashboard" data-type="5"
                                style="border-left: 3px solid black; border-radius: 5px; background-color: #d63031;">
                                <div>
                                    <h2 style="display: flex; align-items: center; justify-content: center; position: relative;">
                                        <span id="overdueTasksCount" style="flex: 1; text-align: center;">0</span>
                                        <i class="fas fa-exclamation-triangle" style="font-size: 25px; margin-right: 10px; position: absolute; right: 0; top: 50%; transform: translateY(-50%);"></i>
                                    </h2>
                                    <p>Công Việc Trễ Tiến Độ</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="card card-outline card-success itemDashboard" data-type="6"
                                style="border-left: 3px solid black; border-radius: 5px; background-color: #f1c40f;">
                                <div>
                                    <h2 style="display: flex; align-items: center; justify-content: center; position: relative;">
                                        <span id="totalDisbursed" style="flex: 1; text-align: center;">0</span>
                                        <i class="fas fa-donate" style="font-size: 25px; margin-right: 10px; position: absolute; right: 0; top: 50%; transform: translateY(-50%);"></i>
                                    </h2>
                                    <p>Đã giải ngân</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <?php
                    $tvCode = $_SESSION['code'];
                    //Get số comment
                    $sql = "SELECT c.* FROM danhsachcongviec c WHERE c.DSCV_NGAYKETTHUC_TRANGTHAI = 0 AND c.TV_MA = '$tvCode' ";
                    $result = mysqli_query($conn, $sql);
                    $jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

                    ?>
                    <!-- Nhận xét-->
                    <?php if ($_SESSION['active'] == 0 && !empty($jobs)): ?>

                    <?php endif; ?>
                    
                </div>
                <hr class="border-primary">

                
                <?php
                // Always show progress bar for all users
                $showProgressBar = true;
                
                if ($showProgressBar): ?>
                <!-- BIỂU ĐỒ CỘT TIẾN ĐỘ CÔNG VIỆC -->
                <div class="card mt-4">
                    <div class="card-header d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-bold me-3">Thống kê tiến độ hoàn thành công việc</span>
                        <div class="btn-group btn-group-sm me-2" role="group">
                            <button type="button" class="btn btn-outline-primary" id="btn-mode-month">Theo tháng</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-mode-quarter">Theo quý</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-mode-year">Theo năm</button>
                        </div>
                        <input type="number" min="2000" max="2100" class="form-control form-control-sm w-auto" id="input-year" value="<?php echo date('Y'); ?>" style="display:inline-block; width:90px;" />
                    </div>
                    <div class="card-body">
                        <canvas id="progressBarChart" height="80"></canvas>
                    </div>
                </div>
                <hr class="border-primary">
                <?php endif; ?>

                


                
                <?php
                // Always show Gantt chart for all users
                $showGantt = true;
                
                // Hiển thị biểu đồ luôn
                if ($showGantt):
                ?>
                <div class=" align-items-center mb-3">
                <a class="btn btn-sm btn-success text-white" href="../exportda.php"><i class="fas fa-file-excel"></i>&nbsp;Xuất
                    báo cáo dự án</a>
                <a class="btn btn-sm btn-success text-white" href="../export.php"><i class="fas fa-file-excel"></i>&nbsp;Xuất
                    báo cáo công việc</a>
                </div>
                    
                <h3 class="text-center mb-3">Biểu đồ tiến trình dự án</h3>
                
                <div class="gantt-scroll-container">
                    <!-- Thêm 2 nút chuyển đổi chế độ biểu đồ -->
                    <div class="mb-3 text-center">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" id="btnGanttMyWork">Công việc riêng</button>
                            <button type="button" class="btn btn-outline-primary" id="btnGanttProject">Biểu đồ dự án</button>
                        </div>
                    </div>
                    <div id="ganttChart"></div>
                </div>

                <!-- Container cho Gantt chart 2 - Ẩn ban đầu -->
                <div id="gantt2Container" class="gantt2-container" style="display: none; position: relative;">
                    <div id="projectTitleContainer" class="mb-3 position-relative" style="padding-right: 40px;">
                        <h4 class="text-center"><span id="projectTitle"></span></h4>
                        <button id="closeGantt2" class="btn btn-sm btn-danger" style="position: absolute; right: 0; top: 50%; transform: translateY(-50%);">
                            <i class="fas fa-times"></i> Đóng
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-4 p-3 bg-white border border-slate-200 rounded-lg shadow-sm text-xs text-slate-700 w-full">
                        <!-- 1. Chưa tiếp nhận -->
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-sm bg-slate-500 mr-2"></span>
                            <span>Chưa tiếp nhận</span>
                        </div>
                        <!-- 2. Đang tiến hành -->
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-sm bg-blue-500 mr-2"></span>
                            <span>Đang tiến hành</span>
                        </div>
                        <!-- 3. Đang trễ -->
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-sm bg-rose-500 mr-2"></span>
                            <span class="font-medium text-rose-600">Đang trễ</span>
                        </div>
                        <!-- 4. Hoàn thành đúng hạn -->
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-sm bg-emerald-500 mr-2"></span>
                            <span>Hoàn thành (Đúng hạn)</span>
                        </div>
                        <!-- 5. Hoàn thành trễ -->
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-sm bg-amber-500 mr-2"></span>
                            <span>Hoàn thành (Trễ)</span>
                        </div>
                        <!-- 6. Huỷ -->
                        <div class="flex items-center opacity-60">
                            <span class="w-3 h-3 rounded-sm bg-stone-500 mr-2"></span>
                            <span class="line-through decoration-stone-400">Huỷ</span>
                        </div>
                    </div>
                    <div class="gantt-scroll-container">
                        <div id="ganttChart2"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Task Detail Container -->
                <div id="taskDetailContainer" class="mt-4" style="display: none;">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Chi tiết công việc</h5>
                            <button id="closeTaskDetail" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times"></i> Đóng
                            </button>
                        </div>
                        <div class="card-body" id="taskDetailContent">
                            <!-- Nội dung chi tiết công việc sẽ được tải ở đây -->
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
    <?php include('ajax_work/modal_dashboard.php'); ?>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script>
    // Initialize tabs
    $(document).ready(function() {
        // Enable tab functionality
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            // Update active tab styling
            $('.nav-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            
            // Show the corresponding tab content
            var target = $(this).attr('href');
            $('.tab-pane').removeClass('show active');
            $(target).addClass('show active');
        });
    });
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>

    <!-- jQuery Gantt CSS and JS -->
    <link rel="stylesheet" href="./css/gantt.css">
    <script src="./css/jquery.fn.gantt.min.js"></script>

    <script type="text/javascript">
        // Hàm ẩn Gantt chart 2
                    // Hàm ẩn Gantt chart 2 - định nghĩa ở scope global
            window.hideGantt2 = function() {
            $('#gantt2Container').fadeOut(300);
        }

        var initGanttChart = function() {
    console.log('Initializing Gantt chart...');
    
    $.ajax({
        url: "lichCVCN_GRANT_mywork.php",
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                console.error('Error loading Gantt data:', data.error);
                return;
            }
            console.log('Got Gantt data:', data);
            $("#ganttChart").gantt({
                source: function(callback) {
                    callback(data);
                },
                navigate: "scroll",
                scale: "days",
                maxScale: "months",
                minScale: "hours",
                itemsPerPage: 50,
                monthDisplay: 'month',
                monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                dateFormat: 'dd/MM/yyyy',
                timeFormat: 'HH:mm',
                useShowDay: function(date) {
                    var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                    return date.getDate() <= lastDay;
                },
                onItemClick: function(clickedItemId, e) {
                    console.log('Đã click vào công việc ID:', clickedItemId);
                    if (!clickedItemId) return;
                    
                    var idStr = String(clickedItemId).toUpperCase();
                    
                    // Nếu là công việc (bắt đầu bằng 'CV')
                    if (idStr.startsWith('CV')) {
                        loadTaskDetail(clickedItemId);
                    }
                    // Nếu là ID dự án (chỉ chứa số)
                    else if (/^\d+$/.test(clickedItemId)) {
                        window.open('lichCVCN_GRANT2.php?id=' + encodeURIComponent(clickedItemId), '_blank');
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Failed to load Gantt data:', error);
            console.error('Server response:', xhr.responseText);
            // Thử lại sau 2 giây
            setTimeout(initGanttChart, 2000);
        }
    });
};

            // Xử lý form lọc Gantt Chart
            $('#ganttFilterForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validation
                var fromDate = $('#ganttFromDate').val();
                var toDate = $('#ganttToDate').val();
                
                if (fromDate && toDate && fromDate > toDate) {
                    alert('Ngày bắt đầu không thể lớn hơn ngày kết thúc!');
                    return;
                }
                
                reloadGanttChart();
            });

            // Xử lý nút đặt lại bộ lọc
            $('#resetGanttFilter').on('click', function() {
                $('#ganttFromDate').val('');
                $('#ganttToDate').val('');
                $('#ganttStatus').val('');
                reloadGanttChart();
            });
            
            // Auto-submit khi thay đổi trạng thái
            $('#ganttStatus').on('change', function() {
                reloadGanttChart();
            });
            
            // Auto-submit khi thay đổi ngày (với delay)
            var dateChangeTimeout;
            $('#ganttFromDate, #ganttToDate').on('change', function() {
                clearTimeout(dateChangeTimeout);
                dateChangeTimeout = setTimeout(function() {
                    reloadGanttChart();
                }, 500);
            });

            // Xử lý nút chuyển đổi chế độ biểu đồ
            $('#btnGanttProject').on('click', function() {
                // Chuyển về chế độ dự án
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnGanttMyWork').removeClass('btn-primary').addClass('btn-outline-primary');
                
                // Reload Gantt chart với dữ liệu dự án
                reloadGanttChart();
            });

            $('#btnGanttMyWork').on('click', function() {
                // Chuyển về chế độ công việc riêng
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnGanttProject').removeClass('btn-primary').addClass('btn-outline-primary');
                
                // Ẩn Gantt chart 2 khi chuyển sang công việc riêng
                if (typeof window.hideGantt2 === 'function') {
                    window.hideGantt2();
                } else {
                    $('#gantt2Container').fadeOut(300);
                }
                
                // Ẩn task detail container
                $('#taskDetailContainer').fadeOut(300);
                
                // Reload Gantt chart với dữ liệu công việc riêng (kèm tham số lọc ngày)
                var fromDate = $('#ganttFromDate').val();
                var toDate = $('#ganttToDate').val();
                var phongbanId = $('#phongbanFilter').val();
                var sourceUrl = 'lichCVCN_GRANT_mywork.php';
                var params = [];
                if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
                if (toDate) params.push('to_date=' + encodeURIComponent(toDate));
                if (phongbanId) params.push('phongban=' + encodeURIComponent(phongbanId));
                if (params.length > 0) {
                    sourceUrl += '?' + params.join('&');
                }
                $('#ganttChart').empty();
                
                $("#ganttChart").gantt({
                    source: sourceUrl,
                    navigate: "scroll",
                    scale: "days",
                    maxScale: "months",
                    minScale: "hours",
                    itemsPerPage: 50,
                    monthDisplay: 'month',
                    monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    dateFormat: 'dd/MM/yyyy',
                    timeFormat: 'HH:mm',
                    useShowDay: function(date) {
                        var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                        return date.getDate() <= lastDay;
                    },
                    onItemClick: function(clickedItemId, e) {
                        console.log('Đã click vào công việc ID (Gantt 2):', clickedItemId);
                        if (!clickedItemId) return;
                        
                        var idStr = String(clickedItemId).toUpperCase();
                        console.log('Chuỗi ID sau khi chuyển đổi:', idStr);
                        
                        // Nếu là công việc (bắt đầu bằng 'CV')
                        if (idStr.startsWith('CV')) {
                            console.log('Đây là công việc, gọi loadTaskDetail');
                            loadTaskDetail(clickedItemId);
                            return; // Dừng xử lý tiếp
                        }
                        
                        // Nếu là ID dự án (chỉ chứa số)
                        if (/^\d+$/.test(clickedItemId)) {
                            console.log('Đây là dự án, mở lichCVCN_GRANT2.php');
                            window.open('lichCVCN_GRANT2.php?id=' + encodeURIComponent(clickedItemId), '_blank');
                        }
                    },
                    onRender: function() {
                        console.log("Biểu đồ công việc riêng đã được tải xong");
                        // Hiển thị thông tin lọc nếu có
                        var filterText = '';
                        if (fromDate || toDate) {
                            filterText = 'Đang hiển thị công việc riêng';
                            if (fromDate) filterText += ' từ ' + formatDateForDisplay(fromDate);
                            if (toDate) filterText += ' đến ' + formatDateForDisplay(toDate);
                            $('#filterInfoText').text(filterText);
                            $('#ganttFilterInfo').show();
                        } else {
                            $('#ganttFilterInfo').hide();
                        }
                    }
                });
            });

            // Hàm reload Gantt chart với bộ lọc - định nghĩa ở scope global
            window.reloadGanttChart = function() {
                var fromDate = $('#ganttFromDate').val();
                var toDate = $('#ganttToDate').val();
                var status = $('#ganttStatus').val();
                var phongbanId = $('#phongbanFilter').val();
                
                
                // Ẩn Gantt chart 2 khi lọc mới
                $('#gantt2Container').fadeOut(300);
                $('#taskDetailContainer').fadeOut(300);
                
                // Hiển thị loading
                $('#ganttLoading').show();
                $('#ganttFilterInfo').hide();
                $('#filterBtn').prop('disabled', true);
                
                // Tạo URL với tham số lọc
                var sourceUrl = 'lichCVCN_GRANT.php';
                var params = [];
                
                if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
                if (toDate) params.push('to_date=' + encodeURIComponent(toDate));
                if (status) params.push('status=' + encodeURIComponent(status));
                if (phongbanId) params.push('phongban=' + encodeURIComponent(phongbanId));
                
                if (params.length > 0) {
                    sourceUrl += '?' + params.join('&');
                }
                
                console.log('Reloading Gantt chart with URL:', sourceUrl);
                
                // Destroy chart hiện tại
                $('#ganttChart').empty();
                
                // Tạo lại chart với source mới
                $("#ganttChart").gantt({
                    source: sourceUrl,
                    navigate: "scroll",
                    scale: "days",
                    maxScale: "months",
                    minScale: "hours",
                    itemsPerPage: 50,
                    monthDisplay: 'month',
                    monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    dateFormat: 'dd/MM/yyyy',
                    timeFormat: 'HH:mm',
                    useShowDay: function(date) {
                        var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                        return date.getDate() <= lastDay;
                    },
                    onItemClick: function(clickedProjectId) {
                        console.log("Đã nhấn vào dự án ID:", clickedProjectId);
                        loadGrantChart(clickedProjectId);
                    },
                    onRender: function() {
                        // Ẩn loading sau khi render xong
                        $('#ganttLoading').hide();
                        $('#filterBtn').prop('disabled', false);
                        
                        // Hiển thị thông tin lọc
                        var filterText = '';
                        if (fromDate || toDate || status) {
                            filterText = 'Đang hiển thị dự án';
                            if (fromDate) filterText += ' từ ' + formatDateForDisplay(fromDate);
                            if (toDate) filterText += ' đến ' + formatDateForDisplay(toDate);
                            if (status) {
                                var statusText = $('#ganttStatus option:selected').text();
                                filterText += ' với trạng thái "' + statusText + '"';
                            }
                            
                            $('#filterInfoText').text(filterText);
                            $('#ganttFilterInfo').show();
                        } else {
                            $('#ganttFilterInfo').hide();
                        }
                        
                        // Cuộn đến Gantt chart 1 sau khi lọc
                        $('html, body').animate({
                            scrollTop: $('#ganttChart').offset().top - 50
                        }, 500);
                    }
                });
            }
            
            // Hàm format date cho hiển thị - định nghĩa ở scope global
            window.formatDateForDisplay = function(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('vi-VN');
            }

            // Hàm tải chi tiết công việc - định nghĩa ở scope global
            window.loadTaskDetail = function(taskId) {
                if (!taskId) {
                    console.error('Không có ID công việc được cung cấp');
                    alert('Lỗi: Không tìm thấy thông tin công việc');
                    return;
                }
                
                console.log('Đang tải chi tiết công việc ID:', taskId);
                
                // Hiển thị loading
                $('#taskDetailContent').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Đang tải thông tin...</p></div>');
                $('#taskDetailContainer').fadeIn(300);
                
                $.ajax({
                    url: 'ajax_work/task_detail.php',
                    type: 'GET',
                    data: { id: taskId },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Phản hồi từ server:', response);
                        
                        if (response.success && response.data) {
                            var task = response.data;
                            try {
                                var html = `
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-2"><span class="font-weight-bold">Tên công việc:</span> ${escapeHtml(task.DSCV_TEN || 'N/A')}</p>
                                            ${task.TEN_CONGVIEC_TQ ? `
                                            <p class="mb-2"><span class="font-weight-bold">Công việc tiên quyết:</span> 
                                                <span class="badge badge-info">
                                                    ${escapeHtml(task.TEN_CONGVIEC_TQ || 'N/A')}
                                                </span>
                                            </p>` : ''}
                                            <p class="mb-2"><span class="font-weight-bold">Dự án:</span> ${task.DA_TEN ? escapeHtml(task.DA_TEN) : 'Không thuộc dự án'}</p>
                                            <p class="mb-2"><span class="font-weight-bold">Người thực hiện:</span> ${escapeHtml(task.TV_TEN || 'Chưa giao')}</p>
                                            <p class="mb-2"><span class="font-weight-bold">Trạng thái:</span> 
                                                <span class="badge badge-${getStatusBadgeClass(task.DSCV_TRANGTHAI)}">
                                                    ${escapeHtml(task.TEN_TRANGTHAI || 'N/A')}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2"><span class="font-weight-bold">Ngày bắt đầu:</span> ${task.DSCV_NGAYBATDAU ? formatDate(task.DSCV_NGAYBATDAU) : 'Chưa xác định'}</p>
                                            <p class="mb-2"><span class="font-weight-bold">Hạn hoàn thành:</span> ${task.DSCV_NGAYKETTHUC ? formatDate(task.DSCV_NGAYKETTHUC) : 'Chưa xác định'}</p>
                                            <p class="mb-2"><span class="font-weight-bold">Tiến độ:</span> ${task.TIEN_DO || 0}%</p>
                                        </div>
                                    </div>
                                    ${task.DSCV_MOTA ? `
                                    <div class="form-group">
                                        <label class="font-weight-bold">Mô tả:</label>
                                        <div class="border p-3 rounded bg-light">
                                            ${task.DSCV_MOTA}
                                        </div>
                                    </div>` : ''}
                                    ${task.FILE ? `
                                    <div class="form-group">
                                        <label class="font-weight-bold">Tệp đính kèm:</label>
                                        <div class="border p-3 rounded bg-light">
                                            <a href="../capquanly/${task.FILE.split('/').map(part => encodeURIComponent(part)).join('/')}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-download"></i> Tải xuống
                                            </a>
                                        </div>
                                    </div>` : ''}
                                    
                                    <div class="form-group mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="font-weight-bold mb-0">Hoạt động</label>
                                        </div>
                                        <div class="border rounded">
                                            <div id="taskComments" class="p-3">
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-spinner fa-spin"></i> Đang tải hoạt động...
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                $('#taskDetailContent').html(html);
                                
                                // Cuộn đến phần chi tiết công việc
                                $('html, body').animate({
                                    scrollTop: $('#taskDetailContainer').offset().top - 20
                                }, 500);
                                
                                // Load comments after showing task details
                                console.log('Loading comments for task:', taskId);
                                loadTaskComments(taskId);
                            } catch (e) {
                                console.error('Lỗi khi tạo giao diện chi tiết:', e);
                                $('#taskDetailContent').html(`
                                    <div class="alert alert-danger">
                                        <h5>Đã xảy ra lỗi khi hiển thị thông tin chi tiết</h5>
                                        <p>${escapeHtml(e.message)}</p>
                                    </div>
                                `);
                            }
                        } else {
                            var errorMsg = response.message || 'Có lỗi xảy ra khi tải thông tin công việc';
                            console.error('Lỗi từ server:', errorMsg);
                            $('#taskDetailContent').html(`
                                <div class="alert alert-warning">
                                    <h5>Không thể tải thông tin chi tiết</h5>
                                    <p>${escapeHtml(errorMsg)}</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi AJAX:', status, error);
                        var errorMsg = 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            try {
                                var jsonResponse = JSON.parse(xhr.responseText);
                                if (jsonResponse.message) errorMsg = jsonResponse.message;
                            } catch (e) {
                                errorMsg = xhr.responseText.substring(0, 200) + '...';
                            }
                        }
                        
                        $('#taskDetailContent').html(`
                            <div class="alert alert-danger">
                                <h5>Lỗi khi tải dữ liệu</h5>
                                <p>${escapeHtml(errorMsg)}</p>
                                <button class="btn btn-sm btn-outline-secondary mt-2" onclick="loadTaskDetail('${escapeHtml(taskId)}')">
                                    <i class="fas fa-sync-alt"></i> Thử lại
                                </button>
                            </div>
                        `);
                    }
                });
            }
            
            // Hàm tải bình luận của công việc
            function loadTaskComments(taskId) {
                if (!taskId) return;
                
                // Hiển thị loading cho phần bình luận
                $('#taskComments').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Đang tải bình luận...</div>');
                
                $.ajax({
                    url: 'ajax_work/get_comments.php',
                    type: 'GET',
                    data: { task_id: taskId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && Array.isArray(response.data)) {
                            renderComments(response.data, taskId);
                        } else {
                            $('#taskComments').html('<div class="alert alert-warning">' + (response.message || 'Không thể tải bình luận') + '</div>');
                        }
                    },
                    error: function() {
                        $('#taskComments').html('<div class="alert alert-danger">Lỗi khi tải bình luận. Vui lòng thử lại sau.</div>');
                    }
                });
            }
            
            // Hàm hiển thị danh sách bình luận
            function renderComments(comments, taskId) {
                if (!comments || comments.length === 0) {
                    $('#taskComments').html('<div class="text-muted text-center p-3">Chưa có bình luận nào</div>');
                    return;
                }
                
                var html = '<div class="comments-list">';
                
                comments.forEach(function(comment) {
                    var commentDate = new Date(comment.date);
                    var formattedDate = commentDate.toLocaleString('vi-VN', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    html += `
                        <div class="mb-2 p-2 border-bottom" data-comment-id="${comment.id}">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span class="font-weight-bold">${escapeHtml(comment.user_name)}</span>
                                <span>${formattedDate}</span>
                            </div>
                            <div class="comment-content">${comment.content}</div>
                        </div>`;
                });
                
                html += '</div>';
                
                $('#taskComments').html(html);
            }
            
            // Hàm xử lý nội dung HTML an toàn
            // Hàm escape HTML - định nghĩa ở scope global
            window.escapeHtml = function(unsafe) {
                if (typeof unsafe === 'undefined' || unsafe === null) return '';
                
                // Chỉ escape các ký tự đặc biệt không nằm trong thẻ HTML
                // Đầu tiên, thay thế tất cả các thẻ mở và đóng tạm thời
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = unsafe.toString();
                
                // Lấy nội dung text đã được xử lý bởi trình duyệt
                const processedHtml = tempDiv.innerHTML;
                
                // Thay thế các ký tự đặc biệt ngoài thẻ HTML
                return processedHtml
                    .replace(/&(?!amp;|lt;|gt;|quot;|#039;)/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
            
            // Hàm định dạng ngày tháng
            // Hàm format date - định nghĩa ở scope global
            window.formatDate = function(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('vi-VN');
            }
            
            // Hàm lấy class badge dựa trên trạng thái
            // Hàm lấy class badge cho trạng thái - định nghĩa ở scope global
            window.getStatusBadgeClass = function(status) {
                return 'badge-status badge-status-' + parseInt(status);
            }
            
            // Sự kiện đóng chi tiết công việc
            $(document).on('click', '#closeTaskDetail', function() {
                $('#taskDetailContainer').fadeOut(300);
            });
            
            // Định nghĩa hàm loadGrantChart ở scope global
            window.loadGrantChart = function(projectId) {
                console.log('Đang tải dự án ID:', projectId);
                
                // Kiểm tra nếu ID bắt đầu bằng 'CV' thì gọi loadTaskDetail
                var idStr = String(projectId).toUpperCase();
                if (idStr.startsWith('CV')) {
                    console.log('Phát hiện ID công việc, chuyển hướng sang loadTaskDetail');
                    loadTaskDetail(projectId);
                    return;
                }

                // Hiển thị container Gantt chart 2 và các thành phần bên trong
                $('#gantt2Container').fadeIn(300);
                $('#projectTitleContainer').show();
                $('#projectTitle').text('Đang tải thông tin dự án...');
                $('#ganttChart2').show();
                $('#taskDetailContainer').hide(); // Ẩn chi tiết công việc khi tải lại biểu đồ


                // Thêm sự kiện click cho nút đóng
                $(document).on('click', '#closeGantt2', function(e) {
                    e.preventDefault();
                    hideGantt2();
                });

                // Lấy tên dự án từ API
                $.ajax({
                    url: 'ajax/get_project_name.php',
                    type: 'GET',
                    data: {
                        id: projectId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Phản hồi từ API:', response);
                        if (response && response.success) {
                            $('#projectTitle').text('Dự án: ' + response.projectName);
                        } else {
                            $('#projectTitle').text('Không thể tải tên dự án');
                            console.error('Lỗi khi lấy tên dự án:', response ? response.message : 'Lỗi không xác định');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#projectTitle').text('Lỗi khi tải thông tin dự án');
                        console.error('Lỗi khi gọi API lấy tên dự án:', error);
                    }
                });

                // Destroy chart hiện tại nếu có
                $('#ganttChart2').empty();

                // Khởi tạo biểu đồ Gantt
                var yearVal = $('#yearFilter').val();
                var ganttSource = 'lichCVCN_GRANT2.php?id=' + encodeURIComponent(projectId);
                if (yearVal) {
                    ganttSource += '&year=' + encodeURIComponent(yearVal);
                }
                $("#ganttChart2").gantt({
                    source: ganttSource,
                    navigate: "scroll",
                    scale: "days",
                    maxScale: "months",
                    minScale: "hours",
                    itemsPerPage: 50,
                    monthDisplay: 'month',
                    monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    dateFormat: 'dd/MM/yyyy',
                    timeFormat: 'HH:mm',
                    scrollToToday: true,
                    onItemClick: function(clickedItemId, e) {
                        try {
                            console.log('Đã click vào công việc trong Gantt chính, ID ban đầu:', clickedItemId, 'Sự kiện:', e);
                            
                            // Nếu không có ID, thử lấy từ phần tử được click (nếu có event)
                            if (!clickedItemId && e && e.target) {
                                var $target = $(e.target);
                                var $row = $target.closest('tr');
                                
                                // Thử lấy ID từ data-id nếu có
                                clickedItemId = $row.find('.gantt-label').data('id');
                                console.log('Lấy ID từ data-id:', clickedItemId);
                                if (!clickedItemId) return;
                            } else if (!clickedItemId) {
                                console.log('Không có ID từ sự kiện');
                                return;
                            }
                            
                            // Chuyển đổi ID thành chuỗi để kiểm tra
                            var idStr = String(clickedItemId);
                            console.log('Chuỗi ID sau khi chuyển đổi:', idStr);
                            
                            // Kiểm tra xem có phải là ID công việc không
                            if (idStr.startsWith('CV') || idStr.startsWith('cv')) {
                                // Nếu là công việc (bắt đầu bằng 'CV' hoặc 'cv')
                                console.log('Đây là công việc, gọi loadTaskDetail với ID:', clickedItemId);
                                loadTaskDetail(clickedItemId);
                                return false; // Ngăn chặn mọi xử lý tiếp theo
                            } 
                            
                            // Kiểm tra xem có phải là ID dự án không (chỉ chứa số)
                            if (/^\d+$/.test(clickedItemId)) {
                                console.log('Đây là dự án, gọi loadGrantChart với ID:', clickedItemId);
                                loadGrantChart(clickedItemId);
                                return false; // Ngăn chặn mọi xử lý tiếp theo
                            }
                            
                            console.log('Không xác định được loại ID:', clickedItemId);
                        } catch (error) {
                            console.error('Lỗi khi xử lý sự kiện click:', error);
                        }
                    },
                    useShowDay: function(date) {
                        // Chỉ hiển thị ngày từ 1 đến ngày cuối cùng của tháng
                        var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                        return date.getDate() <= lastDay;
                    },
                    useCookie: true,
                    showControls: false,
                    useShowWeek: false,
                    onRender: function() {
                        if (window.console && typeof console.log === "function") {
                            console.log("Biểu đồ chi tiết đã được tải xong");
                        }
                        
                        // Ẩn thông báo sau khi tải xong
                        $('.alert-info').fadeOut(500, function() {
                            $(this).remove();
                        });
                    },
                });
            }
        
    </script>

    <script type="text/javascript">
        // Hàm cập nhật số liệu thống kê
        function updateDashboardCounts(yearFilter,projectId = '', depart = '') {
            var data = {};
            if (projectId) {
                data.project_id = projectId;
                data.year = yearFilter;
                data.depart = depart;
            }
            //console.log('Phòng ban đã chọn:', depart);
            $.ajax({
                url: 'ajax_work/get_task_counts.php',
                type: 'GET',
                data: data,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    console.log('Phản hồi từ server:', response);
                    if (response && response.status === 'success') {
                        // Cập nhật số liệu cho từng card
                        $('#totalTasksCount').text(response.data?.total || 0);
                        $('#pendingTasksCount').text(response.data?.pending || 0);
                        $('#inProgressTasksCount').text(response.data?.in_progress || 0);
                        $('#completedTasksCount').text(response.data?.completed || 0);
                        $('#overdueTasksCount').text(response.data?.overdue || 0);
                        const disbursed = response.data?.disbursed || 0;
                        $('#totalDisbursed').text(disbursed >= 1000000 ? (disbursed / 1000000).toFixed(1) + ' triệu' : disbursed.toLocaleString('vi-VN'));
                    } else {
                        console.error('Lỗi khi lấy dữ liệu thống kê:', response?.message || 'Lỗi không xác định');
                        // Đặt lại về 0 nếu có lỗi
                        $('#overdueTasksCount').text('0');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', status, error);
                    console.error('Phản hồi từ server:', xhr.responseText);
                    // Đặt lại về 0 nếu có lỗi
                    $('#overdueTasksCount').text('0');
                }
            });
        }

// Đảm bảo hàm được gọi khi trang tải xong
$(document).ready(function() {
    updateDashboardCounts(new Date().getFullYear());
});

// Hàm showModal - định nghĩa ở scope global
window.showModal = function(type = 1) {
    // Lấy mã dự án đang được chọn
    var projectId = $('#projectFilter2').val();
    var phongbanId = $('#phongbanFilter').val();
    
    // Tạo URL với tham số
    var url = 'ajax_work/get_dashboard.php?type=' + type;
    if (projectId) {
        url += '&da_ma=' + encodeURIComponent(projectId);
    }

    if (phongbanId) {
        url += '&pb_ma=' + encodeURIComponent(phongbanId);
    }
    
    // fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
                    document.getElementById('bodyDashBoard').innerHTML = html;
                    let text = '';
                    if (type == 1)
                        text = 'Danh sách công việc';
                    else if (type == 2)
                        text = 'Danh sách công việc chưa tiếp nhận';
                    else if (type == 3)
                        text = 'Danh sách công việc đang tiến hành';
                    else if (type == 4)
                        text = 'Danh sách công việc đã hoàn thành';
                    else if (type == 5)
                        text = 'Danh sách công việc chậm tiến độ';
                    else if (type == 6)
                        text = 'Danh sách công việc giải ngân';
                    else if (type == 7)
                        text = 'Danh sách công việc tiến độ 80&';
                    else if (type == 9)
                        text = 'Danh sách công việc có nhận xét mới';
                    else
                        text = 'Danh sách công việc chờ xét duyệt';

                    $("#textDashboard").text(text);
                    $('#modalDashBoard').modal('show');
                })
                .catch(error => console.error('Error:', error));
        }

        $(document).ready(function() {
            <?php if ($_SESSION['active'] == 1): ?>
                showModal(8);
            <?php else: ?>
                showModal(9);
            <?php endif; ?>
            $('body').on('click', '.itemDashboard', function(e) {
                let type = $(this).data('type');
                showModal(type);
            });
        });
    </script>

    <script>
    $(document).ready(function() {
        // Khai báo biến cho biểu đồ
        var statusChart, statusChart2, progressChart, disbursementChart, workloadChart;

        // Khởi tạo ban đầu
        function initializeCharts() {
            console.log('Initializing charts...');
            try {
                // Load biểu đồ trạng thái và giải ngân ngay lập tức
                var yearVal = $('#yearFilter').val() || new Date().getFullYear();
                $('#yearFilter').val(yearVal);
                $('#input-year').val(yearVal);

                // Đảm bảo các giá trị được set đúng
                if (typeof year !== 'undefined') {
                    year = parseInt(yearVal);
                }

                // Tải dữ liệu cho biểu đồ
                reloadDisbursementChart();
                if (typeof window.fetchAndDrawProgressBar === 'function') {
                    window.fetchAndDrawProgressBar();
                }
                
                console.log('Charts initialized successfully');
            } catch (e) {
                console.error('Error initializing charts:', e);
            }
        }

        // Set interval để kiểm tra và tải lại biểu đồ nếu chưa có dữ liệu
        var retryCount = 0;
        var maxRetries = 3;
        var retryInterval = setInterval(function() {
            var chartHasData = $('#disbursementChart').is(':visible') && 
                             $('#statusChart2').is(':visible');
            
            if (!chartHasData && retryCount < maxRetries) {
                console.log('Retrying chart initialization...');
                initializeCharts();
                retryCount++;
            } else {
                clearInterval(retryInterval);
            }
        }, 2000); // Thử lại sau mỗi 2 giây
        
        // Lấy dữ liệu từ PHP
        <?php
        $userCode = $_SESSION['code'];
        $isAdmin = ($_SESSION['active'] == 1);
        
        // Lấy dữ liệu trạng thái công việc
        $statusData = [];
        $statusLabels = [];
        $statusColors = [];
        
        // Các trạng thái cần thống kê
        $statuses = [
            ['id' => 5, 'label' => 'Chưa tiếp nhận', 'color' => '#64748B'],
            ['id' => 1, 'label' => 'Đang tiến hành', 'color' => '#3B82F6'],
            ['id' => 2, 'label' => 'Hoàn thành', 'color' => '#10B981'],
            ['id' => 3, 'label' => 'Trễ', 'color' => '#F43F5E'],
            ['id' => 4, 'label' => 'Hủy', 'color' => '#78716C'],
            ['id' => 6, 'label' => 'Hoàn thành trể', 'color' => '#F59E0B']
        ];
        
        foreach ($statuses as $status) {
            if ($isAdmin) {
                $sql = "SELECT COUNT(*) as count FROM `danhsachcongviec` WHERE DSCV_TRANGTHAI = ? AND DA_MA IS NOT NULL AND dscv_trangthaihd = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $status['id']);
            } else {
                // Kiểm tra xem người dùng có phải là người phụ trách dự án không
                $sql = "SELECT COUNT(*) as count ";
                $sql .= "FROM `danhsachcongviec` dcv ";
                $sql .= "INNER JOIN `duan` da ON dcv.DA_MA = da.DA_MA ";
                $sql .= "LEFT JOIN `duan_thanhvien` dt ON da.DA_MA = dt.DA_MA ";
                $sql .= "WHERE dcv.DSCV_TRANGTHAI = ? ";
                // Loại trừ công việc riêng (không thuộc dự án)
                $sql .= "AND dcv.DA_MA IS NOT NULL ";
                // Chỉ tính công việc hoạt động
                $sql .= "AND dcv.dscv_trangthaihd = 1 ";
                $sql .= "AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('isss', $status['id'], $userCode, $userCode, $userCode);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Luôn thêm nhãn ngay cả khi count = 0 để tránh mảng rỗng
            $statusData[] = (int)$row['count'];
            $statusLabels[] = $status['label'] . ' (' . (int)$row['count'] . ')';
            $statusColors[] = $status['color'];
        }
        
        // Lấy dữ liệu tiến độ công việc
        $progressData = [];
        $progressLabels = [];
        $progressColors = [];
        
        // Các mức tiến độ cần thống kê
        $progressLevels = [
            ['min' => 0, 'max' => 49, 'label' => 'Dưới 50%', 'color' => '#e74c3c'],
            ['min' => 50, 'max' => 79, 'label' => '50-79%', 'color' => '#e67e22'],
            ['min' => 80, 'max' => 99, 'label' => '80-99%', 'color' => '#3498db'],
            ['min' => 100, 'max' => 100, 'label' => 'Đã hoàn thành', 'color' => '#2ecc71']
        ];
        
        foreach ($progressLevels as $level) {
            if ($isAdmin) {
                $sql = "SELECT COUNT(*) as count FROM `danhsachcongviec` WHERE TIEN_DO >= ? AND TIEN_DO <= ? AND DA_MA IS NOT NULL AND dscv_trangthaihd = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $level['min'], $level['max']);
            } else {
                // Kiểm tra xem người dùng có phải là người phụ trách dự án không
                $sql = "SELECT COUNT(*) as count ";
                $sql .= "FROM `danhsachcongviec` dcv ";
                $sql .= "INNER JOIN `duan` da ON dcv.DA_MA = da.DA_MA ";
                $sql .= "LEFT JOIN `duan_thanhvien` dt ON da.DA_MA = dt.DA_MA ";
                $sql .= "WHERE dcv.TIEN_DO >= ? AND dcv.TIEN_DO <= ? ";
                // Loại trừ công việc riêng (không thuộc dự án)
                $sql .= "AND dcv.DA_MA IS NOT NULL ";
                // Chỉ tính công việc hoạt động
                $sql .= "AND dcv.dscv_trangthaihd = 1 ";
                $sql .= "AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iisss', $level['min'], $level['max'], $userCode, $userCode, $userCode);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Luôn thêm nhãn ngay cả khi count = 0 để tránh mảng rỗng
            $progressData[] = (int)$row['count'];
            $progressLabels[] = $level['label'] . ' (' . (int)$row['count'] . ')';
            $progressColors[] = $level['color'];
        }
        ?>
        
        // Hàm vẽ biểu đồ trạng thái - định nghĩa ở scope global
        window.drawStatusChart = function(data, labels, colors) {
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            
            // Destroy chart cũ nếu có
            if (statusChart) {
                statusChart.destroy();
            }
            
            var statusTotal = data.reduce((a, b) => a + b, 0);
            statusChart = new Chart(statusCtx, {
                type: 'pie',
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                font: { size: 13 }
                            }
                        },
                        tooltip: {
                            titleFont: { size: 13 },
                            bodyFont: { size: 13 }
                        }
                    }
                },
                data: {
                    labels: labels.map(function(label, i) {
                        var percent = statusTotal > 0 ? (data[i] / statusTotal * 100).toFixed(1) : 0;
                        return label.replace(/\(.+\)/, '') + ' (' + percent + '%)';
                    }),
                    datasets: [{
                        data: data,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var percent = statusTotal > 0 ? (value / statusTotal * 100).toFixed(1) : 0;
                                    return label + ': ' + percent + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Hàm vẽ biểu đồ tiến độ (đã comment vì element progressChart không tồn tại)
        // function drawProgressChart(data, labels, colors) {
        //     var progressCtx = document.getElementById('progressChart').getContext('2d');
        //     
        //     // Destroy chart cũ nếu có
        //     if (progressChart) {
        //         progressChart.destroy();
        //     }
        //     
        //     var progressTotal = data.reduce((a, b) => a + b, 0);
        //     progressChart = new Chart(progressCtx, {
        //         type: 'pie',
        //         data: {
        //             labels: labels.map(function(label, i) {
        //                 var percent = progressTotal > 0 ? (data[i] / progressTotal * 100).toFixed(1) : 0;
        //                 return label.replace(/\(.+\)/, '') + ' (' + percent + '%)';
        //             }),
        //             datasets: [{
        //                 data: data,
        //                 backgroundColor: colors
        //             }]
        //         },
        //         options: {
        //             responsive: true,
        //             maintainAspectRatio: false,
        //             plugins: {
        //                 legend: {
        //                     position: 'right',
        //                     labels: {
        //                         boxWidth: 12
        //                     }
        //                 },
        //                 tooltip: {
        //                     callbacks: {
        //                         label: function(context) {
        //                             var label = context.label || '';
        //                             var value = context.parsed;
        //                             var percent = progressTotal > 0 ? (value / progressTotal * 100).toFixed(1) : 0;
        //                             return label + ': ' + percent + '%';
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     });
        // }
        
        // Vẽ biểu đồ ban đầu
        var statusDataRaw = <?php echo json_encode($statusData); ?>;
        var statusLabelsRaw = <?php echo json_encode($statusLabels); ?>;
        var statusColorsRaw = <?php echo json_encode($statusColors); ?>;
        
        var progressDataRaw = <?php echo json_encode($progressData); ?>;
        var progressLabelsRaw = <?php echo json_encode($progressLabels); ?>;
        var progressColorsRaw = <?php echo json_encode($progressColors); ?>;
        // drawProgressChart(progressDataRaw, progressLabelsRaw, progressColorsRaw); // Đã xóa vì element progressChart không tồn tại
        
        // (Đã bỏ) reloadStatusChart cho biểu đồ trạng thái cũ
        
        // ===== Biểu đồ giải ngân =====
        function drawDisbursementChart(data, labels, colors) {
            var ctx = document.getElementById('disbursementChart');
            if (!ctx) return;
            
            // Xóa biểu đồ cũ nếu có
            if (disbursementChart) {
                disbursementChart.destroy();
            }
            
            // Kiểm tra nếu không có dữ liệu hoặc tổng = 0
            if (!data || data.length === 0 || data.reduce((a, b) => a + b, 0) === 0) {
                ctx = ctx.getContext('2d');
                disbursementChart = new Chart(ctx, {
                    type: 'doughnut',
                    options: {
                        plugins: {
                            legend: {
                                labels: {
                                    font: { size: 13 }
                                }
                            },
                            tooltip: {
                                titleFont: { size: 13 },
                                bodyFont: { size: 13 }
                            }
                        }
                    },
                    data: {
                        labels: ['Chưa có dữ liệu'],
                        datasets: [{
                            data: [1],
                            backgroundColor: ['#f8f9fa'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        animation: { animateRotate: false },
                        events: []
                    },
                    plugins: [{
                        id: 'noDataText',
                        beforeDraw: function(chart) {
                            var width = chart.width,
                                height = chart.height,
                                ctx = chart.ctx;
                            
                            ctx.restore();
                            // Giảm kích thước chữ và thêm xuống dòng
                            var fontSize = Math.min(14, height / 10); // Giới hạn kích thước tối đa
                            ctx.font = 'bold ' + fontSize + 'px sans-serif';
                            ctx.textBaseline = 'middle';
                            ctx.textAlign = 'center';
                            
                            var text = 'Chưa có dữ liệu';
                            var textX = width / 2;
                            var textY = height / 2;
                            
                            // Tạo hiệu ứng mờ cho chữ
                            ctx.shadowColor = 'rgba(0,0,0,0.1)';
                            ctx.shadowBlur = 5;
                            ctx.shadowOffsetX = 1;
                            ctx.shadowOffsetY = 1;
                            
                            // Vẽ chữ với màu nhạt hơn
                            ctx.fillStyle = '#adb5bd';
                            ctx.fillText(text, textX, textY);
                            
                            // Xóa shadow sau khi vẽ
                            ctx.shadowColor = 'transparent';
                            ctx.save();
                        }
                    }]
                });
                return;
            }
            
            // Nếu có dữ liệu thì vẽ biểu đồ bình thường
            ctx = ctx.getContext('2d');
            var total = data.reduce(function(a,b){return a+b;}, 0);
            disbursementChart = new Chart(ctx, {
                type: 'pie',
                data: { labels: labels, datasets: [{ data: data, backgroundColor: colors }] },
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                font: { size: 13 }
                            }
                        },
                        tooltip: {
                            titleFont: { size: 13 },
                            bodyFont: { size: 13 }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'right', 
                            labels: { 
                                boxWidth: 12,
                                generateLabels: function(chart) {
                                    const dset = chart.data.datasets[0];
                                    return chart.data.labels.map(function(label, i) {
                                        const value = (dset.data[i] || 0);
                                        return {
                                            text: label + ' ( ' + value.toLocaleString('vi-VN') + ')' , 
                                            fillStyle: dset.backgroundColor[i],
                                            strokeStyle: dset.backgroundColor[i],
                                            hidden: isNaN(dset.data[i]) || dset.data[i] === null,
                                            index: i
                                        };
                                    });
                                }
                            } 
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var value = context.parsed || 0;
                                    var percent = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    return context.label + ': ' + value.toLocaleString('vi-VN') + ' (' + percent + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        function reloadDisbursementChart() {
            var projectId = $('#projectFilter2').val();
            var yearVal = $('#yearFilter').val();
            
            // Nếu không có dự án nào được chọn, hiển thị thông báo "Chưa có dữ liệu"
            if (!projectId) {
                drawDisbursementChart([], [], []);
                return;
            }
            
            // Cập nhật thẻ giá trị sau khi fetch
            var params = [];
            if (projectId) params.push('project_id=' + encodeURIComponent(projectId));
            params.push('chart_type=disbursement');
            // Luôn lọc theo năm nếu có giá trị năm
            if (yearVal) {
                var fromDate = yearVal + '-01-01';
                var toDate = yearVal + '-12-31';
                params.push('from_date=' + encodeURIComponent(fromDate));
                params.push('to_date=' + encodeURIComponent(toDate));
            }
            var url = 'ajax/get_chart_data.php' + (params.length ? ('?' + params.join('&')) : '');
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.disbursement && response.disbursement.data && response.disbursement.data.length > 0) {
                        var hasData = response.disbursement.data.reduce((a, b) => a + b, 0) > 0;
                        if (hasData) {
                            drawDisbursementChart(
                                response.disbursement.data || [], 
                                response.disbursement.labels || ['Chưa có dữ liệu'], 
                                response.disbursement.colors || ['#f8f9fa']
                            );
                            $('#disbursementNoData').remove();
                            $('#disbursementChart').show();
                            var pj = response.disbursement.project || {};
                            var remaining = (typeof pj.remaining_raw === 'number') ? pj.remaining_raw : ((pj.total_investment || 0) - (pj.total_disbursed || 0));
                            if (remaining < 0) remaining = 0;
                            $('#valTotalInvestment').text((pj.total_investment || 0).toLocaleString('vi-VN'));
                            $('#valTotalDisbursed').text((pj.total_disbursed || 0).toLocaleString('vi-VN'));
                            if (pj.over_budget) {
                                $('#valRemainingWrapper').hide();
                                $('#valOverAmount').text((pj.over_amount || 0).toLocaleString('vi-VN'));
                                $('#valOverWrapper').show();
                            } else {
                                $('#valOverWrapper').hide();
                                $('#valRemaining').text(remaining.toLocaleString('vi-VN'));
                                $('#valRemainingWrapper').show();
                            }
                            $('#disbursementInfoCard').show();
                        } else {
                            drawDisbursementChart([], [], []);
                            $('#disbursementInfoCard').hide();
                        }
                    } else {
                        drawDisbursementChart([], [], []);
                        $('#disbursementInfoCard').hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', error);
                    drawDisbursementChart([], [], []);
                    $('#disbursementInfoCard').hide();
                }
            });
        }

        // ===== Biểu đồ khối lượng thực hiện =====
        function drawWorkloadChart(data, labels, colors) {
            var ctx = document.getElementById('workloadChart');
            if (!ctx) return;
            
            if (workloadChart) {
                workloadChart.destroy();
            }
            
            if (!data || data.length === 0 || data.reduce((a, b) => a + b, 0) === 0) {
                ctx = ctx.getContext('2d');
                workloadChart = new Chart(ctx, {
                    type: 'doughnut',
                    options: {
                        plugins: {
                            legend: {
                                labels: {
                                    font: { size: 13 }
                                }
                            },
                            tooltip: {
                                titleFont: { size: 13 },
                                bodyFont: { size: 13 }
                            }
                        }
                    },
                    data: {
                        labels: ['Chưa có dữ liệu'],
                        datasets: [{
                            data: [1],
                            backgroundColor: ['#f8f9fa'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        }
                    },
                    plugins: [{
                        id: 'noDataText',
                        beforeDraw: function(chart) {
                            var width = chart.width, height = chart.height, ctx = chart.ctx;
                            ctx.restore();
                            var fontSize = Math.min(14, height / 10);
                            ctx.font = 'bold ' + fontSize + 'px sans-serif';
                            ctx.textBaseline = 'middle';
                            ctx.textAlign = 'center';
                            ctx.fillStyle = '#adb5bd';
                            ctx.fillText('Chưa có dữ liệu', width / 2, height / 2);
                            ctx.save();
                        }
                    }]
                });
                return;
            }
            
            ctx = ctx.getContext('2d');
            var total = data.reduce(function(a,b){return a+b;}, 0);
            workloadChart = new Chart(ctx, {
                type: 'pie',
                data: { labels: labels, datasets: [{ data: data, backgroundColor: colors }] },
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                font: { size: 13 }
                            }
                        },
                        tooltip: {
                            titleFont: { size: 13 },
                            bodyFont: { size: 13 }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'right',
                            labels: { 
                                boxWidth: 12,
                                generateLabels: function(chart) {
                                    const dset = chart.data.datasets[0];
                                    return chart.data.labels.map(function(label, i) {
                                        const value = (dset.data[i] || 0);
                                        return {
                                            text: label + ' (' + value.toLocaleString('vi-VN') + ')',
                                            fillStyle: dset.backgroundColor[i],
                                            strokeStyle: dset.backgroundColor[i],
                                            hidden: isNaN(dset.data[i]) || dset.data[i] === null,
                                            index: i
                                        };
                                    });
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var value = context.parsed || 0;
                                    var percent = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    return context.label + ': ' + value.toLocaleString('vi-VN') + ' VNĐ (' + percent + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        function reloadWorkloadChart() {
            var projectId = $('#projectFilter2').val();
            var yearVal = $('#yearFilter').val();
            
            if (!projectId) {
                drawWorkloadChart([], [], []);
                return;
            }
            
            var params = [];
            if (projectId) params.push('project_id=' + encodeURIComponent(projectId));
            params.push('chart_type=workload');
            if (yearVal) {
                var fromDate = yearVal + '-01-01';
                var toDate = yearVal + '-12-31';
                params.push('from_date=' + encodeURIComponent(fromDate));
                params.push('to_date=' + encodeURIComponent(toDate));
            }
            
            var url = 'ajax/get_workload_chart.php' + (params.length ? ('?' + params.join('&')) : '');
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.workload && response.workload.data && response.workload.data.length > 0) {
                        var hasData = response.workload.data.reduce((a, b) => a + b, 0) > 0;
                        if (hasData) {
                            drawWorkloadChart(
                                response.workload.data || [],
                                response.workload.labels || ['Chưa có dữ liệu'],
                                response.workload.colors || ['#f8f9fa']
                            );
                        } else {
                            drawWorkloadChart([], [], []);
                        }
                    } else {
                        drawWorkloadChart([], [], []);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', error);
                    drawWorkloadChart([], [], []);
                }
            });
        }

        // Xử lý chuyển tab
        $('#workload-tab').on('shown.bs.tab', function (e) {
            reloadWorkloadChart();
        });

        // Gộp bộ lọc: dùng dropdown dự án chung ở nơi khác (projectFilter2)
        $('#projectFilter2').on('change', function() { 
            reloadDisbursementChart();
            reloadWorkloadChart(); // Luôn reload để clear biểu đồ cũ
        });
        $('#yearFilter').on('change', function() { 
            // Đồng bộ với input-year
            $('#input-year').val($(this).val());
            // Cập nhật biến year trong scope progress bar
            if (typeof year !== 'undefined') {
                year = parseInt($(this).val());
            }
            reloadStatusChart2(); 
            reloadDisbursementChart();
            reloadWorkloadChart(); // Luôn reload để clear biểu đồ cũ
            if (typeof window.fetchAndDrawProgressBar === 'function') {
                window.fetchAndDrawProgressBar();
            }
        });
        // Tải lần đầu: tổng toàn bộ dự án đang hoạt động so với tất cả công việc đang hoạt động
        reloadDisbursementChart();
        
        // Hàm reload biểu đồ tiến độ với bộ lọc (đã comment vì element progressChart không tồn tại)
        // function reloadProgressChart() {
        //     var fromDate = $('#progressFromDate').val();
        //     var toDate = $('#progressToDate').val();
        //     
        //     // Hiển thị loading
        //     $('#progressFilterInfo').hide();
        //     $('#applyProgressFilter').prop('disabled', true);
        //     
        //     // Tạo URL với tham số lọc
        //     var params = [];
        //     if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
        //     if (toDate) params.push('to_date=' + encodeURIComponent(toDate));
        //     params.push('chart_type=progress');
        //     
        //     var url = 'ajax/get_chart_data.php';
        //     if (params.length > 0) {
        //         url += '?' + params.join('&');
        //     }
        //     
        //     // Gọi API để lấy dữ liệu mới
        //     $.ajax({
        //         url: url,
        //         method: 'GET',
        //         dataType: 'json',
        //         success: function(response) {
        //             if (response.error) {
        //                 alert('Lỗi: ' + response.error);
        //                 return;
        //             }
        //             
        //             // Cập nhật biểu đồ tiến độ
        //             if (response.progress && response.progress.data.length > 0) {
        //                 drawProgressChart(response.progress.data, response.progress.labels, response.progress.colors);
        //                 $('#progressNoData').remove();
        //                 $('#progressChart').show();
        //             } else {
        //                 $('#progressChart').hide();
        //                 if ($('#progressNoData').length === 0) {
        //                     $('#progressChart').after('<div id="progressNoData" class="text-center text-muted py-3"><i class="fas fa-chart-pie fa-2x mb-2"></i><p class="mb-0">Không có dữ liệu</p></div>');
        //                 }
        //             }
        //             
        //             // Hiển thị thông tin lọc
        //             var filterText = [];
        //             if (fromDate) filterText.push('Từ: ' + fromDate);
        //             if (toDate) filterText.push('Đến: ' + toDate);
        //             
        //             if (filterText.length > 0) {
        //                 $('#progressFilterInfoText').text('Đang lọc: ' + filterText.join(', '));
        //                 $('#progressFilterInfo').show();
        //             }
        //             
        //             // Ẩn loading
        //             $('#applyProgressFilter').prop('disabled', false);
        //         },
        //         error: function(xhr, status, error) {
        //             console.error('Lỗi AJAX:', error);
        //             alert('Lỗi khi tải dữ liệu biểu đồ: ' + error);
        //             $('#applyProgressFilter').prop('disabled', false);
        //         }
        //     });
        // }
        
        // Bộ lọc dùng chung luôn hiển thị ở đầu trang, không cần toggle
        
        // Xử lý nút toggle bộ lọc biểu đồ tiến độ (đã comment vì element không tồn tại)
        // $('#progressFilterBtn').on('click', function() {
        //     $('#progressFilterPanel').slideToggle(200);
        // });
        
        // Nút Lọc dùng chung
        $('#applyStatusFilter2').on('click', function() {
            reloadStatusChart2();
            reloadDisbursementChart();
            if (typeof window.fetchAndDrawProgressBar === 'function') {
                window.fetchAndDrawProgressBar();
            }


            var yearVal = $('#yearFilter').val();
            var phong = $('#phongbanFilter').val();
            var projectId = $('#projectFilter2').val();
            updateDashboardCounts(yearVal, projectId, phong);
            
            if (!$('#phongbanFilter').length) {
                console.log("phòng:", $('#phongbanFilter').length);
                // Ẩn tất cả các option trong dropdown dự án
                $('#projectFilter2 option').hide();
                $('#projectFilter2 option[value=""]').show();
                // Hiển thị các option thuộc phòng ban đã chọn
                $('#projectFilter2 option[data-nam="' + year + '"]').show();
            }
        });
        
        // Xử lý nút lọc biểu đồ tiến độ (đã comment vì element không tồn tại)
        // $('#applyProgressFilter').on('click', function() {
        //     // Validation
        //     var fromDate = $('#progressFromDate').val();
        //     var toDate = $('#progressToDate').val();
        //     
        //     if (fromDate && toDate && fromDate > toDate) {
        //         alert('Ngày bắt đầu không thể lớn hơn ngày kết thúc!');
        //         return;
        //     }
        //     
        //     reloadProgressChart();
        // });
        
        // Nút Đặt lại dùng chung
        $('#resetStatusFilter2').on('click', function() {
            var currentYear = new Date().getFullYear();
            // Đặt lại bộ lọc phòng ban nếu có
            if ($('#phongbanFilter').length) {
                $('#phongbanFilter').val('').trigger('change');
            }
            $('#projectFilter2').val('');
            $('#yearFilter').val(currentYear);
            $('#input-year').val(currentYear);
            // Cập nhật biến year trong scope progress bar
            if (typeof year !== 'undefined') {
                year = currentYear;
            }
            $('#statusFilterInfo2').hide();
            reloadStatusChart2();
            reloadDisbursementChart();
            if (typeof window.fetchAndDrawProgressBar === 'function') {
                window.fetchAndDrawProgressBar();
            }
        });
        
        // Tự động lọc khi thay đổi dự án (dùng chung)
        $('#projectFilter2').on('change', function() {
            reloadStatusChart2();
            reloadDisbursementChart();
            if (typeof window.fetchAndDrawProgressBar === 'function') {
                window.fetchAndDrawProgressBar();
            }
        });
        
        // Xử lý nút đặt lại bộ lọc biểu đồ tiến độ (đã comment vì element không tồn tại)
        // $('#resetProgressFilter').on('click', function() {
        //     $('#progressFromDate').val('');
        //     $('#progressToDate').val('');
        //     $('#progressFilterInfo').hide();
        //     reloadProgressChart();
        // });
        
        // Hàm reload biểu đồ trạng thái 2 - định nghĩa ở scope global
        window.reloadStatusChart2 = function() {
            var projectId = $('#projectFilter2').val();
            var yearVal = $('#yearFilter').val();
            var phong = $('#phongbanFilter').val();
            
            // Hiển thị loading
            $('#statusFilterInfo2').hide();
            $('#applyStatusFilter2').prop('disabled', true);
            
            // Tạo URL với tham số lọc
            var params = [];
            if (projectId) params.push('project_id=' + encodeURIComponent(projectId));
            params.push('chart_type=status');
            if (yearVal) {
                var fromDate = yearVal + '-01-01';
                var toDate = yearVal + '-12-31';
                params.push('from_date=' + encodeURIComponent(fromDate));
                params.push('to_date=' + encodeURIComponent(toDate));
            }
            if (phong) params.push('depart=' + encodeURIComponent(phong));
            var url = 'ajax/get_chart_data.php';
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            // Gọi API để lấy dữ liệu mới
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert('Lỗi: ' + response.error);
                        return;
                    }
                    
                    // Cập nhật biểu đồ trạng thái 2
                    if (response.status && response.status.data.length > 0) {
                        drawStatusChart2(response.status.data, response.status.labels, response.status.colors);
                        $('#statusNoData2').remove();
                        $('#statusChart2').show();
                    } else {
                        $('#statusChart2').hide();
                        if ($('#statusNoData2').length === 0) {
                            $('#statusChart2').after('<div id="statusNoData2" class="text-center text-muted py-3"><i class="fas fa-chart-pie fa-2x mb-2"></i><p class="mb-0">Không có dữ liệu</p></div>');
                        }
                    }
                    
                    // Hiển thị thông tin lọc
                    var filterText = [];
                    if (projectId) filterText.push('Dự án: ' + $('#projectFilter2 option:selected').text());
                    if (yearVal) filterText.push('Năm: ' + yearVal);
                    
                    if (filterText.length > 0) {
                        $('#statusFilterInfoText2').text('Đang lọc: ' + filterText.join(', '));
                        $('#statusFilterInfo2').show();
                    }
                    
                    // Ẩn loading
                    $('#applyStatusFilter2').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', error);
                    alert('Lỗi khi tải dữ liệu biểu đồ: ' + error);
                    $('#applyStatusFilter2').prop('disabled', false);
                }
            });
        }
        
        // Hàm vẽ biểu đồ trạng thái 2 - định nghĩa ở scope global
        window.drawStatusChart2 = function(data, labels, colors) {
            var statusCtx2 = document.getElementById('statusChart2').getContext('2d');
            
            // Destroy chart cũ nếu có
            if (statusChart2) {
                statusChart2.destroy();
            }
            
            var statusTotal = data.reduce((a, b) => a + b, 0);
            statusChart2 = new Chart(statusCtx2, {
                type: 'pie',
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                font: { size: 13 }
                            }
                        },
                        tooltip: {
                            titleFont: { size: 13 },
                            bodyFont: { size: 13 }
                        }
                    }
                },
                data: {
                    labels: labels.map(function(label, i) {
                        var percent = statusTotal > 0 ? (data[i] / statusTotal * 100).toFixed(1) : 0;
                        return label.replace(/\(.+\)/, '') + ' (' + percent + '%)';
                    }),
                    datasets: [{
                        data: data,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var percent = statusTotal > 0 ? (value / statusTotal * 100).toFixed(1) : 0;
                                    return label + ': ' + percent + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Vẽ biểu đồ trạng thái 2 ban đầu
        drawStatusChart2(statusDataRaw, statusLabelsRaw, statusColorsRaw);
        // Gọi hàm load dữ liệu biểu đồ trạng thái 2 khi vừa load trang
        //reloadStatusChart();
        reloadStatusChart2();
        
        // Hàm cập nhật tất cả số liệu công việc theo dự án
        // Sự kiện khi thay đổi dự án trong bộ lọc
$('#projectFilter, #projectFilter2').on('change', function() {
    var projectId = $(this).val();
    var yearFilter = $('#yearFilter').val();
    var phong = $('#phongbanFilter').val();

    //console.log('Đã chọn phòng:', phong);
    
    // Cập nhật số liệu thống kê theo dự án được chọn
    updateDashboardCounts(yearFilter, projectId, phong);

    // Ẩn Gantt 1 và hiển thị Gantt 2 theo dự án khi có bộ lọc
    if (projectId) {
        // Ẩn hai nút chuyển chế độ khi đang áp dụng bộ lọc dự án
        $('#btnGanttMyWork, #btnGanttProject').hide();
        // Ẩn gantt chính và hiển thị gantt dự án chi tiết
        $('#ganttChart').hide();
        if (typeof window.loadGrantChart === 'function') {
            window.loadGrantChart(projectId);
        } else {
            // Dự phòng: hiện container gantt 2 nếu hàm chưa sẵn sàng
            $('#gantt2Container').fadeIn(300);
        }
    } else {
        // Không chọn dự án: trở về Gantt 1
        if (typeof window.hideGantt2 === 'function') {
            window.hideGantt2();
        } else {
            $('#gantt2Container').fadeOut(300);
        }
        $('#ganttChart').show();
        // Hiện lại hai nút khi bỏ lọc dự án
        $('#btnGanttMyWork, #btnGanttProject').show();
        if (typeof window.reloadMainGanttChart === 'function') {
            window.reloadMainGanttChart();
        }
    }
});

// Sửa lại hàm updateDashboardCounts để nhận tham số projectId
function updateDashboardCounts(yearFilter, projectId, depart) {
    //console.log('Năm:', yearFilter);
    const data = projectId ? { project_id: projectId } : { year: yearFilter, depart: depart};
    
    $.ajax({
        url: 'ajax_work/get_task_counts.php',
        type: 'GET',
        data: data,
        dataType: 'json',
        cache: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Dữ liệu thống kê:', response);
            if (response && response.status === 'success' && response.data) {
                // Giá trị đã fallback 0 khi không có
                var totalVal = response.data.total || 0;
                var pendingVal = response.data.pending || 0;
                var inProgressVal = response.data.in_progress || 0;
                var completedVal = response.data.completed || 0;
                var overdueVal = response.data.overdue || 0;
                var disbursedVal = response.data.disbursed || 0;

                // Cập nhật card
                $('.card[data-type="1"] span:first').text(totalVal);
                $('.card[data-type="2"] span:first').text(pendingVal);
                $('.card[data-type="3"] span:first').text(inProgressVal);
                $('.card[data-type="4"] span:first').text(completedVal);
                $('.card[data-type="5"] span:first').text(overdueVal);
                const disbursed = parseFloat(disbursedVal) || 0;
                $('#totalDisbursed').text(disbursed >= 1000000 ? (disbursed / 1000000).toFixed(1) + ' triệu' : disbursed.toLocaleString('vi-VN'));

                // Đồng bộ các phần tử theo ID nếu tồn tại
                $('#totalTasksCount').text(totalVal);
                $('#pendingTasksCount').text(pendingVal);
                $('#inProgressTasksCount').text(inProgressVal);
                $('#completedTasksCount').text(completedVal);
                $('#overdueTasksCount').text(overdueVal);
            } else {
                // Reset về 0 nếu dữ liệu lỗi
                $('.card[data-type="1"] span:first').text(0);
                $('.card[data-type="2"] span:first').text(0);
                $('.card[data-type="3"] span:first').text(0);
                $('.card[data-type="4"] span:first').text(0);
                $('.card[data-type="5"] span:first').text(0);
                $('#totalDisbursed').text('0');
                $('#totalTasksCount').text(0);
                $('#pendingTasksCount').text(0);
                $('#inProgressTasksCount').text(0);
                $('#completedTasksCount').text(0);
                $('#overdueTasksCount').text(0);
            }
        },
        error: function(xhr, status, error) {
            console.error('Lỗi AJAX:', status, error);
            console.error('Phản hồi từ server:', xhr.responseText);
            // Reset về 0 nếu request lỗi
            $('.card[data-type="1"] span:first').text(0);
            $('.card[data-type="2"] span:first').text(0);
            $('.card[data-type="3"] span:first').text(0);
            $('.card[data-type="4"] span:first').text(0);
            $('.card[data-type="5"] span:first').text(0);
            $('#totalDisbursed').text('0');
            $('#totalTasksCount').text(0);
            $('#pendingTasksCount').text(0);
            $('#inProgressTasksCount').text(0);
            $('#completedTasksCount').text(0);
            $('#overdueTasksCount').text(0);
        }
    });
}

// Cập nhật lần đầu khi tải trang
$(document).ready(function() {
    // Khởi tạo biểu đồ với thông báo "Chưa có dữ liệu"
    drawDisbursementChart([], [], []);
    
    // Cập nhật thống kê tổng quan
    updateDashboardCounts(new Date().getFullYear());
    
    // Khởi tạo biểu đồ
    initializeCharts();
    
    // Khởi tạo biểu đồ trạng thái
    if (typeof window.reloadStatusChart2 === 'function') {
        window.reloadStatusChart2();
    }

    // Khởi tạo biểu đồ tiến độ
    if (typeof window.fetchAndDrawProgressBar === 'function') {
        window.fetchAndDrawProgressBar();
    }

        // Khởi tạo biểu đồ gantt đã được chuyển xuống đoạn khởi tạo cuối cùng
    
    // Thiết lập trạng thái nút theo bộ lọc hiện tại khi tải trang
    var initProjectId = $('#projectFilter').val() || $('#projectFilter2').val();
    if (initProjectId) {
        $('#btnGanttMyWork, #btnGanttProject').hide();
        $('#ganttChart').hide();
        // Nếu có dự án được chọn từ URL, tải dữ liệu biểu đồ
        reloadDisbursementChart();
    } else {
        $('#btnGanttMyWork, #btnGanttProject').show();
        $('#ganttChart').show();
    }
    
    // Thêm sự kiện khi chọn dự án
    $('#projectFilter, #projectFilter2').on('change', function() {
        reloadDisbursementChart();
    });
    
    // Thêm sự kiện khi thay đổi năm
    $('#yearFilter').on('change', function() {
        var projectId = $('#projectFilter2').val();
        if (projectId) {
            reloadDisbursementChart();
        }
    });
});
        
        // Đã có lời gọi updateDashboardCounts() ở trên khi tải trang
        // Khởi tạo progress bar với bộ lọc năm
        if (typeof window.fetchAndDrawProgressBar === 'function') {
            window.fetchAndDrawProgressBar();
        }
    });
    </script>

    <?php if ($showProgressBar): ?>
    <script>
    let progressBarChart;
    let mode = 'month';
    let year = new Date().getFullYear();

    // Hàm fetch và vẽ progress bar - định nghĩa ở scope global
    window.fetchAndDrawProgressBar = function() {
      let params = `mode=${mode}`;
      if (mode !== 'year') params += `&year=${year}`;
      // Thêm bộ lọc năm từ filter
      var yearFilter = $('#yearFilter').val();
      if (yearFilter) {
        params += `&filter_year=${yearFilter}`;
      }
      // Thêm bộ lọc dự án nếu có
      var projectFilter = $('#projectFilter2').val();
      if (projectFilter) {
        params += `&project_id=${projectFilter}`;
      }

      // Thêm bộ lọc dự án nếu có
      var phongbanFilter = $('#phongbanFilter').val();
      if (phongbanFilter) {
        params += `&phongban_id=${phongbanFilter}`;
      }
      
      fetch('ajax_work/get_progress_bar_data.php?' + params)
        .then(res => res.json())
        .then(data => {
          drawProgressBarChart(data);
        });
    }

    // Hàm vẽ progress bar chart - định nghĩa ở scope global
    window.drawProgressBarChart = function(data) {
      const canvas = document.getElementById('progressBarChart');
      if (!canvas) {
        console.log('Progress bar chart canvas not found');
        return;
      }
      const ctx = canvas.getContext('2d');
      if (progressBarChart) progressBarChart.destroy();
      progressBarChart = new Chart(ctx, {
        type: 'bar',
        options: {
            plugins: {
                legend: {
                    labels: {
                        font: { size: 13 }
                    }
                },
                tooltip: {
                    titleFont: { size: 13 },
                    bodyFont: { size: 13 }
                }
            },
            scales: {
                x: {
                    ticks: { font: { size: 13 } }
                },
                y: {
                    ticks: { font: { size: 13 } }
                }
            }
        },
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Đúng tiến độ',
              data: data.ontime,
              backgroundColor: 'rgba(54, 162, 235, 0.7)'
            },
            {
              label: 'Trễ tiến độ',
              data: data.late,
              backgroundColor: 'rgba(255, 99, 132, 0.7)'
            }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'top' },
            title: { display: false }
          },
          scales: {
            x: { stacked: false },
            y: { beginAtZero: true, stacked: false }
          }
        }
      });
    }

    const btnModeMonth = document.getElementById('btn-mode-month');
    const btnModeQuarter = document.getElementById('btn-mode-quarter');
    const btnModeYear = document.getElementById('btn-mode-year');
    const inputYear = document.getElementById('input-year');

    if (btnModeMonth) {
      btnModeMonth.addEventListener('click', () => { mode = 'month'; fetchAndDrawProgressBar(); });
    }

    if (btnModeQuarter) {
      btnModeQuarter.onclick = function() {
        mode = 'quarter';
        if (inputYear) inputYear.style.display = '';
        fetchAndDrawProgressBar();
      };
    }

    if (btnModeYear) {
      btnModeYear.onclick = function() {
        mode = 'year';
        if (inputYear) inputYear.style.display = 'none';
        fetchAndDrawProgressBar();
      };
    }

    if (inputYear) {
      inputYear.onchange = function() {
        year = this.value;
        // Đồng bộ với yearFilter
        $('#yearFilter').val(year);
        fetchAndDrawProgressBar();
      };
      // Đồng bộ input-year với yearFilter khi tải trang
      inputYear.value = $('#yearFilter').val();
    }

    // Khởi tạo mặc định chỉ khi các element cần thiết tồn tại
    if (document.getElementById('progressBarChart')) {
      fetchAndDrawProgressBar();
    }
    
    // Xử lý sự kiện khi chọn phòng ban
    if ($('#phongbanFilter').length) {
        $('#phongbanFilter').on('change', function() {
            var phongbanId = $(this).val();
            var year = $('#yearFilter').val();
            
            // Ẩn tất cả các option trong dropdown dự án
            $('#projectFilter2 option').hide();
            
            // Hiển thị option mặc định và các option thuộc phòng ban đã chọn
            if (phongbanId === '') {
                // Nếu chọn "Tất cả phòng ban" thì hiển thị tất cả
                $('#projectFilter2 option').show();
            } else {
                // Hiển thị option mặc định
                $('#projectFilter2 option[value=""]').show();
                // Hiển thị các option thuộc phòng ban đã chọn
                $('#projectFilter2 option[data-phongban="' + phongbanId + '"][data-nam="' + year + '"]').show();

            }
            
            // Đặt lại giá trị chọn về mặc định
            $('#projectFilter2').val('').trigger('change');

            // Không chọn dự án: trở về Gantt 1
            if (typeof window.hideGantt2 === 'function') {
                window.hideGantt2();
            } else {
                $('#gantt2Container').fadeOut(300);
            }
            $('#ganttChart').show();
            // Hiện lại hai nút khi bỏ lọc dự án
            $('#btnGanttMyWork, #btnGanttProject').show();
            if (typeof window.reloadMainGanttChart === 'function') {
                window.reloadMainGanttChart();
            }

        });
    }
    </script>
    <?php endif; ?>

    <script>
    $(document).ready(function() {
        // Hàm reload biểu đồ trạng thái theo dự án - định nghĩa ở scope global (để nguyên nếu phần khác còn dùng)
        window.reloadStatusChartByProject = function() {
            var projectId = $('#projectFilter').val();
            // Hiển thị loading
            $('#progressFilterInfo').hide();
            $('#applyProgressFilter').prop('disabled', true);
            // Tạo URL với tham số lọc
            var params = [];
            if (projectId) params.push('project_id=' + encodeURIComponent(projectId));
            params.push('chart_type=status');
            var url = 'ajax/get_chart_data.php';
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            // Gọi API để lấy dữ liệu mới
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert('Lỗi: ' + response.error);
                        return;
                    }
                    // Cập nhật biểu đồ trạng thái
                    if (response.status && response.status.data.length > 0) {
                        drawStatusChart(response.status.data, response.status.labels, response.status.colors);
                        $('#progressNoData').remove();
                        $('#progressChart').show();
                    } else {
                        $('#progressChart').hide();
                        if ($('#progressNoData').length === 0) {
                            $('#progressChart').after('<div id="progressNoData" class="text-center text-muted py-3"><i class="fas fa-chart-pie fa-2x mb-2"></i><p class="mb-0">Không có dữ liệu</p></div>');
                        }
                    }
                    // Hiển thị thông tin lọc
                    var filterText = [];
                    if (projectId) filterText.push('Dự án: ' + $('#projectFilter option:selected').text());
                    if (filterText.length > 0) {
                        $('#progressFilterInfoText').text('Đang lọc: ' + filterText.join(', '));
                        $('#progressFilterInfo').show();
                    }
                    // Ẩn loading
                    $('#applyProgressFilter').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', error);
                    alert('Lỗi khi tải dữ liệu biểu đồ: ' + error);
                    $('#applyProgressFilter').prop('disabled', false);
                }
            });
        }
        // Sự kiện click nút Lọc cho biểu đồ trạng thái theo dự án
        $('#applyProgressFilter').on('click', function() {
            reloadStatusChartByProject();
        });
        // Sự kiện click nút Đặt lại
        $('#resetProgressFilter').on('click', function() {
            $('#projectFilter').val('');
            reloadStatusChartByProject();
        });
    });
    </script>

    <script type="text/javascript">
        // Khai báo biến toàn cục
        var ganttMode = 'mywork'; // 'project' hoặc 'mywork' (mặc định công việc riêng)
        
        // Hàm khởi tạo Gantt chart
        function initGanttChart() {
            console.log('Đang khởi tạo Gantt chart...');
            // Kiểm tra xem phần tử ganttChart có tồn tại không
            var $ganttChart = $('#ganttChart');
            if ($ganttChart.length === 0) {
                console.error('Không tìm thấy phần tử #ganttChart');
                return false;
            }
            
            // Kiểm tra xem thư viện Gantt đã được tải chưa
            if (typeof $.fn.gantt === 'undefined') {
                console.error('Thư viện Gantt chưa được tải');
                return false;
            }
            
            // Xóa nội dung cũ
            $ganttChart.empty();
            
            // Tạo URL nguồn dữ liệu
            var sourceUrl = (ganttMode === 'project') ? 'lichCVCN_GRANT.php' : 'lichCVCN_GRANT_mywork.php';
            var phongbanId = $('#phongbanFilter').val();
            var params = [];
            if (phongbanId) params.push('phongban=' + encodeURIComponent(phongbanId));
            if (params.length > 0) {
                    sourceUrl += '?' + params.join('&');
                }

            try {
                // Khởi tạo Gantt chart
                $ganttChart.gantt({
                    source: sourceUrl,
                    navigate: "scroll",
                    scale: "days",
                    maxScale: "months",
                    minScale: "hours",
                    itemsPerPage: 50,
                    monthDisplay: 'month',
                    monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    dateFormat: 'dd/MM/yyyy',
                    timeFormat: 'HH:mm',
                    useShowDay: function(date) {
                        var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                        return date.getDate() <= lastDay;
                    },
                    onItemClick: function(clickedProjectId) {
                        if (typeof loadGrantChart === 'function') {
                            loadGrantChart(clickedProjectId);
                        }
                    },
                    onError: function(error) {
                        console.error('Lỗi khi tải Gantt chart:', error);
                    }
                });
                
                console.log('Đã khởi tạo Gantt chart thành công');
                return true;
            } catch (e) {
                console.error('Lỗi khi khởi tạo Gantt chart:', e);
                return false;
            }
        }
        
        // Hàm reload Gantt chart
        function reloadMainGanttChart() {
            console.log('Đang tải lại Gantt chart...');
            return initGanttChart();
        }
        
        // Sự kiện click nút chuyển đổi
        $(document).on('click', '#btnGanttProject', function() {
            ganttMode = 'project';
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $('#btnGanttMyWork').removeClass('btn-primary').addClass('btn-outline-primary');
            reloadMainGanttChart();
        });
        
        $(document).on('click', '#btnGanttMyWork', function() {
            ganttMode = 'mywork';
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $('#btnGanttProject').removeClass('btn-primary').addClass('btn-outline-primary');
            reloadMainGanttChart();
        });
    </script>

    <script>
    // Hàm kiểm tra và khởi tạo Gantt chart
    function initGantt() {
        // Kiểm tra xem phần tử ganttChart có tồn tại không
        if ($('#ganttChart').length === 0) {
            console.log('Chưa tìm thấy phần tử ganttChart, sẽ thử lại sau...');
            setTimeout(initGantt, 500);
            return;
        }
        
        // Đặt chế độ mặc định
        ganttMode = 'mywork';
        
        // Khởi tạo Gantt chart
        if (!initGanttChart()) {
            // Nếu khởi tạo thất bại, thử lại sau 1 giây
            console.log('Khởi tạo Gantt chart thất bại, sẽ thử lại sau 1 giây...');
            setTimeout(initGantt, 1000);
        }
    }
    
    // Bắt đầu khởi tạo khi DOM đã sẵn sàng
    $(document).ready(function() {
        console.log('DOM đã sẵn sàng, đang khởi tạo Gantt chart...');
        
        // Thêm sự kiện click trực tiếp vào các phần tử của biểu đồ Gantt
        $(document).on('click', '.gantt .gantt-labels .gantt-label', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $label = $(this);
            var taskId = $label.data('id');
            
            if (!taskId) {
                console.log('Không tìm thấy ID công việc');
                return false;
            }
            
            console.log('Click trực tiếp vào công việc ID:', taskId);
            
            // Kiểm tra xem có phải là ID công việc không
            var idStr = String(taskId).toUpperCase();
            if (idStr.startsWith('CV')) {
                console.log('Gọi loadTaskDetail cho công việc:', taskId);
                loadTaskDetail(taskId);
                return false;
            }
            
            // Nếu không phải công việc, cho phép xử lý mặc định
            return true;
        });
        
        // Khởi tạo Gantt chart ngay lập tức
        initGanttChart();
        
        // Đảm bảo accessibility cho modalDashBoard
        $('#modalDashBoard').on('shown.bs.modal', function () {
            $(this).attr('aria-hidden', 'false');
        });
        $('#modalDashBoard').on('hidden.bs.modal', function () {
            $(this).attr('aria-hidden', 'true');
        });
    });

    
    </script>


</body>

</html>