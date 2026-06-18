<?php
include('../../config.php');

try {
    $code   = trim($_GET['code'] ?? '');
    $id     = trim($_GET['id'] ?? '');
    $status = intval($_GET['status'] ?? 1);

    if (empty($id)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    // Whitelist trạng thái hợp lệ
    $allowedStatus = [1, 2, 3, 4, 5, 6];
    if (!in_array($status, $allowedStatus)) {
        echo json_encode(['status' => false, 'message' => 'Trạng thái không hợp lệ']);
        return;
    }

    if ($status == 2) {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = 2, DSCV_NGAYHOANTHANH = NOW() WHERE DSCV_MA = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $id);
    } else {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = ? WHERE DSCV_MA = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $status, $id);
    }

    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $result = $stmt->execute();
    $error  = $stmt->error;
    $stmt->close();
    $conn->close();

    if (!$result) {
        echo json_encode([
            'status'  => false,
            'message' => 'Lỗi SQL: ' . $error
        ]);
        return;
    }

    echo json_encode([
        'status'  => true,
        'data'    => $code,
        'message' => 'Cập nhật thành công'
    ]);

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>