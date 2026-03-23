<?php
session_start();
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request for debugging
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'post' => $_POST,
    'session' => $_SESSION,
    'session_id' => session_id()
];
file_put_contents('delete_comment_debug.log', print_r($logData, true) . "\n", FILE_APPEND);

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['code'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['id']) || empty($_POST['id']) || !isset($_POST['DSCV_MA']) || empty($_POST['DSCV_MA'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Dữ liệu không hợp lệ', 
        'received_data' => [
            'id' => $_POST['id'] ?? 'not_set',
            'DSCV_MA' => $_POST['DSCV_MA'] ?? 'not_set'
        ]
    ]);
    exit;
}

$commentId = trim($_POST['id']);
$taskId = trim($_POST['DSCV_MA']);
$userId = trim($_SESSION['code']);

try {
    // Log thông tin trước khi xóa
    $logMessage = "Attempting to delete comment - Comment ID: $commentId, Task ID: $taskId, User ID: $userId\n";
    file_put_contents('delete_comment_debug.log', $logMessage, FILE_APPEND);
    
    // Kiểm tra xem bình luận có tồn tại và thuộc về người dùng không
    $checkSql = "SELECT ID, TV_MA FROM binhluan_cv WHERE ID = ? AND DSCV_MA = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $commentId, $taskId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        // Nếu không tìm thấy bình luận, coi như đã xóa thành công
        echo json_encode([
            'status' => 'success',
            'message' => 'Bình luận đã được xóa trước đó',
            'already_deleted' => true
        ]);
        exit;
    }
    
    // Kiểm tra quyền sở hữu
    $comment = $result->fetch_assoc();
    if ($comment['TV_MA'] !== $userId) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Bạn không có quyền xóa bình luận này'
        ]);
        exit;
    }
    
    // Nếu kiểm tra thành công, tiến hành xóa
    $sql = "DELETE FROM binhluan_cv WHERE ID = ? AND DSCV_MA = ? AND TV_MA = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $commentId, $taskId, $userId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $logMessage = "Comment deleted successfully - Comment ID: $commentId\n";
            file_put_contents('delete_comment_debug.log', $logMessage, FILE_APPEND);
            echo json_encode(['status' => 'success']);
        } else {
            $logMessage = "No rows affected - Comment ID: $commentId\n";
            file_put_contents('delete_comment_debug.log', $logMessage, FILE_APPEND);
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Không thể xóa bình luận',
                'debug' => [
                    'error' => $conn->error,
                    'errno' => $conn->errno
                ]
            ]);
        }
    } else {
        throw new Exception("Lỗi khi thực hiện xóa bình luận: " . $conn->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    $errorData = [
        'time' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'sql_error' => $conn->error ?? 'No SQL error',
        'sql' => $sql ?? 'No SQL query',
        'params' => [$commentId, $taskId, $userId]
    ];
    file_put_contents('delete_comment_error.log', print_r($errorData, true) . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Có lỗi xảy ra khi xóa bình luận',
        'debug' => $conn->error ?? 'No database error',
        'sql' => $sql ?? 'No SQL query',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
