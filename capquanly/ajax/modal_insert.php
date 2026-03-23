<?php
session_start();
include('../config.php');

// Khởi tạo các biến
$userCode = $_SESSION['code'] ?? '';
$userName = $_SESSION['username'] ?? '';
$userDepartment = '';

// Lấy thông tin phòng ban và quyền người dùng nếu đã đăng nhập
if (!empty($userCode)) {
    $sql = "SELECT tv.PB_MA, tv.NND_MA FROM thanhvien tv WHERE tv.TV_MA = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $userCode);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userDepartment = $row['PB_MA'] ?? '';
            $userRole = $row['NND_MA'] ?? 0;
        }
        $stmt->close();
    }
} else {
    die('Vui lòng đăng nhập để sử dụng chức năng này');
}
?>

<div class="modal fade" id="modalInsert" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm dự án</h5>
                <button type="button" class="close" id="btnCloseModalInsert" data-dismiss="modal" aria-label="Close" tabindex="0" role="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="projectFormInsert" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="status" id="inputStatus">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="inputProjectName">Tên Dự Án :</label>
                                <input type="text" class="form-control form-control-sm" id="inputProjectName" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="inputStartDate">Ngày Bắt Đầu</label>
                                <input type="date" class="form-control form-control-sm" id="inputStartDate" autocomplete="off" required
                                name="start_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="inputTotalInvestment">Tổng mức đầu tư (VNĐ)</label>
                                <input type="number" class="form-control form-control-sm" id="inputTotalInvestment" 
                                name="DA_TONGMUCDAUTU" value="0" min="0" step="1">
                            </div>
                            <div class="form-group">
                                <label for="inputDepartment">Phòng ban</label>
                                <select class="form-control form-control-sm" id="inputDepartment" name="department" <?php echo (isset($userRole) && $userRole == 3) ? 'disabled' : ''; ?>>
                                    <option value="">-- Chọn phòng ban --</option>
                                    <?php
                                    // Lấy danh sách phòng ban
                                    $sql = "SELECT PB_MA, PB_TEN FROM phongban ORDER BY PB_TEN ASC";
                                    $result = mysqli_query($conn, $sql);
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo '<option value="' . $row['PB_MA'] . '"' . ($row['PB_MA'] == $userDepartment ? ' selected' : '') . ' data-pbma="' . $row['PB_MA'] . '">' . $row['PB_TEN'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <input type="hidden" name="pb_ma" id="inputPBMA" value="<?php echo htmlspecialchars($userDepartment); ?>">
                            </div>
                            <div class="form-group">
                                <label for="inputLeader">Người phụ trách</label>
                                <select class="form-control form-control-sm" id="inputLeader" name="leader" <?php echo (empty($userDepartment) || (isset($userRole) && $userRole == 3)) ? 'disabled' : ''; ?>>
                                    <option value="">-- Đang tải danh sách thành viên --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" id="btnDismissModalInsert" data-dismiss="modal" tabindex="0" role="button">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Hàm tải thành viên theo phòng ban
function loadMembersByDepartment(departmentId, selectUserId = '') {
    console.log('Loading members for department:', departmentId, 'Select user:', selectUserId);
    
    if (!departmentId) {
        console.log('No department ID provided');
        $('#inputLeader').html('<option value="">-- Vui lòng chọn phòng ban trước --</option>').prop('disabled', true);
        return;
    }
    
    // Hiển thị trạng thái đang tải
    $('#inputLeader').html('<option value="">Đang tải danh sách thành viên...</option>').prop('disabled', true);
    
    // Gửi yêu cầu AJAX để lấy danh sách thành viên
    $.ajax({
        url: '../capquanly/fetch_thanhvien.php',
        type: 'GET',
        data: { phongban_id: departmentId },
        dataType: 'json',
        success: function(response) {
            console.log('Received member list:', response);
            var options = '<option value="">-- Chọn người phụ trách --</option>';
            var userFound = false;
            
            if (response && response.length > 0) {
                $.each(response, function(index, member) {
                    var selected = '';
                    // Nếu có selectUserId và khớp với TV_MA hiện tại
                    if (selectUserId && member.TV_MA == selectUserId) {
                        selected = 'selected';
                        userFound = true;
                    }
                    options += '<option value="' + member.TV_MA + '" ' + selected + '>' + member.TV_TEN + '</option>';
                });
                
                // Nếu không tìm thấy người dùng trong danh sách (có thể do phòng ban thay đổi)
                if (selectUserId && !userFound) {
                    options = '<option value="' + selectUserId + '" selected><?php echo addslashes($userName); ?> </option>' + options;
                }
                
                $('#inputLeader').html(options);
                // Only enable if user is not NND_MA = 3
                var isUserRole3 = <?php echo (isset($userRole) && $userRole == 3) ? 'true' : 'false'; ?>;
                if (!isUserRole3) {
                    $('#inputLeader').prop('disabled', false);
                }
            } else {
                // Nếu không có thành viên nào nhưng có selectUserId (trường hợp đặc biệt)
                if (selectUserId) {
                    options = '<option value="' + selectUserId + '" selected><?php echo addslashes($userName); ?> </option>';
                    $('#inputLeader').html(options);
                    // Only enable if user is not NND_MA = 3
                    var isUserRole3 = <?php echo (isset($userRole) && $userRole == 3) ? 'true' : 'false'; ?>;
                    if (!isUserRole3) {
                        $('#inputLeader').prop('disabled', false);
                    }
                } else {
                    $('#inputLeader').html('<option value="">Không có thành viên nào trong phòng ban này</option>').prop('disabled', true);
                }
            }
        },
        error: function() {
            // Nếu có lỗi nhưng có selectUserId, vẫn hiển thị người dùng hiện tại
            if (selectUserId) {
                var options = '<option value="' + selectUserId + '" selected><?php echo addslashes($userName); ?> </option>';
                $('#inputLeader').html(options);
                // Only enable if user is not NND_MA = 3
                var isUserRole3 = <?php echo (isset($userRole) && $userRole == 3) ? 'true' : 'false'; ?>;
                if (!isUserRole3) {
                    $('#inputLeader').prop('disabled', false);
                }
            } else {
                $('#inputLeader').html('<option value="">Lỗi khi tải danh sách thành viên</option>').prop('disabled', true);
            }
        }
    });
}

// Đảm bảo modal đóng mở đúng chuẩn, tránh lỗi khi load động hoặc xung đột
$(function() {
        // Hiển thị thông tin debug
    $('#debugInfo').removeClass('d-none');
    
        // Tự động tải thành viên nếu đã có phòng ban
    var initialDepartmentId = '<?php echo $userDepartment; ?>';
    var userCode = '<?php echo $userCode; ?>';
    
    console.log('Initial Department:', initialDepartmentId);
    console.log('User Code:', userCode);
    
    if (initialDepartmentId && initialDepartmentId !== '') {
        // Cập nhật giá trị selected cho dropdown phòng ban
        $('#inputDepartment').val(initialDepartmentId);
        // Tải danh sách thành viên
        loadMembersByDepartment(initialDepartmentId, '<?php echo $userCode; ?>');
    }

    // Xử lý khi thay đổi phòng ban
    $('#inputDepartment').on('change', function() {
        const departmentId = $(this).val();
        const selectedOption = $(this).find('option:selected');
        const pbMa = selectedOption.data('pbma') || '';
        
        // Cập nhật giá trị PB_MA ẩn
        $('#inputPBMA').val(pbMa);
        
        // Tải danh sách thành viên
        loadMembersByDepartment(departmentId);
    });
    // Đảm bảo chỉ có 1 modalInsert trong DOM
    if ($('body #modalInsert').length > 1) {
        $('body #modalInsert').not(':first').remove();
    }

    // Xử lý phím ESC cho đóng modal
    $('#projectFormInsert').on('submit', function(e) {
        e.preventDefault();
        
        // Lấy giá trị PB_MA từ select box
        const departmentSelect = $('#inputDepartment');
        const pbMa = departmentSelect.find('option:selected').data('pbma') || '';
        
        // Thêm PB_MA vào dữ liệu form
        const formData = $(this).serialize() + '&pb_ma=' + encodeURIComponent(pbMa);
        
        // Gửi yêu cầu AJAX để thêm dự án
        $.ajax({
            url: '../capquanly/ajax_insert_project.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Received response:', response);
                if (response.success) {
                    // Đóng modal và reload trang
                    $('#modalInsert').modal('hide');
                    location.reload();
                } else {
                    // Hiển thị lỗi
                    alert('Lỗi khi thêm dự án: ' + response.message);
                }
            },
            error: function() {
                // Hiển thị lỗi
                alert('Lỗi khi thêm dự án');
            }
        });
    });

    // Gắn sự kiện đóng thủ công cho nút 'x' và nút Đóng nếu Bootstrap bị lỗi
    $('#btnCloseModalInsert, #btnDismissModalInsert').off('click').on('click', function(e) {
        // Ưu tiên gọi Bootstrap modal
        if ($.fn.modal && typeof $('#modalInsert').modal === 'function') {
            $('#modalInsert').modal('hide');
        } else {
            // Nếu Bootstrap bị lỗi, ẩn thủ công
            $('#modalInsert').removeClass('show').hide();
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }
    });

    // Đảm bảo modal thực sự đóng khi hidden.bs.modal
    $('#modalInsert').on('hidden.bs.modal', function () {
        $(this).removeClass('show').hide();
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    });

    // Xử lý phím ESC cho đóng modal
    $('#modalInsert').on('keydown', function(e) {
        if (e.key === 'Escape') {
            if ($.fn.modal && typeof $('#modalInsert').modal === 'function') {
                $('#modalInsert').modal('hide');
            } else {
                $('#modalInsert').removeClass('show').hide();
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        }
    });

    // Debug: log trạng thái modal và sự kiện click
    $('#btnCloseModalInsert, #btnDismissModalInsert').on('click', function() {
        console.log('Đã click nút đóng modalInsert');
    });
});

</script>
