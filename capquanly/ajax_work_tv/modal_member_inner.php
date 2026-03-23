<input type="hidden" name="code" id="inputCodeMember" value="<?php echo $code; ?>">
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Phòng ban:</label><br>
            <select id="selectRoom" name="room_id" class="form-control" required>
                <option value="">--Chọn phòng ban--</option>
                <?php foreach($rooms as $item): ?>
                    <option value="<?php echo $item["PB_MA"]; ?>" <?php echo ($item["PB_MA"] == $project["PB_MA"]) ? 'selected' : ''; ?>  ><?php echo $item["PB_TEN"]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Thành viên :</label><br>
            <select id="selectMember" name="member_id" class="form-control select2" required>
                <option value="">--Chọn thành viên--</option>
                <?php foreach($members as $item): ?>
                    <option value="<?php echo $item["TV_MA"]; ?>"  <?php echo ($item["TV_MA"] == $project["TV_MA"]) ? 'selected' : ''; ?> ><?php echo $item["TV_TEN"]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>