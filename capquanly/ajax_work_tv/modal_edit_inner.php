<?php
function time_elapsed_string($datetime, $full = false)
{
    date_default_timezone_set('Asia/Bangkok');
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $units = [
        'y' => 'năm',
        'm' => 'tháng',
        'd' => 'ngày',
        'h' => 'giờ',
        'i' => 'phút',
        's' => 'giây',
    ];
    $string = [];

    // Xử lý tuần riêng
    $weeks = floor($diff->d / 7);
    if ($weeks > 0) {
        $string['w'] = $weeks . ' tuần' . ($weeks > 1 ? '' : '');
        $diff->d = $diff->d % 7; // Cập nhật số ngày còn lại sau khi đã tính tuần
    }

    // Xử lý các đơn vị thời gian khác
    foreach ($units as $k => $label) {
        if ($k === 'd' && $diff->d === 0 && !empty($string['w'])) continue; // Bỏ qua ngày nếu đã hiển thị tuần
        if (isset($diff->$k) && $diff->$k > 0) {
            $string[$k] = $diff->$k . ' ' . $label . ($diff->$k > 1 ? '' : '');
        }
    }

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
            <?php if ($project['DA_TEN'] != null): ?>
                <div class="form-group">
                    <label><i class="fal fa-file-signature"></i>&nbsp;Tên dự án:</label>
                    <input value="<?php echo $project['DA_TEN'] ?? null; ?>" type="text"
                           class="form-control form-control-sm" readonly>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label><i class="fal fa-file-signature"></i>&nbsp;Tên công việc:</label>
                
                <input value="<?php echo $project['DSCV_TEN'] ?? null; ?>" id="inputName" type="text"
                       class="form-control form-control-sm" name="name" required>
                <?php if ($project['DSCV_NGAYKETTHUC_TRANGTHAI'] != NULL && $project['DSCV_NGAYKETTHUC_TRANGTHAI'] == 0) : ?>
                    <div style="margin-top: 10px;" class="btn btn-sm btn-danger">
                        <i class="far fa-bell">&nbsp;Thay đổi ngày kết thúc </i>
                        bị từ chối
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
                           class="slider" id="myRange">
                    <div class="value" id="sliderValue"><?php echo $project['TIEN_DO']; ?></div>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-tasks-alt"></i>&nbsp;Mô tả:</label>
                <textarea class="form-control form-control-sm" name="editor" id="editor"
                          rows="5"><?php echo $project['DSCV_MOTA'] ?? null; ?></textarea>
            </div>
            <div class="form-group">
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
                                        <?php if (isset($_SESSION['code']) && $item['TV_MA'] == $_SESSION['code']): ?>
                                            <a class="btn btn-sm btn-danger removeComment" 
                                               data-code="<?php echo $project['DSCV_MA']; ?>"
                                               data-id="<?php echo $item['ID']; ?>">Xóa</a>
                                        <?php endif; ?>
                                    </small>
                                    <small><?php echo $item['TEXT']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <label><i class="far fa-comments-alt"></i>&nbsp;Hoạt động:</label>
                <textarea id="comment" class="form-control"></textarea>
                <a class="btn btn-sm btn-danger" id="btnComment" data-id="<?php echo $project['DSCV_MA']; ?>">Gửi</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <b>Thêm vào thẻ</b>
                <ul class="list-group">
                    <li id="menuDate" class="list-group-item"><i class="fal fa-clock"></i>&nbsp;Ngày</li>
                </ul>
            </div>
            <div class="form-group">
                <label>Tình Trạng:</label>
                <select name="status" class="form-control" id="inputStatus">
                    <option value="5" <?php echo ($project['DSCV_TRANGTHAI'] == 5) ? 'selected' : ''; ?>>Chưa tiếp nhận
                    </option>
                    <option value="1" <?php echo ($project['DSCV_TRANGTHAI'] == 1) ? 'selected' : ''; ?>>Đang tiến hành
                    </option>
                    <option value="2" <?php echo ($project['DSCV_TRANGTHAI'] == 2) ? 'selected' : ''; ?>>Hoàn Thành
                    </option>
                    <option value="6" <?php echo ($project['DSCV_TRANGTHAI'] == 6) ? 'selected' : ''; ?>>Hoàn Thành (Trể)
                    </option>
                    <option value="3" <?php echo ($project['DSCV_TRANGTHAI'] == 3) ? 'selected' : ''; ?>>Trễ</option>
                    <option value="4" <?php echo ($project['DSCV_TRANGTHAI'] == 4) ? 'selected' : ''; ?>>Hủy</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="far fa-file-image"></i>&nbsp;File:</label>
                <input type="file" class="form-control" name="file"/>
                <?php if($project['FILE'] != null): ?>
                    <div class="file-link-container">
                        <a href="<?php echo $project['FILE'] ?? null; ?>" download="" class="file-link" title="<?php echo basename($project['FILE']) ?? ''; ?>">
                            <?php 
                                $filename = basename($project['FILE']) ?? '';
                                // Xóa số và dấu gạch dưới ở đầu tên file
                                $displayName = preg_replace('/^\d+_/', '', $filename);
                                echo htmlspecialchars($displayName);
                            ?>
                        </a>
                    </div>
                    <style>
                        .file-link-container {
                            margin-top: 5px;
                            max-width: 100%;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }
                        .file-link {
                            display: inline-block;
                            max-width: 100%;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                            vertical-align: middle;
                        }
                        .file-link:hover {
                            text-decoration: underline;
                            color: #0d6efd !important; /* Màu xanh Bootstrap */
                        }
                    </style>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Thêm thư viện jQuery trước khi sử dụng -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Xử lý sự kiện gửi bình luận
        $(document).on('click', '#btnComment', function(e) {
            e.preventDefault();
            
            const taskId = $(this).data('id');
            const commentText = $('#comment').val().trim();
            
            if (!commentText) {
                alert('Vui lòng nhập nội dung bình luận');
                return;
            }

            // Gửi dữ liệu lên server
            console.log('Sending comment:', {task_id: taskId, comment: commentText});
            $.ajax({
                url: 'ajax_work_tv/add_comment.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    task_id: taskId,
                    comment: commentText
                },
                success: function(response, status, xhr) {
                    console.log('Server response:', response);
                    console.log('Status:', status);
                    console.log('Response headers:', xhr.getAllResponseHeaders());
                    if (response.status === 'success') {
                        // Thêm bình luận mới vào danh sách
                        const newComment = `
                            <div class="d-flex border-2 p-2 py-2">
                                <div class="pr-2">
                                    <img class="img-fluid rounded-circle" src="./css/avatar.png" width="40" height="40">
                                </div>
                                <div class="d-flex flex-column">
                                    <small>
                                        <b>Bạn</b><span>&nbsp;&nbsp;vừa xong</span>
                                        <a class="btn btn-sm btn-danger removeComment"
                                           data-code="${taskId}"
                                           data-id="${response.comment_id}">Xóa</a>
                                    </small>
                                    <small>${commentText}</small>
                                </div>
                            </div>
                        `;
                        
                        // Thêm bình luận vào đầu danh sách
                        $('.comments').prepend(newComment);
                        
                        // Xóa nội dung trong textarea
                        $('#comment').val('');
                    } else {
                        alert('Có lỗi xảy ra: ' + (response.message || 'Không thể gửi bình luận'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    alert('Có lỗi xảy ra khi gửi bình luận. Vui lòng kiểm tra console để biết thêm chi tiết.');
                }
            });
        });

        // Xử lý sự kiện xóa bình luận
        $(document).on('click', '.removeComment', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            
            // Vô hiệu hóa nút để tránh nhiều lần nhấn
            if ($button.hasClass('deleting')) {
                return;
            }
            $button.addClass('deleting').prop('disabled', true);
            
            if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
                $button.removeClass('deleting').prop('disabled', false);
                return;
            }
            
            const commentId = $button.data('id');
            const taskId = $button.data('code');
            const commentElement = $button.closest('.d-flex.border-2');
            
            // Thêm lớp loading
            $button.html('<i class="fas fa-spinner fa-spin"></i> Đang xóa...');
            
            $.ajax({
                url: 'ajax_work_tv/delete_comment.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: commentId,
                    DSCV_MA: taskId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        commentElement.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Có lỗi xảy ra: ' + (response.message || 'Không thể xóa bình luận'));
                        $button.removeClass('deleting').prop('disabled', false).text('Xóa');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xóa bình luận. Vui lòng thử lại.');
                    $button.removeClass('deleting').prop('disabled', false).text('Xóa');
                }
            });
        });
    });
    </script>
<?php endif; ?>