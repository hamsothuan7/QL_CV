<?php
define('BASE_URL', dirname(__DIR__, 2));
include BASE_URL.'/config.php';


// Lấy danh sách phòng ban
$sql_rooms = "SELECT * FROM phongban";
$result_rooms = mysqli_query($conn, $sql_rooms);
$rooms = mysqli_fetch_all($result_rooms, MYSQLI_ASSOC);

// Lấy danh sách thành viên
$sql_members = "SELECT TV_MA, TV_TEN FROM thanhvien WHERE active = 0";
$result_members = mysqli_query($conn, $sql_members);
$members = mysqli_fetch_all($result_members, MYSQLI_ASSOC);

// Lấy danh sách đơn vị phối hợp
$sql_phoihop = "SELECT * FROM donviphoihop";
$result_phoihop = mysqli_query($conn, $sql_phoihop);
$phoihop = mysqli_fetch_all($result_phoihop, MYSQLI_ASSOC);

// Danh sách công việc tiên quyết sẽ được tải động qua AJAX
$prerequisite_tasks = [];
?>


<div class="modal fade" id="modalWork" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm công việc</h5>
                <button type="button" class="close custom-x-btn" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="post" id="workFormInsert">
                <div class="modal-body" >
                    <input type="hidden" name="code" id="inputCodeWork" >
                    <input type="hidden" name="parent" id="inputCodeParent" >
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Tên công việc:</label><br>
                                <input type="text" class="form-control" name="name" placeholder="Nhập tên công việc..." autocomplete="off" required>
                            </div>
                            <div class="form-group">
                                <label>Phòng ban:</label><br>
                                <select id="selectRoom" name="room_id" class="form-control">
                                    <option value="">--Chọn phòng ban--</option>
                                    <?php if(isset($rooms) && is_array($rooms)): ?>
                                        <?php foreach($rooms as $item): ?>
                                            <option value="<?php echo $item["PB_MA"]; ?>" ><?php echo $item["PB_TEN"]; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Thành viên :</label><br>
                                <select id="selectMember2" name="member_id" class="form-control select2" required>
                                    <option value="">--Chọn thành viên--</option>
                                    <?php if(isset($members) && is_array($members)): ?>
                                        <?php foreach($members as $item): ?>
                                            <option value="<?php echo $item["TV_MA"]; ?>" ><?php echo $item["TV_TEN"]; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Đơn vị:</label><br>
                                <select id="selectph" name="ph_id" class="form-control">
                                    <option value="">--Chọn đơn vị--</option>
                                    <?php if(isset($phoihop) && is_array($phoihop)): ?>
                                        <?php foreach($phoihop as $item): ?>
                                            <option value="<?php echo $item["PH_MA"]; ?>" ><?php echo $item["PH_TEN"]; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Ngày bắt đầu:</label><br>
                                <input type="date" class="form-control" name="start_date" placeholder="Nhập ngày bắt đầu..." autocomplete="off" required>
                            </div>

                            <div class="form-group">
                                <label>Thời gian hoàn thành (số ngày):</label><br>
                                <input type="number" class="form-control" name="duration" placeholder="Nhập số ngày..." autocomplete="off" required min="1">
                            </div>

                            <div class="form-group">
                                <label>Công việc tiên quyết:</label><br>
                                <select name="prerequisite_task" id="prerequisiteTask" class="form-control select2">
                                    <option value="">--Không có--</option>
                                    <!-- Danh sách công việc sẽ được tải động qua AJAX -->
                                </select>
                            </div>
                            
                            <!-- Trường ẩn chứa trạng thái -->
                            <input type="hidden" name="status" id="workStatus" value="2">
                            
                            <script>
                            // Hàm tải danh sách công việc tiên quyết
                            function loadPrerequisiteTasks(projectCode) {
                                if (!projectCode) {
                                    console.error('Không có mã dự án');
                                    return;
                                }
                                
                                console.log('Đang tải công việc cho dự án:', projectCode);
                                
                                $.ajax({
                                    url: 'ajax/get_prerequisite_tasks.php',
                                    type: 'GET',
                                    data: { 
                                        project_code: projectCode,
                                        _: new Date().getTime() // Tránh cache
                                    },
                                    dataType: 'json',
                                    success: function(response) {
                                        console.log('Kết quả trả về:', response);
                                        if (response && response.status && response.data) {
                                            const select = $('#prerequisiteTask');
                                            // Lưu giá trị đang chọn
                                            const selectedValue = select.val();
                                            // Xóa tất cả option trừ option đầu tiên
                                            select.find('option:not(:first)').remove();
                                            // Thêm các option mới
                                            response.data.forEach(function(task) {
                                                select.append(new Option(task.text, task.id, false, task.id === selectedValue));
                                            });
                                            // Kích hoạt lại select2
                                            select.trigger('change');
                                            console.log('Đã cập nhật danh sách công việc tiên quyết');
                                        } else {
                                            console.error('Dữ liệu trả về không hợp lệ:', response);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Lỗi khi tải danh sách công việc:', {
                                            status: xhr.status,
                                            statusText: xhr.statusText,
                                            responseText: xhr.responseText
                                        });
                                    }
                                });
                            }
                            
                            $(document).ready(function() {
                                // Tải danh sách công việc tiên quyết khi mở modal
                                $('#modalWork').on('shown.bs.modal', function (e) {
                                    const projectCode = $('#inputCodeWork').val();
                                    console.log('Modal opened, project code:', projectCode);
                                    if (projectCode) {
                                        loadPrerequisiteTasks(projectCode);
                                    } else {
                                        console.error('Không tìm thấy mã dự án');
                                    }
                                });
                                // Hàm cập nhật trạng thái
                                function updateStatus() {
                                    if ($('#prerequisiteTask').val() === '') {
                                        // Nếu không có công việc tiên quyết, đặt trạng thái 1 (Đang tiến hành)
                                        $('#workStatus').val('1');
                                    } else {
                                        // Nếu có công việc tiên quyết, đặt trạng thái 5 (Chưa tiếp nhận)
                                        $('#workStatus').val('5');
                                    }
                                    //console.log('Trạng thái đã cập nhật thành:', $('#workStatus').val());
                                }

                                // Xử lý khi thay đổi công việc tiên quyết
                                $('#prerequisiteTask').on('change', updateStatus);
                                
                                // Khởi tạo giá trị ban đầu
                                updateStatus();

                                // Kiểm tra trước khi submit form
                                $('#workFormInsert').on('submit', function(e) {
                                    //console.log('Đang gửi form với trạng thái:', $('#workStatus').val());
                                    //console.log('Công việc tiên quyết:', $('#prerequisiteTask').val());
                                    
                                    // Đảm bảo cập nhật trạng thái trước khi gửi
                                    updateStatus();
                                    
                                    // Kiểm tra xem trạng thái đã được đặt chưa
                                    if (!$('#workStatus').val()) {
                                        console.error('Lỗi: Chưa đặt trạng thái!');
                                        e.preventDefault();
                                        return false;
                                    }
                                    
                                    return true;
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info" id="saveWorkBtn">Lưu</button>
                </div>
            </form>

        </div>
    </div>
</div>

