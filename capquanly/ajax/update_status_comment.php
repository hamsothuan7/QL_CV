<?php
include('../../config.php');

try {
    $code = $_GET['id'];

    $sql = "UPDATE binhluan_cv SET TRANGTHAI = 1 WHERE DSCV_MA = '$code' ";

    $result = mysqli_query($conn, $sql);


    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'Cập nhật trạng thái dự án thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật dự án thất bại'
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