<style>
    /* Modal full height, 1/3 width */
    #modalChonMau .modal-dialog {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        margin: 0;
        width: 70%;
        max-width: 820px;
        min-width: 500px;
        height: 100vh;
        max-height: 100vh;
    }
    #modalChonMau .modal-content {
        height: 100%;
        border: none;
        border-radius: 0;
    }
    #modalChonMau .modal-body {
        overflow-y: auto;
        padding: 20px;
    }
    /* Tùy chỉnh bảng */
    #modalChonMau .table-responsive {
        min-height: 300px;
    }
    /* Đảm bảo dòng 'Không có mẫu nào' có chiều cao tối thiểu */
    #modalChonMau tbody tr td[colspan] {
        height: 300px;
        vertical-align: middle;
        text-align: center;
    }
    
    /* Tùy chỉnh modal chi tiết mẫu công việc */
    #modalChiTietMau .modal-dialog {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        margin: 0;
        width: 122%;
        max-width: 1000px;
        min-width: 600px;
        height: 100vh;
        max-height: 100vh;
    }
    #modalChiTietMau .modal-content {
        height: 100%;
        border: none;
        border-radius: 0;
    }
    #modalChiTietMau .modal-body {
        overflow-y: auto;
        padding: 20px;
    }
</style>

<!-- Modal chọn mẫu công việc -->
<div class="modal fade" id="modalChonMau" tabindex="-1" role="dialog" aria-labelledby="modalChonMauLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 1015px !important;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChonMauLabel">Chọn mẫu công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
    <!-- Thanh tìm kiếm mẫu công việc -->
    <div class="form-group">
        <input type="text" class="form-control" id="searchMauCV" placeholder="Tìm kiếm tên mẫu công việc...">
    </div>
                <?php
                include(__DIR__ . '/../../config.php');
                // Lấy danh sách mẫu công việc đang hoạt động, sắp xếp theo tên
                $sql = "SELECT mamau, tenmau, macvmau FROM maucv WHERE trangthai = 1 ORDER BY tenmau ASC";
                $result = mysqli_query($conn, $sql);
                if (!$result) {
                    echo "<tr><td colspan='4' class='text-danger'>Lỗi truy vấn: " . mysqli_error($conn) . "</td></tr>";
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 200px;">Tên mẫu</th>
                                <th style="width: 120px;">Thao tác</th>
                                <!-- s<th style="width: 90px;">Chọn</th> -->
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): $i=1; while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['tenmau']); ?><br>
                                    <!--<small class="text-muted">Mã: <?php echo $row['mamau']; ?></small>-->
                                </td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm xem-chi-tiet" data-mamau="<?php echo (int)$row['mamau']; ?>">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </button>
                                </td>
                                <!-- <td>
                                    <button type="button" class="btn btn-primary btn-sm chon-mau-btn" 
                                        data-mamau="<?php echo (int)$row['mamau']; ?>" 
                                        data-tenmau="<?php echo htmlspecialchars($row['tenmau']); ?>" 
                                        data-cvmau="<?php echo htmlspecialchars($row['macvmau']); ?>">
                                        Chọn
                                    </button>
                                </td> -->
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center">Không có mẫu nào hoạt động</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
<!-- Modal xem chi tiết công việc mẫu -->
<div class="modal fade" id="modalChiTietMau" tabindex="-1" role="dialog" aria-labelledby="modalChiTietMauLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChiTietMauLabel">Chi tiết công việc mẫu: <span id="tenMauHienTai"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
                
                <!-- Phần chọn người phụ trách chung -->
                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label">Phòng ban:</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="phongBanChon" autocomplete="off">
                            <option value="">-- Chọn phòng ban --</option>
                            <?php
                            $sql_pb = "SELECT * FROM phongban ORDER BY PB_TEN";
                            $result_pb = mysqli_query($conn, $sql_pb);
                            while ($row_pb = mysqli_fetch_assoc($result_pb)) {
                                echo '<option value="' . $row_pb['PB_MA'] . '">' . $row_pb['PB_TEN'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label">Người phụ trách:</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="nguoiPhuTrachChung" disabled autocomplete="off">
                            <option value="">-- Vui lòng chọn phòng ban trước --</option>
                        </select>
                    </div>
                </div>
                <!-- Thêm trường nhập Tên gói thầu -->
                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label">Tên gói thầu:</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="tenGoiThauInput" placeholder="Nhập tên gói thầu...">
                    </div>
                </div>
                <!-- Input ẩn để lưu trữ mã mẫu -->
                <input type="hidden" id="hiddenMamau" value="">
                
                <!-- Thêm trường chọn ngày bắt đầu -->
                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label">Ngày bắt đầu:</label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control" id="ngayBatDau" required>
                    </div>
                </div>
                
                <div class="form-container" id="chiTietMauContainer">
                    <!-- Nội dung sẽ được điền bằng JavaScript -->
                    <div class="text-center py-4" id="loadingIndicator">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải dữ liệu...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnXacNhanCongViec">
                    <i class="fas fa-check"></i> Xác nhận công việc
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
// Hàm tải danh sách người phụ trách theo phòng ban
function loadNguoiPhuTrach(phongBanId, targetElement) {
    if (!phongBanId) {
        targetElement.html('<option value="">-- Vui lòng chọn phòng ban trước --</option>').prop('disabled', true);
        return;
    }
    
    // Lưu lại giá trị đã chọn (nếu có)
    const currentValue = targetElement.val();
    
    // Hiển thị loading
    targetElement.html('<option value="">Đang tải danh sách...</option>').prop('disabled', true);
    
    // Gọi AJAX để lấy danh sách thành viên
    $.ajax({
        url: 'ajax_nguoiphutrach.php',
        type: 'GET',
        data: { 
            action: 'get_thanh_vien',
            pb_ma: phongBanId
        },
        dataType: 'json',
        success: function(response) {
            let options = '<option value="">Chọn người phụ trách</option>';
            
            if (response.status === 'success' && response.data && response.data.length > 0) {
                // Thêm các option mới
                response.data.forEach(function(thanhVien) {
                    const selected = (thanhVien.TV_MA === currentValue) ? 'selected' : '';
                    options += `<option value="${thanhVien.TV_MA}" ${selected}>${thanhVien.TV_TEN} (${thanhVien.TV_MA})</option>`;
                });
            } else {
                options = '<option value="">Không tìm thấy thành viên nào</option>';
            }
            
            // Cập nhật dropdown
            targetElement.html(options);
            
            // Khôi phục giá trị đã chọn (nếu có)
            if (currentValue) {
                targetElement.val(currentValue);
            }
            
            // Kích hoạt lại dropdown
            targetElement.prop('disabled', false);
        },
        error: function() {
            targetElement.html('<option value="">Lỗi khi tải danh sách</option>');
            targetElement.prop('disabled', false);
        }
    });
}

// Xử lý khi chọn phòng ban
$(document).on('change', '#phongBanChon', function() {
    // Nếu trường này từng bị đánh dấu lỗi, bỏ lỗi khi chọn lại
    $(this).removeClass('is-invalid');
    $('#nguoiPhuTrachChung').removeClass('is-invalid');
    
    const pbMa = $(this).val();
    const $nguoiPTSelect = $('#nguoiPhuTrachChung');
    
    if (!pbMa) {
        $nguoiPTSelect.prop('disabled', true).html('<option value="">-- Vui lòng chọn phòng ban trước --</option>');
        // Vô hiệu hóa tất cả dropdown người phụ trách trong bảng
        $('.cv-assignee').prop('disabled', true).html('<option value="">-- Vui lòng chọn phòng ban --</option>');
        return;
    }
    
    // Hiển thị loading
    $nguoiPTSelect.prop('disabled', true).html('<option value="">Đang tải danh sách thành viên...</option>');
    
    // Gọi AJAX để lấy danh sách thành viên
    $.ajax({
        url: 'ajax/get_thanhvien_by_phongban.php',
        type: 'GET',
        data: { 
            pb_ma: pbMa
        },
        dataType: 'json',
        success: function(response) {
            console.log('Response from server:', response); // Debug log
            if (response.status === 'success' && response.data && Array.isArray(response.data)) {
                let options = '<option value="">Chọn người phụ trách</option>';
                
                // Thêm các option mới
                response.data.forEach(function(thanhVien) {
                    const selected = (thanhVien.TV_MA === $nguoiPTSelect.val()) ? 'selected' : '';
                    options += `<option value="${thanhVien.TV_MA}" ${selected}>${thanhVien.TV_TEN} (${thanhVien.TV_MA})</option>`;
                });
                
                // Cập nhật dropdown chung
                $nguoiPTSelect.html(options);
                
                // Cập nhật tất cả dropdown người phụ trách trong bảng
                $('.cv-assignee').each(function() {
                    const $select = $(this);
                    const currentValue = $select.data('current-value');
                    
                    // Tạo options mới
                    $select.html(options);
                    
                    // Khôi phục giá trị đã chọn (nếu có)
                    if (currentValue) {
                        $select.val(currentValue);
                    }
                });
            } else {
                $nguoiPTSelect.html('<option value="">Không tìm thấy thành viên nào</option>');
                $('.cv-assignee').prop('disabled', true).html('<option value="">Không có dữ liệu</option>');
            }
            
            $nguoiPTSelect.prop('disabled', false);
        },
        error: function() {
            $nguoiPTSelect.html('<option value="">Lỗi khi tải danh sách</option>');
            $nguoiPTSelect.prop('disabled', false);
            $('.cv-assignee').prop('disabled', true).html('<option value="">Lỗi tải dữ liệu</option>');
        }
    });
});

// Hàm đóng modal tương thích với Bootstrap 4
function closeModal(modalId) {
    var $modal = $('#' + modalId);
    if ($.fn.modal) { // Kiểm tra xem hàm modal có tồn tại không
        $modal.modal('hide');
    } else {
        // Fallback nếu không có Bootstrap JS
        $modal.removeClass('show');
        $modal.css('display', 'none');
    }
}

// Xử lý sự kiện click cho nút đóng modal
$(document).on('click', '[data-dismiss="modal"], .btn-close', function(e) {
    e.preventDefault();
    var $modal = $(this).closest('.modal');
    if ($modal.length) {
        closeModal($modal.attr('id'));
    }
});

// Xử lý click ra ngoài modal để đóng
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        closeModal($(this).attr('id'));
    }
});


