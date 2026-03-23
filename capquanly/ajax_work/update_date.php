<?php
include('../../config.php');
session_start();

try {
    $code = $_POST['code'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý

    $accept_date = $_POST['accept_date'];
    $finish_date = $_POST['finish_date'];

    if ($nndMa == 4){
        echo json_encode([
            'status' => false,
            'message' => 'Bạn không có quyền thay đổi tiến độ công việc'
        ]);
        return;
    }

    if($end_date < $start_date){
        echo json_encode([
            'status' => false,
            'message' => 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu'
        ]);
        return;
    }

    $sql = "UPDATE danhsachcongviec SET DSCV_NGAYBATDAU = '$start_date', DSCV_NGAYKETTHUC = '$end_date', DSCV_NGAYTIEPNHAN = '$accept_date', DSCV_NGAYHOANTHANH = '$finish_date' WHERE DSCV_MA = '$code' ";

    $result = mysqli_query($conn, $sql);

    $conn->close();
    if ($result) {
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Cập nhật công việc thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại'
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