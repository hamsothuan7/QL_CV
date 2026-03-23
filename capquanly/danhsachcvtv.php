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
$project_id = $_GET['project_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';


//Get mã thành viên
$username = $_SESSION['code'];
$sql = "SELECT TV_MA FROM thanhvien WHERE TV_MA = '$username' ";
// Thực thi câu truy vấn và gán vào $result
$result = mysqli_query($conn, $sql);
$member = mysqli_fetch_assoc($result);
$memberCode = $member['TV_MA'] ?? "";

//1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
function querySql($conn, $status, $memberCode, $keywords = '', $project_id = '', $from_date = '', $to_date = '')
{
    // Lấy các dự án mình phụ trách
    $sqlProject = "SELECT DA_MA FROM duan WHERE DA_NGUOIPHUTRACH = '$memberCode'";
    $resultProject = mysqli_query($conn, $sqlProject);
    $projectIds = [];
    while ($row = mysqli_fetch_assoc($resultProject)) {
        $projectIds[] = $row['DA_MA'];
    }
    $where = "(TV_MA = '$memberCode'";
    if (!empty($projectIds)) {
        $projectIdsStr = "'" . implode("','", $projectIds) . "'";
        $where .= " OR DA_MA IN ($projectIdsStr)";
    }
    $where .= ")";

    $sql = "SELECT * FROM danhsachcongviec WHERE $where AND dscv_trangthaihd = 1";
    if ($status == 2) {
                    $sql .= " AND (DSCV_TRANGTHAI = $status OR DSCV_TRANGTHAI = 6)";
                } else {
                    $sql .= " AND DSCV_TRANGTHAI = $status";
                }
    if ($keywords != '') {
        $sql .= " AND DSCV_TEN LIKE '%$keywords%'";
    }
    if ($from_date != '') {
        $sql .= " AND DATE(DSCV_NGAYBATDAU) >= '$from_date'";
    }
    if ($to_date != '') {
        $sql .= " AND DATE(DSCV_NGAYKETTHUC) <= '$to_date'";
    }
    if ($project_id === 'private') {
        $sql .= " AND (DA_MA IS NULL OR DA_MA = '')";
    } else if (!empty($project_id)) {
        $sql .= " AND DA_MA = '$project_id'";
    }
    $sql .= " ORDER BY DSCV_MA DESC";
    $result = mysqli_query($conn, $sql);
    return $result;
}

//Get danh sách dự án
$sql = "SELECT DISTINCT du.* FROM duan du
        LEFT JOIN duan_thanhvien dt ON du.DA_MA = dt.DA_MA
        LEFT JOIN danhsachcongviec cv ON du.DA_MA = cv.DA_MA AND cv.TV_MA = '$memberCode'
        WHERE du.DA_TRANGTHAI != 0 
          AND (du.DA_NGUOIPHUTRACH = '$memberCode'
           OR dt.TV_MA = '$memberCode'
           OR cv.TV_MA = '$memberCode')
        ORDER BY du.DA_MA DESC";
$result = mysqli_query($conn, $sql);
$projects = mysqli_fetch_all($result, MYSQLI_ASSOC);

//Data trả về theo thứ tự ở view
$projectsStart = mysqli_fetch_all(querySql($conn, 5, $memberCode, $keywords, $project_id, $from_date, $to_date), MYSQLI_ASSOC);

$projectsInProgress = mysqli_fetch_all(querySql($conn, 1, $memberCode, $keywords, $project_id, $from_date, $to_date), MYSQLI_ASSOC);

$projectsMove = mysqli_fetch_all(querySql($conn, 3, $memberCode, $keywords, $project_id, $from_date, $to_date), MYSQLI_ASSOC);

$projectsFinish = mysqli_fetch_all(querySql($conn, 2, $memberCode, $keywords, $project_id, $from_date, $to_date), MYSQLI_ASSOC);

$projectsCancel = mysqli_fetch_all(querySql($conn, 4, $memberCode, $keywords, $project_id, $from_date, $to_date), MYSQLI_ASSOC);


?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Quản Lý Công Việc Cá Nhân</title>
    <!-- Bootstrap CSS CDN -->
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
        [type="file"] {
            /* Style the color of the message that says 'No file chosen' */
            color: #878787;
        }
        [type="file"]::-webkit-file-upload-button {
            background: #ED1C1B;
            border: 2px solid #ED1C1B;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            outline: none;
            padding: 10px 25px;
            text-transform: uppercase;
            transition: all 1s ease;
        }

        [type="file"]::-webkit-file-upload-button:hover {
            background: #fff;
            border: 2px solid #535353;
            color: #000;
        }

        /* GENERAL STYLING OF PAGE — NOT APPLICABLE TO EXAMPLE */
        body {
            padding: 20px;
        }
    </style>
</head>

<body>
<?php include('ajax_work_tv/loading.php'); ?>
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
                        <i class="fas fa-sync-alt"></i>
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
                            // Xử lý chọn project
                            document.getElementById('project_id').addEventListener('change', function() {
                                const projectId = this.value;
                                const searchForm = document.getElementById('searchForm');
                                const url = new URL(window.location.href);
                                
                                if (projectId) {
                                    url.searchParams.set('project_id', projectId);
                                } else {
                                    url.searchParams.delete('project_id');
                                }
                                
                                // Reset trang về 1 khi lọc
                                url.searchParams.set('page', '1');
                                
                                // Thêm project_id vào form nếu chưa có
                                let hiddenInput = searchForm.querySelector('input[name="project_id"]');
                                if (!hiddenInput) {
                                    hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = 'project_id';
                                    searchForm.appendChild(hiddenInput);
                                }
                                hiddenInput.value = projectId;
                                
                                // Submit form
                                searchForm.submit();
                            });
                            
                            // Xử lý submit form
                            document.getElementById('searchForm').onsubmit = function(e) {
                                e.preventDefault();
                                const searchForm = e.target;
                                const url = new URL(window.location.href);
                                
                                // Cập nhật các tham số tìm kiếm
                                const formData = new FormData(searchForm);
                                for (let [key, value] of formData.entries()) {
                                    if (value) {
                                        url.searchParams.set(key, value);
                                    } else {
                                        url.searchParams.delete(key);
                                    }
                                }
                                
                                // Reset trang về 1 khi tìm kiếm
                                url.searchParams.set('page', '1');
                                
                                window.location.href = url.toString();
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
                        <script>
                            // Toggle panel lọc cho mobile theo responsive.css (dùng class d-block)
                            (function() {
                                var toggleBtn = document.getElementById('toggleFilterBtn');
                                var overlay = document.getElementById('filterOverlay');
                                var panel = document.getElementById('filterPanel');
                                var closeBtn = document.getElementById('closeFilterPanel');

                                if (!toggleBtn || !overlay || !panel || !closeBtn) return;

                                function openFilterPanel() {
                                    panel.classList.add('d-block');
                                    overlay.classList.add('show');
                                }

                                function closeFilterPanel() {
                                    panel.classList.remove('d-block');
                                    overlay.classList.remove('show');
                                }

                                toggleBtn.addEventListener('click', function() {
                                    if (window.matchMedia('(max-width: 768px)').matches) {
                                        openFilterPanel();
                                    }
                                });
                                overlay.addEventListener('click', closeFilterPanel);
                                closeBtn.addEventListener('click', closeFilterPanel);
                                window.addEventListener('resize', function() {
                                    if (!window.matchMedia('(max-width: 768px)').matches) {
                                        closeFilterPanel();
                                    }
                                });
                            })();
                        </script>
                    </div>
                </div>
            </div>
        </div>
        <div class="body-section" id="result">
            <?php include('ajax_work_tv/jobs.php'); ?>
        </div>

    </div>
</div>
<!-- Modal -->
<?php include('ajax_work_tv/modal_insert.php'); ?>
<?php include('ajax_work_tv/modal_edit.php'); ?>
<?php include('ajax_work_tv/modal_member.php'); ?>
<?php include('ajax_work_tv/modal_date.php'); ?>

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
<script>
$(document).ready(function() {
    $('#project_id').on('change', function() {
        $('#searchForm').submit();
    });
});
</script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="./css/select2/select2.min.js"></script>
<script src="css/app2.js"></script>
    
<script src="./css/ckeditor/ckeditor.js"></script>
<!-- Responsive filter assets -->
<link rel="stylesheet" href="../style/responsive/responsive.css">
<script src="../style/responsive/responsive.js"></script>

<script type="text/javascript">
// Mở modal Thêm công việc riêng khi bấm nút
$(document).on('click', '#btnAddPrivateTask', function(e) {
    e.preventDefault();
    $('#addTaskModal').modal('show');
});

// Submit form thêm công việc riêng
$('#addPrivateTaskForm').on('submit', function(e) {
    e.preventDefault();
    const formData = {
        title: $(this).find('#taskTitle').val(),
        start_date: $(this).find('#startDate').val(),
        duration: $(this).find('#duration').val()
    };

    if (!formData.title || !formData.start_date || !formData.duration) {
        if (typeof toastr !== 'undefined') toastr.error('Vui lòng điền đầy đủ thông tin');
        return;
    }

    $('.loading').addClass('loader');
    $.ajax({
        method: 'POST',
        url: 'ajax_work/add_private_task.php',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                $('#addTaskModal').modal('hide');
                $('#addPrivateTaskForm')[0].reset();
                // Làm mới danh sách
                window.location.reload();
                if (typeof toastr !== 'undefined') toastr.success('Thêm công việc thành công!');
            } else {
                if (typeof toastr !== 'undefined') toastr.error(response.message || 'Có lỗi xảy ra khi thêm công việc');
            }
            $('.loading').removeClass('loader');
        },
        error: function(xhr, status, error) {
            if (typeof toastr !== 'undefined') {
                if (xhr.responseJSON && xhr.responseJSON.message) toastr.error(xhr.responseJSON.message);
                else toastr.error('Không thể kết nối đến máy chủ');
            }
            $('.loading').removeClass('loader');
        }
    });
});
</script>
<script type="text/javascript">
    $(document).ready(function () {
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

        $('body').on('change', '#selectRoom', function (){
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
                        $('#selectMember').html('<option>--Chọn thành vien--</option>');
                        $.each(res.data, function (key, value) {
                            $('#selectMember').append('<option value="' + value.TV_MA + '">' + value.TV_TEN + '</option>');
                        });
                    }
                }
            });
        });

        $('body').on('click', '#menuMember', function (e) {
            let code = $('#inputCode').val();
            $('#inputCodeMember').val(code);
            fetch('ajax_work_tv/get_project.php?code=' + code, {
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
            fetch('ajax_work_tv/get_project_date.php?code=' + code, {
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
                url: "ajax_work_tv/update_member.php",
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
            CKEDITOR.replace('editor2', {
                height: 200
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

        $('body').on('click', '.removeProject', function (e) {
            e.preventDefault();
            let code = $(this).data('id');
            $('.loading').addClass('loader');
            $('.list-group-item').hide();
            $.ajax({
                method: "GET",
                url: "ajax_work_tv/remove_project.php",
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
                url: "ajax_work_tv/remove_label.php",
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
                method: "POST",
                url: "ajax_work_tv/delete_comment.php",
                data: {
                    'id': id,
                    'DSCV_MA': code
                },
                dataType: 'json',
                success: function (response) {
                    $('.loading').removeClass('loader');
                    if (response.status === 'success') {
                        // Reload the comments section or update the UI as needed
                        loadViewEdit(code);
                        toastr.success('Xóa bình luận thành công');
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra khi xóa bình luận');
                    }
                },
                error: function(xhr, status, error) {
                    $('.loading').removeClass('loader');
                    console.error('Error:', error);
                    toastr.error('Có lỗi xảy ra khi kết nối đến máy chủ');
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
                url: "ajax_work_tv/remove_work.php",
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
                url: "ajax_work_tv/update_date.php",
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
                url: "ajax_work_tv/update_label.php",
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


        $('#projectFormInsert').submit(function (e) {
            e.preventDefault();
            $('.loading').addClass('loader');
            var form = document.getElementById('projectFormInsert');
            // Create a FormData object from the form element
            var formData = new FormData(form);

            // Get the CKEditor content and append it to the FormData object
            var editorData = CKEDITOR.instances.editor2.getData();
            formData.append('editor2', editorData);
            $.ajax({
                method: "POST",
                url: "ajax_work_tv/insert_project.php",
                data: formData,
                dataType: 'json',
                processData: false, // Important!
                contentType: false, // Important!
                success: function (res) {
                    if (res.status) {
                        loadViewJobs();
                        $('#projectFormInsert')[0].reset();
                        $('#modalInsert').modal('hide');
                        toastr.success(res.message);
                        $('.loading').removeClass('loader');
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        $('#projectFormUpdate').submit(function (e) {
            e.preventDefault();
            // let form = $(this).serialize();
            $('.loading').addClass('loader');
            // Get the form element
            var form = document.getElementById('projectFormUpdate');
            // Create a FormData object from the form element
            var formData = new FormData(form);

            // Get the CKEditor content and append it to the FormData object
            var editorData = CKEDITOR.instances.editor.getData();
            formData.append('editor', editorData);
            $.ajax({
                method: "POST",
                url: "ajax_work_tv/update_project.php",
                data: formData,
                processData: false, // Important!
                contentType: false, // Important!
                dataType: 'json',
                success: function (res) {
                    if (res.status) {
                        // Hiển thị thông báo rồi tải lại trang để đảm bảo dữ liệu và script đồng bộ
                        $('.loading').removeClass('loader');
                        toastr.success(res.message);
                        setTimeout(function(){
                            window.location.reload();
                        }, 400);
                    } else {
                        toastr.error(res.message);
                        $('.loading').removeClass('loader');
                    }
                }
            });
        });

        function loadViewJobs() {
            fetch('ajax_work_tv/get_view.php', {
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
            fetch('ajax_work_tv/get_view_edit.php?code=' + code, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bodyModalEdit').innerHTML = html;
                    CKEDITOR.replace('editor', {
                        height: 200
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

        function removeScriptTags(html) {
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
</script>
</body>
</html>
