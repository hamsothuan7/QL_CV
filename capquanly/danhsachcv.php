<?php
include('../config.php');
// Bắt đầu session
session_start();
// Kiểm tra xem session đã được tạo hay chưa và nếu tên người dùng không được lưu trữ trong session 'username', chuyển hướng người dùng đến trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$keywords = $_GET['keywords'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$project_id = $_GET['project_id'] ?? '';

/**
 * 1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
 * @param mysqli $conn Kết nối database
 * @param int $status Trạng thái công việc
 * @param string $keywords Từ khóa tìm kiếm
 * @param string $from_date Ngày bắt đầu lọc
 * @param string $to_date Ngày kết thúc lọc
 * @return array Danh sách công việc
 */
function querySql($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '')
{
    $userId = $_SESSION['code'] ?? null;
    $isAdmin = ($_SESSION['active'] == 1) || ($_SESSION['nnd_ma'] == 4);
    if (!$userId) return [];
    if ($isAdmin) {
        if ($status == 2) {
            $sql = "SELECT * FROM danhsachcongviec WHERE (DSCV_TRANGTHAI = 2 OR DSCV_TRANGTHAI = 6)  AND dscv_trangthaihd = 1";
            $types = "";
        } else {
            $sql = "SELECT * FROM danhsachcongviec WHERE DSCV_TRANGTHAI = ? AND dscv_trangthaihd = 1";
            $params = [$status];
            $types = "i";
        }
    } else {
        if ($status == 2) {
            $sql = "SELECT DISTINCT d.* FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                WHERE (d.DSCV_TRANGTHAI = 2 OR  d.DSCV_TRANGTHAI = 6)
                AND d.dscv_trangthaihd = 1 
                AND (d.TV_MA = ? OR dt.TV_MA = ? OR du.DA_NGUOIPHUTRACH = ? )";
            $params = [$userId, $userId, $userId];
            $types = "sss";
        } else {
            $sql = "SELECT DISTINCT d.* FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                WHERE d.DSCV_TRANGTHAI = ? 
                AND d.dscv_trangthaihd = 1 
                AND (d.TV_MA = ? OR dt.TV_MA = ? OR du.DA_NGUOIPHUTRACH = ? )";
            $params = [$status, $userId, $userId, $userId];
            $types = "isss";
        }
    }
    if (!empty($keywords)) {
        $sql .= " AND DSCV_TEN LIKE ?";
        $params[] = "%$keywords%";
        $types .= "s";
    }
    if (!empty($from_date)) {
        $sql .= " AND DATE(DSCV_NGAYBATDAU) >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    if (!empty($to_date)) {
        $sql .= " AND DATE(DSCV_NGAYKETTHUC) <= ?";
        $params[] = $to_date;
        $types .= "s";
    }
    if ($project_id === 'private') {
        $sql .= $isAdmin ? " AND (DA_MA IS NULL OR DA_MA = '')" : " AND (d.DA_MA IS NULL OR d.DA_MA = '')";
    } else if (!empty($project_id)) {
        $sql .= $isAdmin ? " AND DA_MA = ?" : " AND d.DA_MA = ?";
        $params[] = $project_id;
        $types .= "s";
    }
    $sql .= " ORDER BY DSCV_MA DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return [];
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) { $stmt->close(); return []; }
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Hàm kiểm tra và cập nhật trạng thái công việc
function updateExpiredTasks($conn)
{
    $currentDate = date('Y-m-d');
    $affectedRows = 0;

    // 1. Cập nhật các công việc chưa bắt đầu (status = 5) nếu đến ngày bắt đầu
    /* $sqlStart = "UPDATE danhsachcongviec 
                SET DSCV_TRANGTHAI = 1 
                WHERE DSCV_NGAYBATDAU <= ? 
                AND DSCV_TRANGTHAI = 5
                AND DA_MA IS NOT NULL";
    
    $stmt = $conn->prepare($sqlStart);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $affectedRows += $stmt->affected_rows; */

    // 2. Cập nhật các công việc đã hết hạn (ngày kết thúc < ngày hiện tại)
    // và đang ở trạng thái đang tiến hành (status = 1) hoặc chưa bắt đầu (status = 5)
    $sqlExpire = "UPDATE danhsachcongviec 
                SET DSCV_TRANGTHAI = 3 
                WHERE DSCV_NGAYKETTHUC < ? AND DATEDIFF(DSCV_NGAYKETTHUC, DSCV_NGAYBATDAU) < DATEDIFF(NOW(), DSCV_NGAYTIEPNHAN) 
                AND DSCV_TRANGTHAI =1
                AND DA_MA IS NOT NULL";

    $stmt = $conn->prepare($sqlExpire);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $affectedRows += $stmt->affected_rows;

    return $affectedRows;
}