// Xử lý mở modal xem chi tiết
$(document).on('click', '.xem-chi-tiet', function() {
    // Lấy giá trị từ thuộc tính data-mamau
    var $button = $(this);
    var mamau = $button.data('mamau');
    var tenmau = $button.closest('tr').find('td:nth-child(2)').text().trim();
    
    // Log để kiểm tra
    console.log('=== XEM CHI TIẾT ===');
    console.log('Mã mẫu từ data-mamau:', mamau);
    console.log('Tên mẫu:', tenmau);
    
    // Cập nhật tên mẫu và lưu mã mẫu vào input ẩn
    $('#hiddenMamau').val(mamau);
    $('#modalChiTietMau #tenMauHienTai').text(tenmau);
    
    console.log('Đã lưu mã mẫu vào input ẩn:', mamau);
    
    // Hiển thị loading
    $('#loadingIndicator').show();
    $('#chiTietMauContainer').html(`
        <div class="text-center py-4" id="loadingIndicator">
            <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
    <span class="sr-only">Đang tải...</span>
            </div>
            <p class="mt-2">Đang tải dữ liệu...</p>
        </div>
    `);
    
    // Log kiểm tra giá trị trước khi gửi AJAX
    console.log('=== GỬI YÊU CẦU XEM CHI TIẾT ===');
    console.log('Mã mẫu gửi đi:', mamau, '(kiểu dữ liệu:', typeof mamau + ')');
    
    // Gọi AJAX để lấy chi tiết công việc mẫu
    $.ajax({
        url: 'ajax/get_chitiet_maucv.php',
        type: 'GET',
        data: { mamau: mamau },
        beforeSend: function() {
            console.log('Đang gửi yêu cầu xem chi tiết cho mã mẫu:', mamau);
        },
        dataType: 'json',
        success: function(response) {
            var container = $('#chiTietMauContainer');
            container.empty();
            
            if (response.status === 'success' && response.data.length > 0) {
                var html = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">STT</th>
                                <th width="45%">Tên công việc</th>
                                <th width="15%">Thời gian dự kiến (ngày)</th>
                                <th width="15%">Công việc tiên quyết (nếu có)</th>
                                <th width="35%">Người phụ trách</th>
                                <th width="10%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                $.each(response.data, function(index, item) {
                    const selected = item.nguoi_phu_trach ? 'selected' : '';
                    const selectedText = item.ten_nguoi_phu_trach ? ` (${item.ten_nguoi_phu_trach})` : '';
                    
                    html += `
                            <tr data-cv-id="${item.id || ''}">
                                <td class="align-middle">${index + 1}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm cv-name" value="${item.ten_cv || ''}" data-field="ten_cv">
                                </td>
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm cv-duration" value="${item.thoi_gian_du_kien || '0'}" data-field="thoi_gian_du_kien">
                                </td>
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm cv-prereq" value="${item.prereq || ''}" 
                                           placeholder="Nhập STT" data-field="prereq">
                                </td>
                                <td>
                                    <select class="form-control form-control-sm cv-assignee" data-field="nguoi_phu_trach" data-current-value="${item.nguoi_phu_trach || ''}">
                                        <option value="">Chọn người phụ trách</option>`;
                    
                                        // Thêm option đã chọn nếu có
                                        if (item.nguoi_phu_trach) {
                                            html += `
                                                            <option value="${item.nguoi_phu_trach}" selected>${item.ten_nguoi_phu_trach || 'Đang tải...'}</option>`;
                                        }
                                        
                                        html += `
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-xoa-cv" title="Xóa công việc">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>`;
                });
                
                container.html(html);
                    
            } else {
                container.html('<div class="alert alert-warning text-center">Không có dữ liệu công việc</div>');
            }
            
            // Thêm sự kiện xóa công việc
            $(document).off('click', '.btn-xoa-cv').on('click', '.btn-xoa-cv', function(e) {
                e.stopPropagation(); // Ngăn sự kiện nổi bọt
                if (confirm('Bạn có chắc chắn muốn xóa công việc này?')) {
                    $(this).closest('tr').remove();
                    // Cập nhật lại STT các dòng
                    $('table tbody tr').each(function(index) {
                        $(this).find('td:first').text(index + 1);
                    });
                }
                return false; // Ngăn hành động mặc định
            });
        },
        error: function() {
            $('#chiTietMauContainer').html('<div class="alert alert-danger text-center">Không thể kết nối đến máy chủ</div>');
        }
    });
    
    // Hiển thị modal
    $('#modalChiTietMau').modal('show');
});

