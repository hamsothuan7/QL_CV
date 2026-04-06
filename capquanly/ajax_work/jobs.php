
<?php
/**
 * jobs.php — Render Kanban + Table views
 * 
 * THAY ĐỔI:
 * - Xóa getProjectName() (N+1) → dùng DA_TEN từ JOIN ở query_helper
 * - Xóa getCommentCount() (N+1) → dùng comment_count từ subquery ở query_helper
 * - Thêm UI phân trang cho Table View
 * - Thêm nút "Xem thêm" cho Kanban lazy load
 * - Sắp xếp theo ngày còn lại (giữ nguyên logic)
 */

// Sắp xếp công việc theo số ngày còn lại
function sortJobsByRemainingDays(&$jobs) {
    if (!is_array($jobs)) return;
    usort($jobs, function($a, $b) {
        $today = new DateTime();
        $dateA = new DateTime($a['DSCV_NGAYKETTHUC']);
        $dateB = new DateTime($b['DSCV_NGAYKETTHUC']);
        $daysA = $today->diff($dateA)->days * ($dateA < $today ? -1 : 1);
        $daysB = $today->diff($dateB)->days * ($dateB < $today ? -1 : 1);
        return $daysA - $daysB;
    });
}

// Sắp xếp
sortJobsByRemainingDays($projectsStart);
sortJobsByRemainingDays($projectsInProgress);
sortJobsByRemainingDays($projectsMove);
sortJobsByRemainingDays($projectsFinish);
sortJobsByRemainingDays($projectsCancel);

// Helper: render 1 kanban card (tối ưu: dùng DA_TEN + comment_count đã có sẵn từ query)
function renderKanbanCard($item, $conn) {
    $id = $item['DSCV_MA'];
    $isPrivate = empty($item['DA_MA']);
    $isExpired = ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d'));
    // Dùng comment_count từ query thay vì gọi query riêng (loại bỏ N+1)
    $commentCount = $item['comment_count'] ?? 0;
    
    $cardClass = 'cv-card';
    if ($isPrivate) $cardClass .= ' cv-card-private';
    
    // Determine date badge style
    $dateClass = 'cv-date-ok';
    if ($isExpired) {
        $dateClass = 'cv-date-danger';
    } elseif (in_array($item['DSCV_TRANGTHAI'], [1])) {
        $dateClass = 'cv-date-progress';
    } elseif (in_array($item['DSCV_TRANGTHAI'], [5])) {
        $dateClass = 'cv-date-pending';
    }
    
    $startDate = date('d/m', strtotime($item['DSCV_NGAYBATDAU']));
    $endDate = date('d/m', strtotime($item['DSCV_NGAYKETTHUC']));
    
    $html = '<li data-id="'.$id.'">';
    $html .= '<div class="'.$cardClass.'">';
    
    // Hidden hover actions
    $html .= '<div class="cv-card-actions">';
    $html .= '<button class="cv-card-action-btn viewDetail" data-id="'.$id.'" title="Xem chi tiết"><i class="fal fa-eye"></i></button>';
    $html .= '<button class="cv-card-action-btn viewDetail2" data-id="'.$id.'" title="Chỉnh sửa"><i class="fal fa-pen"></i></button>';
    $html .= '</div>';
    
    // Card name (2-line clamp)
    $html .= '<div class="cv-card-name">'.$item['DSCV_TEN'].'</div>';
    
    // Card footer
    $html .= '<div class="cv-card-footer">';
    $html .= '<span class="cv-card-date '.$dateClass.'"><i class="fal fa-calendar-alt"></i> '.$startDate.' - '.$endDate.'</span>';
    
    $html .= '<div class="cv-card-meta">';
    if ($item['DSCV_NGAYKETTHUC_TV'] != NULL) {
        $html .= '<span class="cv-card-alert viewDetail2" data-id="'.$id.'" title="Yêu cầu đổi ngày: '.$item['DSCV_NGAYKETTHUC_TV'].'"><i class="far fa-bell"></i> 1</span>';
    }
    $html .= '<span class="cv-meta-item viewDetail2" data-id="'.$id.'"><i class="far fa-comment"></i> '.$commentCount.'</span>';
    $html .= '</div>';
    
    $html .= '</div>'; // card-footer
    $html .= '</div>'; // cv-card
    $html .= '</li>';
    
    return $html;
}

