<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kết nối database
include('../config.php');

// Lấy tên công việc hoặc ID từ request
$taskName = isset($_GET['taskName']) ? trim($_GET['taskName']) : '';
$taskId = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($taskName) && empty($taskId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin công việc']);
    exit;
}

try {
    $query = "SELECT DSCV_MA, DSCV_TEN, TV_MA FROM danhsachcongviec WHERE ";
    $params = [];
    
    if (!empty($taskId)) {
        $query .= "DSCV_MA = ?";
        $params[] = $taskId;
    } else {
        $query .= "DSCV_TEN = ?";
        $params[] = $taskName;
    }
    
    $query .= " LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($task = mysqli_fetch_assoc($result)) {
        // Kiểm tra quyền truy cập
        if ($_SESSION['active'] != 1 && $task['TV_MA'] != $_SESSION['code']) {
            echo json_encode([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập công việc này'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'DSCV_MA' => $task['DSCV_MA'],
            'DSCV_TEN' => $task['DSCV_TEN']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy công việc: ' . (!empty($taskName) ? htmlspecialchars($taskName) : 'ID ' . htmlspecialchars($taskId))
        ]);
    }
} catch (Exception $e) {
    error_log('Lỗi get_task_info: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}
?>
