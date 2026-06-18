<?php
include('../config.php');
// Bắt đầu session
session_start();
// Kiểm tra xem session đã được tạo hay chưa
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

// Include query helper dùng chung (thay thế querySql inline)
include_once(__DIR__ . '/ajax_work/query_helper.php');

$keywords = $_GET['keywords'] ?? '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');
$project_id = $_GET['project_id'] ?? '';

// Tham số phân trang
$page = max(1, (int)($_GET['page'] ?? 1));
$perPageInput = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$perPage = in_array($perPageInput, [20, 50, 100]) ? $perPageInput : 20;

// Số card Kanban load lần đầu (lazy load)
$kanbanInitLimit = 10;

// querySql() đã được chuyển sang ajax_work/query_helper.php
// Sử dụng getTasksByStatus(), getTasksForTable(), getTasksForKanban()

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

// === KANBAN DATA (lazy load: chỉ load $kanbanInitLimit card đầu tiên mỗi cột) ===
$projectsStart = getTasksForKanban($conn, 5, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
$projectsInProgress = getTasksForKanban($conn, 1, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
$projectsMove = getTasksForKanban($conn, 3, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
$projectsFinish = getTasksForKanban($conn, 2, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
$projectsCancel = getTasksForKanban($conn, 4, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);

// Đếm tổng để biết còn bao nhiêu card chưa load
$countStart = countTasksForKanban($conn, 5, $keywords, $from_date, $to_date, $project_id);
$countInProgress = countTasksForKanban($conn, 1, $keywords, $from_date, $to_date, $project_id);
$countMove = countTasksForKanban($conn, 3, $keywords, $from_date, $to_date, $project_id);
$countFinish = countTasksForKanban($conn, 2, $keywords, $from_date, $to_date, $project_id);
$countCancel = countTasksForKanban($conn, 4, $keywords, $from_date, $to_date, $project_id);

// === TABLE DATA (phân trang) ===
$tableData = getTasksForTable($conn, $keywords, $from_date, $to_date, $project_id, $page, $perPage);

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
    <link rel="stylesheet" href="./css/danhsachcv_new.css">
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
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
        <!-- ========== NEW FILTER & HEADER BAR ========== -->
        <div class="cv-header-bar">
            <div class="cv-header-row">
                <!-- Filter Group -->
                <div class="cv-filter-group">
                    <select id="project_id" name="project_id" class="cv-filter-select select2">
                        <option value="">📁 Tất cả dự án</option>
                        <option value="private" <?php if(isset($_GET['project_id']) && $_GET['project_id'] === 'private') echo 'selected'; ?>>Công việc riêng</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['DA_MA']; ?>" <?php if(isset($_GET['project_id']) && $_GET['project_id'] == $project['DA_MA']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($project['DA_TEN']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <form id="searchForm" class="cv-filter-group" method="get" style="gap:8px;flex:unset;">
                        <input type="text" name="keywords" class="cv-filter-input" placeholder="🔍 Tìm kiếm..." value="<?php echo htmlspecialchars($keywords); ?>">
                        <div class="cv-filter-date-group">
                            <span class="cv-filter-label">Từ ngày</span>
                            <input type="text" id="from_date" name="from_date" class="cv-filter-input" placeholder="Từ ngày..." value="<?php echo htmlspecialchars($from_date); ?>">
                        </div>
                        <div class="cv-filter-date-group">
                            <span class="cv-filter-label">Đến ngày</span>
                            <input type="text" id="to_date" name="to_date" class="cv-filter-input" placeholder="Đến ngày..." value="<?php echo htmlspecialchars($to_date); ?>">
                        </div>
                        <button class="cv-btn cv-btn-danger" type="submit"><i class="fal fa-search"></i> Tìm</button>
                        <button type="button" id="resetSearch" class="cv-btn cv-btn-outline"><i class="fal fa-undo"></i> Đặt lại</button>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="cv-header-actions" style="display:flex;gap:8px;align-items:center;">
                    <a href="#" class="cv-btn cv-btn-success" id="btnAddPrivateTask"><i class="fas fa-plus-circle"></i> Thêm CV riêng</a>
                </div>

                <!-- View Toggle -->
                <div class="cv-view-toggle">
                    <button class="cv-view-toggle-btn active" id="btnKanbanView" title="Chế độ Kanban"><i class="fas fa-columns"></i> Kanban</button>
                    <button class="cv-view-toggle-btn" id="btnTableView" title="Chế độ Bảng"><i class="fas fa-list"></i> Bảng</button>
                </div>
            </div>
        </div>

        <!-- ========== BODY VIEWS ========== -->
        <div class="body-section" id="result">
            <?php include('ajax_work/jobs.php'); ?>
        </div>
        
        <!-- Filter form JS -->
        <script>
            // Submit form with project_id
            document.getElementById('searchForm').onsubmit = function(e) {
                e.preventDefault();
                var form = this;
                var projectId = document.getElementById('project_id').value;
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
            // Reset form
            document.getElementById('resetSearch').onclick = function() {
                window.location.href = window.location.pathname;
            };
        </script>
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
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="./css/select2/select2.min.js"></script>
<script src="css/app2.js"></script>
<script src="./css/ckeditor/ckeditor.js"></script>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
<script>
$(document).ready(function() {
    flatpickr("#from_date, #to_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        locale: "vn"
    });
});
</script>

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
            loadViewJobs(currentPage, currentPerPage);
            initKanbanLazyLoad();
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

        // === Biến phân trang toàn cục ===
        var currentPage = <?php echo $page; ?>;
        var currentPerPage = <?php echo $perPage; ?>;

        function loadViewJobs(page, perPage) {
            page = page || currentPage || 1;
            perPage = perPage || currentPerPage || 20;

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
            params.append('page', page);
            params.append('per_page', perPage);
            
            const url = 'ajax_work/get_view.php?' + params.toString();
            
            // Hiển thị loading spinner
            var $result = $('#result');
            $result.addClass('cv-loading');
            if (!$result.find('.cv-loading-overlay').length) {
                $result.append('<div class="cv-loading-overlay"><div class="cv-spinner"></div></div>');
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    $result.removeClass('cv-loading');
                    document.getElementById('result').innerHTML = html;
                    loadJsDropDrag();
                    
                    // Cập nhật biến toàn cục
                    currentPage = page;
                    currentPerPage = perPage;
                    
                    // Cập nhật URL trên thanh địa chỉ (không reload trang)
                    var stateParams = new URLSearchParams(window.location.search);
                    stateParams.set('page', page);
                    stateParams.set('per_page', perPage);
                    var newUrl = window.location.pathname + '?' + stateParams.toString();
                    history.pushState({page: page, perPage: perPage}, '', newUrl);

                    // Restore view toggle state after AJAX reload
                    var savedView = localStorage.getItem('cv_view_mode') || 'kanban';
                    if (savedView === 'table' && typeof window.switchToTable === 'function') {
                        window.switchToTable(false);
                    } else if (typeof window.switchToKanban === 'function') {
                        window.switchToKanban(false);
                    }

                    // Khởi tạo lại kanban lazy load
                    initKanbanLazyLoad();
                })
                .catch(error => {
                    $result.removeClass('cv-loading');
                    console.error('Error:', error);
                });
        }

        // === Event delegation cho phân trang ===
        $('body').on('click', '.cv-page-btn[data-page], .cv-page-num[data-page]', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page && !$(this).hasClass('disabled')) {
                loadViewJobs(page, currentPerPage);
                // Scroll lên đầu bảng
                $('html, body').animate({ scrollTop: $('#result').offset().top - 80 }, 300);
            }
        });

        // === Thay đổi số bản ghi / trang ===
        $('body').on('change', '#perPageSelect', function() {
            currentPerPage = parseInt($(this).val()) || 20;
            loadViewJobs(1, currentPerPage); // Reset về trang 1
        });

        // === Xử lý nút Back/Forward của browser ===
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.page) {
                loadViewJobs(e.state.page, e.state.perPage || currentPerPage);
            }
        });

        // === Kanban Lazy Load ===
        function initKanbanLazyLoad() {
            $('.cv-kanban-col-body').each(function() {
                var $colBody = $(this);
                var $loadMoreBtn = $colBody.find('.cv-load-more-btn');
                if ($loadMoreBtn.length === 0) return;

                // Intersection Observer để tự động load khi cuộn tới nút
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            $loadMoreBtn.trigger('click');
                        }
                    });
                }, { root: $colBody[0], threshold: 0.1 });

                if ($loadMoreBtn[0]) {
                    observer.observe($loadMoreBtn[0]);
                }
            });
        }

        // === Load thêm cards cho Kanban ===
        $('body').on('click', '.cv-load-more-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.hasClass('cv-loading-more')) return;

            var status = $btn.data('status');
            var offset = $btn.data('offset');
            var total = $btn.data('total');
            var $list = $btn.closest('.cv-kanban-col-body').find('ul');

            $btn.addClass('cv-loading-more').html('<div class="cv-spinner-sm"></div> Đang tải...');

            var params = new URLSearchParams();
            params.append('status', status);
            params.append('offset', offset);
            params.append('limit', 30);
            var keywords = $('input[name="keywords"]').val() || '';
            var from_date = $('#from_date').val() || '';
            var to_date = $('#to_date').val() || '';
            var project_id = $('#project_id').val() || '';
            if (keywords) params.append('keywords', keywords);
            if (from_date) params.append('from_date', from_date);
            if (to_date) params.append('to_date', to_date);
            if (project_id) params.append('project_id', project_id);

            fetch('ajax_work/get_kanban_cards.php?' + params.toString(), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.html) {
                    $btn.closest('li').before(res.html);
                }
                var newOffset = offset + (res.loaded || 0);
                if (newOffset >= total || !res.html) {
                    $btn.closest('li').remove(); // Không còn card nào
                } else {
                    $btn.data('offset', newOffset)
                        .removeClass('cv-loading-more')
                        .html('<i class="fal fa-arrow-down"></i> Xem thêm (' + (total - newOffset) + ')');
                }
                loadJsDropDrag();
            })
            .catch(function() {
                $btn.removeClass('cv-loading-more').html('<i class="fal fa-arrow-down"></i> Thử lại');
            });
        });

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
            var select2Options = {
                placeholder: 'Chọn dự án',
                allowClear: true,
                width: 'resolve'
            };
            if ($('#filterPanel').length) {
                select2Options.dropdownParent = $('#filterPanel');
            }
            $('#project_id').select2(select2Options);
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

