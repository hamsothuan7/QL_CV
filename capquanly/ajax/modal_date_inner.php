<input type="hidden" name="code" id="inputCodeDate" value="<?php echo $code; ?>" >
<div class="col-md-12">
    <div class="form-group">
        <label>Ngày Bắt Đầu</label>
        <input type="date" class="form-control form-control-sm" autocomplete="off" required name="start_date" value="<?php echo (isset($project) && isset($project['DA_NGAYBATDAU'])) ? $project['DA_NGAYBATDAU'] : date('Y-m-d'); ?>">
    </div>
    <div class="form-group">
        <label>Ngày Kết Thúc</label>
        <input type="date" class="form-control form-control-sm" autocomplete="off" required name="end_date" value="<?php echo (isset($project) && isset($project['DA_NGAYKETTHUC'])) ? $project['DA_NGAYKETTHUC'] : date('Y-m-d'); ?>">
    </div>
    <div class="form-group">
        <label>Thiết lập nhắc nhở</label>
        <select class="form-control" name="setting">
            <option value="1" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 1) ? 'selected': '';?>>Không có</option>
            <option value="2" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 2) ? 'selected': '';?>>Vào thời điểm hết hạn</option>
            <option value="3" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 3) ? 'selected': '';?>>5 Phút trước</option>
            <option value="4" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 4) ? 'selected': '';?>>10 Phút trước</option>
            <option value="5" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 5) ? 'selected': '';?>>15 Phút trước</option>
            <option value="6" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 6) ? 'selected': '';?>>1 giờ trước</option>
            <option value="7" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 7) ? 'selected': '';?>>2 giờ trước</option>
            <option value="8" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 8) ? 'selected': '';?>>1 Ngày trước</option>
            <option value="9" <?php echo (isset($project) && isset($project['DA_SETTING']) && $project['DA_SETTING'] == 9) ? 'selected': '';?>>2 Ngày trước</option>
        </select>
    </div>
</div>