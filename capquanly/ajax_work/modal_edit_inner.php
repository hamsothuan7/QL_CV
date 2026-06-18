<?php
function time_elapsed_string($datetime, $full = false)
{
    date_default_timezone_set('Asia/Bangkok');
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = [];

    // Tính tuần từ ngày trong kết quả diff
    $weeks = floor($diff->d / 7);
    $days  = $diff->d % 7;

    // Thêm các đơn vị thời gian
    if ($diff->y > 0) $string[] = $diff->y . ' năm';
    if ($diff->m > 0) $string[] = $diff->m . ' tháng';
    if ($weeks > 0)   $string[] = $weeks . ' tuần';
    if ($days > 0)    $string[] = $days . ' ngày';
    if ($diff->h > 0) $string[] = $diff->h . ' giờ';
    if ($diff->i > 0) $string[] = $diff->i . ' phút';
    if ($diff->s > 0) $string[] = $diff->s . ' giây';

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' trước' : 'vừa xong';
}


function convertDate($date)
{
    $dateTime = new DateTime($date);
    $formattedDate = $dateTime->format('d/m/Y');
    echo $formattedDate;
}

?>
<?php if (isset($project)): ?>
    <input type="hidden" name="code" id="inputCode" value="<?php echo $project['DSCV_MA'] ?? null; ?>">
    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label><i class="fal fa-file-signature"></i>&nbsp;Tên công việc:
                </label>
                <input value="<?php echo $project['DSCV_TEN'] ?? null; ?>" id="inputName" type="text"
                       class="form-control form-control-sm" name="name" required>
                <?php if (($project['DSCV_NGAYKETTHUC_TV'] ?? null) != null && ($project['DSCV_NGAYKETTHUC_TRANGTHAI'] ?? null) == null) : ?>
                    <div style="margin-top: 10px;"  class="btn btn-sm btn-danger"
                    >
                        <i class="far fa-bell">&nbsp;Thay đổi ngày kết
                            thúc <?php echo date('d/m/y', strtotime($project['DSCV_NGAYKETTHUC_TV'])); ?></i>
                        <br>
                        <div class="btn btn-sm btn-info cancelDate" data-id="<?php echo $project['DSCV_MA']; ?>">Từ chối
                        </div>
                        <div class="btn btn-sm btn-info activeDate" data-id="<?php echo $project['DSCV_MA']; ?>"
                             data-date="<?php echo $project['DSCV_NGAYKETTHUC_TV']; ?>">Duyệt
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label><i class="fal fa-money-bill-wave"></i>&nbsp;Khối lượng thực hiện:</label>
                    <div class="d-flex" style="gap: 20px;">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="yeucau_giaingan" 
                                   name="yeucau_giaingan" value="1" 
                                   <?php echo (!empty($project['DSCV_YEUCAUGIAINGAN']) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="yeucau_giaingan">Yêu cầu giải ngân</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="trangthai_giaingan" 
                                   name="trangthai_giaingan" value="1" 
                                   <?php echo (!empty($project['DSCV_TRANGTHAIGIAINGAN']) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="trangthai_giaingan">Đã giải ngân</label>
                        </div>
                    </div>
                </div>
                <input type="number" class="form-control form-control-sm mt-2" 
                       name="gia_tri_giai_ngan" 
                       value="<?php echo $project['DSCV_GIATRIGIAINGAN'] ?? 0; ?>" 
                       min="0" step="1">
            </div>
            
            <div class="form-group">
                <label><i class="fal fa-clock"></i>&nbsp;Ngày hết hạn:<br>
                    <?php echo convertDate($project['DSCV_NGAYBATDAU']); ?>
                    - <?php echo convertDate($project['DSCV_NGAYKETTHUC']); ?>
                </label>
            </div>
            <div class="form-group">
                <label><i class="fal fa-users"></i>&nbsp;Thành viên:</label><br>
                <?php if ($project['TV_TEN'] != null): ?>
                    <div class="d-flex flex-column align-items-start">
                        <img class="img-fluid rounded-circle" src="./css/avatar.png" width="40" height="40">
                        <small> <?php echo $project['TV_TEN']; ?></small>
                    </div>
                <?php else: ?>
                    <div><small>Không có thành viên nào</small></div>
                <?php endif; ?>
            </div>
            
            
            <div class="form-group">
                <label><i class="fal fa-percentage"></i>&nbsp;Tiến độ:</label>
                <div class="slider-container">
                    <input name="progress" type="range" min="0" max="100" value="<?php echo $project['TIEN_DO']; ?>"
                           class="slider" id="myRange" readonly>
                    <div class="value" id="sliderValue"><?php echo $project['TIEN_DO']; ?></div>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-tasks-alt"></i>&nbsp;Mô tả:</label>
                <textarea class="form-control form-control-sm" name="editor" id="editor"
                          rows="5"><?php echo $project['DSCV_MOTA'] ?? null; ?></textarea>
            </div>
            <div class="form-group">
                <label><i class="far fa-comments-alt"></i>&nbsp;Hoạt động:</label>
                <textarea id="comment" class="form-control"></textarea>
                <a class="btn btn-sm btn-danger" id="btnComment" data-id="<?php echo $project['DSCV_MA']; ?>">Gửi</a>
                <div class="comments mt-2">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $item): ?>
                            <div class="d-flex border-2 p-2 py-2">
                                <div class="pr-2">
                                    <img class="img-fluid rounded-circle" src="./css/avatar.png" width="40" height="40">
                                </div>
                                <div class="d-flex flex-column">
                                    <small>
                                        <b><?php echo $item['TV_TEN']; ?></b><span>&nbsp;&nbsp;<?php echo time_elapsed_string($item['CREATED_AT']); ?></span>
                                        <a class="btn btn-sm btn-danger removeComment"
                                           data-code="<?php echo $project['DSCV_MA']; ?>"
                                           data-id="<?php echo $item['ID']; ?>">Xóa</a>
                                    </small>
                                    <small><?php echo $item['TEXT']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>               
        </div>


        <div class="col-md-4">
            <div class="form-group">
                <b>Thêm vào thẻ</b>
                <ul class="list-group">
                    <li id="menuMember" class="list-group-item"><i class="far fa-user"></i>&nbsp;Thành viên</li>
                    <li id="menuDate" class="list-group-item"><i class="fal fa-clock"></i>&nbsp;Ngày</li>
                </ul>
            </div>
            <div class="form-group">
                <label>Dự án:</label>
                <select name="project_id" class="form-control select2">
                    <?php if (!empty($projects)): ?>
                        <?php
                        // Hiển thị dự án hiện tại của công việc trước
                        if (!empty($project['DA_MA'])) {
                            foreach ($projects as $item):
                                if ($item['DA_MA'] == $project['DA_MA']):
                        ?>
                                    <option value="<?php echo htmlspecialchars($item["DA_MA"]); ?>" selected>
                                        <?php echo htmlspecialchars($item["DA_TEN"]); ?>
                                    </option>
                                <?php
                                break;
                                endif;
                            endforeach;
                        }
                        
                        // Hiển thị option "Chọn dự án" chỉ khi DA_MA là null
                        if (empty($project['DA_MA'])): ?>
                            <option value="0">--Chọn dự án--</option>
                        <?php endif; ?>

                        <?php
                        // Hiển thị các dự án còn lại
                        foreach ($projects as $item):
                            if ($item['DA_MA'] != $project['DA_MA']):
                        ?>
                            <option value="<?php echo htmlspecialchars($item["DA_MA"]); ?>">
                                <?php echo htmlspecialchars($item["DA_TEN"]); ?>
                            </option>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tình Trạng:</label>
                <select name="status" class="form-control" id="inputStatus">
                    <option value="5" <?php echo (($project['DSCV_TRANGTHAI'] ?? null) == 5) ? 'selected' : ''; ?>>Chưa tiếp nhận
                    </option>
                    <option value="1" <?php echo (($project['DSCV_TRANGTHAI'] ?? null) == 1) ? 'selected' : ''; ?>>Đang Tiến Hành
                    </option>
                    <option value="2" <?php echo (($project['DSCV_TRANGTHAI'] ?? null) == 2) ? 'selected' : ''; ?>>Hoàn Thành
                    </option>
                    <option value="6" <?php echo (($project['DSCV_TRANGTHAI'] ?? null) == 6) ? 'selected' : ''; ?>>Hoàn Thành (Trể)
                    </option>
                    <option value="3" <?php echo (($project['DSCV_TRANGTHAI'] ?? null) == 3) ? 'selected' : ''; ?>>Trễ</option>
                    <option value="4" <?php echo (($project['DSCV_TRANGTHAI'] ?? null) == 4) ? 'selected' : ''; ?>>Hủy</option>
                </select>
            </div>
            <div class="form-group">
                    <label><i class="far fa-file-image"></i>&nbsp;File:</label>
                    <?php if($project['FILE'] != null): ?>
                        <a href="<?php echo $project['FILE'] ?? null; ?>" download=""><?php echo $project['FILE'] ?? null; ?></a>
                    <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>