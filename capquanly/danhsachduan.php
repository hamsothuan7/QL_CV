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

// Khôi phục lại phần xử lý từ khoá tìm kiếm và thêm lọc theo thời gian
$keywords = filter_input(INPUT_GET, 'keywords', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$keywords = trim($keywords);
$from_date = filter_input(INPUT_GET, 'from_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$to_date = filter_input(INPUT_GET, 'to_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// Lấy danh sách dự án (có tìm kiếm và lọc thời gian)
function querySql($conn, $status, $keywords = '', $from_date = '', $to_date = '')
{
    $status = (int)$status;
    $userId = $_SESSION['code'] ?? null;
    $isAdmin = ($_SESSION['active'] == 1) || ($_SESSION['nnd_ma'] == 4);
    if (!$userId) return [];
    if ($isAdmin) {
        $sql = "SELECT * FROM duan WHERE DA_TRANGTHAI = ?";
        $params = [$status];
        $types = "i";
    } else {
        $sql = "SELECT DISTINCT d.* FROM duan d LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA WHERE d.DA_TRANGTHAI = ? AND (d.DA_NGUOIPHUTRACH = ? OR dt.TV_MA = ?)";
        $params = [$status, $userId, $userId];
        $types = "iss";
    }
    // Thêm điều kiện tìm kiếm nếu có từ khoá
    if (!empty($keywords)) {
        $sql .= " AND DA_TEN LIKE ?";
        $params[] = "%$keywords%";
        $types .= "s";
    }
    // Thêm điều kiện lọc theo thời gian nếu có
    if (!empty($from_date)) {
        $sql .= " AND DATE(DA_NGAYBATDAU) >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    if (!empty($to_date)) {
        $sql .= " AND DATE(DA_NGAYKETTHUC) <= ?";
        $params[] = $to_date;
        $types .= "s";
    }
    $sql .= " ORDER BY DA_MA DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) return [];
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);
    return $data;
}

// Lấy dữ liệu dự án theo từng trạng thái (có tìm kiếm và lọc thời gian)
try {
    $projectsStart = querySql($conn, 5, $keywords, $from_date, $to_date);
    $projectsInProgress = querySql($conn, 1, $keywords, $from_date, $to_date);
    $projectsMove = querySql($conn, 3, $keywords, $from_date, $to_date);
    $projectsFinish = querySql($conn, 2, $keywords, $from_date, $to_date);
    $projectsCancel = querySql($conn, 4, $keywords, $from_date, $to_date);
} catch (Exception $e) {
    error_log('Lỗi khi lấy dữ liệu dự án: ' . $e->getMessage());
    $projectsStart = $projectsInProgress = $projectsMove = $projectsFinish = $projectsCancel = [];
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
            <div class="d-flex justify-content-between align-items-center mb-3" style="padding: 0 15px;">
                <div class="btn-group-mobile-center">
                    <button type="button" class="btn btn-success" id="btnThemMauCV">
                        <i class="fas fa-plus"></i> Thêm mẫu
                    </button>
                    <button type="button" class="btn btn-secondary ms-2" id="btnQuanLyMauCV">
                        <i class="fas fa-layer-group"></i> Quản lý mẫu
                    </button>
                </div>
                <div class="top-bar-block d-flex justify-content-center py-3">
                    <!-- Nút lọc chỉ hiện trên mobile -->
                    <button id="toggleFilterBtn" class="btn btn-primary d-md-none ml-1" type="button">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <div id="filterOverlay" style="display:none;"></div>
                    <div id="filterPanel" style="position:relative;">
                        <button id="closeFilterPanel" class="btn btn-link" type="button" style="position:absolute;top:8px;right:8px;font-size:22px;">&times;</button>
                        <form id="searchForm" method="GET" class="d-flex align-items-end flex-nowrap gap-2 w-100 justify-content-center">
                            <input autocomplete="off" name="keywords" type="text" class="form-control form-control-sm rounded-pill shadow-sm"
                                placeholder="Tìm kiếm tên dự án..." value="<?php echo htmlspecialchars($keywords); ?>" style="width: 180px; min-width: 120px;">
                            <div class="d-flex flex-column align-items-start">
                                <label class="mb-1 text-secondary small" for="from_date">Từ ngày</label>
                                <input type="date" id="from_date" name="from_date" class="form-control form-control-sm rounded-pill shadow-sm" value="<?php echo htmlspecialchars($from_date); ?>" style="width: 130px; min-width: 100px;">
                            </div>
                            <div class="d-flex flex-column align-items-start">
                                <label class="mb-1 text-secondary small" for="to_date">Đến ngày</label>
                                <input type="date" id="to_date" name="to_date" class="form-control form-control-sm rounded-pill shadow-sm" value="<?php echo htmlspecialchars($to_date); ?>" style="width: 130px; min-width: 100px;">
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center" style="height: 34px;">
                                <i class="fal fa-search me-2"></i> Tìm kiếm
                            </button>
                            <button type="button" id="resetSearch" class="btn btn-secondary btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center" style="height: 34px;">
                                <i class="fal fa-undo me-2"></i> Đặt lại
                            </button>
                        </form>
                    </div>
                </div>
            </div>
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
        // Hàm loadViewJobs để tải lại danh sách công việc
        function loadViewJobs() {
            $.ajax({
                url: 'ajax/jobs.php',
                type: 'GET',
                data: {
                    keywords: $('input[name="keywords"]').val(),
                    from_date: $('input[name="from_date"]').val(),
                    to_date: $('input[name="to_date"]').val()
                },
                success: function(response) {
                    $('#result').html(response);
                    // Khởi tạo lại các plugin cần thiết sau khi load nội dung mới
                    if (typeof $().select2 === 'function') {
                        $('.select2').select2();
                    }
                },
                error: function() {
                    toastr.error('Có lỗi xảy ra khi tải dữ liệu');
                }
            });
        }

        $(document).ready(function() {
            // Gọi loadViewJobs khi trang tải xong
            loadViewJobs();
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
                // Gắn ra scope global để các iframe/modal có thể gọi
                window.loadViewJobs = loadViewJobs;
                fetch('ajax/get_view.php', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('result').innerHTML = html;
                        // Destroy any existing sortable instances to avoid duplicate bindings
                        $("#new-jobs-list, #in-progress-list, #waiting-jobs-list, #complete-jobs-list, #rework-jobs-list").each(function () {
                            if ($(this).data('ui-sortable')) {
                                $(this).sortable('destroy');
                            }
                        });
                        // Remove previously attached delegated click handler to prevent stacking
                        $('body').off('click', '.menuJob');

                        // Re-initialise sortable and other JS behaviours
                        loadJsDropDrag();

                        // Attach the delegated click handler once
                        $('body').on('click', '.menuJob', function (e) {
                            e.preventDefault();
                            let id = $(this).data('id');
                            $('.menuDiv_' + id).toggle();
                        });
                    })
                    .catch(error => console.error('Error:', error));
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