// Lọc realtime theo tên mẫu công việc
$(document).on('keyup', '#searchMauCV', function() {
    var filter = $(this).val().toLowerCase();
    var $table = $(this).closest('.modal-body').find('table');
    var $rows = $table.find('tbody tr');
    
    $rows.each(function() {
        var $row = $(this);
        var tenmau = $row.find('td').eq(1).text().toLowerCase();
        if (tenmau.indexOf(filter) > -1) {
            $row.show();
        } else {
            $row.hide();
        }
    });
});
        // Gửi AJAX khi ấn nút chọn
        $(document).off('click', '.chon-mau-btn').on('click', '.chon-mau-btn', function(e) {
            // Ngăn chặn hành vi mặc định của nút
            e.preventDefault();
            e.stopPropagation();
            
            // Lấy dữ liệu từ nút được click
            var $button = $(this);
            
            // Lấy mã mẫu từ thuộc tính data-mamau của nút
            var mamau = $button.attr('data-mamau');
            
            // Lấy tên mẫu và cvmau từ thuộc tính data
            var tenmau = $button.attr('data-tenmau');
            var cvmau = $button.attr('data-cvmau');
            
            // Log để kiểm tra
            console.log('Mã mẫu từ data-attribute:', mamau);
            console.log('Tên mẫu:', tenmau);
            
            // Kiểm tra dữ liệu
            if (!mamau || !tenmau) {
                console.error('Thiếu dữ liệu mẫu');
                alert('Có lỗi xảy ra khi chọn mẫu. Vui lòng thử lại.');
                return;
            }
            
            // Kiểm tra dữ liệu
            if (!mamau || !tenmau) {
                console.error('Thiếu dữ liệu mẫu');
                alert('Có lỗi xảy ra khi chọn mẫu. Vui lòng thử lại.');
                return;
            }
            
            // Kiểm tra giá trị trước khi gửi
            var duanMa = window.parent.DUAN_MA_MODAL;
            if (!duanMa) {
                alert('Không tìm thấy mã dự án. Vui lòng tải lại trang và thử lại.');
                $button.prop('disabled', false).html('Chọn');
                return;
            }
            
            // Log kiểm tra giá trị trước khi gửi
            console.log('=== GIÁ TRỊ TRƯỚC KHI GỬI ===');
            console.log('Mã dự án:', duanMa);
            console.log('Mã mẫu:', mamau, '(kiểu dữ liệu:', typeof mamau + ')');
            console.log('Tên mẫu:', tenmau);
            console.log('Công việc mẫu:', cvmau);
            
            console.log('Dữ liệu gửi đi:', {
                duan_ma: duanMa,
                mamau: mamau,
                tenmau: tenmau,
                cvmau: cvmau
            });
            
            // Chuyển đổi đối tượng cvmau thành chuỗi JSON
            var cvmauJson = typeof cvmau === 'object' ? JSON.stringify(cvmau) : cvmau;
            
            // Lấy giá trị tên gói thầu
            var tenGoiThau = $('#tenGoiThauInput').val();
            if (!tenGoiThau || tenGoiThau.trim() === '') tenGoiThau = null;
            
            // Gửi AJAX để lưu mẫu đã chọn
            $.ajax({
                url: 'ajax/insert_duan_maucv.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    duan_ma: duanMa,
                    mamau: mamau,
                    tenmau: tenmau,
                    cvmau: cvmauJson,
                    tengoithau: tenGoiThau
                },
                beforeSend: function() {
                    console.log('Dữ liệu gửi đi (sau khi xử lý):', {
                        duan_ma: duanMa,
                        mamau: mamau,
                        tenmau: tenmau,
                        cvmau: cvmauJson,
                        tengoithau: tenGoiThau
                    });
                },
                success: function(response) {
                    console.log('Phản hồi từ server:', response);
                    if (response.status === 'success') {
                        alert('Đã lưu thành công ' + response.saved + ' công việc.');
                        $('#modalChiTietMau').modal('hide');
                        $('#modalChonMau').modal('hide');
                        // Reload lại modaleditinner để nhận thông tin mẫu mới
                        var maDuAn = window.DUAN_MA_MODAL || $('input[name="mada"]').val();
                        console.log('Reload modaleditinner với mã dự án:', maDuAn);
                        if (typeof loadViewEdit === 'function' && maDuAn) {
                            console.log('Gọi loadViewEdit trực tiếp');
                            loadViewEdit(maDuAn);
                        } else {
                            console.log('Không tìm thấy hàm loadViewEdit ở local');
                        }
                    } else {
                        alert('Có lỗi xảy ra: ' + (response.message || 'Lỗi không xác định'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', status, error);
                    console.error('Phản hồi lỗi từ server:', xhr.responseText);
                    
                    var errorMsg = 'Có lỗi xảy ra: ' + error + '\n';
                    errorMsg += 'Mã trạng thái: ' + xhr.status + ' ' + xhr.statusText + '\n';
                    errorMsg += 'Phản hồi từ server (500 ký tự đầu tiên):\n' + xhr.responseText.substring(0, 500);
                    
                    console.error('Chi tiết lỗi:', errorMsg);
                    alert(errorMsg);
                    $button.prop('disabled', false).html('Chọn');
                }
            });
        });
    
    // Xử lý lưu người phụ trách chung
    $('#btnLuuPhuTrach').on('click', function() {
        const nguoiPTId = $('#nguoiPhuTrachChung').val();
        if (!nguoiPTId) {
            alert('Vui lòng chọn người phụ trách');
            return;
        }
        
        if (confirm('Bạn có chắc chắn muốn gán người phụ trách này cho tất cả công việc?')) {
            $.ajax({
                url: 'ajax/update_maucv_nguoiphuquyet.php',
                type: 'POST',
                data: {
                    mamau: mamau,
                    nguoiphuquyet: nguoiPTId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Đã cập nhật người phụ trách cho tất cả công việc');
                    } else {
                        alert('Có lỗi xảy ra: ' + (response.message || 'Lỗi không xác định'));
                    }
                },
                error: function() {
                    alert('Không thể kết nối đến máy chủ');
                }
            });
        }
    });

    // Xử lý xác nhận và lưu công việc
    $(document).on('click', '#btnXacNhanCongViec', function() {
    console.log('Nút xác nhận được nhấn');

    // Lấy mã dự án từ biến toàn cục hoặc URL hoặc từ phần tử ẩn
    var mada = window.DUAN_MA_MODAL || 
              (new URLSearchParams(window.location.search)).get('mada') || 
              $('input[name="mada"]').val();

    if (!mada) {
        alert('Không tìm thấy mã dự án. Vui lòng đảm bảo đang chọn dự án trước khi thêm công việc.');
        console.error('Không tìm thấy mã dự án. Các nguồn kiểm tra:', {
            'window.DUAN_MA_MODAL': window.DUAN_MA_MODAL,
            'URL mada': (new URLSearchParams(window.location.search)).get('mada'),
            'input mada': $('input[name="mada"]').val()
        });
        return;
    }

    // Kiểm tra điều kiện bắt buộc cho phòng ban và người phụ trách
    $.ajax({
        url: 'ajax/get_project_leader.php',
        type: 'GET',
        data: { duan_ma: mada },
        dataType: 'json',
        success: function(res) {
            var requirePB = false;
            var requireNPT = false;
            if (res.status === 'success') {
                if (!res.has_leader) {
                    requirePB = true;
                    requireNPT = true;
                }
            } else {
                alert('Không kiểm tra được thông tin trưởng dự án. Vui lòng thử lại.');
                return;
            }

            var pbValue = $('#phongBanChon').val();
            var nptValue = $('#nguoiPhuTrachChung').val();

            // Nếu bắt buộc, kiểm tra
            if (requirePB && (!pbValue || pbValue === '')) {
                alert('Vui lòng chọn phòng ban!');
                $('#phongBanChon').focus();
                return;
            }
            if (requireNPT && (!nptValue || nptValue === '')) {
                alert('Vui lòng chọn người phụ trách!');
                $('#nguoiPhuTrachChung').focus();
                return;
            }

            // Tiếp tục xử lý lưu công việc như cũ
            const mamau = $('#hiddenMamau').val();
            console.log('Mã mẫu từ input ẩn:', mamau);
            if (!mamau) {
                alert('Không tìm thấy mã mẫu. Vui lòng thử lại.');
                return;
            }
            // Debug: In ra cấu trúc HTML của bảng
            console.log('Nội dung bảng:', $('#modalChiTietMau table').html());
            // Đếm số hàng trong bảng
            const rows = $('#modalChiTietMau table tbody tr');
            console.log('Tìm thấy', rows.length, 'hàng trong bảng');
            // Lấy danh sách công việc
            const congViecList = [];
            $('table tbody tr[data-cv-id]').each(function() {
                const row = $(this);
                const tenCv = row.find('.cv-name').val();
                const thoiGian = row.find('.cv-duration').val() || 0;
                const tienQuyet = row.find('.cv-prereq').val() || '';
                const nguoiPhuTrach = row.find('.cv-assignee').val() || '';
                
                if (tenCv) {
                    congViecList.push({
                        ten_cv: tenCv,
                        thoigian_dukien: thoiGian,
                        congviec_tienquyet: tienQuyet,
                        nguoiphutrach: nguoiPhuTrach
                    });
                }
            });
            if (congViecList.length === 0) {
                alert('Không tìm thấy công việc nào để lưu');
                return;
            }
            // Lấy ngày bắt đầu từ input
            const ngayBatDau = $('#ngayBatDau').val();
            
            // Nếu có ngày bắt đầu, cập nhật ngày bắt đầu cho tất cả các công việc không có tiên quyết
            if (ngayBatDau && congViecList.length > 0) {
                const startDate = new Date(ngayBatDau);
                
                // Duyệt qua tất cả các công việc
                congViecList.forEach((congViec, index) => {
                    // Nếu là công việc đầu tiên hoặc công việc không có tiên quyết
                    if (index === 0 || !congViec.congviec_tienquyet) {
                        // Đặt ngày bắt đầu là ngày do người dùng chọn
                        congViec.ngaybatdau = startDate.toISOString().split('T')[0];
                        
                        // Tính toán ngày kết thúc dựa trên thời gian dự kiến
                        const thoiGianDukien = parseInt(congViec.thoigian_dukien) || 0;
                        if (thoiGianDukien > 0) {
                            const endDate = new Date(startDate);
                            endDate.setDate(startDate.getDate() + thoiGianDukien - 1);
                            congViec.ngayketthuc = endDate.toISOString().split('T')[0];
                        }
                        
                        console.log(`Công việc ${index + 1}:`, {
                            ten: congViec.ten_cv,
                            batDau: congViec.ngaybatdau,
                            ketThuc: congViec.ngayketthuc,
                            thoiGianDukien: thoiGianDukien
                        });
                    }
                });
            }
            
            console.log('Dữ liệu gửi đi:', congViecList);
            // Log dữ liệu trước khi gửi
            const postData = {
                mamau: mamau,
                mada: mada,
                congviec: JSON.stringify(congViecList),
                tengoithau: (function(){
                    var v = $('#tenGoiThauInput').val();
                    return (!v || v.trim() === '') ? null : v;
                })()
            };
            console.log('Dữ liệu gửi lên server:', postData);
            // Gửi dữ liệu về server
            $.ajax({
                url: 'ajax/luu_congviec_mau.php',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    console.log('Phản hồi từ server:', response);
                    if (response.status === 'success') {
                        alert('Đã lưu thành công ' + response.saved + ' công việc.');
                        $('#modalChiTietMau').modal('hide');
                        $('#modalChonMau').modal('hide');
                        // Reload lại modaleditinner để nhận thông tin mẫu mới
                        var maDuAn = window.DUAN_MA_MODAL || $('input[name="mada"]').val();
                        console.log('Reload modaleditinner với mã dự án:', maDuAn);
                        if (typeof loadViewEdit === 'function' && maDuAn) {
                            console.log('Gọi loadViewEdit trực tiếp');
                            loadViewEdit(maDuAn);
                        } else {
                            console.log('Không tìm thấy hàm loadViewEdit ở local');
                        }
                    } else {
                        alert('Có lỗi xảy ra: ' + (response.message || 'Lỗi không xác định'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi AJAX:', status, error);
                    console.log('Phản hồi lỗi:', xhr.responseText);
                    alert('Không thể kết nối đến máy chủ: ' + error);
                }
            });
        },
        error: function(xhr, status, error) {
            alert('Không thể kiểm tra trưởng dự án: ' + error);
        }
    });
});
$(document).ready(function() {
    // Khi modalChonMau sắp đóng, nếu phần tử đang focus nằm trong modal thì blur nó
    $('#modalChonMau').on('hide.bs.modal', function () {
        var $modal = $(this);
        var $focused = $(':focus');
        if ($focused.length && $.contains($modal[0], $focused[0])) {
            $focused.blur();
        }
    });
    // Tương tự cho modalChiTietMau nếu cần
    $('#modalChiTietMau').on('hide.bs.modal', function () {
        var $modal = $(this);
        var $focused = $(':focus');
        if ($focused.length && $.contains($modal[0], $focused[0])) {
            $focused.blur();
        }
    });
});
</script>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<!-- Kết thúc modal chọn mẫu -->


