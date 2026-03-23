<?php
session_start();
include '../config.php'; // Kết nối CSDL

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
    // Lấy danh sách công việc hiện có, sắp xếp theo ngày bắt đầu giảm dần
    $sql = "SELECT DSCV_MA, DSCV_TEN 
            FROM danhsachcongviec 
            ORDER BY DSCV_NGAYBATDAU DESC";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new Exception('Lỗi truy vấn: ' . mysqli_error($conn));
    }

    $tasks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tasks[] = [
            'id' => $row['DSCV_MA'],
            'text' => $row['DSCV_TEN']
        ];
    }

    $response = [
        'status' => true,
        'data' => $tasks,
        'count' => count($tasks)
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
if (isset($conn)) {
    mysqli_close($conn);
}
?>
