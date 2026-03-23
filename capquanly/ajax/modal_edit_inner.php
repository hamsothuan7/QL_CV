<?php
include('../../config.php');

// Khởi tạo biến chứa thông tin người phụ trách
$current_leader_info = null;

function time_elapsed_string($datetime, $full = false)
{
    date_default_timezone_set('Asia/Bangkok');
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Sửa lỗi: DateInterval không có thuộc tính w trong PHP < 8.2
    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;
    // Thêm vào mảng kết quả nếu cần hiển thị tuần
    if ($weeks > 0) {
        $string['w'] = $weeks . ' tuần';
    }

    $string = [];
    
    // Tính toán các đơn vị thời gian
    $units = [
        'y' => ['năm', $diff->y],
        'm' => ['tháng', $diff->m],
        'd' => ['ngày', $diff->d % 7], // Số ngày còn lại sau khi đã tính tuần
        'h' => ['giờ', $diff->h],
        'i' => ['phút', $diff->i],
        's' => ['giây', $diff->s],
    ];
    
    // Thêm tuần nếu có đủ 7 ngày
    $weeks = floor($diff->d / 7);
    if ($weeks > 0) {
        $string['w'] = $weeks . ' tuần' . ($weeks > 1 ? '' : '');
    }
    
    // Thêm các đơn vị thời gian khác
    foreach ($units as $unit) {
        list($label, $value) = $unit;
        if ($value > 0) {
            $string[] = $value . ' ' . $label . ($value > 1 ? '' : '');
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
    <input type="hidden" name="code" id="inputCode" value="<?php echo $project['DA_MA'] ?? null; ?>">
    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label><i class="fal fa-file-signature"></i>&nbsp;Tên Dự Án :</label>
                <input value="<?php echo $project['DA_TEN'] ?? null; ?>" id="inputName" type="text"
                    class="form-control form-control-sm" name="name" readonly>
            </div>

            <div class="form-group">
                <label><i class="fal fa-money-bill-wave"></i>&nbsp;Tổng mức đầu tư (VNĐ):</label>
                <input type="number" class="form-control form-control-sm" id="inputTotalInvestment" 
                    name="DA_TONGMUCDAUTU" value="<?php echo $project['DA_TONGMUCDAUTU'] ?? 0; ?>" min="0" step="1">
            </div>

            
            <?php 
            // Lấy thông tin người phụ trách chi tiết
            $nguoi_phu_trach = '';
            $current_leader_info = null;
            if (!empty($project['DA_NGUOIPHUTRACH'])) {
                $sql_nguoiphutrach = "SELECT tv.TV_MA, tv.TV_TEN, tv.PB_MA, pb.PB_TEN 
                                     FROM thanhvien tv 
                                     LEFT JOIN phongban pb ON tv.PB_MA = pb.PB_MA 
                                     WHERE tv.TV_MA = '" . mysqli_real_escape_string($conn, $project['DA_NGUOIPHUTRACH']) . "'";
                $result_nguoiphutrach = mysqli_query($conn, $sql_nguoiphutrach);
                if ($row = mysqli_fetch_assoc($result_nguoiphutrach)) {
                    $nguoi_phu_trach = $row['TV_TEN'];
                    $current_leader_info = [
                        'TV_MA' => $row['TV_MA'],
                        'TV_TEN' => $row['TV_TEN'],
                        'PB_MA' => $row['PB_MA'],
                        'PB_TEN' => $row['PB_TEN']
                    ];
                }
            }
            ?>
            <?php if (!empty($nguoi_phu_trach)): ?>
            <div class="form-group">
                <label><i class="fas fa-user-tie"></i>&nbsp;Người phụ trách:</label>
                <div class="font-weight-bold"><?php echo htmlspecialchars($nguoi_phu_trach); ?></div>
            </div>
            <?php endif; ?>
    <div class="form-group">
        <label><i class="fal fa-clock"></i>&nbsp;Ngày hết hạn:<br>
            <?php echo convertDate($project['DA_NGAYBATDAU']); ?>
            <?php if (!empty($project['DA_NGAYKETTHUC'])) {
                echo ' - ';
                echo convertDate($project['DA_NGAYKETTHUC']);
            } ?>
        </label>
    </div>
            
    <div class="form-group">
        <label><i class="fal fa-tags"></i>&nbsp;Nhãn:</label><br>
        <?php if (!empty($labels)): ?>
            <?php foreach ($labels as $item): ?>
                <a style="background:<?php echo $item['COLOR']; ?> !important;" href="javascript:;"
                    class="badge badge-primary p-2"><?php echo $item['NAME']; ?>
                            <i class="fas fa-trash-alt removeLabel" data-id="<?php echo $item['ID']; ?>"
                                data-code="<?php echo $item['DA_MA']; ?>"></i>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div><small>Không có nhãn nào</small></div>
                <?php endif; ?>
    </div>
    <div class="form-group">
                <label><i class="fas fa-tasks-alt"></i>&nbsp;Mô tả:</label>
                <textarea class="form-control form-control-sm" name="editor"
                    rows="5"><?php echo htmlspecialchars($project['DA_MOTA'] ?? '', ENT_QUOTES); ?></textarea>
            </div>
            <?php
            // Hiển thị công việc mẫu ngay dưới mô tả nếu có mẫu liên kết
            if (!empty($project['DA_MA'])) {
    include(__DIR__ . '/../../config.php');
    $duan_ma = $project['DA_MA'];
    // Lấy mamau từ duan_maucv
    $sqlDel = "SELECT mamau FROM duan_maucv WHERE duan_ma = ? LIMIT 1";
    $stmtDel = $conn->prepare($sqlDel);
    $stmtDel->bind_param('s', $duan_ma);
    $stmtDel->execute();
    $stmtDel->bind_result($mamau_linked);
    $stmtDel->fetch();
    $stmtDel->close();

    // Lấy danh sách công việc mẫu từ bảng cv_mau
    $sqlTasks = "SELECT ten_cv, thoi_gian_du_kien FROM cv_mau WHERE ma_mau = ? AND trang_thai = 1 ORDER BY id ASC";
    $stmtTasks = $conn->prepare($sqlTasks);
    $stmtTasks->bind_param('i', $mamau_linked);
    $stmtTasks->execute();
    $resultTasks = $stmtTasks->get_result();

    // Hiển thị danh sách công việc mẫu
    // if ($resultTasks && $resultTasks->num_rows > 0) {
    //     echo '<div class="table-responsive mb-2">';
    //     echo '<table class="table table-bordered table-hover">';
    //     echo '<thead><tr>';
    //     echo '<th class="text-center" style="width: 5%">STT</th>';
    //     echo '<th style="width: 60%">Tên công việc</th>';
    //     echo '<th style="width: 20%" class="text-center">Thời gian</th>';
    //     echo '<th style="width: 15%" class="text-center">Thao tác</th>';
    //     echo '</tr></thead><tbody>';

    //     $stt = 1;
    //     while ($row = $resultTasks->fetch_assoc()) {
    //         $taskName = htmlspecialchars($row['ten_cv']);
    //         if (empty($taskName)) continue;
    //         $taskDuration = isset($row['thoi_gian_du_kien']) ? intval($row['thoi_gian_du_kien']) : 0;

    //         echo '<tr>';
    //         echo '<td class="text-center">' . $stt++ . '</td>';
    //         echo '<td>' . $taskName . '</td>';
    //         echo '<td class="text-center">' . $taskDuration . ' ngày</td>';
    //         echo '<td class="text-center">';
    //         echo '<button type="button" class="btn btn-success btn-sm btn-add-task mr-1" data-task-name="' . htmlspecialchars($taskName) . '" data-task-duration="' . $taskDuration . '" title="Thêm">';
    //         echo '<i class="fas fa-plus"></i> Thêm';
    //         echo '</button>';
    //         echo '<button type="button" class="btn btn-danger btn-sm btn-remove-task" data-task-name="' . htmlspecialchars($taskName) . '" title="Xóa">';
    //         echo '<i class="fas fa-trash"></i>';
    //         echo '</button>';
    //         echo '</td>';
    //         echo '</tr>';
    //     }
    //     echo '</tbody></table></div>';
    // }
    $stmtTasks->close();
    $conn->close();
}

            ?>
            <!-- Danh sách công việc thuộc dự án -->
            <div class="form-group mt-4">
                <h5 class="mb-3"><i class="fas fa-tasks me-2"></i>Danh sách công việc</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm" style="margin-bottom:0; table-layout: fixed; width: 100%;">
                        <thead class="table-light" style="color: black; display: block; width: 100%; table-layout: fixed;">
                            <tr style="display: table; table-layout: fixed; width: 100%;">
                                <th class="col-stt text-center">STT</th>
                                <th class="col-ten">Tên công việc</th>
                                <th class="col-phutrach">Người phụ trách</th>
                            </tr>
                        </thead>
                        <tbody style="display: block; max-height: 350px; overflow-y: auto; width: 100%; table-layout: fixed;">
                            <?php
                            // Kết nối database
                            include('../../config.php');
                            
                            // Lấy mã dự án
                            $projectCode = $project['DA_MA'] ?? '';
                            
                            if (!empty($projectCode)) {
                                // Truy vấn lấy tất cả công việc
                                $sql = "SELECT c.*, tv.TV_TEN 
                                        FROM danhsachcongviec c 
                                        LEFT JOIN thanhvien tv ON c.TV_MA = tv.TV_MA 
                                        WHERE c.DA_MA = ? AND c.dscv_trangthaiHD = 1
                                        ORDER BY c.DSCV_MA DESC";
                                        
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("s", $projectCode);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $total_records = $result->num_rows;
                                
                                if ($result->num_rows > 0) {
                                    $stt = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        // Xác định class và text cho trạng thái
                                        switch ($row['DSCV_TRANGTHAI']) {
                                            case 1:
                                                $statusClass = 'bg-warning';
                                                $statusText = 'Đang thực hiện';
                                                break;
                                            case 2:
                                                $statusClass = 'bg-success';
                                                $statusText = 'Đã hoàn thành';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                $statusText = 'Chưa bắt đầu';
                                        }
                                        
                                        echo '<tr style="display: table; table-layout: fixed; width: 100%;">';
                                        echo '<td class="col-stt text-center">' . $stt++ . '</td>';
                                        echo '<td class="col-ten">' . htmlspecialchars($row['DSCV_TEN']) . '</td>';
                                        echo '<td class="col-phutrach">' . ($row['TV_TEN'] ? htmlspecialchars($row['TV_TEN']) : 'Chưa giao') . '</td>';

                                    }
                                    
                                    // Hiển thị tổng số công việc
                                    echo '<tr style="display: table; table-layout: fixed; width: 100%;"><td colspan="3" class="text-muted small">Tổng cộng: ' . $total_records . ' công việc</td></tr>';
                                } else {
                                    echo '<tr style="display: table; table-layout: fixed; width: 100%;"><td colspan="3" class="text-center">Chưa có công việc nào</td></tr>';
                                }
                                
                                $stmt->close();
                            } else {
                                echo '<tr style="display: table; table-layout: fixed; width: 100%;"><td colspan="3" class="text-center text-danger">Không tìm thấy thông tin dự án</td></tr>';
                            }
                            ?>
                            <style>
                            .col-stt { width: 5%; min-width: 60px; }
                            .col-ten { width: 45%; min-width: 200px; }
                            .col-phutrach { width: 25%; min-width: 150px; }
                            .task-row:hover {
                                background-color: #f8f9fa;
                                cursor: pointer;
                            }
                            </style>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="form-group">
                <label><i class="far fa-comments-alt"></i>&nbsp;Hoạt động:</label>
                <textarea id="comment" class="form-control"></textarea>
                <a class="btn btn-sm btn-danger" id="btnComment" data-id="<?php echo $project['DA_MA']; ?>">Gửi</a>
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
                                            data-code="<?php echo $project['DA_MA']; ?>"
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
                <ul class="list-group" style=" overflow-y: auto;">
                    <!--                    <li id="menuMember" class="list-group-item"><i class="far fa-user"></i>&nbsp;Thành viên</li>-->
                    <li id="menuLeader" class="list-group-item" style="height: 40px;"><i class="fas fa-user-tie"></i>&nbsp;Người phụ trách</li>
                    <li id="menuLabel" class="list-group-item" style="height: 40px;"><i class="fal fa-tags"></i>&nbsp;Nhãn</li>
                    <li id="menuWork" class="list-group-item" style="height: 40px;"><i class="fal fa-check-square"></i>&nbsp;Việc cần làm</li>
                    <li id="menuDate" class="list-group-item" style="height: 40px;"><i class="fal fa-clock"></i>&nbsp;Ngày</li>
                </ul>
            </div>
            <div class="form-group">
                <label>Tình Trạng :</label>
                <select name="status" class="form-control" id="inputStatus">
                    <option value="5" <?php echo ($project['DA_TRANGTHAI'] == 5) ? 'selected' : ''; ?>>Chưa tiếp nhận</option>
                    <option value="1" <?php echo ($project['DA_TRANGTHAI'] == 1) ? 'selected' : ''; ?>>Đang Tiến Hành
                    </option>
                    <option value="2" <?php echo ($project['DA_TRANGTHAI'] == 2) ? 'selected' : ''; ?>>Hoàn Thành
                    </option>
                    <option value="3" <?php echo ($project['DA_TRANGTHAI'] == 3) ? 'selected' : ''; ?>>Trễ</option>
                    <option value="4" <?php echo ($project['DA_TRANGTHAI'] == 4) ? 'selected' : ''; ?>>Hủy</option>
                </select>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-info btn-block" id="btnChonMau" onclick="updateDuAnMa('<?php echo $project['DA_MA'] ?? ''; ?>')">
                    <i class="fas fa-list-alt"></i> Chọn mẫu công việc
                </button>
                <div id="selectedTemplateInfo" class="alert alert-info p-2 mt-2" style="display: none;">
                    <i class="fas fa-info-circle"></i> Đã chọn mẫu: <span id="templateName"></span>
                </div>
            </div>
            <!-- Hiển thị tên mẫu công việc đã liên kết -->
            <?php
            if (!empty($project['DA_MA'])) {
                include(__DIR__ . '/../../config.php');
                $duan_ma = $project['DA_MA'];
                $sqlMau = "SELECT m.mamau, m.tenmau, m.macvmau, d.tengoithau, d.id, 
                          (SELECT MAX(dcv.DSCV_NGAYKETTHUC) 
                           FROM danhsachcongviec dcv 
                           WHERE dcv.PARENT_ID = m.mamau 
                           AND dcv.DSCV_TRANGTHAIHD = 1 AND dcv.DA_MA = ?) as ngay_ket_thuc
                          FROM duan_maucv d 
                          JOIN maucv m ON d.mamau = m.mamau 
                          WHERE d.duan_ma = ? AND d.trangthai = 1";
                $stmtMau = $conn->prepare($sqlMau);
                $stmtMau->bind_param('ss', $duan_ma, $duan_ma);
                $stmtMau->execute();
                $resultMau = $stmtMau->get_result();
                
                // Debug: In ra câu truy vấn và kết quả
                // echo "<pre>SQL: " . $sqlMau . "</pre>";
                // echo "<pre>Parameters: " . print_r($duan_ma, true) . "</pre>";
                
                if ($resultMau && $resultMau->num_rows > 0) {
                    echo '<div class="mt-2"><b>Mẫu công việc đã liên kết:</b><ul class="pl-3">';
                    while ($rowMau = $resultMau->fetch_assoc()) {
                        // Hiển thị tên mẫu và tên gói thầu nếu có
                        $tenHienThi = htmlspecialchars($rowMau['tenmau']);
                        if (!empty($rowMau['tengoithau'])) {
                            $tenHienThi .= '_' . htmlspecialchars($rowMau['tengoithau']);
                        }
                        echo '<li class="d-flex align-items-center">'
                            . '<div class="d-flex justify-content-between align-items-center w-100">'
                            . '<div class="d-flex align-items-center">'
                            . '<span class="ten-mau" title="' . $tenHienThi . '">' . $tenHienThi . '</span>'
                            . '</div>'
                            . '<div class="d-flex align-items-center">'
                            . ($rowMau['ngay_ket_thuc'] ? 
                               '<span class="text-muted small mr-3">' . date('d/m/Y', strtotime($rowMau['ngay_ket_thuc'])) . '</span>' : 
                               '<span class="text-muted small mr-3">Chưa cập nhật</span>')
                            . '<button type="button" class="btn btn-sm btn-link text-danger btn-xoa-mau"'
                            . ' data-mamau="' . htmlspecialchars($rowMau['mamau']) . '"'
                            . ' data-duanma="' . htmlspecialchars($duan_ma) . '"'
                            . ' data-id="' . htmlspecialchars($rowMau['id']) . '"'
                            . ' title="Xóa liên kết mẫu công việc"'
                            . '><i class="fas fa-times"></i></button>'
                            . '</div>'
                            . '</div>'
                            . '</li>';
                    }
                    echo '</ul></div>';
                } else {
                    echo '<div class="mt-2 text-muted">Chưa liên kết mẫu công việc nào.</div>';
                }
                $stmtMau->close();
            }
            ?>
        </div>
    </div>
<?php endif; ?>



<script>
    // Xóa công việc mẫu khỏi mẫu liên kết dự án
    // Dùng event delegation để luôn hoạt động kể cả khi DOM thay đổi
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-remove-task');
        if (!btn) return;
        
        e.preventDefault();
        
        var row = btn.closest('tr');
        if (!row) return;
        
        var taskName = row.querySelector('td:nth-child(2)') ? row.querySelector('td:nth-child(2)').textContent.trim() : '';
        var mamau = window.CURRENT_MAMAU;
        var duan_ma = window.CURRENT_DUANMA;
        
        if (!mamau) {
            var mamauNode = document.querySelector('[data-mamau]');
            if (mamauNode) mamau = mamauNode.getAttribute('data-mamau');
        }
        
        if (!duan_ma) {
            var duanmaNode = document.querySelector('[data-duanma]');
            if (duanmaNode) duan_ma = duanmaNode.getAttribute('data-duanma');
            
            // Thử lấy từ URL nếu không tìm thấy trong data attribute
            if (!duan_ma) {
                var urlParams = new URLSearchParams(window.location.search);
                duan_ma = urlParams.get('code');
            }
        }
        
        if (!taskName || !mamau || !duan_ma) {
            console.error('Thiếu dữ liệu để xóa công việc mẫu!', {taskName, mamau, duan_ma});
            alert('Không thể xác định thông tin cần thiết để xóa công việc!');
            return;
        }
        
        if (!confirm('Bạn có chắc muốn xóa công việc "' + taskName + '" khỏi danh sách hiển thị?')) {
            return;
        }
        
        // Hiển thị loading
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xóa...';
        
        // Gửi yêu cầu xóa
        fetch('ajax/update_deleted_works.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'duan_ma=' + encodeURIComponent(duan_ma) + 
                  '&mamau=' + encodeURIComponent(mamau) + 
                  '&task_name=' + encodeURIComponent(taskName)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Xóa dòng khỏi bảng
                if (row) row.remove();
                
                // Hiển thị thông báo thành công
                if (typeof toastr !== 'undefined') {
                    toastr.success('Đã xóa công việc khỏi danh sách hiển thị');
                }
            } else {
                throw new Error(data.msg || 'Lỗi không xác định');
            }
        })
        .catch(error => {
            console.error('Lỗi khi xóa công việc:', error);
            alert('Có lỗi xảy ra khi xóa công việc: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    
                // Đặt đoạn này ở cuối file, chỉ giữ 1 lần duy nhất!
                // Sự kiện click cho nút thêm công việc mẫu
                document.addEventListener('DOMContentLoaded', function() {
                    document.addEventListener('click', function(e) {
                        var btn = e.target.closest('.btn-add-task');
                        if (!btn) return;
                        e.preventDefault();
                        e.stopPropagation(); // Ngăn chặn sự kiện nổi bọt
                        
                        var taskName = btn.getAttribute('data-task-name') || '';
                        var taskDuration = btn.getAttribute('data-task-duration') || 0;
                        console.log('[DEBUG] Click Thêm:', taskName, 'Thời gian:', taskDuration);

                        // Mở modalWorkMau và truyền dữ liệu công việc
                        var modal = $('#modalWorkMau');
                        if (modal.length === 0) {
                            alert('Không tìm thấy form thêm công việc!');
                            return;
                        }

                        // Reset form
                        var form = modal.find('form');
                        if (form.length) form[0].reset();

                        // Điền thông tin công việc vào form
                        var nameInput = modal.find('input[name="name"]');
                        var durationInput = modal.find('input[name="end_date"]');
                        
                        if (nameInput.length) {
                            nameInput.val(taskName);
                            nameInput.focus();
                            
                            // Nếu có thời gian, tự động tính ngày kết thúc
                            if (taskDuration > 0 && durationInput.length) {
                                var startDate = new Date();
                                var endDate = new Date();
                                endDate.setDate(startDate.getDate() + parseInt(taskDuration));
                                
                                // Định dạng ngày thành YYYY-MM-DD
                                var formattedDate = endDate.toISOString().split('T')[0];
                                durationInput.val(formattedDate);
                            }
                        } else {
                            alert('Không tìm thấy các trường cần thiết trong form!');
                            return;
                        }

                        // Mở modal
                        const modalElement = document.getElementById('modalWorkMau');
                        if (modalElement) {
                            // Sử dụng jQuery để mở modal nếu đang dùng jQuery
                            if (typeof $ !== 'undefined') {
                                $('#modalWorkMau').modal('show');
                            } 
                            // Hoặc sử dụng Bootstrap 5 cách thủ công
                            else if (typeof bootstrap !== 'undefined') {
                                const modal = new bootstrap.Modal(modalElement);
                                modal.show();
                            }
                            // Nếu không có cả hai, thử cách khác
                            else {
                                modalElement.style.display = 'block';
                                modalElement.classList.add('show');
                                document.body.classList.add('modal-open');
                                const backdrop = document.createElement('div');
                                backdrop.className = 'modal-backdrop fade show';
                                document.body.appendChild(backdrop);
                            }
                        } else {
                            console.error('Không tìm thấy modalWorkMau');
                        }
                    });
                });
            
</script>

<script>
// Biến lưu trạng thái xoá
var idMauXoa = null;
var tenMauXoa = '';
var $btnXoaMauRef = null;
var $listItemRef = null;

$(document).off('click', '.btn-xoa-mau').on('click', '.btn-xoa-mau', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $btnXoaMauRef = $(this);
    var $listItemRef = $btnXoaMauRef.closest('li');
    var idMauXoa = $btnXoaMauRef.data('id');
    var tenMauXoa = $btnXoaMauRef.siblings('.ten-mau').text().trim();
    if (!idMauXoa) {
        console.error('Thiếu thông tin id bản ghi duan_maucv');
        return;
    }
    // Sử dụng window.confirm thay cho modal
    if (!window.confirm('Bạn có chắc muốn xoá liên kết với mẫu "' + tenMauXoa + '"?')) {
        return;
    }
    var originalHtml = $btnXoaMauRef.html();
    $btnXoaMauRef.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    var apiUrl = window.location.pathname.indexOf('/ajax/') !== -1 ? 'remove_duan_maucv.php' : 'ajax/remove_duan_maucv.php';
    $.ajax({
        url: apiUrl,
        type: 'POST',
        data: { id: idMauXoa },
        dataType: 'json',
        context: { $btn: $btnXoaMauRef, $item: $listItemRef, originalHtml: originalHtml }
    })
    .done(function(data) {
        if (!data) {
            alert('Không nhận được phản hồi từ máy chủ');
            return;
        }
        if (data.success) {
            if (this.$item.length) {
                var $list = this.$item.parent();
                this.$item.remove();
                if ($list.find('li').length === 0) {
                    $list.parent().html('<div class="mt-2 text-muted">Chưa liên kết mẫu công việc nào.</div>');
                }
            }
            var duanma = this.$btn.data('duanma') || window.DUAN_MA_MODAL || '';
            if (typeof loadViewEdit === 'function' && duanma) {
                loadViewEdit(duanma);
                $('#modalEdit').modal('show');
            }
        } else {
            alert(data.msg || 'Có lỗi xảy ra khi xoá liên kết mẫu công việc');
        }
    })
    .fail(function(xhr, status, error) {
        alert('Lỗi kết nối máy chủ khi xoá liên kết mẫu công việc: ' + error);
    })
    .always(function() {
        this.$btn.prop('disabled', false).html(this.originalHtml);
        // Không cần ẩn modal nữa
        idMauXoa = null;
        tenMauXoa = '';
        $btnXoaMauRef = null;
        $listItemRef = null;
    });
});

// Sự kiện click cho menuLeader để mở modal chọn người phụ trách
$(document).on('click', '#menuLeader', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Lấy mã dự án
    var projectCode = $('#inputCode').val();
    if (!projectCode) {
        alert('Không tìm thấy mã dự án!');
        return;
    }
    
    // Load danh sách phòng ban và thông tin người phụ trách hiện tại
    loadPhongBanAndCurrentLeader(projectCode);
    
    // Mở modal
    $('#modalChonLeader').modal('show');
});

// Hàm load danh sách phòng ban và thông tin người phụ trách hiện tại
function loadPhongBanAndCurrentLeader(projectCode) {
    //console.log('Đang load danh sách phòng ban và thông tin người phụ trách...');
    
    // Load danh sách phòng ban trước
    $.ajax({
        url: 'ajax/get_phongban.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            //console.log('Response phòng ban:', data);
            var options = '<option value="">-- Chọn phòng ban --</option>';
            if (data && data.status === 'success' && data.data && data.data.length > 0) {
                $.each(data.data, function(index, pb) {
                    options += '<option value="' + pb.PB_MA + '">' + pb.PB_TEN + '</option>';
                });
            }
            $('#pbSelect').html(options);
            
            // Sau khi load xong phòng ban, load thông tin người phụ trách hiện tại
            loadCurrentLeaderInfo(projectCode);
        },
        error: function(xhr, status, error) {
            console.error('Lỗi load phòng ban:', error);
            $('#pbSelect').html('<option value="">Lỗi khi tải danh sách phòng ban</option>');
        }
    });
}

