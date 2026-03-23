<?php
include('../../config.php');

try {
    $code = $_GET['code'];

    //Get label
    $sql = "SELECT * FROM nhan WHERE DA_MA = '$code' ORDER BY ID DESC ";
    $query = mysqli_query($conn, $sql);
    $labels = mysqli_fetch_all($query, MYSQLI_ASSOC);



    $data = [
        'code' => $code,
        'labels' => $labels,
    ];
    // Render the view and pass the data
    echo renderView('modal_label_inner.php', $data);

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