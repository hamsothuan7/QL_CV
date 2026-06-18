<?php
include('../../config.php');

try {
    $id   = trim($_GET['id'] ?? '');
    $code = trim($_GET['code'] ?? '');

    if (empty($id)) {
        echo json_encode(['status' => false, 'message' => 'Mã bình luận không hợp lệ']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM binhluan_cv WHERE ID = ?");
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);
    $result   = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($result && $affected > 0) {
        echo json_encode([
            'status'  => true,
            'data'    => $code,
            'message' => 'Xóa bình luận thành công'
        ]);
    } else {
        echo json_encode([
            'status'  => false,
            'message' => 'Xóa bình luận thất bại hoặc không tìm thấy bình luận'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>