// Gọi hàm kiểm tra và cập nhật trạng thái công việc
$updatedTasks = updateExpiredTasks($conn);

// Kiểm tra dữ liệu trực tiếp
function checkDatabaseData($conn, $userId, $userRoom) {
    $data = [];
    
    // 1. Kiểm tra thông tin người dùng
    $userQuery = "SELECT * FROM thanhvien WHERE TV_MA = '$userId'";
    $result = mysqli_query($conn, $userQuery);
    $data['user_info'] = mysqli_fetch_assoc($result);
    
    // 2. Kiểm tra các dự án trong phòng ban
    $deptProjectsQuery = "SELECT d.* FROM duan d 
                         INNER JOIN thanhvien tv ON d.DA_NGUOIPHUTRACH = tv.TV_MA 
                         WHERE tv.PB_MA = '$userRoom' AND d.DA_TRANGTHAI != 0";
    $result = mysqli_query($conn, $deptProjectsQuery);
    $data['dept_projects'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // 3. Kiểm tra thành viên trong phòng ban
    $deptMembersQuery = "SELECT * FROM thanhvien WHERE PB_MA = '$userRoom'";
    $result = mysqli_query($conn, $deptMembersQuery);
    $data['dept_members'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // 4. Kiểm tra dự án của các thành viên trong phòng
    if (!empty($data['dept_members'])) {
        $memberIds = array_column($data['dept_members'], 'TV_MA');
        $memberIdsStr = implode("','", $memberIds);
        
        $memberProjectsQuery = "SELECT DISTINCT d.* FROM duan d 
                              INNER JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                              WHERE dt.TV_MA IN ('$memberIdsStr') AND d.DA_TRANGTHAI != 0";
        $result = mysqli_query($conn, $memberProjectsQuery);
        $data['member_projects'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    
    return $data;
}

// Lấy thông tin người dùng hiện tại
$userId = $_SESSION['code'] ?? '';
$userRole = $_SESSION['NND_MA'] ?? 0;
$userDept = $_SESSION['PB_MA'] ?? 0;

// Kiểm tra và cập nhật thông tin người dùng từ database nếu cần
if (!empty($userId) && ($userRole == 0 || $userDept == 0)) {
    $userQuery = "SELECT * FROM thanhvien WHERE TV_MA = '$userId'";
    $result = mysqli_query($conn, $userQuery);
    if ($userInfo = mysqli_fetch_assoc($result)) {
        // Cập nhật session nếu thông tin chưa đầy đủ
        if (empty($_SESSION['NND_MA']) && !empty($userInfo['NND_MA'])) {
            $_SESSION['NND_MA'] = $userInfo['NND_MA'];
            $userRole = $userInfo['NND_MA'];
        }
        if ((empty($_SESSION['PB_MA']) || $_SESSION['PB_MA'] == 0) && !empty($userInfo['PB_MA'])) {
            $_SESSION['PB_MA'] = $userInfo['PB_MA'];
            $userDept = $userInfo['PB_MA'];
        }
    }
}

// Chạy kiểm tra nếu là quản lý
$dbCheck = [];
if ($userRole == 2) {
    $dbCheck = checkDatabaseData($conn, $userId, $userDept);
    error_log("Database check - User: $userId, Role: $userRole, Dept: $userDept");
    error_log("Check results: " . print_r($dbCheck, true));
}

// Debug thông tin session
error_log("Session Info - User ID: " . ($_SESSION['code'] ?? '') . ", Role: " . ($_SESSION['NND_MA'] ?? '') . ", Department: " . ($_SESSION['PB_MA'] ?? ''));

// Debug thông tin
$debug_info = [
    'user_id' => $_SESSION['code'] ?? '',
    'user_role' => $_SESSION['NND_MA'] ?? '',
    'user_dept' => $_SESSION['PB_MA'] ?? '',
    'is_admin' => ($_SESSION['active'] ?? 0) == 1 ? 'Yes' : 'No'
];

// Lấy danh sách dự án theo phân quyền
if ($_SESSION['active'] == 1 || $userRole == 4) {
    // Admin: lấy toàn bộ dự án có trạng thái khác 0
    $sql = "SELECT * FROM duan WHERE DA_TRANGTHAI != 0 ORDER BY DA_MA DESC";
    $result = mysqli_query($conn, $sql);
    $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
} elseif ($userRole == 2) {
    // Quản lý: lấy tất cả dự án trong phòng ban của họ
    $userRoom = $_SESSION['PB_MA'] ?? 0;
    
    // Lấy tất cả dự án trong phòng ban của quản lý
    // Bao gồm cả dự án do quản lý phụ trách và dự án có thành viên trong phòng ban
    $sql = "SELECT DISTINCT d.* 
            FROM duan d
            LEFT JOIN thanhvien tv ON d.DA_NGUOIPHUTRACH = tv.TV_MA
            LEFT JOIN duan_thanhvien dtv ON d.DA_MA = dtv.DA_MA
            LEFT JOIN thanhvien tvm ON dtv.TV_MA = tvm.TV_MA
            WHERE d.DA_TRANGTHAI != 0 
            AND (tv.PB_MA = '$userRoom' OR tvm.PB_MA = '$userRoom' OR d.DA_NGUOIPHUTRACH = '$userId')
            ORDER BY d.DA_MA DESC";
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("SQL Error: " . mysqli_error($conn));
        $projects = [];
    } else {
        $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Log câu truy vấn và kết quả
        error_log("SQL Query: " . $sql);
        error_log("Found " . count($projects) . " projects");
        if (count($projects) > 0) {
            error_log("First project: " . print_r($projects[0], true));
        }
    }
} else {
    // Thành viên thông thường: chỉ xem dự án mình phụ trách hoặc tham gia
    $sql = "SELECT DISTINCT d.* 
            FROM duan d
            LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA
            WHERE (d.DA_NGUOIPHUTRACH = '$userId' OR dt.TV_MA = '$userId') 
            AND d.DA_TRANGTHAI != 0 
            ORDER BY d.DA_MA DESC";
    $result = mysqli_query($conn, $sql);
    $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

//Danh sách room
$sql = "SELECT * FROM phongban ";
$result = mysqli_query($conn, $sql);
$rooms = mysqli_fetch_all($result, MYSQLI_ASSOC);

//Data trả về theo thứ tự ở view
$projectsStart = querySql($conn, 5, $keywords, $from_date, $to_date, $project_id);
$projectsInProgress = querySql($conn, 1, $keywords, $from_date, $to_date, $project_id);
$projectsMove = querySql($conn, 3, $keywords, $from_date, $to_date, $project_id);
$projectsFinish = querySql($conn, 2, $keywords, $from_date, $to_date, $project_id);
$projectsCancel = querySql($conn, 4, $keywords, $from_date, $to_date, $project_id);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Danh Sách Công Việc</title>
    <!-- Bootstrap CSS CDN -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
          integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/style_DAL.css">
    <link rel="stylesheet" href="./css/style.css">
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"
            integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"
            integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
    </script>
    <script>
        // Tự động tải lại trang sau 30 phút (30 * 60 * 1000 ms)
        setTimeout(function() {
            window.location.href = window.location.pathname + '?t=' + new Date().getTime();
        }, 30 * 60 * 1000);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css"/>
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet"
          type="text/css"/>
    <style>
        #myRange {
            pointer-events: none;
        }
    </style>
</head>

<body>
<?php include('ajax_work/loading.php'); ?>
<div class="wrapper">
    <!-- Sidebar  -->
    <?php include("../menu.php"); ?>

    <!-- Page Content  -->
    <div id="content">
        <div class="top-bar-block">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center ml-4 mb-2">
                    <a href="#" class="btn btn-success mr-2" id="btnAddPrivateTask">
                        <i class="fas fa-plus-circle mt-1 mr-1"></i> Thêm công việc riêng
                    </a>
                    <button onclick="window.location.href = window.location.pathname + '?t=' + new Date().getTime()" class="btn btn-primary" title="Tải lại trang (không cache)">
                         <a><i class="fas fa-sync-alt"></i> Tải lại trang</a>
                    </button>
                    <!-- reload trang không xoá cache 
                    <button onclick="window.location.reload()" class="btn btn-primary" title="Tải lại trang">
                        <i class="fas fa-sync-alt"></i>
                    </button> -->
                </div>
                <!-- Phần tìm kiếm và nút thêm công việc - đặt sát lề phải -->
                <div class="d-flex align-items-center ml-auto flex-column">
                    <!-- Nút lọc chỉ hiện ở mobile/tablet -->
                    <button id="toggleFilterBtn" class="btn btn-primary d-md-none mb-2" type="button" style="width: 100%"><i class="fas fa-filter"></i> Lọc</button>
                    <div id="filterOverlay" style="display:none;"></div>
                    <div id="filterPanel" style="position:relative;">
                        <button id="closeFilterPanel" class="btn btn-link" type="button" style="position:absolute;top:8px;right:8px;font-size:22px;display:none;">&times;</button>
                        
                        <!-- Dòng lọc dự án nằm trên -->
                        <div class="mb-3">
                            <label class="mb-1 text-secondary small" for="project_id">Dự án</label>
                            <select id="project_id" name="project_id" class="form-control form-control-sm rounded-pill shadow-sm select2" style="width: 100%;">
                                <option value="">-- Tất cả dự án --</option>
                                <option value="private" <?php if(isset($_GET['project_id']) && $_GET['project_id'] === 'private') echo 'selected'; ?>>Công việc riêng</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['DA_MA']; ?>" <?php if(isset($_GET['project_id']) && $_GET['project_id'] == $project['DA_MA']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($project['DA_TEN']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Form tìm kiếm còn lại -->
                        <form id="searchForm" class="d-flex align-items-end flex-nowrap gap-2" method="get">
                            <input type="text" name="keywords" class="form-control form-control-sm rounded-pill shadow-sm" placeholder="Tìm kiếm công việc..." value="<?php echo htmlspecialchars($keywords); ?>" style="width: 180px; min-width: 120px;">
                            <div class="d-flex flex-column align-items-start">
                                <label class="mb-1 text-secondary small" for="from_date">Từ ngày</label>
                                <input type="date" id="from_date" name="from_date" class="form-control form-control-sm rounded-pill shadow-sm" value="<?php echo htmlspecialchars($from_date); ?>" style="width: 130px; min-width: 100px;">
                            </div>
                            <div class="d-flex flex-column align-items-start">
                                <label class="mb-1 text-secondary small" for="to_date">Đến ngày</label>
                                <input type="date" id="to_date" name="to_date" class="form-control form-control-sm rounded-pill shadow-sm" value="<?php echo htmlspecialchars($to_date); ?>" style="width: 130px; min-width: 100px;">
                            </div>
                            <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center" style="height: 34px;" type="submit">
                                <i class="fal fa-search me-2"></i> Tìm kiếm
                            </button>
                            <button type="button" id="resetSearch" class="btn btn-secondary btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center" style="height: 34px;">
                                <i class="fal fa-undo me-2"></i> Đặt lại
                            </button>
                        </form>
                        <script>
                            // Xử lý submit form với project_id
                            document.getElementById('searchForm').onsubmit = function(e) {
                                e.preventDefault();
                                var form = this;
                                var projectId = document.getElementById('project_id').value;
                                
                                // Tạo input hidden cho project_id nếu chưa có
                                var hiddenInput = form.querySelector('input[name="project_id"]');
                                if (!hiddenInput) {
                                    hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = 'project_id';
                                    form.appendChild(hiddenInput);
                                }
                                hiddenInput.value = projectId;
                                
                                form.submit();
                            };
                            
                            // Xử lý reset form
                            document.getElementById('resetSearch').onclick = function() {
                                var form = document.getElementById('searchForm');
                                var projectSelect = document.getElementById('project_id');
                                
                                form.keywords.value = '';
                                form.from_date.value = '';
                                form.to_date.value = '';
                                projectSelect.value = ''; // Reset project_id
                                
                                // Trigger change event cho select2
                                if (typeof $.fn.select2 !== 'undefined') {
                                    $(projectSelect).trigger('change');
                                }
                                
                                form.submit();
                            };
                        </script>
                    </div>
                </div>
            </div>
        </div>
        <div class="body-section" id="result">
            <?php include('ajax_work/jobs.php'); ?>
        </div>
        
    </div>
</div>
<!-- Modal -->
<?php include('ajax_work/modal_insert.php'); ?>
<?php include('ajax_work/modal_edit.php'); ?>
<?php include('ajax_work/modal_member.php'); ?>
<?php include('ajax_work/modal_date.php'); ?>

<!-- Modal Thêm công việc riêng -->
<div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addTaskModalLabel">Thêm công việc riêng</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addPrivateTaskForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="taskTitle">Tên công việc <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="taskTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="startDate" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="duration">Số ngày hoàn thành <span class="text-danger">*</span></label>
                        <input type="number" min="1" value="1" class="form-control" id="duration" name="duration" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="./css/select2/select2.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="./css/select2/select2.min.js"></script>
<script src="css/app2.js"></script>
<script src="./css/ckeditor/ckeditor.js"></script>

<script type="text/javascript">
    // Xử lý sự kiện click nút Thêm công việc riêng
    $(document).on('click', '#btnAddPrivateTask', function(e) {
        e.preventDefault();
        // Mở modal thêm công việc
        $('#addTaskModal').modal('show');
    });



    // Xử lý sự kiện submit form thêm công việc riêng
    $('#addPrivateTaskForm').on('submit', function(e) {
        e.preventDefault();
        // Lấy đúng giá trị trong form đang submit
        const formData = {
            title: $(this).find('#taskTitle').val(),
            start_date: $(this).find('#startDate').val(),
            duration: $(this).find('#duration').val()
        };
        console.log('duration gửi đi:', formData.duration);
        
        // Kiểm tra dữ liệu
        if (!formData.title || !formData.start_date || !formData.duration) {
            toastr.error('Vui lòng điền đầy đủ thông tin');
            return;
        }
        
        // Hiển thị loading
        $('.loading').addClass('loader');
        
        // Gửi dữ liệu qua AJAX
        $.ajax({
            method: 'POST',
            url: 'ajax_work/add_private_task.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // Đóng modal, reset form và làm mới danh sách công việc
                    $('#addTaskModal').modal('hide');
                    $('#addPrivateTaskForm')[0].reset();
                    
                    // Tải lại trang thay vì gọi loadViewJobs
                    window.location.reload();
                    
                    toastr.success('Thêm công việc thành công!');
                } else {
                    toastr.error(response.message || 'Có lỗi xảy ra khi thêm công việc');
                }
                $('.loading').removeClass('loader');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Không thể kết nối đến máy chủ');
                }
                $('.loading').removeClass('loader');
            }
        });
    });

    $(document).ready(function () {
        // Load dữ liệu công việc khi trang load xong
        setTimeout(function() {
            loadViewJobs();
        }, 100);
        
        // Khởi tạo select2 cho dropdown dự án
        $('#project_id').select2({
            placeholder: 'Chọn dự án',
            allowClear: true,
            width: 'resolve'
        });
        // Hàm cập nhật danh sách tháng dựa trên quý được chọn
        function updateMonths() {
            const quarter = $('#filterQuarter').val();
            const $monthSelect = $('#filterMonth');
            
            if (quarter === 'all') {
                // Hiển thị tất cả các tháng nếu chọn 'Tất cả' quý
                $monthSelect.find('option').show();
            } else {
                // Ẩn tất cả các tháng
                $monthSelect.find('option').hide();
                // Hiển thị option 'Tất cả'
                $monthSelect.find('option[value="all"]').show();
                // Hiển thị các tháng thuộc quý đã chọn
                $monthSelect.find('option[data-quarter="' + quarter + '"]').show();
                
                // Nếu tháng hiện tại không thuộc quý đã chọn, chuyển về 'Tất cả'
                const currentMonth = $monthSelect.val();
                if (currentMonth !== 'all' && $monthSelect.find('option[value="' + currentMonth + '"]').is(':hidden')) {
                    $monthSelect.val('all');
                }
            }
        }
        
        // Khởi tạo lần đầu
        updateMonths();
        
        // Cập nhật khi thay đổi quý
        $('#filterQuarter').change(function() {
            updateMonths();
            $('#filterForm').submit();
        });
        
        // Tự động submit khi chọn tháng
        $('#filterMonth').change(function() {
            $('#filterForm').submit();
        });
        
        // Xử lý sự kiện submit form lọc
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const currentUrl = window.location.pathname + '?' + formData;
            window.location.href = currentUrl;
        });
        
        // Tự động submit form khi thay đổi lựa chọn năm/quý
        $('#filterYear, #filterQuarter').on('change', function() {
            $('#filterForm').submit();
        });
        
        // Xử lý khi nhấn nút xóa lọc
        $('a[href="?"]').on('click', function(e) {
            e.preventDefault();
            window.location.href = window.location.pathname;
        });
        
        // Khởi tạo tooltip cho nút xóa lọc
        $('[title]').tooltip();
        
        let url_string = document.URL;
        let url = new URL(url_string);
        let code = url.searchParams.get("id");
        if (code){
            loadViewEdit(code);
            $('#modalEdit').modal('show');
        }
        $('.select2').select2();
        $('body').on('click', '.menuJob', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            if ($('.menuDiv_' + id).is(':hidden'))
                $('.menuDiv_' + id).show();
            else
                $('.menuDiv_' + id).hide();
        });

        $('body').on('change', '.selectRoom', function () {
            let id = $(this).val();
            $.ajax({
                method: "GET",
                url: "ajax_work/get_members_by_room.php",
                data: {
                    'id': id,
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        $('.selectMember').html('<option>--Chọn thành vien--</option>');
                        $.each(res.data, function (key, value) {
                            $('.selectMember').append('<option value="' + value.TV_MA + '">' + value.TV_TEN + '</option>');
                        });
                    }
                }
            });
        });

        $('body').on('click', '#menuMember', function (e) {
            let code = $('#inputCode').val();
            $('#inputCodeMember').val(code);
            fetch('ajax_work/get_project.php?code=' + code, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bodyModalMember').innerHTML = html;
                    $('.select2').select2();
                    loadJsDropDrag();
                    $('#modalMember').modal('show');
                })
                .catch(error => console.error('Error:', error));
        });


        $('body').on('click', '#menuDate', function (e) {
            let code = $('#inputCode').val();
            $('#inputCodeDate').val(code);
            fetch('ajax_work/get_project_date.php?code=' + code, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bodyModalDate').innerHTML = html;
                    loadJsDropDrag();
                    $('#modalDate').modal('show');
                })
                .catch(error => console.error('Error:', error));
        });

        $('#memberFormInsert').submit(function (e) {
            e.preventDefault();
            let form = $(this).serialize();
            $('.loading').addClass('loader');
            $.ajax({
                method: "POST",
                url: "ajax_work/update_member.php",
                data: form,
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        $('#modalMember').modal('hide');
                        loadViewEdit(res.data);
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('body').on('click', '.btnInsert', function (e) {
            e.preventDefault();
            let status = $(this).data('status');
            $('#inputStatus').val(status);
            $('#modalInsert').modal('show');
        });

        $('body').on('click', '#btnComment', function (e) {
            e.preventDefault();
            
            let editorData = '';
            // Kiểm tra CKEDITOR có tồn tại không
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.comment) {
                editorData = CKEDITOR.instances.comment.getData();
            } else {
                // Fallback: lấy dữ liệu từ textarea thông thường
                editorData = $('#comment').val();
            }
            let text = removeScriptTags(editorData);
            let code = $(this).data('id');
            if (!text || text.trim() === '' || text.trim() === '<p>&nbsp;</p>' || text.trim() === '<p></p>') {
                toastr.error('Vui lòng nhập comment');
                return;
            }
            $('.loading').addClass('loader');
            $.ajax({
                method: "POST",
                url: "ajax_work/insert_comment.php",
                data: {
                    'code': code,
                    'text': text
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances['comment']) {
                            CKEDITOR.instances['comment'].setData('');
                        }
                        loadViewEdit(res.data);
                        loadViewJobs();
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('body').on('click', '.btnInsertWork', function (e) {
            e.preventDefault();
            let code = $('#inputCode').val();
            let id = $(this).data('id');
            $('#inputCodeWork').val(code);
            $('#inputCodeParent').val(id);
            $('#modalWork').modal('show');
        });


        $('body').on('click', '.viewDetail', function (e) {
            e.preventDefault();
            $('.loading').addClass('loader');
            let code = $(this).data('id');
            $('.list-group-edit').hide();
            loadViewEdit(code);
            $('#modalEdit').modal('show');
            $('.loading').removeClass('loader');
        });

        $('body').on('click', '.viewDetail2', function (e) {
            e.preventDefault();
            $('.loading').addClass('loader');
            let code = $(this).data('id');
            loadViewEdit(code);
            $('#modalEdit').modal('show');
            $('.loading').removeClass('loader');
        });

        $('body').on('click', '.activeDate', function (e) {
            e.preventDefault();
            let code = $(this).data('id');
            let date = $(this).data('date');
            $('.loading').addClass('loader');
            $('.list-group-item').hide();
            $.ajax({
                method: "GET",
                url: "ajax_work/update_active_date.php",
                data: {
                    'code': code,
                    'date': date
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewJobs();
                        loadViewEdit(code);
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });


        $('body').on('click', '.cancelDate', function (e) {
            e.preventDefault();
            let code = $(this).data('id');
            $('.loading').addClass('loader');
            $('.list-group-item').hide();
            $.ajax({
                method: "GET",
                url: "ajax_work/update_cancel_date.php",
                data: {
                    'code': code,
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewJobs();
                        loadViewEdit(code);
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });


        $('body').on('click', '.removeProject', function (e) {
            e.preventDefault();
            let code = $(this).data('id');
            $('.loading').addClass('loader');
            $('.list-group-item').hide();
            $.ajax({
                method: "GET",
                url: "ajax_work/remove_project.php",
                data: {
                    'code': code
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewJobs();
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('body').on('click', '.removeLabel', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            let code = $(this).data('code');
            $('.loading').addClass('loader');
            $.ajax({
                method: "GET",
                url: "ajax_work/remove_label.php",
                data: {
                    'id': id,
                    'code': code
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewEdit(res.data);
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('body').on('click', '.removeComment', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            let code = $(this).data('code');
            $('.loading').addClass('loader');
            $.ajax({
                method: "GET",
                url: "ajax_work/remove_comment.php",
                data: {
                    'id': id,
                    'code': code
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewEdit(res.data);
                        loadViewJobs();
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('body').on('click', '.removeWork', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            let code = $(this).data('code');
            $('.loading').addClass('loader');
            $.ajax({
                method: "GET",
                url: "ajax_work/remove_work.php",
                data: {
                    'id': id,
                    'code': code
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewEdit(res.data);
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('#dateFormInsert').submit(function (e) {
            e.preventDefault();
            let form = $(this).serialize();
            $('.loading').addClass('loader');
            $.ajax({
                method: "POST",
                url: "ajax_work/update_date.php",
                data: form,
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        $('#modalDate').modal('hide');
                        loadViewEdit(res.data);
                        loadViewJobs();
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });


        $('#labelFormInsert').submit(function (e) {
            e.preventDefault();
            let form = $(this).serialize();
            $('.loading').addClass('loader');
            $.ajax({
                method: "POST",
                url: "ajax_work/update_label.php",
                data: form,
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        $('#labelFormInsert')[0].reset();
                        $('#modalLabel').modal('hide');
                        loadViewEdit(res.data);
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });


        /* $('#projectFormInsert').submit(function (e) {
            e.preventDefault();
            var form = document.getElementById('projectFormInsert');
            // Create a FormData object from the form element
            var formData = new FormData(form);

            // Get the CKEditor content and append it to the FormData object
            var editorData = '';
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.editor) {
                editorData = CKEDITOR.instances.editor.getData();
            }
            formData.append('editor', editorData);
            $('.loading').addClass('loader');
            $.ajax({
                method: "POST",
                url: "ajax_work/insert_project.php",
                data: formData,
                dataType: 'json',
                processData: false, // Important!
                contentType: false, // Important!
                success: function (res) {
                    if (res.status) {
                        loadViewJobs();
                        $('#projectFormInsert')[0].reset();
                            // reset select2 fields
                            if($.fn.select2){
                                $('#projectFormInsert .select2').val(null).trigger('change.select2');
                            }
                            // clear CKEditor if present
                            if(window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor){
                                CKEDITOR.instances.editor.setData('');
                            }
                        $('#modalInsert').modal('hide');
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        }); */

        $('#projectFormUpdate').submit(function (e) {
            e.preventDefault();
            // let form = $(this).serialize();
            $('.loading').addClass('loader');
            // Get the form element
            var form = document.getElementById('projectFormUpdate');
            // Create a FormData object from the form element
            var formData = new FormData(form);

            // Get the CKEditor content and append it to the FormData object
            var editorData = '';
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.editor) {
                editorData = CKEDITOR.instances.editor.getData();
            }
            formData.append('editor', editorData);
            $.ajax({
                method: "POST",
                url: "ajax_work/update_project.php",
                data: formData,
                processData: false, // Important!
                contentType: false, // Important!
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        loadViewJobs();
                        $('#modalEdit').modal('hide');
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        function loadViewJobs() {
            // Lấy các tham số lọc hiện tại
            const keywords = $('input[name="keywords"]').val() || '';
            const from_date = $('#from_date').val() || '';
            const to_date = $('#to_date').val() || '';
            const project_id = $('#project_id').val() || '';
            
            // Tạo URL với tham số
            const params = new URLSearchParams();
            if (keywords) params.append('keywords', keywords);
            if (from_date) params.append('from_date', from_date);
            if (to_date) params.append('to_date', to_date);
            if (project_id) params.append('project_id', project_id);
            
            const url = 'ajax_work/get_view.php' + (params.toString() ? '?' + params.toString() : '');
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('result').innerHTML = html;
                    loadJsDropDrag();
                    $('body').on('click', '.menuJob', function (e) {
                        e.preventDefault();
                        let id = $(this).data('id');
                        if ($('.menuDiv_' + id).is(':visible'))
                            $('.menuDiv_' + id).show();
                        else
                            $('.menuDiv_' + id).hide();
                    });

                })
                .catch(error => console.error('Error:', error));
        }

        function loadViewEdit(code) {
            fetch('ajax_work/get_view_edit.php?code=' + code, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bodyModalEdit').innerHTML = html;
                    function waitForCKEDITOR(callback) {
                        if (typeof CKEDITOR !== 'undefined') {
                            callback();
                        } else {
                            setTimeout(function() { waitForCKEDITOR(callback); }, 100);
                        }
                    }
                    waitForCKEDITOR(function() {
                        CKEDITOR.replace('editor', {
                            height: 200
                        });
                        CKEDITOR.replace('comment', {
                            height: 50
                        });
                    });
                    const slider = document.getElementById('myRange');
                    const output = document.getElementById('sliderValue');

                    slider.oninput = function() {
                        output.textContent = this.value;
                    }
                    loadJsDropDrag();
                })
                .catch(error => console.error('Error:', error));
        }


        $("#sidebar").mCustomScrollbar({
            theme: "minimal"
        });

        $('#sidebarCollapse').on('click', function () {
            $('#sidebar, #content').toggleClass('active');
            $('.collapse.in').toggleClass('in');
            $('a[ariaexpanded=true]').attr('aria-expanded', 'false');
        });

        // Hàm remove script tags - định nghĩa ở scope global
        window.removeScriptTags = function(html) {
            var div = document.createElement('div');
            div.innerHTML = html;
            var scripts = div.getElementsByTagName('script');
            var i = scripts.length;
            while (i--) {
                scripts[i].parentNode.removeChild(scripts[i]);
            }
            return div.innerHTML;
        }
    });
    // Tự động submit form khi chọn dự án
    $('#project_id').on('change', function() {
        $('input[name="project_id"]').val($(this).val());
        $('#searchForm').submit();
    });
// ===== Prerequisite task filtering (global) =====
window.loadPrerequisiteTasksInsert = function(pid){
    if(!pid) return;
    $.get('ajax/get_prerequisite_tasks.php',{project_code:pid},function(r){
        if(r && r.status && r.data){
            const $sel = $('#prerequisite_task');
            if(!$sel.length) return;
            $sel.empty().append('<option value="">--Không có--</option>');
            r.data.forEach(t=> $sel.append(`<option value="${t.id}">${t.text}</option>`));
            $sel.val('').trigger('change.select2');
        }
    },'json');
}
$(document).on('change select2:select', '#modalInsert select[name="project_id"]', function(){
    loadPrerequisiteTasksInsert(this.value);
});
$(document).on('shown.bs.modal', '#modalInsert', function(){
    const pid = $('#modalInsert select[name="project_id"]').val();
    loadPrerequisiteTasksInsert(pid);
});
</script>
<script>
$(document).ready(function() {
    function initSelect2ForProject() {
        if ($('#project_id').length) {
            if ($('#project_id').hasClass('select2-hidden-accessible')) {
                $('#project_id').select2('destroy');
            }
            $('#project_id').select2({
                dropdownParent: $('#filterPanel')
            });
        }
    }
    // Khởi tạo lần đầu cho desktop
    if (window.innerWidth > 768) {
        initSelect2ForProject();
    }
    $('#toggleFilterBtn').on('click', function() {
        if (window.innerWidth <= 768) {
            $('#filterPanel').addClass('show');
            $('#filterOverlay').addClass('show');
            $('#closeFilterPanel').show();
            setTimeout(initSelect2ForProject, 200);
        }
    });
    $('#closeFilterPanel, #filterOverlay').on('click', function() {
        if (window.innerWidth <= 768) {
            $('#filterPanel').removeClass('show');
            $('#filterOverlay').removeClass('show');
            $('#closeFilterPanel').hide();
        }
    });
    // Chỉ tự động submit khi chọn dự án ở desktop (>768px)
    if (window.innerWidth > 768) {
        $('#project_id').on('change', function() {
            $('#searchForm').submit();
        });
    } else {
        $('#project_id').off('change');
    }
});
</script>
</body>
</html>
