<?php
include('../../config.php');

try {
    $code = $_GET['code'];

    $sql = "UPDATE danhsachcongviec SET DSCV_NGAYKETTHUC_TRANGTHAI = 0, DSCV_NGAYKETTHUC_TV = NULL WHERE DSCV_MA = '$code' ";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Từ chối thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Từ chối thất bại'
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