<?php
include('../../config.php');

try {
    $id = $_GET['id'];

    //Get thành viên
    $sql2 = "SELECT TV_MA, TV_TEN FROM thanhvien WHERE PB_MA = '$id' AND active = 0 ";
    $query = mysqli_query($conn, $sql2);
    $members = mysqli_fetch_all($query, MYSQLI_ASSOC);

    $conn->close();

    // Render the view and pass the data
    echo json_encode([
        'status' => true,
        'data' => $members,
        'message' => 'Get data thành công'
    ]);
    return;

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