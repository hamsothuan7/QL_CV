<?php
include('../../config.php');

try {
    $code = $_GET['code'];

    $sql = "SELECT * FROM duan WHERE DA_MA = '$code' ";

    $result = mysqli_query($conn, $sql);

    $project = mysqli_fetch_assoc($result);




    $data = [
        'code' => $code,
        'project' => $project,
    ];
    // Render the view and pass the data
    echo renderView('modal_date_inner.php', $data);

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