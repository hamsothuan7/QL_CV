<?php
header('Content-Type: application/json');
include(__DIR__ . '/../../config.php');

// Kiểm tra xem có phải là POST request không
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Lấy dữ liệu từ request
$id = isset($_POST['id']) ? $_POST['id'] : null;
$ten_cv = isset($_POST['ten_cv']) ? trim($_POST['ten_cv']) : '';
$thoi_gian_du_kien = isset($_POST['thoi_gian_du_kien']) ? intval($_POST['thoi_gian_du_kien']) : 0;
$prereq = isset($_POST['prereq']) ? trim($_POST['prereq']) : '';
$mamau = isset($_POST['mamau']) ? $_POST['mamau'] : null;

// Validate dữ liệu
if (empty($id) || empty($mamau)) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

try {
    // Cập nhật thông tin công việc trong bảng duan_maucv
    $sql = "UPDATE duan_maucv SET 
            ten_cv = ?, 
            thoi_gian_du_kien = ?,
            prereq = ?
            WHERE id = ? AND mamau = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisis", $ten_cv, $thoi_gian_du_kien, $prereq, $id, $mamau);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công']);
    } else {
        throw new Exception('Lỗi khi cập nhật dữ liệu');
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log('Lỗi khi cập nhật công việc mẫu: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

$conn->close();
?>
