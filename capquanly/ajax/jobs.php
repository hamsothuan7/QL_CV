<?php
/**
 * jobs.php — Render Kanban + Table views for Dự án
 */
if (!function_exists('isValidDate')) {
    function isValidDate($date) {
        return ($date && $date !== '0000-00-00' && strtotime($date) !== false);
    }
}

// Helper: render 1 kanban card
if (!function_exists('renderProjectKanbanCard')) {
    function renderProjectKanbanCard($item, $conn) {
        $id = $item['DA_MA'];
        $isExpired = (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d'));
        $commentCount = $item['comment_count'] ?? 0;
        
        $cardClass = 'cv-card';
        // Dùng css của cv (công việc) cho dự án cũng được
        
        $dateClass = 'cv-date-ok';
        if ($isExpired) {
            $dateClass = 'cv-date-danger';
        } elseif (in_array($item['DA_TRANGTHAI'], [1])) {
            $dateClass = 'cv-date-progress';
        } elseif (in_array($item['DA_TRANGTHAI'], [5])) {
            $dateClass = 'cv-date-pending';
        }
        
        $startDate = !empty($item['DA_NGAYBATDAU']) ? date('d/m', strtotime($item['DA_NGAYBATDAU'])) : '';
        $endDate = isValidDate($item['DA_NGAYKETTHUC']) ? date('d/m', strtotime($item['DA_NGAYKETTHUC'])) : '';
        $dateStr = $startDate . ($endDate ? ' - ' . $endDate : '');
        
        $html = '<li data-id="'.$id.'">';
        $html .= '<div class="'.$cardClass.'">';
        
        // Hidden hover actions
        $html .= '<div class="cv-card-actions">';
        // View / Edit / Mở thẻ
        $html .= '<button class="cv-card-action-btn viewDetail" data-id="'.$id.'" title="Mở thẻ"><i class="fal fa-id-card"></i></button>';
        $html .= '<button class="cv-card-action-btn viewDetail2" data-id="'.$id.'" title="Chỉnh sửa"><i class="fal fa-pen"></i></button>';
        $html .= '<button class="cv-card-action-btn cv-action-danger removeProject" data-id="'.$id.'" title="Xóa thẻ"><i class="fal fa-trash-alt"></i></button>';
        $html .= '</div>';
        
        // Card name (2-line clamp)
        $html .= '<div class="cv-card-name">'.$item['DA_TEN'].'</div>';
        
        // Card footer
        $html .= '<div class="cv-card-footer">';
        $html .= '<span class="cv-card-date '.$dateClass.'"><i class="fal fa-calendar-alt"></i> '.$dateStr.'</span>';
        
        $html .= '<div class="cv-card-meta">';
        // Nêu label
        $labelsArr = !empty($item['labels']) ? explode(',', $item['labels']) : [];
        if (!empty($labelsArr)) {
            $html .= '<span class="cv-meta-item" style="display:flex;gap:4px;">';
            foreach ($labelsArr as $lbl) {
                $html .= '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background-color:'.$lbl.';"></span>';
            }
            $html .= '</span>';
        }
        
        $html .= '<span class="cv-meta-item viewDetail2" data-id="'.$id.'"><i class="far fa-comment"></i> '.$commentCount.'</span>';
        $html .= '</div>';
        
        $html .= '</div>'; // card-footer
        $html .= '</div>'; // cv-card
        $html .= '</li>';
        
        return $html;
    }
}

// Helper: render kanban column
if (!function_exists('renderProjectKanbanColumn')) {
    function renderProjectKanbanColumn($title, $dotClass, $statusCode, $items, $listId, $conn, $totalCount = 0, $initLimit = 0) {
        $count = is_array($items) ? count($items) : 0;
        $html = '<div class="cv-kanban-col">';
        $html .= '<div class="cv-kanban-col-header">';
        $displayCount = ($totalCount > 0) ? $totalCount : $count;
        $html .= '<div class="cv-col-title"><span class="cv-col-dot '.$dotClass.'"></span> '.$title.' <span class="cv-col-count">'.$displayCount.'</span></div>';
        $html .= '<button data-status="'.$statusCode.'" class="cv-col-add-btn btnInsert" title="Thêm dự án"><i class="fa fa-plus"></i></button>';
        $html .= '</div>';
        // Wrapper body cho danh sách
        $html .= '<div class="cv-kanban-col-body" id="'.str_replace('-list','',$listId).'">';
        $html .= '<ul id="'.$listId.'">';
        if (!empty($items)) {
            foreach ($items as $item) {
                $html .= renderProjectKanbanCard($item, $conn);
            }
            $remaining = $totalCount - $count;
            if ($initLimit > 0 && $remaining > 0) {
                $html .= '<li class="cv-load-more-item"><button class="cv-load-more-btn" data-status="'.$statusCode.'" data-offset="'.$count.'" data-total="'.$totalCount.'"><i class="fal fa-arrow-down"></i> Xem thêm ('.$remaining.')</button></li>';
            }
        } else {
            $html .= '<li class="cv-empty-col"><i class="fal fa-inbox" style="font-size:22px;margin-bottom:6px;display:block;"></i>Chưa có dự án</li>';
        }
        $html .= '</ul></div></div>';
        return $html;
    }
}

$kanbanInitLimit = $kanbanInitLimit ?? 30;
?>

<!-- ============ KANBAN VIEW ============ -->
<div class="cv-view-container" id="kanbanView">
    <div class="cv-kanban-wrapper jobs-list-wrapper">
        <?php echo renderProjectKanbanColumn('Chưa tiếp nhận', 'cv-dot-pending', 5, $projectsStart ?? [], 'new-jobs-list', $conn, $countStart ?? 0, $kanbanInitLimit); ?>
        <?php echo renderProjectKanbanColumn('Đang tiến hành', 'cv-dot-progress', 1, $projectsInProgress ?? [], 'in-progress-list', $conn, $countInProgress ?? 0, $kanbanInitLimit); ?>
        <?php echo renderProjectKanbanColumn('Hoàn thành', 'cv-dot-done', 2, $projectsFinish ?? [], 'complete-jobs-list', $conn, $countFinish ?? 0, $kanbanInitLimit); ?>
        <?php echo renderProjectKanbanColumn('Trễ', 'cv-dot-late', 3, $projectsMove ?? [], 'waiting-jobs-list', $conn, $countMove ?? 0, $kanbanInitLimit); ?>
        <?php echo renderProjectKanbanColumn('Hủy', 'cv-dot-cancel', 4, $projectsCancel ?? [], 'rework-jobs-list', $conn, $countCancel ?? 0, $kanbanInitLimit); ?>
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
                        <th>Tên dự án</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Người phụ trách</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $allProjects = $tableData['data'] ?? [];
                    $tablePage = $tableData['page'] ?? 1;
                    $tablePerPage = $tableData['perPage'] ?? 20;
                    $tableTotal = $tableData['total'] ?? 0;
                    $tableTotalPages = $tableData['pages'] ?? 1;

                    $statusMap = [
                        5 => ['label' => 'Chưa tiếp nhận', 'class' => 'cv-badge-pending'],
                        1 => ['label' => 'Đang tiến hành', 'class' => 'cv-badge-progress'],
                        2 => ['label' => 'Hoàn thành', 'class' => 'cv-badge-done'],
                        3 => ['label' => 'Trễ', 'class' => 'cv-badge-late'],
                        4 => ['label' => 'Hủy', 'class' => 'cv-badge-cancel'],
                    ];

                    if (empty($allProjects)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--cv-text-muted);font-style:italic;">Không có dự án nào.</td></tr>
                    <?php else:
                        $stt = ($tablePage - 1) * $tablePerPage;
                        foreach ($allProjects as $p):
                            $stt++;
                            $startFormatted = !empty($p['DA_NGAYBATDAU']) ? date('d/m/Y', strtotime($p['DA_NGAYBATDAU'])) : '';
                            $endFormatted = isValidDate($p['DA_NGAYKETTHUC']) ? date('d/m/Y', strtotime($p['DA_NGAYKETTHUC'])) : '';
                            $dateStr = $startFormatted . ($endFormatted ? ' → ' . $endFormatted : '');
                            $statusInfo = $statusMap[$p['DA_TRANGTHAI']] ?? ['label' => '—', 'class' => ''];
                            
                            // Lấy tên tv người phụ trách (Có thể sẽ join bảng sau nhưng hiện lấy mã để show, hoặc truy vấn rời vì getProjectByStatus gom quá gắt).
                            // Ở đây chỉ render mã TV hoặc tuỳ biến.
                            $nguoiPtrach = htmlspecialchars($p['DA_NGUOIPHUTRACH']);
                    ?>
                        <tr>
                            <td class="cv-table-stt"><?php echo $stt; ?></td>
                            <td class="cv-table-task-name viewDetail" data-id="<?php echo $p['DA_MA']; ?>" title="<?php echo htmlspecialchars($p['DA_TEN']); ?>">
                                <?php echo htmlspecialchars($p['DA_TEN']); ?>
                            </td>
                            <td>
                                <span class="cv-table-status-badge <?php echo $statusInfo['class']; ?>">
                                    <i class="fas fa-circle" style="font-size:6px;"></i> <?php echo $statusInfo['label']; ?>
                                </span>
                            </td>
                            <td class="cv-table-date"><?php echo $dateStr; ?></td>
                            <td><?php echo $nguoiPtrach; ?></td>
                            <td>
                                <div class="cv-table-actions">
                                    <button class="cv-table-action-btn viewDetail" data-id="<?php echo $p['DA_MA']; ?>" title="Xem chi tiết"><i class="fal fa-eye"></i></button>
                                    <button class="cv-table-action-btn viewDetail2" data-id="<?php echo $p['DA_MA']; ?>" title="Chỉnh sửa"><i class="fal fa-pen"></i></button>
                                    <button class="cv-table-action-btn cv-action-danger removeProject" data-id="<?php echo $p['DA_MA']; ?>" title="Xóa"><i class="fal fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php 
        if (function_exists('renderPagination')) {
            echo renderPagination($tablePage, $tableTotalPages, $tablePerPage, $tableTotal);
        }
        ?>
    </div>
</div>
