<div class="modal fade" id="modalWork" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm nhãn</h5>
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
                        </div>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info">Lưu</button>
                </div>
            </form>

        </div>
    </div>
</div>
