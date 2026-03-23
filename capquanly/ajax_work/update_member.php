<?php
include('../../config.php');

try {
    $code = $_POST['code'];
    $member = $_POST['member_id'] ?? NULL;
    $room = $_POST['room_id'];
    $ph_id = $_POST['ph_id'];

    $sql="";
    if ($ph_id == '') {
        $sql = "UPDATE danhsachcongviec SET TV_MA = '$member', PB_MA = '$room', PH_MA = NULL WHERE DSCV_MA = '$code' ";
    }else{
        $sql = "UPDATE danhsachcongviec SET TV_MA = '$member', PB_MA = '$room', PH_MA = '$ph_id' WHERE DSCV_MA = '$code' ";
    }
    //$sql = "UPDATE danhsachcongviec SET TV_MA = '$member', PB_MA = '$room', PH_MA = '$ph_id' WHERE DSCV_MA = '$code' ";
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