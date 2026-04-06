<?php
// ajax/get_view.php
include('../../config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    include_once(__DIR__ . '/query_helper.php');

    $keywords = filter_input(INPUT_GET, 'keywords', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $from_date = filter_input(INPUT_GET, 'from_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $to_date = filter_input(INPUT_GET, 'to_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPageInput = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    $perPage = in_array($perPageInput, [20, 50, 100]) ? $perPageInput : 20;

    $kanbanInitLimit = 30;

    $projectsStart = getProjectsForKanban($conn, 5, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsInProgress = getProjectsForKanban($conn, 1, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsMove = getProjectsForKanban($conn, 3, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsFinish = getProjectsForKanban($conn, 2, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);
    $projectsCancel = getProjectsForKanban($conn, 4, $keywords, $from_date, $to_date, $kanbanInitLimit, 0);

    $countStart = countProjectsForKanban($conn, 5, $keywords, $from_date, $to_date);
    $countInProgress = countProjectsForKanban($conn, 1, $keywords, $from_date, $to_date);
    $countMove = countProjectsForKanban($conn, 3, $keywords, $from_date, $to_date);
    $countFinish = countProjectsForKanban($conn, 2, $keywords, $from_date, $to_date);
    $countCancel = countProjectsForKanban($conn, 4, $keywords, $from_date, $to_date);

    $tableData = getProjectsForTable($conn, $keywords, $from_date, $to_date, $page, $perPage);

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
        'kanbanInitLimit' => $kanbanInitLimit
    ];

    echo renderView('jobs.php', $data);
} else {
    echo "This endpoint accepts only AJAX requests.";
}

function renderView($view, $data)
{
    extract($data);
    ob_start();
    include $view;
    return ob_get_clean();
}
?>
