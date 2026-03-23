<?php
// Modal quản lý mẫu công việc
?>
<!-- <style>
    /* điều chỉnh modal */
    #modalQuanLyMau .modal-dialog {
        max-width: 900px;
    }
    /* đảm bảo tiêu đề bảng dễ đọc */
    /* style header + borders */
    #modalQuanLyMau thead th {
        background:#343a40 !important;
        color:#fff !important;
        border:1px solid #6c757d !important;
    }
    #modalQuanLyMau tbody td {
        border:1px solid #6c757d !important;
    }
    #modalQuanLyMau .modal-header {
        background:#343a40 !important;
        color:#fff !important;
    }
    #modalQuanLyMau .modal-title {
        color:#fff !important;
    }
    #modalQuanLyMau .modal-body {
        background: #343a40;
        color: #ffffff;
        background: #ffffff;
        color: #212529;
    }
    #modalQuanLyMau .modal-footer {
        background: #343a40;
        color: #ffffff;
    }
</style> -->
<div class="modal fade" id="modalQuanLyMau" tabindex="-1" aria-labelledby="modalQuanLyMauLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalQuanLyMauLabel"><i class="fas fa-th-list me-2"></i>Quản lý mẫu công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs mb-3" id="tabsQuanLy" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-list-link" data-bs-toggle="tab" data-bs-target="#tab_list" type="button" role="tab" aria-controls="tab_list" aria-selected="true">
                            Danh sách mẫu
                        </button>
                    </li>
                </ul>
                <!-- Tab panes -->
                <div class="tab-content" id="tabsContentQuanLy">
                    <div class="tab-pane fade show active" id="tab_list" role="tabpanel">
                        <!-- search and table moved here -->
                        <div class="mb-3">
                            <input type="text" id="searchQuanLyMau" class="form-control" placeholder="Tìm kiếm tên mẫu công việc...">
                        </div>
                
                <?php
                include(__DIR__ . '/../../config.php');
                $sql="SELECT mamau, tenmau, created_at FROM maucv WHERE trangthai = 1 ORDER BY created_at DESC";
                $result = mysqli_query($conn, $sql);
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark text-white">
                            <tr>
                                <th style="width:60px" class="text-center">STT</th>
                                <th>Tên mẫu</th>
                                <th style="width:140px" class="text-center">Ngày tạo</th>
                                <th style="width:140px" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="quanLyMauTbody">
                            <?php if($result && mysqli_num_rows($result)>0): $i=1; while($row=mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['tenmau']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info btn-edit-mau" data-mamau="<?php echo $row['mamau']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="btn btn-sm btn-danger btn-delete-mau ms-1" data-mamau="<?php echo $row['mamau']; ?>"><i class="fas fa-trash"></i> Xóa</button>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center">Không có mẫu nào</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                    </div> <!-- end tab_list -->
                </div> <!-- end tab-content -->
            </div>
            
        </div>
    </div>
</div>

<script>
// Tìm kiếm realtime
function loadTemplateList(){
    window.reloadTemplates = loadTemplateList;
    $('#quanLyMauTbody').html('<tr><td colspan="4" class="text-center text-muted">Đang tải...</td></tr>');
    $('#quanLyMauTbody').load('ajax/list_templates.php');
}
// initial load when modal shown
$('#modalQuanLyMau').on('shown.bs.modal',function(){
    loadTemplateList();
});

$('#searchQuanLyMau').on('keyup', function(){
    var filter = $(this).val().toLowerCase();
    $('#quanLyMauTbody tr').each(function(){
        var text = $(this).find('td').eq(1).text().toLowerCase();
        $(this).toggle(text.indexOf(filter)>-1);
    });
});


$(document).on('click', '.btn-delete-mau', function(){
    var mamau=$(this).data('mamau');
    var row=$(this).closest('tr');
    if(!confirm('Bạn có chắc muốn xoá mẫu này?')) return;
    $.post('ajax/delete_template.php',{mamau:mamau},function(resp){
        if(resp.status==='success'){
            toastr?.success('Đã xoá mẫu');
            if($('#modalQuanLyMau').hasClass('show')){loadTemplateList();}
            row.remove();
        }else{
            toastr?.error(resp.message||'Không xoá được');
        }
    },'json').fail(()=>alert('Lỗi kết nối'));
});
// ---------------- Edit template handler ----------------
$(document).on('click', '.btn-edit-mau', function(){
    var mamau = $(this).data('mamau');
    var tenmau = $(this).closest('tr').find('td').eq(1).text();

    // if tab exists just activate
    if($('#tab_'+mamau).length){
        $('#tab-'+mamau+'-link').tab('show');
        return;
    }

    // add new tab header with close button
    var tabHeader = `<li class="nav-item" role="presentation" id="li_${mamau}">
        <button class="nav-link" id="tab-${mamau}-link" data-bs-toggle="tab" data-bs-target="#tab_${mamau}" type="button" role="tab" aria-controls="tab_${mamau}" aria-selected="false">
         ${tenmau} <span class="ms-1 text-muted close-tab" data-mamau="${mamau}" style="cursor:pointer">&times;</span>
        </button>
    </li>`;
    $('#tabsQuanLy').append(tabHeader);

    // add pane
    var pane = `<div class="tab-pane fade" id="tab_${mamau}" role="tabpanel">
                    <p class="text-muted">Đang tải dữ liệu...</p>
                </div>`;
    $('#tabsContentQuanLy').append(pane);
    $('#tab-'+mamau+'-link').tab('show');

    // fetch details
    $.get('ajax/get_chitiet_maucv.php',{mamau: mamau}, function(resp){
        try{ var data = typeof resp==='string'? JSON.parse(resp): resp;}catch(e){ console.error(e); data={status:'error'}; }
        if(data.status==='success'){
            var tasks=data.data||[];
            var rows='';
            tasks.forEach(function(item,idx){
                rows += `<tr>
                    <td class="text-center">${idx+1}</td>
                    <td><input type="text" class="form-control" value="${item.ten_cv}"></td>
                    <td style="width:120px"><input type="number" class="form-control" value="${item.thoi_gian_du_kien}"></td>
                    <td style="width:120px"><input type="text" class="form-control" value="${item.prereq}"></td>
                    <td class="text-center"><button class="btn btn-sm btn-danger btn-remove-row">X</button></td>
                </tr>`;
            });
            var html = `<form class="mt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên mẫu</label>
                        <input type="text" id="ten_mau_${mamau}" class="form-control" value="${tenmau}">
                    </div>
                    <div class="table-responsive mb-2" style="max-height:60vh; overflow-y:auto !important;">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:60px" class="text-center">STT</th>
                                    <th>Tên công việc</th>
                                    <th style="width:100px" class="text-center">Thời gian</th>
                                    <th style="width:110px" class="text-center">Tiên quyết</th>
                                    <th style="width:60px" class="text-center">Xóa</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary btn-add-row"><i class="fas fa-plus me-1"></i> Thêm công việc</button>
                    <button type="button" class="btn btn-success btn-save-template" data-mamau="${mamau}" data-tenmau="${tenmau}">Lưu thay đổi</button>
                </form>`;
            $('#tab_'+mamau).html(html);
        }else{
            $('#tab_'+mamau).html('<div class="alert alert-danger">Không lấy được chi tiết mẫu.</div>');
        }
    });
});

// close tab on click x
$(document).on('click','.close-tab',function(e){
    var mamau=$(this).data('mamau');
    // remove tab header & pane
    $('#li_'+mamau).remove();
    $('#tab_'+mamau).remove();
    // activate list tab
    $('#tab-list-link').tab('show');
});
// Add new task row
$(document).on('click','.btn-add-row',function(){
    var tbody=$(this).closest('div').find('tbody');
    var idx=tbody.find('tr').length+1;
    var row=`<tr>
            <td class="text-center">${idx}</td>
            <td><input type="text" class="form-control" value=""></td>
            <td style="width:120px"><input type="number" class="form-control" value="1"></td>
            <td style="width:120px"><input type="text" class="form-control" value=""></td>
            <td class="text-center"><button class="btn btn-sm btn-danger btn-remove-row">X</button></td>
        </tr>`;
    tbody.append(row);
});
// Remove row
$(document).on('click','.btn-remove-row',function(){
    var tr=$(this).closest('tr');
    if(!confirm('Bạn có chắc muốn xoá công việc này?')) return;
    var tbody=tr.parent();
    tr.remove();
    // Renumber STT
    tbody.find('tr').each(function(i){
        $(this).find('td').first().text(i+1);
    });
});

// Save template
$(document).on('click','.btn-save-template',function(){
    var btn = $(this);
    var mamau = btn.data('mamau');
    var tenmau = $('#ten_mau_'+mamau).val().trim();
    var pane = $('#tab_'+mamau);
    if(!tenmau) {
        alert('Vui lòng nhập tên mẫu');
        return false;
    }
    var tasks=[];
    pane.find('tbody tr').each(function(){
        var name=$(this).find('input').eq(0).val().trim();
        var duration=$(this).find('input').eq(1).val();
        var prereq=$(this).find('input').eq(2).val().trim();
        if(name!==''){
            tasks.push({name:name,duration:duration,prereq:prereq});
        }
    });
    $.ajax({
        url: 'ajax/update_template.php',
        type: 'POST',
        dataType: 'json',
        data: {
            mamau: mamau,
            tenmau: tenmau,
            tasks: JSON.stringify(tasks)
        },
        success: function(resp) {
            if(resp.status === 'success') {
                toastr?.success('Đã lưu mẫu thành công');
                // Cập nhật tên trong bảng danh sách
                var $row = $('#quanLyMauTbody button.btn-edit-mau[data-mamau="'+mamau+'"]').closest('tr');
                $row.find('td').eq(1).text(tenmau);
                // Cập nhật tiêu đề tab
                $('#tab-'+mamau+'-link').contents().filter(function(){
                    return this.nodeType === 3;
                }).first().replaceWith(tenmau + ' ');
            } else {
                toastr?.error(resp.message || 'Có lỗi xảy ra khi lưu mẫu');
            }
        },
        error: function(xhr, status, error) {
            console.error('Lỗi AJAX:', status, error);
            // Nếu vẫn cập nhật được dữ liệu dù có lỗi, thông báo thành công nhưng cảnh báo
            if(xhr.status === 200 && xhr.responseText) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if(resp.status === 'success') {
                        toastr?.success('Đã lưu mẫu nhưng có lỗi hiển thị phản hồi');
                        // Cập nhật giao diện dù có lỗi
                        var $row = $('#quanLyMauTbody button.btn-edit-mau[data-mamau="'+mamau+'"]').closest('tr');
                        $row.find('td').eq(1).text(tenmau);
                        $('#tab-'+mamau+'-link').contents().filter(function(){
                            return this.nodeType === 3;
                        }).first().replaceWith(tenmau + ' ');
                        return;
                    }
                } catch(e) {}
            }
            toastr?.error('Lỗi kết nối: ' + error);
        }
    });
});

</script>
