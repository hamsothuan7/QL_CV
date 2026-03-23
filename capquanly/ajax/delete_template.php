<?php
header('Content-Type: application/json');
include(__DIR__.'/../../config.php');

$mamau = isset($_POST['mamau']) ? intval($_POST['mamau']) : 0;
if($mamau <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mã mẫu không hợp lệ']);
    exit;
}

// Soft delete template by setting trangthai = 0
mysqli_begin_transaction($conn);
try {
    // Update template status
    $sql = "UPDATE maucv SET trangthai = 0 WHERE mamau = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi khi chuẩn bị câu lệnh: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $mamau);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi khi cập nhật trạng thái mẫu: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    // Also update related template items if needed
    $sql = "UPDATE cv_mau SET trang_thai = 0 WHERE ma_mau = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi khi chuẩn bị câu lệnh cập nhật chi tiết mẫu: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $mamau);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi khi cập nhật trạng thái chi tiết mẫu: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_commit($conn);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>
