<?php
include('../../config.php');

try {
    $code = $_POST['code'];
    $membersIds = $_POST['members'] ?? [];
    $room = $_POST['room_id'] ?? "";

    if(!empty($membersIds)){
        //Update phòng ban
        $sql = "UPDATE duan SET  PB_MA = '$room' WHERE DA_MA = '$code' ";
        $result = mysqli_query($conn, $sql);

        foreach ($membersIds as $item){
            //Get thành viên
            $sql = "SELECT TV_MA FROM duan_thanhvien WHERE TV_MA = '$item' AND DA_MA = '$code' ";
            $result = mysqli_query($conn, $sql);
            $member = mysqli_fetch_assoc($result);
            if(!$member){
                $sql = "INSERT INTO duan_thanhvien(DA_MA, TV_MA) VALUES('$code', '$item') ";
                $result = mysqli_query($conn, $sql);
            }
        }
    }
    
    echo json_encode([
        'status' => true,
        'data' => $code,
        'message' => 'Cập nhật thành viên thành công'
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