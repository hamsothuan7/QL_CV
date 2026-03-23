<?php
include('../../config.php');

try {
    $code = $_POST['code'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    

    if($end_date < $start_date){
        echo json_encode([
            'status' => false,
            'message' => 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu'
        ]);
        return;
    }

    $sql = "UPDATE danhsachcongviec SET DSCV_NGAYBATDAU = '$start_date', DSCV_NGAYKETTHUC_TV = '$end_date', DSCV_NGAYKETTHUC_TRANGTHAI = NULL WHERE DSCV_MA = '$code' ";

    $result = mysqli_query($conn, $sql);

    $conn->close();
    if ($result) {
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Chờ xác nhận ngày kết thúc'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Thay đổi ngày kết thúc thất bại'
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