<!-- ========== VIEW TOGGLE SCRIPT ========== -->
<script>
$(document).ready(function() {
    var $btnKanban = $('#btnKanbanView');
    var $btnTable = $('#btnTableView');
    
    // Restore saved view preference
    var savedView = localStorage.getItem('cv_view_mode') || 'kanban';
    if (savedView === 'table') {
        switchToTable(false);
    }
    
    // Kanban button click
    $btnKanban.on('click', function() {
        switchToKanban(true);
    });
    
    // Table button click
    $btnTable.on('click', function() {
        switchToTable(true);
    });
    
    // Hàm luôn query fresh DOM elements (vì #result bị thay innerHTML sau AJAX)
    function switchToKanban(animate) {
        $btnKanban.addClass('active');
        $btnTable.removeClass('active');
        // Query lại từ DOM mới mỗi lần
        $('#tableView').addClass('cv-hidden').removeClass('cv-fade-in');
        $('#kanbanView').removeClass('cv-hidden');
        if (animate) $('#kanbanView').addClass('cv-fade-in');
        localStorage.setItem('cv_view_mode', 'kanban');
    }
    
    function switchToTable(animate) {
        $btnTable.addClass('active');
        $btnKanban.removeClass('active');
        // Query lại từ DOM mới mỗi lần
        $('#kanbanView').addClass('cv-hidden').removeClass('cv-fade-in');
        $('#tableView').removeClass('cv-hidden');
        if (animate) $('#tableView').addClass('cv-fade-in');
        localStorage.setItem('cv_view_mode', 'table');
    }

    // Expose functions globally để loadViewJobs có thể gọi
    window.switchToKanban = switchToKanban;
    window.switchToTable = switchToTable;
});
</script>

</body>
</html>
