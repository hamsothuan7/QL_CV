<?php
include('../../config.php');

try {
    $code = $_GET['code'];
    $id = $_GET['id'];

    //1 đang tiến hành, 2 hoàn thành
    $status = $_GET['status'] ?? 1;

    if ($status == 2) {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = 2, DSCV_NGAYHOANTHANH = NOW() WHERE DSCV_MA = '$id' ";
    } else {
        $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = $status WHERE DSCV_MA = '$id' ";
    }
    $result = mysqli_query($conn, $sql);
    $conn->close();
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