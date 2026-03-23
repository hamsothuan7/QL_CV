<?php
include('../config.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    insertDataAndMembers();
}

function getDepartments() {
    $query = "SELECT PB_MA, PB_TEN FROM `phongban`";
    $result = mysqli_query($conn, $query);
    $departments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
    mysqli_close($conn);
    return $departments;
}

function insertDataAndMembers() {
    $tenda = $_POST['txtName'];
    $tinhtrang = $_POST['tinhtrang'];
    $ngaybd = $_POST['txtNgayBD'];
    $ngaykt = $_POST['txtNgayKT'];
    $tasks = $_POST['tasks'];

    
    if ($ngaybd > $ngaykt) {
        echo "<script>alert('Ngày Kết Thúc Phải > Ngày Bắt Đầu');</script>";
    } else {
        mysqli_autocommit($conn, FALSE);

        $duan_ma = generateProjectCode($conn);

        $query1 = "INSERT INTO `duan`(`DA_MA`, `DA_TEN`, `DA_TRANGTHAI`, `DA_NGAYBATDAU`, `DA_NGAYKETTHUC`) 
                  VALUES ('$duan_ma', '$tenda', '$tinhtrang', '$ngaybd', '$ngaykt')";
        $result1 = mysqli_query($conn, $query1);

        if ($result1) {
            $allTasksInserted = true;

            foreach ($tasks as $task) {
                $dscv_ma = generateTaskCode($conn);
                $dscv_ten = $task['dscv_ten'];
                $dscv_ngaybatdau = $task['dscv_ngaybatdau'];
                $dscv_ngayketthuc = $task['dscv_ngayketthuc'];
                $dscv_trangthai = $task['dscv_trangthai'];
                $tv_ma = $task['tv_ma'];
                $pb_ma = $task['pb_ma'];

                if (!$pb_ma) {
                    echo "Error: pb_ma is missing for task: " . json_encode($task);
                    continue;
                }

                $query2 = "INSERT INTO `danhsachcongviec`(`DSCV_MA`, `DSCV_TEN`, `DSCV_NGAYBATDAU`, `DSCV_NGAYKETTHUC`, `DSCV_TRANGTHAI`, `DA_MA`, `TV_MA`, `PB_MA`) 
                           VALUES ('$dscv_ma', '$dscv_ten', '$dscv_ngaybatdau', '$dscv_ngayketthuc', '$dscv_trangthai', '$duan_ma', '$tv_ma', '$pb_ma')";
                $result2 = mysqli_query($conn, $query2);

                if (!$result2) {
                    echo "Lỗi thêm dữ liệu công việc: " . mysqli_error($conn);
                    $allTasksInserted = false;
                    break;
                }
            }

            if ($allTasksInserted) {
                mysqli_commit($conn);
                header('location:themda.php');
            } else {
                mysqli_rollback($conn);
                echo "Failed to insert tasks.";
            }
        } else {
            echo "Lỗi thêm dữ liệu dự án: " . mysqli_error($conn);
        }
        mysqli_autocommit($conn, TRUE);
    }
}

function generateProjectCode($conn) {
    $today = date("Ymd");
    $query = "SELECT COUNT(*) AS count FROM `duan` WHERE `DA_MA` LIKE '$today%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];
    $project_code = $today . str_pad(($count + 1), 2, "0", STR_PAD_LEFT);
    return $project_code;
}

function generateTaskCode($conn) {
    $today = date("Ymd");
    $query = "SELECT COUNT(*) AS count FROM `danhsachcongviec` WHERE `DSCV_MA` LIKE 'CV$today%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];
    $task_code = 'CV' . $today . str_pad(($count + 1), 2, "0", STR_PAD_LEFT);
    return $task_code;
}

