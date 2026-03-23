<?php
// Bắt đầu session
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kết nối database
require_once('../../config.php');

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['mamau']) || !isset($_POST['nguoiphuquyet'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

$mamau = mysqli_real_escape_string($conn, $_POST['mamau']);
$nguoiphuquyet = mysqli_real_escape_string($conn, $_POST['nguoiphuquyet']);

try {
    // Cập nhật người phụ trách cho tất cả công việc trong mẫu
    $sql = "UPDATE duan_maucv SET nguoiphuquyet = ? WHERE mamau = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nguoiphuquyet, $mamau);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Đã cập nhật người phụ trách cho tất cả công việc'
        ]);
    } else {
        throw new Exception('Lỗi khi cập nhật dữ liệu');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi khi cập nhật người phụ trách: ' . $e->getMessage()
    ]);
}

// Đóng kết nối
$stmt->close();
$conn->close();
?>
