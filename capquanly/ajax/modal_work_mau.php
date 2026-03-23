
<?php
$sql = "SELECT * FROM donviphoihop ";
$result = mysqli_query($conn, $sql);
$phoihop = mysqli_fetch_all($result, MYSQLI_ASSOC); ?>


<div class="modal fade" id="modalWorkMau" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm công việc modal work mẫu</h5>
                <button type="button" class="close" data-dismiss="modal" onclick="$('#modalWorkMau').modal('hide');">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="workFormInsert_Mau">
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
                                <label>Ngày Bắt Đầu</label>
                                <input type="date" class="form-control form-control-sm" autocomplete="off" required name="start_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Ngày Kết Thúc</label>
                                <input type="date" class="form-control form-control-sm" autocomplete="off" required name="end_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal" onclick="$('#modalWorkMau').modal('hide');">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info" >Lưu</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Xử lý sự kiện submit form
    $('#workFormInsert_Mau').on('submit', function(e) {
        e.preventDefault(); // Ngăn chặn gửi form mặc định
        
        // Kiểm tra form hợp lệ
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return false;
        }
        
        // Lấy dữ liệu form
        var formData = new FormData(this);
        
        // Thêm action để xác định hành động
        formData.append('action', 'add_work');
        
        // Lưu nút submit gốc để khôi phục sau khi gửi
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalBtnText = $submitBtn.html();
        
        // Vô hiệu hóa nút submit và hiển thị loading
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
        
        // Gửi dữ liệu bằng AJAX
        $.ajax({
            url: 'ajax/update_work.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    var result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        // Hiển thị thông báo thành công
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Thêm công việc thành công!');
                        } else {
                            alert('Thêm công việc thành công!');
                        }
                        
                        // Reset form
                        const form = document.getElementById('workFormInsert');
                        if (form) {
                            form.reset();
                            form.classList.remove('was-validated');
                            const backdrops = document.getElementsByClassName('modal-backdrop');
                            while(backdrops[0]) {
                                backdrops[0].parentNode.removeChild(backdrops[0]);
                            }
                            
                            // Bỏ class modal-open khỏi body
                            document.body.classList.remove('modal-open');
                            document.body.style.paddingRight = '';
                            document.body.style.overflow = '';
                            
                            // Reset form
                            const form = document.getElementById('workFormInsert');
                            if (form) {
                                form.reset();
                                form.classList.remove('was-validated');
                            }
                        }
                        
                        // Tải lại dữ liệu hoặc trang
                        if (typeof loadWorks === 'function') {
                            loadWorks();
                        } else if (window.parent && typeof window.parent.loadWorks === 'function') {
                            window.parent.loadWorks();
                        } else {
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        }
                    } else {
                        // Hiển thị thông báo lỗi
                        var errorMsg = result.message || 'Có lỗi xảy ra khi thêm công việc!';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (e) {
                    console.error('Lỗi xử lý phản hồi:', e);
                    alert('Lỗi xử lý dữ liệu từ máy chủ!');
                }
            },
            error: function(xhr, status, error) {
                console.error('Lỗi AJAX:', status, error);
                alert('Không thể kết nối đến máy chủ!');
            },
            complete: function() {
                // Khôi phục trạng thái nút submit
                $submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
    
    // Đặt lại form khi modal đóng - Sử dụng JavaScript thuần
    const workModal = document.getElementById('modalWorkMau');
    if (workModal) {
        workModal.addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('workFormInsert');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }
            
            // Xóa instance modal để đảm bảo lần mở sau hoạt động đúng
            const modal = bootstrap.Modal.getInstance(workModal);
            if (modal) {
                modal.dispose();
            }
        });
    }
});
</script>