// Helper: render kanban column (thêm lazy load "Xem thêm")
function renderKanbanColumn($title, $dotClass, $statusCode, $items, $listId, $conn, $totalCount = 0, $initLimit = 0) {
    $count = is_array($items) ? count($items) : 0;
    $html = '<div class="cv-kanban-col">';
    $html .= '<div class="cv-kanban-col-header">';
    // Hiển thị tổng số thực tế, không chỉ số đã load
    $displayCount = ($totalCount > 0) ? $totalCount : $count;
    $html .= '<div class="cv-col-title"><span class="cv-col-dot '.$dotClass.'"></span> '.$title.' <span class="cv-col-count">'.$displayCount.'</span></div>';
    $html .= '<button data-status="'.$statusCode.'" class="cv-col-add-btn btnInsert" title="Thêm công việc"><i class="fa fa-plus"></i></button>';
    $html .= '</div>';
    $html .= '<div class="cv-kanban-col-body" id="'.str_replace('-list','',$listId).'">';
    $html .= '<ul id="'.$listId.'">';
    if (!empty($items)) {
        foreach ($items as $item) {
            $html .= renderKanbanCard($item, $conn);
        }
        // Nút "Xem thêm" nếu còn card chưa load
        $remaining = $totalCount - $count;
        if ($initLimit > 0 && $remaining > 0) {
            $html .= '<li class="cv-load-more-item"><button class="cv-load-more-btn" data-status="'.$statusCode.'" data-offset="'.$count.'" data-total="'.$totalCount.'"><i class="fal fa-arrow-down"></i> Xem thêm ('.$remaining.')</button></li>';
        }
    } else {
        $html .= '<li class="cv-empty-col"><i class="fal fa-inbox" style="font-size:22px;margin-bottom:6px;display:block;"></i>Chưa có công việc</li>';
    }
    $html .= '</ul></div></div>';
    return $html;
}

// Lấy biến $kanbanInitLimit từ scope cha (nếu có)
$kanbanInitLimit = $kanbanInitLimit ?? 30;
?>

<!-- ============ KANBAN VIEW ============ -->
<div class="cv-view-container" id="kanbanView">
    <div class="cv-kanban-wrapper jobs-list-wrapper">
        <?php echo renderKanbanColumn('Chưa tiếp nhận', 'cv-dot-pending', 5, $projectsStart, 'new-jobs-list', $conn, $countStart ?? 0, $kanbanInitLimit); ?>
        <?php echo renderKanbanColumn('Đang tiến hành', 'cv-dot-progress', 1, $projectsInProgress, 'in-progress-list', $conn, $countInProgress ?? 0, $kanbanInitLimit); ?>
        <?php echo renderKanbanColumn('Hoàn thành', 'cv-dot-done', 2, $projectsFinish, 'complete-jobs-list', $conn, $countFinish ?? 0, $kanbanInitLimit); ?>
        <?php echo renderKanbanColumn('Trễ', 'cv-dot-late', 3, $projectsMove, 'waiting-jobs-list', $conn, $countMove ?? 0, $kanbanInitLimit); ?>
        <?php echo renderKanbanColumn('Hủy', 'cv-dot-cancel', 4, $projectsCancel, 'rework-jobs-list', $conn, $countCancel ?? 0, $kanbanInitLimit); ?>
    </div>
</div>

