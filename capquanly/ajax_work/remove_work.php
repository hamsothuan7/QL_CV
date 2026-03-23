<?php
include('../../config.php');

try {
    $code = $_GET['code'];
    $id = $_GET['id'];
    $sql = "DELETE FROM danhsachcongviec WHERE DSCV_MA = '$id' ";
    $result = mysqli_query($conn, $sql);
    $conn->close();
    echo json_encode([
        'status' => true,
        'data' => $code,
        'message' => 'Xóa công việc thành công'
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