<?php
/**
 * get_kanban_cards.php — AJAX endpoint trả về thêm cards cho Kanban lazy load
 * 
 * Params: status, offset, limit, keywords, from_date, to_date, project_id
 * Returns: JSON { html: "...", loaded: N }
 */
include('../../config.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['html' => '', 'loaded' => 0]);
    exit;
}

include_once(__DIR__ . '/query_helper.php');

$status = (int)($_GET['status'] ?? 0);
$offset = (int)($_GET['offset'] ?? 0);
$limit = min(100, max(1, (int)($_GET['limit'] ?? 30)));
$keywords = $_GET['keywords'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$project_id = $_GET['project_id'] ?? '';

if ($status < 1 || $status > 6) {
    echo json_encode(['html' => '', 'loaded' => 0]);
    exit;
}

$tasks = getTasksForKanban($conn, $status, $keywords, $from_date, $to_date, $project_id, $limit, $offset);

// Sắp xếp theo ngày còn lại (giữ nhất quán với jobs.php)
if (!empty($tasks)) {
    usort($tasks, function($a, $b) {
        $today = new DateTime();
        $dateA = new DateTime($a['DSCV_NGAYKETTHUC']);
        $dateB = new DateTime($b['DSCV_NGAYKETTHUC']);
        $daysA = $today->diff($dateA)->days * ($dateA < $today ? -1 : 1);
        $daysB = $today->diff($dateB)->days * ($dateB < $today ? -1 : 1);
        return $daysA - $daysB;
    });
}

// Include renderKanbanCard function từ jobs.php
// Nhưng ta không include jobs.php vì nó render toàn bộ HTML
// → Viết inline render card ở đây

$html = '';
foreach ($tasks as $item) {
    $id = $item['DSCV_MA'];
    $isPrivate = empty($item['DA_MA']);
    $isExpired = ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d'));
    $commentCount = $item['comment_count'] ?? 0;
    
    $cardClass = 'cv-card';
    if ($isPrivate) $cardClass .= ' cv-card-private';
    
    $dateClass = 'cv-date-ok';
    if ($isExpired) {
        $dateClass = 'cv-date-danger';
    } elseif ($item['DSCV_TRANGTHAI'] == 1) {
        $dateClass = 'cv-date-progress';
    } elseif ($item['DSCV_TRANGTHAI'] == 5) {
        $dateClass = 'cv-date-pending';
    }
    
    $startDate = date('d/m', strtotime($item['DSCV_NGAYBATDAU']));
    $endDate = date('d/m', strtotime($item['DSCV_NGAYKETTHUC']));
    
    $html .= '<li data-id="'.$id.'">';
    $html .= '<div class="'.$cardClass.'">';
    $html .= '<div class="cv-card-actions">';
    $html .= '<button class="cv-card-action-btn viewDetail" data-id="'.$id.'" title="Xem chi tiết"><i class="fal fa-eye"></i></button>';
    $html .= '<button class="cv-card-action-btn viewDetail2" data-id="'.$id.'" title="Chỉnh sửa"><i class="fal fa-pen"></i></button>';
    $html .= '</div>';
    $html .= '<div class="cv-card-name">'.htmlspecialchars($item['DSCV_TEN']).'</div>';
    $html .= '<div class="cv-card-footer">';
    $html .= '<span class="cv-card-date '.$dateClass.'"><i class="fal fa-calendar-alt"></i> '.$startDate.' - '.$endDate.'</span>';
    $html .= '<div class="cv-card-meta">';
    if ($item['DSCV_NGAYKETTHUC_TV'] != NULL) {
        $html .= '<span class="cv-card-alert viewDetail2" data-id="'.$id.'" title="Yêu cầu đổi ngày: '.$item['DSCV_NGAYKETTHUC_TV'].'"><i class="far fa-bell"></i> 1</span>';
    }
    $html .= '<span class="cv-meta-item viewDetail2" data-id="'.$id.'"><i class="far fa-comment"></i> '.$commentCount.'</span>';
    $html .= '</div>';
    $html .= '</div></div></li>';
}

echo json_encode([
    'html' => $html,
    'loaded' => count($tasks)
]);
?>
