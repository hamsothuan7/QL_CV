<?php
session_start();
include '../../config.php'; // Kết nối CSDL

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra đăng nhập
if (!isset($_SESSION['nnd_ma'])) {
    echo json_encode(['status' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    // Lấy mã dự án từ tham số
    $project_code = $_GET['project_code'] ?? '';

    if (empty($project_code)) {
        throw new Exception('Thiếu mã dự án');
    }

    // Lấy danh sách công việc của dự án
    $sql = "SELECT DSCV_MA, DSCV_TEN 
            FROM danhsachcongviec 
            WHERE DA_MA = ? 
            AND DSCV_trangthaiHD = 1
            ORDER BY DSCV_TEN ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi truy vấn: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 's', $project_code);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi thực thi truy vấn: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $tasks = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tasks[] = [
                'id' => $row['DSCV_MA'],
                'text' => $row['DSCV_TEN']
            ];
        }
    }

    $response = [
        'status' => true,
        'data' => $tasks,
        'debug' => [
            'project_code' => $project_code,
            'task_count' => count($tasks)
        ]
    ];

} catch (Exception $e) {
    $response = [
        'status' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    http_response_code(500);
}

// Trả về kết quả dưới dạng JSON
echo json_encode($response);

// Đóng kết nối
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
if (isset($conn)) {
    mysqli_close($conn);
}
?>
