<?php
include('../../config.php');

try {
    $code = $_GET['code'];
    $date = $_GET['date'];

    $sql = "UPDATE danhsachcongviec SET DSCV_NGAYKETTHUC = '$date', DSCV_NGAYKETTHUC_TV = NULL, DSCV_NGAYKETTHUC_TRANGTHAI = 1 WHERE DSCV_MA = '$code' ";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Duyệt thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Duyệt thất bại'
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