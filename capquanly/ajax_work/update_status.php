<?php
include('../../config.php');
session_start();

try {
    $code = $_GET['code'];
    $status = $_GET['status'];

    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý
    if ($nndMa == 4) {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại. Bạn không có quyền thực hiện thao tác.'
        ]);
        return;
    }

    if ($status == 2) {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = CASE WHEN DATEDIFF(DSCV_NGAYKETTHUC, DSCV_NGAYBATDAU) > DATEDIFF(NOW(), DSCV_NGAYTIEPNHAN) THEN 2 ELSE 6 END, DSCV_NGAYHOANTHANH = NOW() WHERE DSCV_MA = '$code' ";
    } else if ($status == 1) {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = 1, DSCV_NGAYTIEPNHAN = NOW() WHERE DSCV_MA = '$code' ";
    } else {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = $status WHERE DSCV_MA = '$code' ";
    }

    $result = mysqli_query($conn, $sql);

    $conn->close();
    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'Cập nhật trạng thái công việc thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật trạng thái công việc thất bại'
        ]);
        return;
    }

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}

?>