<div class="modal fade" id="modalInsert">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm công việc</h5>
                <button type="button" class="close custom-x-btn" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="post" id="projectFormInsert">
                <div class="modal-body">
                    <input type="hidden" name="status" id="inputStatus">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Tên công việc:</label>
                                <input type="text" class="form-control form-control-sm" name="name" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tasks-alt"></i>&nbsp;Mô tả:</label>
                                <textarea class="form-control form-control-sm" name="editor2" id="editor2"
                                          rows="5"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Ngày Bắt Đầu</label>
                                <input type="date" class="form-control form-control-sm" autocomplete="off" required
                                       name="start_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Ngày Kết Thúc</label>
                                <input type="date" class="form-control form-control-sm" autocomplete="off" required
                                       name="end_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
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
