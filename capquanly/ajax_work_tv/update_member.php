<?php
include('../../config.php');

try {
    $code = $_POST['code'];
    $member = $_POST['member_id'] ?? NULL;
    $room = $_POST['room_id'];
    $sql = "UPDATE danhsachcongviec SET TV_MA = '$member', PB_MA = '$room' WHERE DSCV_MA = '$code' ";
    $result = mysqli_query($conn, $sql);
    $conn->close();
    if($result){
        echo json_encode([
            'status' => true,
            'data' => $code,
            'message' => 'Cập nhật thành viên thành công'
        ]);
        return;
    }else{
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật thành viên thất bại'
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