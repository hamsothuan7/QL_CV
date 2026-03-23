<?php
include('../../config.php');

try {
    $code = $_GET['code'];

    $sql = "SELECT * FROM duan WHERE DA_MA = '$code' ";
    $result = mysqli_query($conn, $sql);
    $project = mysqli_fetch_assoc($result);

    $sql = "SELECT * FROM phongban ";
    $result = mysqli_query($conn, $sql);
    $rooms = mysqli_fetch_all($result, MYSQLI_ASSOC);

    //Get thành viên
    $sql2 = "SELECT TV_MA, TV_TEN FROM thanhvien WHERE active = 0";
    $query = mysqli_query($conn, $sql2);
    $members = mysqli_fetch_all($query, MYSQLI_ASSOC);

    //Get thành viên
    $sql = "SELECT d.TV_MA FROM duan_thanhvien d INNER JOIN thanhvien t ON d.TV_MA = t.TV_MA WHERE d.DA_MA = '$code' ";
    $result = mysqli_query($conn, $sql);
    $membersSL = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $membersIds = [];
    if(!empty($membersSL)){
        foreach ($membersSL as $item)
            $membersIds[] = $item['TV_MA'];
    }

     // Get đơn vị
    $sql3 = "SELECT PH_MA, PH_TEN FROM donviphoihop";
    $query = mysqli_query($conn, $sql3);
    $phoihop = mysqli_fetch_all($query, MYSQLI_ASSOC);

    $data = [
        'code' => $code,
        'project' => $project,
        'rooms' => $rooms,
        'members' => $members,
        'membersIds' => $membersIds,
        'phoihop' => $phoihop,
        
    ];
    // Render the view and pass the data
    echo renderView('modal_member_inner.php', $data);

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}
function renderView($view, $data)
{
    extract($data);
    ob_start();
    include $view;
    return ob_get_clean();
}
?>