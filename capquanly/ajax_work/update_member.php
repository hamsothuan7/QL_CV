<?php
include('../../config.php');

try {
    $code   = trim($_POST['code'] ?? '');
    $member = trim($_POST['member_id'] ?? '');
    $room   = trim($_POST['room_id'] ?? '');
    $ph_id  = trim($_POST['ph_id'] ?? '');

    if (empty($code)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    // ph_id rỗng → NULL
    $ph_val = (!empty($ph_id)) ? $ph_id : null;

    $sql = "UPDATE danhsachcongviec SET TV_MA = ?, PB_MA = ?, PH_MA = ? WHERE DSCV_MA = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param('ssss', $member, $room, $ph_val, $code);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();

    if ($result) {
        echo json_encode([
            'status'  => true,
            'data'    => $code,
            'message' => 'Cập nhật thành viên thành công'
        ]);
    } else {
        echo json_encode([
            'status'  => false,
            'message' => 'Cập nhật thành viên thất bại'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>