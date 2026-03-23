<?php
include('../../config.php');
session_start();

try {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $status = intval($_POST['status']);
    $content = mysqli_real_escape_string($conn, $_POST['editor']);
    $total_investment = isset($_POST['DA_TONGMUCDAUTU']) ? intval($_POST['DA_TONGMUCDAUTU']) : 0;

    $nndMa = $_SESSION['nnd_ma'] ?? null;
    if($nndMa == 4){
        echo json_encode([
            'status' => false,
            'message' => 'Bạn không có quyền thực hiện chức năng này!'
        ]);
        return;
    }

    $sql = "UPDATE duan SET 
            DA_TEN = '$name', 
            DA_MOTA = '$content', 
            DA_TRANGTHAI = $status,
            DA_TONGMUCDAUTU = $total_investment 
            WHERE DA_MA = '$code'";

    $result = mysqli_query($conn, $sql);


    if ($result) {
        echo json_encode([
            'status' => true,
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