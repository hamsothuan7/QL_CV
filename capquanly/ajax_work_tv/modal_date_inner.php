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
</div>