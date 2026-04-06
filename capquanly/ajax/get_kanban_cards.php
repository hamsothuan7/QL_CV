<?php
// ajax/get_kanban_cards.php
include('../../config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include_once(__DIR__ . '/query_helper.php');
include_once(__DIR__ . '/jobs.php'); // Để lấy hàm renderKanbanCard (sẽ viết sau)

$status = $_GET['status'] ?? 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;

$keywords = $_GET['keywords'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$projects = getProjectsForKanban($conn, $status, $keywords, $from_date, $to_date, $limit, $offset);

$html = '';
if (!empty($projects)) {
    if (function_exists('renderProjectKanbanCard')) { // Hàm tĩnh của jobs.php
        foreach ($projects as $item) {
            $html .= renderProjectKanbanCard($item, $conn);
        }
    }
}

echo json_encode([
    'html' => $html,
    'loaded' => count($projects)
]);