// Biến chứa thông tin người phụ trách hiện tại
var currentLeaderInfo = <?php echo isset($current_leader_info) ? json_encode($current_leader_info) : 'null'; ?>;
//console.log('Debug - currentLeaderInfo từ PHP:', currentLeaderInfo);
//console.log('Debug - project DA_NGUOIPHUTRACH:', '<?php echo $project['DA_NGUOIPHUTRACH'] ?? 'null'; ?>');

// Hàm load thông tin người phụ trách hiện tại
function loadCurrentLeaderInfo(projectCode) {
    //console.log('=== DEBUG loadCurrentLeaderInfo ===');
    //console.log('projectCode:', projectCode);
    
    // Thử sử dụng thông tin có sẵn trước
    if (currentLeaderInfo && currentLeaderInfo.TV_MA && currentLeaderInfo.PB_MA) {
        //console.log('✅ Sử dụng thông tin có sẵn - Người phụ trách:', currentLeaderInfo.TV_TEN);
        $('#pbSelect').val(currentLeaderInfo.PB_MA);
        loadThanhVienByPhongBan(currentLeaderInfo.PB_MA, currentLeaderInfo.TV_MA);
        return;
    }
    
    // Nếu không có thông tin, lấy từ database
    //console.log('🔄 Lấy thông tin người phụ trách từ database...');
    $.ajax({
        url: 'ajax/get_project_leader.php',
        type: 'GET',
        data: { duan_ma: projectCode },
        dataType: 'json',
        success: function(data) {
            //console.log('Response get_project_leader:', data);
            if (data && data.status === 'success' && data.has_leader && data.leader_id) {
                //console.log('✅ Tìm thấy người phụ trách:', data.leader_id);
                
                // Lấy thông tin chi tiết về người phụ trách bằng cách gọi tất cả phòng ban
                // và tìm người phụ trách trong đó
                $.ajax({
                    url: 'ajax/get_phongban.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(pbData) {
                        //console.log('Response phòng ban:', pbData);
                        if (pbData && pbData.status === 'success' && pbData.data) {
                            // Tìm phòng ban chứa người phụ trách
                            var foundLeader = false;
                            pbData.data.forEach(function(pb) {
                                if (!foundLeader) {
                                    $.ajax({
                                        url: 'ajax/get_thanhvien_by_phongban.php',
                                        type: 'GET',
                                        data: { pb_ma: pb.PB_MA },
                                        dataType: 'json',
                                        async: false, // Để đảm bảo thứ tự
                                        success: function(tvData) {
                                            if (tvData && tvData.status === 'success' && tvData.data) {
                                                var leader = tvData.data.find(function(tv) {
                                                    return tv.TV_MA === data.leader_id;
                                                });
                                                
                                                if (leader) {
                                                    //console.log('✅ Tìm thấy người phụ trách trong phòng ban:', pb.PB_TEN);
                                                    foundLeader = true;
                                                    $('#pbSelect').val(pb.PB_MA);
                                                    loadThanhVienByPhongBan(pb.PB_MA, data.leader_id);
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                            
                            if (!foundLeader) {
                                console.log('❌ Không tìm thấy người phụ trách trong bất kỳ phòng ban nào');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi load phòng ban:', error);
                    }
                });
            } else {
                //console.log('❌ Dự án chưa có người phụ trách');
            }
        },
        error: function(xhr, status, error) {
            //console.error('Lỗi load thông tin người phụ trách:', error);
        }
    });
}

// Hàm load danh sách phòng ban (giữ lại để tương thích)
function loadPhongBan() {
    //console.log('Đang load danh sách phòng ban...');
    $.ajax({
        url: 'ajax/get_phongban.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            //console.log('Response phòng ban:', data);
            var options = '<option value="">-- Chọn phòng ban --</option>';
            if (data && data.status === 'success' && data.data && data.data.length > 0) {
                $.each(data.data, function(index, pb) {
                    options += '<option value="' + pb.PB_MA + '">' + pb.PB_TEN + '</option>';
                });
            }
            $('#pbSelect').html(options);
            //console.log('Đã load xong phòng ban, options:', options);
        },
        error: function(xhr, status, error) {
            //console.error('Lỗi load phòng ban:', error);
            $('#pbSelect').html('<option value="">Lỗi khi tải danh sách phòng ban</option>');
        }
    });
}

// Sự kiện khi chọn phòng ban
$(document).on('change', '#pbSelect', function() {
    var pbId = $(this).val();
    if (pbId) {
        loadThanhVienByPhongBan(pbId);
    } else {
        $('#leaderSelect').html('<option value="">-- Vui lòng chọn phòng ban trước --</option>').prop('disabled', true);
    }
});

// Hàm load thành viên theo phòng ban
function loadThanhVienByPhongBan(pbId, selectedLeaderId = null) {
    //console.log('Đang load thành viên cho phòng ban:', pbId, 'Người phụ trách hiện tại:', selectedLeaderId);
    $('#leaderSelect').html('<option value="">Đang tải...</option>').prop('disabled', true);
    
    $.ajax({
        url: 'ajax/get_thanhvien_by_phongban.php',
        type: 'GET',
        data: { pb_ma: pbId },
        dataType: 'json',
        success: function(data) {
            //console.log('Response thành viên:', data);
            var options = '<option value="">-- Chọn người phụ trách --</option>';
            if (data && data.status === 'success' && data.data && data.data.length > 0) {
                $.each(data.data, function(index, tv) {
                    var selected = (selectedLeaderId && tv.TV_MA === selectedLeaderId) ? 'selected' : '';
                    options += '<option value="' + tv.TV_MA + '" ' + selected + '>' + tv.TV_TEN + '</option>';
                });
            }
            $('#leaderSelect').html(options).prop('disabled', false);
            //console.log('Đã load xong thành viên, options:', options);
        },
        error: function(xhr, status, error) {
            //console.error('Lỗi load thành viên:', error);
            $('#leaderSelect').html('<option value="">Lỗi khi tải danh sách thành viên</option>').prop('disabled', true);
        }
    });
}

// Sự kiện lưu người phụ trách
$(document).on('click', '#btnLuuLeader', function() {
    var projectCode = $('#inputCode').val();
    var leaderId = $('#leaderSelect').val();
    
    if (!leaderId) {
        alert('Vui lòng chọn người phụ trách!');
        return;
    }
    
    // Hiển thị loading
    var $btn = $(this);
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('Đang lưu...');
    
    $.ajax({
        url: 'ajax/update_project_leader.php',
        type: 'POST',
        data: {
            duan_ma: projectCode,
            leader_id: leaderId
        },
        dataType: 'json',
        success: function(data) {
            var isSuccess = (data && (data.success === true || data.status === 'success'));
            if (isSuccess) {
                // Đóng modal
                $('#modalChonLeader').modal('hide');
                
                // Reload lại thông tin dự án
                if (typeof loadViewEdit === 'function') {
                    loadViewEdit(projectCode);
                }
                
                // Hiển thị thông báo thành công
                if (typeof toastr !== 'undefined') {
                    toastr.success('Đã cập nhật người phụ trách thành công!');
                } else {
                    alert('Đã cập nhật người phụ trách thành công!');
                }
            } else {
                var errMsg = (data && (data.msg || data.message)) ? (data.msg || data.message) : 'Có lỗi xảy ra khi cập nhật người phụ trách!';
                alert(errMsg);
            }
        },
        error: function() {
            alert('Lỗi kết nối máy chủ!');
        },
        complete: function() {
            $btn.prop('disabled', false).text(originalText);
        }
    });
});
</script>

<!-- Modal chọn người phụ trách -->
<div class="modal fade" id="modalChonLeader" tabindex="-1" role="dialog" aria-labelledby="modalChonLeaderLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalChonLeaderLabel">Chọn người phụ trách dự án</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label>Phòng ban:</label>
            <select id="pbSelect" class="form-control mb-2"></select>
        </div>
        <label>Người phụ trách:</label>
        <select id="leaderSelect" class="form-control"></select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btnLuuLeader">Lưu</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<style>
    /* CSS độ dài cho tên mẫu */
    .ten-mau {
        display: inline-block;
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    @media (max-width: 600px) {
        .ten-mau {
            max-width: 90px;
        }
    }

    .ul-cvmau-scroll {
        max-height: 170px;
        overflow-y: auto;
        margin-bottom: 0.5rem;
        padding-right: 8px;
    }
</style>

<?php
// Đóng kết nối database nếu tồn tại
if (isset($conn) && $conn) {
    $conn->close();
}
?>