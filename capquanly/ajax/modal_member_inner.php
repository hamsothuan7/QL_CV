<?php
$code = $code ?? ($_GET['code'] ?? $_POST['code'] ?? '');
?>
<input type="hidden" name="code" id="inputCodeMember" value="<?php echo $code; ?>">
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Phòng ban:</label><br>
            <select id="selectRoom" name="room_id" class="form-control" required>
                <option value="">--Chọn phòng ban--</option>
                <?php if(isset($rooms) && is_array($rooms)): ?>
                    <?php foreach($rooms as $item): ?>
                        <option value="<?php echo $item["PB_MA"]; ?>" <?php echo (isset($project) && $item["PB_MA"] == $project["PB_MA"]) ? 'selected' : ''; ?>  ><?php echo $item["PB_TEN"]; ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>


        <div class="form-group">
            <label>Thành viên :</label><br>
            <?php if(isset($membersIds)): ?>
                <select id="selectMember" name="members[]" class="form-control select2" multiple="multiple">
                    <?php if(isset($members) && is_array($members)): ?>
                        <?php foreach($members as $item): ?>
                            <option value="<?php echo $item["TV_MA"]; ?>"  <?php echo (in_array($item['TV_MA'], $membersIds)) ? 'selected disabled readonly' : ''?> ><?php echo $item["TV_TEN"]; ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            <?php endif; ?>
        </div>

             <div class="form-group">
            <label>Đơn vị:</label><br>
            <select id="selectPH" name="ph_id" class="form-control" required>
                <option value="">--Chọn đơn vị--</option>
                <?php if(isset($phoihop) && is_array($phoihop)): ?>
                    <?php foreach($phoihop as $item): ?>
                        <option value="<?php echo $item["PH_MA"]; ?>" <?php echo (isset($project) && $item["PH_MA"] == $project["PH_MA"]) ? 'selected' : ''; ?>  ><?php echo $item["PH_TEN"]; ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

    </div>
</div>