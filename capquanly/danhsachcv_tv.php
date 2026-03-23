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

//1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
function querySql($conn, $status, $keywords = '')
{
    //Get data dự án
    if($keywords != '')
        $sql = "SELECT * FROM danhsachcongviec WHERE DSCV_TRANGTHAI = $status AND TV_MA IS NOT NULL AND DA_MA IS NOT NULL AND dscv_trangthaihd = 1 AND DSCV_TEN LIKE '%$keywords%' ORDER BY DSCV_MA DESC";
    else
        $sql = "SELECT * FROM danhsachcongviec WHERE DSCV_TRANGTHAI = $status AND TV_MA IS NOT NULL AND DA_MA IS NOT NULL AND dscv_trangthaihd = 1 ORDER BY DSCV_MA DESC";

    // Thực thi câu truy vấn và gán vào $result
    $result = mysqli_query($conn, $sql);
    return $result;
}

//Get danh sách dự án
$sql = "SELECT * FROM duan ORDER BY DA_MA DESC";
$result = mysqli_query($conn, $sql);
$projects = mysqli_fetch_all($result, MYSQLI_ASSOC);

//Danh sách room
$sql = "SELECT * FROM phongban ";
$result = mysqli_query($conn, $sql);
$rooms = mysqli_fetch_all($result, MYSQLI_ASSOC);

//Data trả về theo thứ tự ở view
$projectsStart = mysqli_fetch_all(querySql($conn, 5, $keywords), MYSQLI_ASSOC);

$projectsInProgress = mysqli_fetch_all(querySql($conn, 1, $keywords), MYSQLI_ASSOC);

$projectsMove = mysqli_fetch_all(querySql($conn, 3, $keywords), MYSQLI_ASSOC);

$projectsFinish = mysqli_fetch_all(querySql($conn, 2, $keywords), MYSQLI_ASSOC);

$projectsCancel = mysqli_fetch_all(querySql($conn, 4, $keywords), MYSQLI_ASSOC);

$conn->close();

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Danh Sách Công Việc</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css"/>
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet"
          type="text/css"/>
</head>

<body>
<?php include('ajax_work/loading.php'); ?>
<div class="wrapper">
    <!-- Sidebar  -->
    <?php include("../menu.php"); ?>

    <!-- Page Content  -->
    <div id="content">
        <div class="header-section">
            <div class="header-top-bar">
                <button type="button" id="sidebarCollapse" class="btn btn-sm btn-info">
                    <i class="fas fa-align-left"></i>
                </button>
                <div class="top-bar-block center">
                    <form method="GET">
                        <div class="d-flex">
                            <input autocomplete="off" name="keywords" type="text" class="form-control input-sm" placeholder="Nhập tên công việc ...." value="<?php echo $_GET['keywords'] ?? ''; ?>" >
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fal fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="top-bar-block right">
                    <div class="profile-icon-block"><img class="img-fuild rounded profile-icon"
                                                         src="./css/avatar.png" height="30" width="30"></div>
                </div>
            </div>
        </div>

        <div class="body-section" id="result">
            <?php include('ajax_work/jobs_tv.php'); ?>
        </div>

    </div>
</div>
<!-- Modal -->
<?php include('ajax_work/modal_insert.php'); ?>
<?php include('ajax_work/modal_edit.php'); ?>
<?php include('ajax_work/modal_member.php'); ?>
<?php include('ajax_work/modal_date.php'); ?>

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
    $(document).ready(function () {
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


        $('#projectFormInsert').submit(function (e) {
            e.preventDefault();
            var form = document.getElementById('projectFormInsert');
            // Create a FormData object from the form element
            var formData = new FormData(form);

            // Get the CKEditor content and append it to the FormData object
            var editorData = CKEDITOR.instances.editor.getData();
            formData.append('editor', editorData);
            $('.loading').addClass('loader');
            $.ajax({
                method: "POST",
                url: "ajax_work/insert_project.php",
                data: form,
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
            fetch('ajax_work/get_view_tv.php', {
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
