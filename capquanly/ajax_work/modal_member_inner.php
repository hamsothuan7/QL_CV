<input type="hidden" name="code" id="inputCodeMember" value="<?php echo $code; ?>">
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Phòng ban:</label><br>
            <select id="selectRoom" name="room_id" class="form-control selectRoom" required>
                <option value="">--Chọn phòng ban 123--</option>
                <?php foreach($rooms as $item): ?>
                    <option value="<?php echo $item["PB_MA"]; ?>" <?php echo (isset($project) && $item["PB_MA"] == $project["PB_MA"]) ? 'selected' : ''; ?>  ><?php echo $item["PB_TEN"]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Thành viên :</label><br>
            <select id="selectMember" name="member_id" class="form-control select2 selectMember" required>
                <option value="">--Chọn thành viên--</option>
                <?php if(isset($members) && is_array($members)): ?>
                    <?php foreach($members as $item): ?>
                        <option value="<?php echo $item["TV_MA"]; ?>"  <?php echo (isset($project) && $item["TV_MA"] == $project["TV_MA"]) ? 'selected' : ''; ?> ><?php echo $item["TV_TEN"]; ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

           <div class="form-group">
            <label>Đơn vị:</label><br>
            <select id="selectph" name="ph_id" class="form-control selectph" >
                <option value="">--Chọn đơn vị--</option>
                <?php foreach($phoihop as $item): ?>
                    <?php if(isset($item["PH_MA"], $item["PH_TEN"])): ?>
                        <option value="<?php echo $item["PH_MA"]; ?>" <?php echo (isset($project) && isset($project["PH_MA"]) && $item["PH_MA"] == $project["PH_MA"]) ? 'selected' : ''; ?>  ><?php echo $item["PH_TEN"]; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div> 
    </div>
</div>