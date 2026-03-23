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
    // Ghi log SQL và lỗi (nếu có) vào error_log
    error_log(date('Y-m-d H:i:s') . " SQL: " . $sql);
    $result = mysqli_query($conn, $sql);
    $error = mysqli_error($conn);
    $conn->close();
    if (!$result) {
        echo json_encode([
            'status' => false,
            'message' => 'Lỗi SQL: ' . $error,
            'sql' => $sql
        ]);
        return;
    }
    // Trả về cả SQL và error (nếu có) để frontend có thể log ra console
    echo json_encode([
        'status' => true,
        'data' => $code,
        'message' => 'Cập nhật thành công',
        'sql' => $sql,
        'error' => $error
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