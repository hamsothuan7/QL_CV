<?php
include('../../config.php');
session_start();

try {
    $code   = trim($_GET['code'] ?? '');
    $status = intval($_GET['status'] ?? 0);

    if (empty($code)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    $nndMa = $_SESSION['nnd_ma'] ?? null;
    if ($nndMa == 4) {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại. Bạn không có quyền thực hiện thao tác.'
        ]);
        return;
    }

    // Validate status hợp lệ
    $allowedStatus = [1, 2, 3, 4, 5, 6];
    if (!in_array($status, $allowedStatus)) {
        echo json_encode(['status' => false, 'message' => 'Trạng thái không hợp lệ']);
        return;
    }

    if ($status == 2) {
        // Hoàn thành: tính đúng/trễ theo ngày
        $sql = "UPDATE danhsachcongviec 
                SET DSCV_TRANGTHAI = CASE 
                    WHEN DATEDIFF(DATE(DSCV_NGAYKETTHUC), DATE(DSCV_NGAYBATDAU)) >= DATEDIFF(DATE(NOW()), DATE(DSCV_NGAYTIEPNHAN)) 
                    THEN 2 ELSE 6 END,
                    DSCV_NGAYHOANTHANH = NOW() 
                WHERE DSCV_MA = ?";
    } elseif ($status == 1) {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = 1, DSCV_NGAYTIEPNHAN = NOW() WHERE DSCV_MA = ?";
    } else {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = ? WHERE DSCV_MA = ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    if ($status == 1 || $status == 2) {
        $stmt->bind_param('s', $code);
    } else {
        $stmt->bind_param('is', $status, $code);
    }

    $result = $stmt->execute();
    $stmt->close();
    $conn->close();

    if ($result) {
        echo json_encode([
            'status'  => true,
            'message' => 'Cập nhật trạng thái công việc thành công'
        ]);
    } else {
        echo json_encode([
            'status'  => false,
            'message' => 'Cập nhật trạng thái công việc thất bại'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}
?>