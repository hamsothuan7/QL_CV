<?php
header('Content-Type: application/json; charset=utf-8');

// Khởi tạo response mặc định
$response = ['success' => false, 'message' => '', 'data' => []];

try {
    // Include config file
    $rootPath = dirname(dirname(dirname(__FILE__)));
    $configPath = $rootPath . '/config.php';
    
    if (!file_exists($configPath)) {
        throw new Exception('Không tìm thấy file cấu hình (config.php)');
    }
    include($configPath);
    
    // Kiểm tra session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra đăng nhập
    if (!isset($_SESSION['username'])) {
        throw new Exception('Chưa đăng nhập');
    }

    // Kiểm tra có mã công việc được truyền vào không
    if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
        throw new Exception('Không tìm thấy mã công việc');
    }

    $taskId = trim($_GET['task_id']);
    
    // Lấy danh sách bình luận
    $sql = "SELECT 
                bc.ID,
                bc.TEXT as NOI_DUNG,
                bc.DSCV_MA,
                bc.TV_MA,
                bc.TRANGTHAI,
                bc.CREATED_AT as NGAY_BINH_LUAN,
                (SELECT TV_TEN FROM thanhvien WHERE TV_MA = bc.TV_MA) as TV_TEN
            FROM binhluan_cv bc
            WHERE bc.DSCV_MA = ?
            ORDER BY bc.CREATED_AT DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 's', $taskId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi thực thi truy vấn: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $comments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = [
            'id' => $row['ID'],
            'content' => $row['NOI_DUNG'],
            'date' => $row['NGAY_BINH_LUAN'],
            'user_name' => $row['TV_TEN'],
            'is_editable' => ($_SESSION['code'] == $row['TV_MA'] || (isset($_SESSION['active']) && $_SESSION['active'] == 1))
        ];
    }
    
    $response = [
        'success' => true,
        'data' => $comments
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    http_response_code(500);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
