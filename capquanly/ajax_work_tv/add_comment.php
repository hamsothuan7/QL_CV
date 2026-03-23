<?php
session_start();
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request data and session
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'post' => $_POST,
    'session' => $_SESSION,
    'session_id' => session_id(),
    'cookies' => $_COOKIE
];
file_put_contents('comment_debug.log', print_r($logData, true) . "\n", FILE_APPEND);

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['code'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['task_id']) || !isset($_POST['comment']) || empty(trim($_POST['comment']))) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$taskId = $_POST['task_id'];
$comment = trim($_POST['comment']);
$userId = $_SESSION['code'];
$createdAt = date('Y-m-d H:i:s');

try {
    // Thêm bình luận vào CSDL
    $sql = "INSERT INTO binhluan_cv (DSCV_MA, TV_MA, TEXT, CREATED_AT) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $taskId, $userId, $comment, $createdAt);
    
    if ($stmt->execute()) {
        $commentId = $stmt->insert_id;
        echo json_encode([
            'status' => 'success',
            'comment_id' => $commentId,
            'created_at' => $createdAt
        ]);
    } else {
        throw new Exception("Không thể thêm bình luận");
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Log the detailed error
    $errorData = [
        'time' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'sql_error' => $conn->error ?? 'No SQL error',
        'sql' => $sql ?? 'No SQL query',
        'params' => [$taskId, $userId, $comment, $createdAt]
    ];
    file_put_contents('add_comment_error.log', print_r($errorData, true) . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Có lỗi xảy ra khi thêm bình luận',
        'debug' => $conn->error ?? 'No database error',
        'sql' => $sql ?? 'No SQL query',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
