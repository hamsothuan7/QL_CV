<?php
$sql = "SELECT * FROM donviphoihop ";
$result = mysqli_query($conn, $sql);
$phoihop = mysqli_fetch_all($result, MYSQLI_ASSOC); ?>

<div class="modal fade" id="modalInsert">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm công việc</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="projectFormInsert">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Tên công việc:</label>
                                <input type="text" class="form-control form-control-sm" name="name" required>
                            </div>

                            <div class="form-group">
                                <label>Dự án:</label>
                                <select name="project_id" class="form-control select2">
                                    <option value="NULL">--Chọn dự án--</option>
                                    <?php foreach ($projects as $item): ?>
                                        <option value="<?php echo $item["DA_MA"]; ?>"><?php echo $item["DA_TEN"]; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Giá trị giải ngân (VNĐ):</label>
                                <input type="number" class="form-control form-control-sm" 
                                       name="DSCV_GIATRIGIAINGAN" value="0" min="0" step="1">
                            </div>

                            <div class="form-group">
                                <label>Phòng ban:</label><br>
                                <select id="selectRoom" name="room_id" class="form-control selectRoom" required>
                                    <option value="">--Chọn phòng ban--</option>
                                    <?php foreach ($rooms as $item): ?>
                                        <option value="<?php echo $item["PB_MA"]; ?>"><?php echo $item["PB_TEN"]; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Thành viên :</label><br>
                                <select id="selectMember" name="member_id" class="form-control select2 selectMember" required>
                                    <option value="">--Chọn thành viên--</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Đơn vị</label><br>
                                <select id="selectph" name="ph_id" class="form-control selectph" >
                                    <option value="NULL">--Chọn đơn vị--</option>
                                    <?php foreach ($phoihop as $item): ?>
                                        <option value="<?php echo $item["PH_MA"]; ?>"><?php echo $item["PH_TEN"]; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- <div class="form-group">
                                <label>Công việc tiên quyết (nếu có):</label><br>
                                <select id="prerequisite_task" name="prerequisite_task" class="form-control select2" style="width: 100%;">
                                    <option value="">--Không có--</option>
                                </select>
                                <script>
                                $(document).ready(function() {
                                    // Khởi tạo select2
                                    $('#prerequisite_task').select2({
                                        placeholder: 'Chọn công việc tiên quyết',
                                        ajax: {
                                            url: 'ajax_work/get_available_tasks.php',
                                            dataType: 'json',
                                            delay: 250,
                                            data: function (params) {
                                                return {
                                                    q: params.term
                                                };
                                            },
                                            processResults: function (data) {
                                                if (data.status) {
                                                    return {
                                                        results: data.data
                                                    };
                                                }
                                                return { results: [] };
                                            },
                                            cache: true
                                        },
                                        minimumInputLength: 0
                                    });
                                });
                                </script>
                            </div> -->

                            <div class="form-group">
                                <label>Ngày bắt đầu:</label><br>
                                <input type="date" class="form-control" id="start_date_input" name="start_date" placeholder="Nhập ngày bắt đầu..." autocomplete="off" required>
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

                            <!-- Trường ẩn chứa ngày kết thúc sẽ được tính toán -->
                            <input type="hidden" id="end_date" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info">Lưu</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Xử lý sự kiện submit form
    $('#projectFormInsert').on('submit', function(e) {
        e.preventDefault();
        
        // Lấy ngày bắt đầu và số ngày thực hiện
        let currentDate = new Date($('#start_date_input').val());
        const duration = parseInt($('input[name="duration"]').val());
        let workingDays = 0;
        
        // Tính toán ngày kết thúc bỏ qua thứ 7 và chủ nhật
        while (workingDays < duration) {
            // Kiểm tra nếu không phải thứ 7 (6) và chủ nhật (0)
            if (currentDate.getDay() !== 0 && currentDate.getDay() !== 6) {
                workingDays++;
            }
            
            // Nếu chưa đủ số ngày làm việc, tăng thêm 1 ngày
            if (workingDays < duration) {
                currentDate.setDate(currentDate.getDate() + 1);
            }
        }
        
        // Định dạng ngày kết thúc thành YYYY-MM-DD
        const formattedEndDate = currentDate.toISOString().split('T')[0];
        
        // Cập nhật giá trị trường ẩn end_date
        $('#end_date').val(formattedEndDate);
        
        // Gửi form
        const formData = new FormData(this);
        
        $.ajax({
            url: 'ajax_work/insert_project.php', // Đảm bảo đúng đường dẫn
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                // Disable button
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status) {
                        alert('Thêm công việc thành công!');
                        $('#modalInsert').modal('hide');
                        location.reload();
                    } else {
                        alert('Có lỗi xảy ra: ' + (result.message || 'Không thể thêm công việc'));
                    }
                } catch (e) {
                    console.error('Lỗi parse JSON:', e);
                    console.error('Response gốc:', response);
                    alert('Có lỗi xảy ra khi xử lý phản hồi từ máy chủ');
                }
            },
            error: function(xhr, status, error) {
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                alert('Lỗi kết nối: ' + error);
            }
        });
    });
});
</script> 