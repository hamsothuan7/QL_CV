<?php
include('../../config.php');
session_start();

try {
    $code = $_POST['code'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $setting = $_POST['setting'] ?? 1;
    $nndMa = $_SESSION['nnd_ma'] ?? null;

    if($end_date < $start_date){
        echo json_encode([
            'status' => false,
            'message' => 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu'
        ]);
        return;
    }

    if($nndMa == 4){
        echo json_encode([
            'status' => false,
            'message' => 'Bạn không có quyền thực hiện chức năng này!'
        ]);
        return;
    }

    $sql = "UPDATE duan SET DA_NGAYBATDAU = '$start_date', DA_NGAYKETTHUC = '$end_date', DA_SETTING = $setting WHERE DA_MA = '$code' ";

    $result = mysqli_query($conn, $sql);


    if ($result) {
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Cập nhật dự án thành công'
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