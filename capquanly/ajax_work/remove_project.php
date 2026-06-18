<?php
include('../../config.php');

try {
    $code = trim($_GET['code'] ?? '');
    $id   = trim($_GET['id'] ?? '');

    if (empty($id)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    $stmt = $conn->prepare("UPDATE danhsachcongviec SET DSCV_trangthaiHD = 0 WHERE DSCV_MA = ?");
    if (!$stmt) {
        throw new Exception('Không thể chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param('s', $id);
    $result   = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($result === false) {
        throw new Exception('Không thể cập nhật trạng thái công việc');
    }

    echo json_encode([
        'status'   => true,
        'data'     => $code,
        'affected' => $affected,
        'message'  => 'Đã cập nhật trạng thái công việc thành không hoạt động'
    ]);

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>