<!-- ============ TABLE VIEW ============ -->
<div class="cv-view-container cv-hidden" id="tableView">
    <div class="cv-table-wrapper">
        <div class="cv-table-scroll">
            <table class="cv-table">
                <thead>
                    <tr>
                        <th class="cv-table-stt">STT</th>
                        <th>Tên công việc</th>
                        <th>Thuộc dự án</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Lấy data từ biến $tableData (đã phân trang từ query_helper)
                    $allTasks = $tableData['data'] ?? [];
                    $tablePage = $tableData['page'] ?? 1;
                    $tablePerPage = $tableData['perPage'] ?? 20;
                    $tableTotal = $tableData['total'] ?? 0;
                    $tableTotalPages = $tableData['pages'] ?? 1;

                    // Map status code → label + class
                    $statusMap = [
                        5 => ['label' => 'Chưa tiếp nhận', 'class' => 'cv-badge-pending'],
                        1 => ['label' => 'Đang tiến hành', 'class' => 'cv-badge-progress'],
                        2 => ['label' => 'Hoàn thành', 'class' => 'cv-badge-done'],
                        6 => ['label' => 'Hoàn thành', 'class' => 'cv-badge-done'],
                        3 => ['label' => 'Trễ', 'class' => 'cv-badge-late'],
                        4 => ['label' => 'Hủy', 'class' => 'cv-badge-cancel'],
                    ];

                    if (empty($allTasks)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--cv-text-muted);font-style:italic;">Không có công việc nào.</td></tr>
                    <?php else:
                        // STT bắt đầu từ vị trí offset
                        $stt = ($tablePage - 1) * $tablePerPage;
                        foreach ($allTasks as $task):
                            $stt++;
                            $isPrivate = empty($task['DA_MA']);
                            // Dùng DA_TEN từ JOIN thay vì gọi getProjectName() (loại bỏ N+1)
                            $projectName = $isPrivate 
                                ? '<span style="color:#92400e;font-style:italic;">Công việc riêng</span>' 
                                : htmlspecialchars($task['DA_TEN'] ?? '—');
                            $startFormatted = date('d/m/Y', strtotime($task['DSCV_NGAYBATDAU']));
                            $endFormatted = date('d/m/Y', strtotime($task['DSCV_NGAYKETTHUC']));
                            $statusInfo = $statusMap[$task['DSCV_TRANGTHAI']] ?? ['label' => '—', 'class' => ''];
                    ?>
                        <tr class="<?php echo $isPrivate ? 'cv-row-private' : ''; ?>">
                            <td class="cv-table-stt"><?php echo $stt; ?></td>
                            <td class="cv-table-task-name viewDetail" data-id="<?php echo $task['DSCV_MA']; ?>" title="<?php echo htmlspecialchars($task['DSCV_TEN']); ?>">
                                <?php echo htmlspecialchars($task['DSCV_TEN']); ?>
                            </td>
                            <td class="cv-table-project"><?php echo $projectName; ?></td>
                            <td>
                                <span class="cv-table-status-badge <?php echo $statusInfo['class']; ?>">
                                    <i class="fas fa-circle" style="font-size:6px;"></i> <?php echo $statusInfo['label']; ?>
                                </span>
                            </td>
                            <td class="cv-table-date"><?php echo $startFormatted; ?> → <?php echo $endFormatted; ?></td>
                            <td>
                                <div class="cv-table-actions">
                                    <button class="cv-table-action-btn viewDetail" data-id="<?php echo $task['DSCV_MA']; ?>" title="Xem chi tiết"><i class="fal fa-eye"></i></button>
                                    <button class="cv-table-action-btn viewDetail2" data-id="<?php echo $task['DSCV_MA']; ?>" title="Chỉnh sửa"><i class="fal fa-pen"></i></button>
                                    <button class="cv-table-action-btn cv-action-danger removeProject" data-id="<?php echo $task['DSCV_MA']; ?>" title="Xóa"><i class="fal fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php 
        // Render phân trang (hàm từ query_helper.php)
        if (function_exists('renderPagination')) {
            echo renderPagination($tablePage, $tableTotalPages, $tablePerPage, $tableTotal);
        }
        ?>
    </div>
</div>