<?php
// Bắt đầu session và báo cáo lỗi
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include file cấu hình
require_once('../../config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra tham số đầu vào
$duan_ma = isset($_GET['duan_ma']) ? trim($_GET['duan_ma']) : '';

if (empty($duan_ma)) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã dự án']);
    exit;
}

// Khởi tạo mảng kết quả
$response = [
    'status' => 'success',
    'data' => []
];

try {
    // Kiểm tra kết nối database
    if ($conn->connect_error) {
        throw new Exception('Kết nối database thất bại: ' . $conn->connect_error);
    }

    // Chuẩn bị câu truy vấn
    $sql = "SELECT id, duan_ma, mamau, tenmau, created_at 
            FROM duan_maucv 
            WHERE duan_ma = ? 
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi prepare: ' . $conn->error);
    }

    // Bind tham số
    $stmt->bind_param('s', $duan_ma);

    // Thực thi truy vấn
    if (!$stmt->execute()) {
        throw new Exception('Lỗi execute: ' . $stmt->error);
    }

    // Lấy kết quả
    $result = $stmt->get_result();
    $templates = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $templates[] = [
                'id' => $row['id'],
                'duan_ma' => $row['duan_ma'],
                'mamau' => $row['mamau'],
                'tenmau' => $row['tenmau'],
                'created_at' => $row['created_at']
            ];
        }
    }

    $response['data'] = $templates;
    $stmt->close();

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Lỗi: ' . $e->getMessage()
    ];
    
    // Ghi log lỗi
    error_log('[' . date('Y-m-d H:i:s') . '] Lỗi get_linked_templates: ' . $e->getMessage() . "\n", 3, __DIR__ . '/error.log');
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($response);

// Đóng kết nối
if (isset($conn) && $conn) {
    $conn->close();
}
?>
