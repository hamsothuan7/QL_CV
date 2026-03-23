<input type="hidden" name="code" id="inputCodeDate" value="<?php echo $code; ?>" >
<div class="col-md-12">
    <div class="form-group">
        <label>Ngày Bắt Đầu</label>
        <input type="date" class="form-control form-control-sm" autocomplete="off" required name="start_date" value="<?php echo $project['DSCV_NGAYBATDAU'] ?? date('Y-m-d'); ?>">
    </div>
    <div class="form-group">
        <label>Ngày Kết Thúc</label>
        <input type="date" class="form-control form-control-sm" autocomplete="off" required name="end_date" value="<?php echo $project['DSCV_NGAYKETTHUC'] ?? date('Y-m-d'); ?>">
    </div>
    <div class="form-group">
        <label>Ngày Tiếp Nhận</label>
        <input type="date" class="form-control form-control-sm" autocomplete="off" name="accept_date" value="<?php echo $project['DSCV_NGAYTIEPNHAN'] ?? date('Y-m-d'); ?>">
    </div>
    <div class="form-group">
        <label>Ngày Hoàn Thành</label>
        <input type="datetime-local" class="form-control form-control-sm" autocomplete="off" required name="finish_date" value="<?php echo $project['DSCV_NGAYHOANTHANH'] ?? date('Y-m-d'); ?>">
    </div>
</div>