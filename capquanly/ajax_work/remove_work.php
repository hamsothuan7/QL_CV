<?php
include('../../config.php');

try {
    $code = trim($_GET['code'] ?? '');
    $id   = trim($_GET['id'] ?? '');

    if (empty($id)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM danhsachcongviec WHERE DSCV_MA = ?");
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param('s', $id);
    $result   = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($result && $affected > 0) {
        echo json_encode([
            'status'  => true,
            'data'    => $code,
            'message' => 'Xóa công việc thành công'
        ]);
    } else {
        echo json_encode([
            'status'  => false,
            'message' => 'Xóa công việc thất bại hoặc không tìm thấy công việc'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>