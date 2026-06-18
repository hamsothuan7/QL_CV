<?php
// Bắt đầu session và báo cáo lỗi
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include file cấu hình
include('../config.php'); 

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

// Tham số phân trang và bộ lọc
$keywords = filter_input(INPUT_GET, 'keywords', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$keywords = trim($keywords);
$from_date = isset($_GET['from_date']) ? filter_input(INPUT_GET, 'from_date', FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? filter_input(INPUT_GET, 'to_date', FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m-t');

// Phân trang cho Table View
$page = max(1, (int)($_GET['page'] ?? 1));
$perPageInput = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$perPage = in_array($perPageInput, [20, 50, 100]) ? $perPageInput : 20;

// Limit cho Kanban lazy load
$kanbanInitLimit = 10;

include_once(__DIR__ . '/ajax/query_helper.php');

try {
    // Load data cho Kanban
    $projectsStart = getProjectsForKanban($conn, 5, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsInProgress = getProjectsForKanban($conn, 1, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsMove = getProjectsForKanban($conn, 3, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsFinish = getProjectsForKanban($conn, 2, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsCancel = getProjectsForKanban($conn, 4, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);

    // Đếm tổng để lazy load "Xem thêm"
    $countStart = countProjectsForKanban($conn, 5, $keywords, $from_date, $to_date);
    $countInProgress = countProjectsForKanban($conn, 1, $keywords, $from_date, $to_date);
    $countMove = countProjectsForKanban($conn, 3, $keywords, $from_date, $to_date);
    $countFinish = countProjectsForKanban($conn, 2, $keywords, $from_date, $to_date);
    $countCancel = countProjectsForKanban($conn, 4, $keywords, $from_date, $to_date);

    // Load data cho Table
    $tableData = getProjectsForTable($conn, $keywords, $from_date, $to_date, $page, $perPage);
} catch (Exception $e) {
    error_log('Lỗi khi lấy dữ liệu dự án: ' . $e->getMessage());
    $projectsStart = $projectsInProgress = $projectsMove = $projectsFinish = $projectsCancel = [];
    $tableData = ['data' => [], 'total' => 0, 'pages' => 1, 'page' => 1, 'perPage' => 20];
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Danh Sách Dự Án</title>
    <!-- Bootstrap CSS CDN -->
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet"
        type="text/css" />
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Đảm bảo chỉ load jQuery trước Bootstrap JS, không lặp lại -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
</head>

<body>
    <?php 
    // Kiểm tra file loading.php có tồn tại không
    $loadingFile = __DIR__ . '/ajax/loading.php';
    if (file_exists($loadingFile)) {
        include($loadingFile);
    } else {
        // Nếu không tìm thấy file, tạo một loading mặc định
        echo '<div id="loading" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; text-align:center; padding-top:20%;">';
        echo '  <div class="spinner-border text-light" role="status">';
        echo '    <span class="sr-only">Loading...</span>';
        echo '  </div>';
        echo '  <p class="text-light mt-2">Đang xử lý...</p>';
        echo '</div>';
    }
    ?>

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
                    <button type="button" class="cv-btn cv-btn-success" id="btnThemMauCV"><i class="fas fa-plus"></i> Thêm mẫu</button>
                    <button type="button" class="cv-btn cv-btn-outline" id="btnQuanLyMauCV"><i class="fas fa-layer-group"></i> Quản lý mẫu</button>
                </div>

                <!-- View Toggle -->
                <div class="cv-view-toggle">
                    <button class="cv-view-toggle-btn active" id="btnKanbanView" title="Chế độ Kanban"><i class="fas fa-columns"></i> Kanban</button>
                    <button class="cv-view-toggle-btn" id="btnTableView" title="Chế độ Bảng"><i class="fas fa-list"></i> Bảng</button>
                </div>
            </div>
        </div>
        
        <!-- Filter form JS -->
        <script>
            // Reset form
            document.getElementById('resetSearch').onclick = function() {
                window.location.href = window.location.pathname;
            };
        </script>
            <div class="body-section" id="result">
                <?php include('ajax/jobs.php'); ?>
            </div>
            
        </div>

        <!-- Modal -->
        <?php include('ajax/modal_insert.php'); ?>
        <?php include('ajax/modal_edit.php'); ?>
        <?php include('ajax/modal_member.php'); ?>
        <?php include('ajax/modal_label.php'); ?>
        <?php include('ajax/modal_work.php'); ?>
        <?php include('ajax/modal_work_mau.php'); ?>
        <?php include('ajax/modal_date.php'); ?>
        <?php include('ajax/modal_themmau.php'); ?>
        <?php include('ajax/modal_quanlymau.php'); ?>

    </div>


    <link rel="stylesheet" href="./css/select2/select2.min.css">
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    
    <!-- Other libraries -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="./css/select2/select2.min.js"></script>
    <script src="css/app.js"></script>
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
    
    <script>
    $(document).ready(function() {
        // Xử lý sự kiện click nút Thêm mẫu CV
        $('#btnThemMauCV').click(function(e) {
            e.preventDefault();
            $('#modalThemMau').modal('show');
        });
        // mở quản lý mẫu
        $('#btnQuanLyMauCV').click(function(e){
            e.preventDefault();
            $('#modalQuanLyMau').modal('show');
        });
        // Thêm xử lý toggle bộ lọc cho mobile/tablet
        $('#toggleFilterBtn').on('click', function() {
            if (window.innerWidth <= 768) {
                $('#filterPanel').toggleClass('show');
                if ($('#filterPanel').hasClass('show')) {
                    $('#filterOverlay').addClass('show');
                } else {
                    $('#filterOverlay').removeClass('show');
                }
            }
        });
        $('#closeFilterPanel').on('click', function() {
            if (window.innerWidth <= 768) {
                $('#filterPanel').removeClass('show');
                $('#filterOverlay').removeClass('show');
            }
        });
        $('#filterOverlay').on('click', function() {
            if (window.innerWidth <= 768) {
                $('#filterPanel').removeClass('show');
                $('#filterOverlay').removeClass('show');
            }
        });
    });
    </script>

    <!-- Load Bootstrap JS trước khi sử dụng modal -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2();
            $('body').on('click', '.menuJob', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                if ($('.menuDiv_' + id).is(':hidden'))
                    $('.menuDiv_' + id).hide();
                else
                    $('.menuDiv_' + id).show();
            });

            $('body').on('change', '#selectRoom', function() {
                let id = $(this).val();
                $.ajax({
                    method: "GET",
                    url: "ajax_work/get_members_by_room.php",
                    data: {
                        'id': id,
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            $('#selectMember2').html('<option>--Chọn thành viên--</option>');
                            $.each(res.data, function(key, value) {
                                $('#selectMember2').append('<option value="' + value.TV_MA + '">' + value.TV_TEN + '</option>');
                            });
                        }
                    }
                });
            });

            $('body').on('click', '#menuMember', function(e) {
                let code = $('#inputCode').val();
                $('#inputCodeMember').val(code);
                fetch('ajax/get_project.php?code=' + code, {
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

            $('body').on('click', '#menuLabel', function(e) {
                let code = $('#inputCode').val();
                $('#inputCodeLabel').val(code);
                $('#modalLabel').modal('show');
            });

            $('body').on('click', '#menuWork', function(e) {
                // Get the project code from the URL or from the hidden input
                const urlParams = new URLSearchParams(window.location.search);
                let projectCode = urlParams.get('code') || '';
                
                // If no code in URL, try to get it from the hidden input
                if (!projectCode) {
                    projectCode = $('#inputCode').val() || '';
                }
                
                if (!projectCode) {
                    console.error('Project code is missing. Cannot open add work modal.');
                    toastr.error('Không thể xác định mã dự án.');
                    return;
                }

                // Load modal content via AJAX
                $.ajax({
                    url: 'ajax/modal_work.php?project_code=' + projectCode,
                    type: 'GET',
                    success: function(response) {
                        // Add the modal to the container and show it
                        $('#modal-container').html(response);
                        $('#modalWork').modal('show');

                        // Set hidden values after modal is loaded
                        $('#inputCodeWork').val(projectCode);
                        $('#inputCodeParent').val('');

                        // Initialize select2 if it exists
                        if ($.fn.select2) {
                            $('#modalWork .select2').select2({
                                dropdownParent: $('#modalWork')
                            });
                        }
                        
                        // Optional: Focus on the first input field
                        setTimeout(() => {
                            $('#modalWork [name="name"]').focus();
                        }, 500);
                    },
                    error: function() {
                        toastr.error('Lỗi khi tải biểu mẫu thêm công việc.');
                    }
                });
            });

            $('body').on('click', '.itemMember', function(e) {
                let code = $('#inputCode').val();
                let id = $(this).data('id');
                $('#selectMember2').val(id).trigger('change');
                $('#inputCodeWork').val(code);
                $('#inputCodeParent').val(null);
                $('#modalWork').modal('show');
            });

            $('body').on('click', '#menuDate', function(e) {
                let code = $('#inputCode').val();
                $('#inputCodeDate').val(code);
                fetch('ajax/get_project_date.php?code=' + code, {
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

            $('body').on('click', '#btnComment', function(e) {
                e.preventDefault();
                let editorData = '';
                if (CKEDITOR.instances['comment']) {
                    editorData = CKEDITOR.instances['comment'].getData();
                } else if ($('#comment').length) {
                    editorData = $('#comment').val();
                }
                let text = removeScriptTags(editorData);
                let code = $(this).data('id');
                if (text.trim() === '' || text.trim() === '<p>&nbsp;</p>') {
                    toastr.error('Vui lòng nhập comment');
                    return;
                }
                $('.loading').addClass('loader');
                $.ajax({
                    method: "POST",
                    url: "ajax/insert_comment.php",
                    data: {
                        'code': code,
                        'text': text
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            if (CKEDITOR.instances['comment']) {
                                CKEDITOR.instances['comment'].setData('');
                            } else if ($('#comment').length) {
                                $('#comment').val('');
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

            $('body').on('click', '.btnInsert', function(e) {
                e.preventDefault();
                let status = $(this).data('status');
                $('#inputStatus').val(status);
                $('#modalInsert').modal('show');
            });

            $('body').on('click', '.btnInsertWork', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let code = $('#inputCode').val();
                $('#inputCodeWork').val(code);
                $('#inputCodeParent').val(id);
                $('#modalWork').modal('show');
            });

            $('body').on('change', '.checkbox', function() {
                let id = $(this).data('id');
                let code = $(this).data('code');
                let status = $(this).is(':checked') ? 2 : 1;
                $.ajax({
                    method: "GET",
                    url: "ajax/update_status_work.php",
                    data: {
                        'id': id,
                        'code': code,
                        'status': status
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            loadViewEdit(res.data);
                            toastr.success(res.message);
                        } else {
                            toastr.error(res.message);
                        }
                    }
                });

            });

            $('body').on('click', '.viewDetail', function(e) {
                e.preventDefault();
                $('.loading').addClass('loader');
                let code = $(this).data('id');
                $('.list-group-edit').hide();
                loadViewEdit(code);
                $('#modalEdit').modal('show');
                $('.loading').removeClass('loader');
            });

            $('body').on('click', '.viewDetail2', function(e) {
                e.preventDefault();
                $('.loading').addClass('loader');
                let code = $(this).data('id');
                loadViewEdit(code);
                $('#modalEdit').modal('show');
                $('.loading').removeClass('loader');
            });

            $('body').on('click', '.removeProject', function(e) {
                e.preventDefault();
                let code = $(this).data('id');
                $('.loading').addClass('loader');
                $('.list-group-item').hide();
                $.ajax({
                    method: "GET",
                    url: "ajax/remove_project.php",
                    data: {
                        'code': code
                    },
                    dataType: 'json',
                    success: function(res) {
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

            $('body').on('click', '.removeLabel', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let code = $(this).data('code');
                $('.loading').addClass('loader');
                $.ajax({
                    method: "GET",
                    url: "ajax/remove_label.php",
                    data: {
                        'id': id,
                        'code': code
                    },
                    dataType: 'json',
                    success: function(res) {
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

            $('body').on('click', '.removeComment', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let code = $(this).data('code');
                $('.loading').addClass('loader');
                $.ajax({
                    method: "GET",
                    url: "ajax/remove_comment.php",
                    data: {
                        'id': id,
                        'code': code
                    },
                    dataType: 'json',
                    success: function(res) {
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

            $('body').on('click', '.removeWork', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let code = $(this).data('code');
                $('.loading').addClass('loader');
                $.ajax({
                    method: "GET",
                    url: "ajax/remove_work.php",
                    data: {
                        'id': id,
                        'code': code
                    },
                    dataType: 'json',
                    success: function(res) {
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

            $('#dateFormInsert').submit(function(e) {
                e.preventDefault();
                let form = $(this).serialize();
                $('.loading').addClass('loader');
                $.ajax({
                    method: "POST",
                    url: "ajax/update_date.php",
                    data: form,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            $('#modalDate').modal('hide');
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


            $('#memberFormInsert').submit(function(e) {
                e.preventDefault();
                let form = $(this).serialize();
                $('.loading').addClass('loader');
                $.ajax({
                    method: "POST",
                    url: "ajax/update_member.php",
                    data: form,
                    dataType: 'json',
                    success: function(res) {
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

            $('#labelFormInsert').submit(function(e) {
                e.preventDefault();
                let form = $(this).serialize();
                $('.loading').addClass('loader');
                $.ajax({
                    method: "POST",
                    url: "ajax/update_label.php",
                    data: form,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            $('#labelFormInsert')[0].reset();
                            $('#modalLabel').modal('hide');
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

            // Xử lý form thêm công việc
            // Sử dụng .off() để xóa listener cũ trước khi gắn listener mới bằng .on()
            // Điều này đảm bảo sự kiện chỉ được kích hoạt một lần duy nhất.
            $('body').off('submit', '#workFormInsert').on('submit', '#workFormInsert', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalBtnText = submitBtn.html();
                
                // Vô hiệu hóa nút submit và hiển thị loading
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
                $('.loading').addClass('loader');
                
                // Lấy mã dự án từ nhiều nguồn khác nhau
                const urlParams = new URLSearchParams(window.location.search);
                let projectCode = '';
                
                // Thứ tự ưu tiên lấy mã dự án:
                // 1. Từ input ẩn trong form
                projectCode = $('#inputCodeWork').val();
                
                // 2. Nếu không có, lấy từ URL
                if (!projectCode) {
                    projectCode = urlParams.get('code') || '';
                }
                
                // 3. Nếu vẫn không có, lấy từ modal đang mở
                if (!projectCode && $('#modalEdit').length) {
                    projectCode = $('#inputCode').val() || '';
                }
                
                //console.log('Project code for submission:', projectCode);
                
                // Lấy dữ liệu từ form
                const formData = {
                    code: projectCode,
                    name: form.find('[name="name"]').val(),
                    member_id: form.find('[name="member_id"]').val(),
                    room_id: form.find('[name="room_id"]').val(),
                    ph_id: form.find('[name="ph_id"]').val() || 0,
                    duration: form.find('[name="duration"]').val(),
                    start_date: form.find('[name="start_date"]').val(), // Thêm trường ngày bắt đầu
                    prerequisite_task: form.find('[name="prerequisite_task"]').val(),
                    status: form.find('[name="status"]').val() || '5',
                    parent: $('#inputCodeParent').val() || ''
                };
                
                // Kiểm tra các trường bắt buộc
                const requiredFields = ['code', 'name', 'member_id', 'duration', 'start_date'];
                const missingFields = requiredFields.filter(field => !formData[field]);
                
                if (missingFields.length > 0) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Vui lòng điền đầy đủ thông tin bắt buộc');
                    }
                    console.error('Thiếu trường bắt buộc:', missingFields);
                    console.log('Form data:', formData);
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    $('.loading').removeClass('loader');
                    return false;
                }
                
                // Gửi dữ liệu bằng AJAX
                //console.log('Sending data to server:', formData);
                $.ajax({
                    method: 'POST',
                    url: 'ajax/update_work.php',
                    data: formData,
                    dataType: 'json'
                })
                .done(function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.status) {
                            toastr.success(data.message || 'Lưu thành công!');
                            // Đóng modal
                            $('#modalWork').modal('hide');
                            // Làm mới dữ liệu
                            const projectCode = formData.code;
                            
                            // Load lại danh sách công việc tiên quyết
                            if (typeof loadPrerequisiteTasks === 'function') {
                                loadPrerequisiteTasks(projectCode);
                            }
                            
                            if (typeof loadViewEdit === 'function') {
                                loadViewEdit(projectCode);
                            } else {
                                window.location.reload();
                            }
                            // Reset form sau khi đóng modal
                            $('#workFormInsert')[0].reset();
                            if ($.fn.select2) {
                                $('#workFormInsert .select2').val(null).trigger('change');
                            }
                        } else {
                            toastr.error(data.message || 'Có lỗi xảy ra khi lưu dữ liệu');
                            submitBtn.prop('disabled', false).html(originalBtnText);
                            $('.loading').removeClass('loader');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        toastr.error('Lỗi xử lý dữ liệu từ máy chủ');
                        submitBtn.prop('disabled', false).html(originalBtnText);
                        $('.loading').removeClass('loader');
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('--- AJAX Request Failed ---');
                    console.error('Status:', textStatus);
                    console.error('Error:', errorThrown);
                    console.error('Server Response:', jqXHR.responseText); // Log chi tiết lỗi từ server

                    if (typeof toastr !== 'undefined') {
                        let errorMessage = 'Lỗi không xác định. Vui lòng thử lại.';
                        try {
                           const response = JSON.parse(jqXHR.responseText);
                           if(response && response.message) {
                               errorMessage = response.message;
                           }
                        } catch(e) {
                            errorMessage = `Lỗi AJAX: ${textStatus} ${errorThrown}`;
                        }
                        toastr.error(errorMessage);
                    }
                })
                .always(function() {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    $('.loading').removeClass('loader');
                });
            });

            // Xử lý sự kiện submit form thêm dự án
            $('body').off('submit', '#projectFormInsert').on('submit', '#projectFormInsert', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                const form = $(this);
                
                // Kiểm tra nếu đang gửi form thì không gửi lại
                if (form.data('submitting')) {
                    return false;
                }
                
                // Đánh dấu là đang gửi form
                form.data('submitting', true);
                
                $('.loading').addClass('loader');
                
                // Log dữ liệu form trước khi gửi
                const formData = form.serialize();
                console.log('Dữ liệu form gửi đi:', formData);
                
                $.ajax({
                    method: "POST",
                    url: "ajax/insert_project.php",
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            toastr.success('Lưu thành công');
                            form[0].reset();
                            $('#modalInsert').modal('hide');
                            loadViewJobs();
                        } else {
                            toastr.error(res.message || 'Có lỗi xảy ra');
                        }
                    },
                    error: function() {
                        toastr.error('Lỗi kết nối, vui lòng thử lại');
                    },
                    complete: function() {
                        $('.loading').removeClass('loader');
                        form.data('submitting', false);
                    }
                });
                
                return false;
            });

            $('#projectFormUpdate').submit(function(e) {
                e.preventDefault();
                // let form = $(this).serialize();
                $('.loading').addClass('loader');
                // Get the form element
                var form = document.getElementById('projectFormUpdate');
                // Create a FormData object from the form element
                var formData = new FormData(form);

                // Get the CKEditor content và append nó vào FormData
                var editorData = '';
                if (CKEDITOR.instances['editor']) {
                    editorData = CKEDITOR.instances['editor'].getData();
                } else if ($('#editor').length) {
                    editorData = $('#editor').val();
                }
                formData.append('editor', editorData);
                $.ajax({
                    method: "POST",
                    url: "ajax/update_project.php",
                    data: formData,
                    processData: false, // Important!
                    contentType: false, // Important!
                    dataType: 'json',
                    success: function(res) {
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
                window.loadViewJobs = loadViewJobs;
                // Lấy param hiện tại
                const urlParams = new URLSearchParams(window.location.search);
                let paramsObj = {};
                for(let [key, val] of urlParams.entries()) {
                    paramsObj[key] = val;
                }
        
                $.ajax({
                    url: 'ajax/get_view.php',
                    type: 'GET',
                    data: paramsObj,
                    success: function(html) {
                        document.getElementById('result').innerHTML = html;
                        $("#new-jobs-list, #in-progress-list, #waiting-jobs-list, #complete-jobs-list, #rework-jobs-list").each(function () {
                            if ($(this).data('ui-sortable')) {
                                $(this).sortable('destroy');
                            }
                        });
                        $('body').off('click', '.menuJob');
                        if (typeof loadJsDropDrag === 'function') {
                            loadJsDropDrag();
                        }
                        
                        // Khôi phục view
                        var storedView = localStorage.getItem('cv_preferred_view_duan');
                        if (storedView === 'table') {
                            switchToTable();
                        } else {
                            switchToKanban();
                        }
                    }
                });
            }
        
            // ========== TOGGLE VIEW LGC ==========
            var CV_VIEW_KEY = "cv_preferred_view_duan";
            function switchToTable() {
                $('#kanbanView').addClass('cv-hidden'); 
                $('#tableView').removeClass('cv-hidden');
                $('#btnKanbanView').removeClass('active'); 
                $('#btnTableView').addClass('active');
                $('body').addClass('table-view-active');
                localStorage.setItem(CV_VIEW_KEY, 'table');
            }
            
            function switchToKanban() {
                $('#tableView').addClass('cv-hidden'); 
                $('#kanbanView').removeClass('cv-hidden');
                $('#btnTableView').removeClass('active'); 
                $('#btnKanbanView').addClass('active');
                $('body').removeClass('table-view-active');
                localStorage.setItem(CV_VIEW_KEY, 'kanban');
            }
        
            // Toggle View Events
            $('body').on('click', '#btnKanbanView', function(e) {
                e.preventDefault();
                switchToKanban();
            });
            $('body').on('click', '#btnTableView', function(e) {
                e.preventDefault();
                switchToTable();
            });
        
            // ========== LAZY LOAD KANBAN ==========
            $('body').on('click', '.cv-load-more-btn', function(e) {
                e.preventDefault();
                let btn = $(this);
                let status = btn.data('status');
                let offset = parseInt(btn.data('offset'));
                let total = parseInt(btn.data('total'));
                let limit = 30; // giống config trong helper
        
                // lấy param search/filter
                let searchKeywords = $('input[name="keywords"]').val() || '';
                let fromDate = $('#from_date').val() || '';
                let toDate = $('#to_date').val() || '';
        
                // add loading state
                btn.html('<i class="fas fa-spinner fa-spin"></i> Đang tải...');
                btn.prop('disabled', true);
        
                $.ajax({
                    url: 'ajax/get_kanban_cards.php',
                    type: 'GET',
                    data: {
                        status: status,
                        offset: offset,
                        limit: limit,
                        keywords: searchKeywords,
                        from_date: fromDate,
                        to_date: toDate
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.html && res.html.trim() !== '') {
                            // Xóa nút bấm hiện tại (li)
                            let liItem = btn.closest('li.cv-load-more-item');
                            let ul = liItem.parent();
                            liItem.remove();
        
                            ul.append(res.html);
        
                            let newOffset = offset + res.loaded;
                            let remaining = total - newOffset;
                            if (remaining > 0) {
                                ul.append('<li class="cv-load-more-item"><button class="cv-load-more-btn" data-status="'+status+'" data-offset="'+newOffset+'" data-total="'+total+'"><i class="fal fa-arrow-down"></i> Xem thêm ('+remaining+')</button></li>');
                            }
                        } else {
                            btn.closest('li.cv-load-more-item').remove();
                        }
                    },
                    error: function() {
                        toastr.error('Lỗi tải thêm công việc.');
                        btn.html('<i class="fal fa-arrow-down"></i> Thử lại');
                        btn.prop('disabled', false);
                    }
                });
            });
        
            // ========== Đổi Trang (Pagination) Table ==========
            $('body').on('click', '.cv-page-btn:not(.disabled), .cv-page-num:not(.active)', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                if (!page) return;
        
                let url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            });
        
            $('body').on('change', '#perPageSelect', function(e) {
                let perPage = $(this).val();
                let url = new URL(window.location.href);
                url.searchParams.set('per_page', perPage);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });
        
            // Khởi tạo toggle ở document ready
            var initView = localStorage.getItem(CV_VIEW_KEY);
            if (initView === 'table') {
                switchToTable();
            } else {
                switchToKanban();
            }

            function loadViewEdit(code) {
                // Gắn ra scope global để modal con gọi khi cần refresh
                window.loadViewEdit = loadViewEdit;
                fetch('ajax/get_view_edit.php?code=' + code, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Lưu trạng thái hiện tại của modal edit
                        const isModalEditShown = $('#modalEdit').hasClass('show') || $('#modalEdit').is(':visible');
                        
                        // Cập nhật nội dung
                        document.getElementById('bodyModalEdit').innerHTML = html;
                        
                        // Khởi tạo lại CKEditor
                        if (typeof CKEDITOR !== 'undefined') {
                            if (CKEDITOR.instances['editor']) {
                                CKEDITOR.instances['editor'].destroy(true);
                            }
                            if (CKEDITOR.instances['comment']) {
                                CKEDITOR.instances['comment'].destroy(true);
                            }
                            if (document.getElementById('editor')) {
                                CKEDITOR.replace('editor', { height: 200 });
                            }
                            if (document.getElementById('comment')) {
                                CKEDITOR.replace('comment', { height: 50 });
                            }
                        }
                        
                        // Khởi tạo lại các sự kiện drag & drop
                        loadJsDropDrag();
                        
                        // Nếu modal edit đang mở trước đó, giữ nguyên trạng thái
                        if (isModalEditShown) {
                            $('#modalEdit').modal('show');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }


            $("#sidebar").mCustomScrollbar({
                theme: "minimal"
            });

            $('#sidebarCollapse').on('click', function() {
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

            // Fix lỗi Maximum call stack size trong modal
            $(document).on('shown.bs.modal', function() {
                $(document).off('focusin.modal');
            });

            // Vô hiệu hóa sự kiện focusin gây lỗi
            $(document).on('focusin', function(e) {
                if ($(e.target).closest('.modal').length) {
                    e.stopPropagation();
                }
            });
        });
    </script>
    <!-- Thêm hàm updateDuAnMa vào phần JavaScript để xử lý sự kiện khi nhấn nút 'Chọn mẫu công việc' -->
    <script>
        // Hàm mở modal chọn mẫu công việc
        function updateDuAnMa(duanMa) {
            // Lưu mã dự án vào biến toàn cục để sử dụng sau này
            window.DUAN_MA_MODAL = duanMa;
            
            // Hiển thị loading
            $('.loading').addClass('loader');
            
            // Tạo modal nếu chưa tồn tại
            if ($('#modalChonMau').length === 0) {
                var modalHtml = `
                    <div class="modal fade" id="modalChonMau" tabindex="-1" role="dialog" aria-labelledby="modalChonMauLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Chọn mẫu công việc</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <p>Đang tải danh sách mẫu công việc...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                $('body').append(modalHtml);
            }
            
            // Hiển thị modal trước khi tải dữ liệu
            $('#modalChonMau').modal('show');
            
            // Gọi AJAX để lấy danh sách mẫu công việc
            $.ajax({
                url: 'ajax/modal_chonmau.php',
                type: 'GET',
                success: function(response) {
                    // Cập nhật nội dung modal
                    $('#modalChonMau .modal-content').html($(response).find('.modal-content').html());
                    
                    // Khởi tạo lại các sự kiện và plugin nếu cần
                    if (typeof $.fn.select2 === 'function') {
                        $('.select2').select2();
                    }
                    
                    // Ẩn loading
                    $('.loading').removeClass('loader');
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi khi tải danh sách mẫu:', error);
                    // Hiển thị thông báo lỗi trong modal
                    $('#modalChonMau .modal-body').html(`
                        <div class="alert alert-danger">
                            <h5>Lỗi khi tải dữ liệu</h5>
                            <p>Không thể tải danh sách mẫu công việc. Vui lòng thử lại sau.</p>
                            <pre>${error}</pre>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        </div>
                    `);
                    $('.loading').removeClass('loader');
                }
            });
        }
    </script>
    
    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- jQuery UI for sortable (phải load sau jQuery và trước Bootstrap) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- mCustomScrollbar -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Thư viện mousetrap để xử lý sự kiện phím -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mousetrap/1.6.5/mousetrap.min.js"></script>
    
    <!-- Toastr for notifications -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Xử lý sự kiện submit form lọc
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const currentUrl = window.location.pathname + '?' + formData;
            
            // Thêm tham số keywords nếu có
            const keywords = $('input[name="keywords"]').val();
            if (keywords) {
                window.location.href = currentUrl + '&keywords=' + encodeURIComponent(keywords);
            } else {
                window.location.href = currentUrl;
            }
        });
        
        // Tự động submit form khi thay đổi lựa chọn
        $('#filterYear, #filterQuarter').on('change', function() {
            $('#filterForm').submit();
        });
        
        // Xử lý khi nhấn nút xóa lọc
        $('a[href="?"]').on('click', function(e) {
            e.preventDefault();
            window.location.href = window.location.pathname;
        });
    });
    </script>
    
    <!-- Khởi tạo lại các plugin sau khi load xong tất cả thư viện -->
    <script>
    $(document).ready(function() {
        // Khởi tạo lại các plugin cần thiết
        if (typeof $.fn.select2 === 'function') {
            $('.select2').select2();
        }
        
        // Khởi tạo lại các sự kiện sortable nếu cần
        if (typeof $.fn.sortable === 'function') {
            $('.sortable').sortable();
        }
    });
    </script>
    
    <script>
        // Vô hiệu hóa sự kiện focusin mặc định của Bootstrap
        if (typeof $.fn.modal !== 'undefined') {
            $.fn.modal.Constructor.prototype._enforceFocus = function() {};
        }
        
        // Hàm đóng modal an toàn
        function closeModalSafely(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            // Kiểm tra và xử lý cho Bootstrap 4
            if (typeof $ !== 'undefined' && $.fn.modal) {
                $(modal).modal('hide');
                return;
            }
            
            // Fallback thủ công nếu không dùng jQuery hoặc Bootstrap
            $(modal).removeClass('show');
            $(modal).css('display', 'none');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    var resetBtn = document.getElementById('resetSearch');
    var form = document.getElementById('searchForm');
    if (resetBtn && form) {
        resetBtn.onclick = function(e) {
            e.preventDefault();
            form.keywords.value = '';
            form.from_date.value = '';
            form.to_date.value = '';
            form.submit();
        };
    }
});
</script>
</body>

</html>