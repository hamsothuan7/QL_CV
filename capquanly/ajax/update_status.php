<?php
header('Content-Type: application/json; charset=utf-8');
include('../../config.php');
session_start();

try {
    $code = $_POST['code'];
    $status = $_POST['status'];

    $nndMa = $_SESSION['nnd_ma'] ?? null;
    if($nndMa == 4){
        echo json_encode([
            'status' => false,
            'message' => 'Bạn không có quyền thực hiện chức năng này!'
        ]);
        return;
    }

    $sql = "UPDATE duan SET DA_TRANGTHAI = $status WHERE DA_MA = '$code' ";

    $result = mysqli_query($conn, $sql);


    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Cập nhật trạng thái dự án thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cập nhật dự án thất bại'
        ]);
        return;
    }

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    return;
}

?>