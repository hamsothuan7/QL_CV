<?php
include('../../config.php');

try {
    $code = $_GET['code'];
    $status = $_GET['status'];

    $sql = "UPDATE danhsachcongviec SET DSCV_TRANGTHAI = $status WHERE DSCV_MA = '$code' ";

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