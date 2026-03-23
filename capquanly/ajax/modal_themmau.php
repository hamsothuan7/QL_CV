<?php
include(__DIR__ . '/../../config.php');
?>


<script>
// Hàm khởi tạo khi tài liệu đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    //console.log('Tài liệu đã sẵn sàng');
    
    // Biến đếm số thứ tự
    let taskCounter = 0;
    
    // Hàm cập nhật số thứ tự
    function updateRowNumbers() {
        const rows = document.querySelectorAll('#tasksList tr');
        rows.forEach((row, index) => {
            row.cells[0].textContent = index + 1;
        });
    }
    
    // Hàm thêm dòng công việc mới
    function addNewTaskRow() {
        taskCounter++;
        const tbody = document.getElementById('tasksList');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td class="text-center" style="color: black;">${taskCounter}</td>
            <td><input type="text" class="form-control task-name" name="task_name[]" placeholder="Nhập tên công việc" required></td>
            <td><input type="number" class="form-control task-duration" name="task_duration[]" min="1" value="1" required></td>
            <td><input type="number" class="form-control task-prereq" name="task_prereq[]" min="" placeholder="STT công việc trước" style="width: 90px"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger btn-remove-task">
                    <i class="fas fa-times"></i>
                </button>
            </td>`;
            
        tbody.appendChild(newRow);
        updateRowNumbers();
        //console.log('Đã thêm dòng mới');
    }
    
    // Thêm sự kiện click cho nút thêm công việc
    const btnAddTask = document.getElementById('btnAddTask');
    if (btnAddTask) {
        btnAddTask.addEventListener('click', function(e) {
            e.preventDefault();
            //console.log('Nhấn nút thêm công việc');
            addNewTaskRow();
        });
    } else {
        console.error('Không tìm thấy nút thêm công việc');
    }
    
    // Sự kiện xóa công việc (sử dụng event delegation)
    document.getElementById('tasksList')?.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-task')) {
            const rows = this.querySelectorAll('tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                e.stopPropagation();
                updateRowNumbers();
                taskCounter--;
            } else {
                alert('Cần ít nhất một công việc');
            }
        }
    });
    
    
    // Xử lý submit form
const form = document.getElementById('formThemMauCV');
if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Kiểm tra có ít nhất một công việc không
        if (document.querySelectorAll('#tasksList tr').length === 0) {
            alert('Vui lòng thêm ít nhất một công việc');
            return false;
        }

        // Hiển thị loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...';

        // Lấy dữ liệu form
        const formData = new FormData(this);

        // Gửi dữ liệu bằng AJAX
        fetch('ajax/save_template.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Lỗi kết nối: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); 
            if (data.status === 'success') {
                // Hiển thị thông báo thành công
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message || 'Lưu mẫu công việc thành công!');
                } else {
                    alert(data.message || 'Lưu mẫu công việc thành công!');
                }

                // Đóng modal sử dụng Bootstrap 5
                const modalElement = document.getElementById('modalThemMau');
                if (modalElement) {
                    if (typeof $ !== 'undefined' && typeof $('#modalThemMau').modal === 'function') {
    // Bootstrap 4 hoặc có jQuery
    $('#modalThemMau').modal('hide');
} else if (typeof bootstrap !== 'undefined' && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
    // Bootstrap 5
    let modal = bootstrap.Modal.getInstance(modalElement);
    if (!modal) modal = new bootstrap.Modal(modalElement);
    modal.hide();
} else {
    // Fallback thủ công
    modalElement.style.display = 'none';
}
// Force removal of 'show' class if still present (edge case)
modalElement.classList.remove('show');
document.body.classList.remove('modal-open');
const backdrop = document.querySelector('.modal-backdrop');
if (backdrop) backdrop.remove();
                }
                
                // Reset form
                form.reset();
                document.getElementById('tasksList').innerHTML = '';
                taskCounter = 0;
                addNewTaskRow();

                // Tải lại danh sách mẫu công việc nếu cần
                if (typeof reloadTemplates === 'function') {
                    reloadTemplates();
                } else {
                    window.location.reload();
                }
            } else {
                throw new Error(data.message || 'Có lỗi xảy ra khi lưu mẫu công việc');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error(error.message || 'Có lỗi xảy ra khi lưu mẫu công việc');
            } else {
                alert(error.message || 'Có lỗi xảy ra khi lưu mẫu công việc');
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
}
    
    // Thêm dòng đầu tiên
    addNewTaskRow();

    // ------------ Import Excel handler -------------
    function processExcelFile() {
        const fileInput = document.getElementById('excelFile');
        if (!fileInput || !fileInput.files.length) {
            return;
        }

        const formData = new FormData();
        formData.append('excel', fileInput.files[0]);
        formData.append('trangthai', document.getElementById('trangthai')?.checked ? '1' : '0');

        // Loading state
        const btnImportExcel = document.getElementById('btnImportExcel');
        const oldHtml = btnImportExcel.innerHTML;
        btnImportExcel.disabled = true;
        btnImportExcel.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý';

        fetch('ajax/parse_template_excel.php', {
            method: 'POST',
            body: formData
        })
            .then(async res => {
                const ct = res.headers.get('content-type') || '';
                if(ct.includes('application/json')){
                    return res.json();
                }
                const text = await res.text();
                throw new Error('Server trả về không đúng JSON:\n'+text.substring(0,500));
            })
            .then(data => {
                if (data.status === 'success') {
                    if(data.status==='success'){
                            const info=data.data;
                            if(info.ten_mau) document.getElementById('tenmau').value=info.ten_mau;
                            // clear current tasks
                            document.getElementById('tasksList').innerHTML='';
                            taskCounter=0;
                            info.tasks.forEach(t=>{
                                taskCounter++;
                                const row=`<tr>
                                    <td class="text-center" style="color: black;">${taskCounter}</td>
                                    <td><input type="text" class="form-control task-name" name="task_name[]" value="${t.ten_cv}" required></td>
                                    <td style="width:120px"><input type="number" class="form-control task-duration" name="task_duration[]" value="${t.thoi_gian_du_kien}" min="1" required></td>
                                    <td style="width:120px"><input type="number" class="form-control task-prereq" name="task_prereq[]" value="${t.prereq}"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-task"><i class="fas fa-times"></i></button></td>
                                </tr>`;
                                document.getElementById('tasksList').insertAdjacentHTML('beforeend',row);
                            });
                            toastr?.success('Đã nạp dữ liệu từ Excel, hãy kiểm tra và nhấn Lưu');
                        } else toastr?.error(data.message || 'Import thành công');
                    
                    const formEl = document.getElementById('formThemMauCV');
                } else {
                    toastr?.error(data.message || 'Có lỗi khi import');
                }
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'Có lỗi kết nối');
            })
            .finally(() => {
                btnImportExcel.disabled = false;
                btnImportExcel.innerHTML = oldHtml;
            });
    }

    // Tự động xử lý khi file được chọn
    const fileInput = document.getElementById('excelFile');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                processExcelFile();
            }
        });
    }

    // Giữ lại nút "Xem trước" để người dùng có thể xử lý lại nếu cần
    const btnImportExcel = document.getElementById('btnImportExcel');
    if (btnImportExcel) {
        btnImportExcel.addEventListener('click', function () {
            const fileInput = document.getElementById('excelFile');
            if (!fileInput || !fileInput.files.length) {
                alert('Vui lòng chọn file Excel (.xlsx hoặc .xls)');
                return;
            }
            processExcelFile();
        });
    }
    // ------------ End Import handler -------------
});
</script>

<!-- Modal Thêm mẫu công việc -->
<div class="modal fade" id="modalThemMau" tabindex="-1" role="dialog" aria-labelledby="modalThemMauLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalThemMauLabel">
                    <i class="fas fa-plus-circle me-2"></i>Thêm mẫu công việc mới
                </h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <form id="formThemMauCV" method="post">
                <div class="modal-body">
                    <!-- Thông báo lỗi/success -->
                    <div id="alertMessage" class="alert d-none mb-3"></div>
                    
                    <!-- Nhập công việc từ Excel -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fas fa-file-excel me-2"></i>Nhập công việc từ Excel
                        </label>
                        <div class="input-group">
                            <input type="file" accept=".xlsx,.xls" class="form-control" id="excelFile" name="excel">
                            <button type="button" class="btn btn-outline-secondary" id="btnImportExcel">
                                <i class="fas fa-upload me-1"></i> Xem trước
                            </button>
                            <a href="ajax/download_template_excel.php" class="btn btn-link" download>Download mẫu</a>
                        </div>
                        <!-- <small class="text-muted">File gồm 2 cột: Tên công việc, Thời gian (ngày). Hàng đầu tiên là header.</small> -->
                    </div>
                    <hr class="my-4">
                    <!-- Tên mẫu -->
                    <div class="mb-4">
                        <label for="tenmau" class="form-label fw-semibold mb-2">
                            <i class="fas fa-tag me-2"></i>Tên mẫu công việc <span class="text-danger"></span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-file-alt text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-2 py-2" 
                                   id="tenmau" name="tenmau" required 
                                   placeholder="Nhập tên mẫu công việc"
                                   style="border-color: #dee2e6;">
                        </div>
                    </div>
                    
                    <!-- Bảng danh sách công việc -->
                    <div class="mb-3">
                        <label class="form-label">Danh sách công việc <span class="text-danger"></span></label>
                        <div id="tasksWrapper" class="table-responsive mb-2" style="max-height:60vh; overflow-y:auto !important;">
                            <style>
                                #tasksWrapper {
                                    position: relative;
                                }
                                #tasksWrapper thead {
                                    position: sticky;
                                    top: 0;
                                    z-index: 10;
                                    background: linear-gradient(135deg, #f0f7ff 0%, #e0f0ff 100%);
                                }
                                #tasksWrapper thead th {
                                    background: linear-gradient(135deg, #f0f7ff 0%, #e0f0ff 100%) !important;
                                    border-bottom: 2px solid #c2dfff !important;
                                }
                                #tasksWrapper tbody {
                                    background: white;
                                }
                            </style>
                            <table class="table table-bordered table-hover" id="tasksTable">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #f0f7ff 0%, #e0f0ff 100%);">
                                        <th class="text-center" style="width: 60px; padding: 16px 12px; font-weight: 600; font-size: 0.75rem; color: #1a73e8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #c2dfff;">
                                            STT
                                        </th>
                                        <th style="padding: 16px 16px; font-weight: 600; font-size: 0.75rem; color: #1a73e8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #c2dfff;">
                                            Tên công việc
                                        </th>
                                        <th style="width: 100px; padding: 16px 12px; font-weight: 600; font-size: 0.75rem; color: #1a73e8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #c2dfff;">
                                             Thời gian (ngày)
                                        </th>
                                        <th style="width: 110px; padding: 16px 12px; font-weight: 600; font-size: 0.75rem; color: #1a73e8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #c2dfff;">
                                            Công việc tiên quyết (STT)
                                        </th>
                                        <th class="text-center" style="width: 60px; padding: 16px 12px; font-weight: 600; font-size: 0.75rem; color: #1a73e8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #c2dfff;">
                                            Thao tác
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tasksList">
                                    <!-- Các dòng công việc sẽ được thêm vào đây bằng JS -->
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAddTask">
                            <i class="fas fa-plus me-1"></i> Thêm công việc
                        </button>
                    </div>
                    
                    <!-- Trạng thái -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="trangthai" name="trangthai" value="1" checked>
                            <label class="form-check-label" for="trangthai">Kích hoạt mẫu này</label>
                        </div>
                    </div>

                                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Hủy bỏ
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSaveMauCV">
                        <i class="fas fa-save me-1"></i> Lưu mẫu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Thêm Bootstrap JS và Popper.js (yêu cầu bởi Bootstrap 5) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>