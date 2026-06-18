<?php
include('../../config.php');
session_start();

try {
    $code   = trim($_POST['code'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');
    $accept_date = trim($_POST['accept_date'] ?? '');
    $finish_date = trim($_POST['finish_date'] ?? '');

    $nndMa = $_SESSION['nnd_ma'] ?? null;

    if (empty($code)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    if ($nndMa == 4) {
        echo json_encode([
            'status' => false,
            'message' => 'Bạn không có quyền thay đổi tiến độ công việc'
        ]);
        return;
    }

    if (!empty($start_date) && !empty($end_date) && $end_date < $start_date) {
        echo json_encode([
            'status' => false,
            'message' => 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu'
        ]);
        return;
    }

    // Xử lý accept_date và finish_date: chuỗi rỗng → NULL
    $acceptDateVal = (!empty($accept_date) && $accept_date !== 'null') ? $accept_date : null;
    $finishDateVal = (!empty($finish_date) && $finish_date !== 'null') ? $finish_date : null;

    $sql = "UPDATE danhsachcongviec 
            SET DSCV_NGAYBATDAU = ?, 
                DSCV_NGAYKETTHUC = ?, 
                DSCV_NGAYTIEPNHAN = ?, 
                DSCV_NGAYHOANTHANH = ? 
            WHERE DSCV_MA = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param('sssss', $start_date, $end_date, $acceptDateVal, $finishDateVal, $code);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();

    if ($result) {
        echo json_encode([
            'status' => true,
            'data'   => $code,
            'message' => 'Cập nhật công việc thành công'
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
?>