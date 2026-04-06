<?php
/**
 * get_view.php — AJAX endpoint trả về HTML cho danh sách công việc
 * 
 * THAY ĐỔI:
 * - Dùng query_helper.php thay vì duplicate querySql()
 * - Nhận thêm params: page, per_page cho phân trang
 * - Code gọn hơn, dùng chung logic với danhsachcv.php
 */
include('../../config.php');
session_start();

// Check if the request is made via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    // Include query helper dùng chung
    include_once(__DIR__ . '/query_helper.php');

    // Lấy các tham số lọc từ request
    $keywords = $_GET['keywords'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $project_id = $_GET['project_id'] ?? '';

    // Tham số phân trang
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = in_array((int)($_GET['per_page'] ?? 20), [20, 50, 100]) ? (int)$_GET['per_page'] : 20;

    // Số card Kanban load lần đầu (lazy load)
    $kanbanInitLimit = 30;

    // === KANBAN DATA (lazy load) ===
    $projectsStart = getTasksForKanban($conn, 5, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
    $projectsInProgress = getTasksForKanban($conn, 1, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
    $projectsMove = getTasksForKanban($conn, 3, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
    $projectsFinish = getTasksForKanban($conn, 2, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);
    $projectsCancel = getTasksForKanban($conn, 4, $keywords, $from_date, $to_date, $project_id, $kanbanInitLimit, 0);

    // Đếm tổng cho lazy load
    $countStart = countTasksForKanban($conn, 5, $keywords, $from_date, $to_date, $project_id);
    $countInProgress = countTasksForKanban($conn, 1, $keywords, $from_date, $to_date, $project_id);
    $countMove = countTasksForKanban($conn, 3, $keywords, $from_date, $to_date, $project_id);
    $countFinish = countTasksForKanban($conn, 2, $keywords, $from_date, $to_date, $project_id);
    $countCancel = countTasksForKanban($conn, 4, $keywords, $from_date, $to_date, $project_id);

    // === TABLE DATA (phân trang) ===
    $tableData = getTasksForTable($conn, $keywords, $from_date, $to_date, $project_id, $page, $perPage);

    $data = [
        'conn' => $conn,
        'projectsStart' => $projectsStart,
        'projectsInProgress' => $projectsInProgress,
        'projectsMove' => $projectsMove,
        'projectsFinish' => $projectsFinish,
        'projectsCancel' => $projectsCancel,
        'countStart' => $countStart,
        'countInProgress' => $countInProgress,
        'countMove' => $countMove,
        'countFinish' => $countFinish,
        'countCancel' => $countCancel,
        'tableData' => $tableData,
        'kanbanInitLimit' => $kanbanInitLimit,
    ];

    // Render the view and pass the data
    echo renderView('jobs.php', $data);
} else {
    echo "This endpoint accepts only AJAX requests.";
}

/**
 * Function to render a PHP view with data
 */
function renderView($view, $data)
{
    extract($data);
    ob_start();
    include $view;
    return ob_get_clean();
}

?>