function getMembers() {
    $query = "SELECT TV_MA, TV_TEN FROM `thanhvien`";
    $result = mysqli_query($conn, $query);
    $members = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
    mysqli_close($conn);
    return $members;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Công Việc Cá Nhân</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style_DAL.css">
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include ("../menu.php"); ?>
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-align-left"></i>
                    </button>
                </div>
            </nav>

            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h2 class="m-0">Quản Lý Dự Án</h2>
                        </div>
                    </div>
                    <hr class="border-primary">
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <div class="col-lg-12">
                        <div class="card card-outline card-primary" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-body">
                                <form method="post" id="projectForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tên Dự Án :</label>
                                                <input type="text" class="form-control form-control-sm" required name="txtName">
                                            </div>
                                            <div class="form-group">
                                                <label>Tình Trạng :</label>
                                                <select id="tinhtrang" name="tinhtrang" class="custom-select mb-3">
                                                    <option value="1">Đang Tiến Hành</option>
                                                    <option value="2">Hoàn Thành</option>
                                                    <option value="3">Dời</option>
                                                    <option value="4">Hủy</option>
                                                    <option value="5">Bắt Đầu</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Ngày Bắt Đầu</label>
                                                <input type="date" class="form-control form-control-sm" autocomplete="off" required name="txtNgayBD" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Ngày Kết Thúc</label>
                                                <input type="date" class="form-control form-control-sm" autocomplete="off" required name="txtNgayKT" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="taskContainer">
                                        <div class="task-row">
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Tên Công Việc :</label>
                                                        <input type="text" class="form-control form-control-sm" required name="tasks[0][dscv_ten]">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Ngày Bắt Đầu :</label>
                                                        <input type="date" class="form-control form-control-sm" required name="tasks[0][dscv_ngaybatdau]" value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Ngày Kết Thúc :</label>
                                                        <input type="date" class="form-control form-control-sm" required name="tasks[0][dscv_ngayketthuc]" value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Tình Trạng :</label>
                                                        <select name="tasks[0][dscv_trangthai]" class="custom-select mb-3">
                                                            <option value="1">Đang Tiến Hành</option>
                                                            <option value="2">Hoàn Thành</option>
                                                            <option value="3">Dời</option>
                                                            <option value="4">Hủy</option>
                                                            <option value="5">Bắt Đầu</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Phòng Ban :</label>
                                                        <select name="tasks[0][pb_ma]" class="custom-select mb-3" onchange="updateMembers(0)">
                                                            <?php
                                                            $departments = getDepartments();
                                                            foreach ($departments as $department) {
                                                                echo "<option value='{$department['PB_MA']}'>{$department['PB_TEN']}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Thành Viên :</label>
                                                        <select name="tasks[0][tv_ma]" class="custom-select mb-3">
                                                            <?php
                                                            $members = getMembers();
                                                            foreach ($members as $member) {
                                                                echo "<option value='{$member['TV_MA']}'>{$member['TV_TEN']}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-danger remove-task" style="display:none;">
                                            Xóa</button>
                                        </div>
                                    </div>

                                    <button type="button" id="addTaskBtn" class="btn btn-success" onclick="addTaskBtn" >Thêm Công Việc</button>
                                    <br><br>
                                    <div class="card-footer border-top border-info">
                                        <div class="d-flex w-100 justify-content-center align-items-center">
                                            <button type="submit" id="btnluu" name="btnluu" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px;border:2px solid #ff1e004d">Save</button>
                                            <a href="danhsachduan.php" class="btn btn-flat bg-gradient-primary mx-2" style="border-radius: 9px;border:2px solid #ff1e004d">Cancel</a>
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

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>

    <script type="text/javascript">
    $(document).ready(function () {
        $("#sidebar").mCustomScrollbar({
            theme: "minimal"
        });

        $('#sidebarCollapse').on('click', function () {
            $('#sidebar, #content').toggleClass('active');
        });

        let taskIndex = 1;

        $('#addTaskBtn').click(function () {
            addTaskRow(taskIndex++);
        });

        $('#taskContainer').on('click', '.remove-task', function () {
            $(this).closest('.task-row').remove();
        });

        updateMembers();
    });

    function addTaskRow(index) {
        let taskRow = `
            <div class="task-row">
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tên Công Việc :</label>
                            <input type="text" class="form-control form-control-sm" required name="tasks[${index}][dscv_ten]">
                        </div>
                        <div class="form-group">
                            <label>Ngày Bắt Đầu :</label>
                            <input type="date" class="form-control form-control-sm" required name="tasks[${index}][dscv_ngaybatdau]" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày Kết Thúc :</label>
                            <input type="date" class="form-control form-control-sm" required name="tasks[${index}][dscv_ngayketthuc]" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tình Trạng :</label>
                            <select name="tasks[${index}][dscv_trangthai]" class="custom-select mb-3">
                                <option value="1">Đang Tiến Hành</option>
                                <option value="2">Hoàn Thành</option>
                                <option value="3">Dời</option>
                                <option value="4">Hủy</option>
                                <option value="5">Bắt Đầu</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Phòng Ban :</label>
                            <select name="tasks[${index}][pb_ma]" class="custom-select mb-3" onchange="updateMembers(${index})">
                                <?php
                                $departments = getDepartments();
                                foreach ($departments as $department) {
                                    echo "<option value='{$department['PB_MA']}'>{$department['PB_TEN']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Thành Viên :</label>
                            <select name="tasks[${index}][tv_ma]" class="custom-select mb-3">
                                <?php
                                $members = getMembers();
                                foreach ($members as $member) {
                                    echo "<option value='{$member['TV_MA']}'>{$member['TV_TEN']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-danger remove-task">Xóa</button>
            </div>
        `;
        $('#taskContainer').append(taskRow);
    }

    function updateMembers(taskIndex = null) {
        let pb_ma = $(`[name="tasks[${taskIndex}][pb_ma]"]`).val();
        $.ajax({
            url: 'getMembers.php', // Điểm kết nối để lấy thành viên dựa trên phòng ban
            method: 'GET',
            data: { pb_ma: pb_ma },
            dataType: 'json',
            success: function(response) {
                let memberSelect = taskIndex !== null ? $(`[name="tasks[${taskIndex}][tv_ma]"]`) : $(`[name*="[tv_ma]"]`);
                memberSelect.empty();
                $.each(response, function(index, member) {
                    memberSelect.append(new Option(member.TV_TEN, member.TV_MA));
                });
            },
            error: function(error) {
                console.error('Lỗi khi lấy thành viên:', error);
            }
        });
    }
    </script>
</body>
</html>
