<?php
include('../../config.php');

try {
    $code = $_GET['code'];
    // Cập nhật trạng thái công việc thành không hoạt động thay vì xóa
    $updateSql = "UPDATE danhsachcongviec SET DSCV_trangthaiHD = 0 WHERE DSCV_MA = ?";
    $stmt = $conn->prepare($updateSql);
    
    if (!$stmt) {
        throw new Exception('Không thể chuẩn bị câu lệnh cập nhật');
    }
    
    $stmt->bind_param('s', $code);
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();
    
    if ($result === false) {
        throw new Exception('Không thể cập nhật trạng thái công việc');
    }
    
    echo json_encode([
        'status' => true,
        'data' => $code,
        'affected' => $affected,
        'message' => 'Đã cập nhật trạng thái công việc thành không hoạt động'
    ]);
    return;

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}

?>