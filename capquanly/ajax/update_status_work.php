<?php
include('../../config.php');

try {
    $code = $_GET['code'];
    $id = $_GET['id'];

    //1 đang tiến hành, 2 hoàn thành
    $status = $_GET['status'] ?? 1;
    if ($status == 2) {
        $endDate = date('Y-m-d');
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = 2, DSCV_NGAYKETTHUC = '$endDate', TIEN_DO = 100, DSCV_NGAYHOANTHANH = NOW() WHERE DSCV_MA = '$id' ";
    } else {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = $status, TIEN_DO = 0 WHERE DSCV_MA = '$id' ";
    }
    $result = mysqli_query($conn, $sql);

    echo json_encode([
        'status' => true,
        'data' => $code,
        'message' => 'Cập nhật thành công'
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