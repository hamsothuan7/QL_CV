<?php
// getView.php
include('../../config.php');

// Check if the request is made via AJAX
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Sample data to be passed to the view

    $code = $_GET['code'];

    //Get data dự án
    $sql = "SELECT * FROM duan WHERE DA_MA = '$code' ";
    $result = mysqli_query($conn, $sql);
    $project = mysqli_fetch_assoc($result);

    //Get thành viên
    $sql = "SELECT t.TV_TEN,t.TV_MA FROM duan_thanhvien d INNER JOIN thanhvien t ON d.TV_MA = t.TV_MA WHERE d.DA_MA = '$code' ";
    $result = mysqli_query($conn, $sql);
    $members = mysqli_fetch_all($result, MYSQLI_ASSOC);

    //Get nhãn
    $sql = "SELECT * FROM nhan n WHERE n.DA_MA = '$code' ORDER BY n.ID DESC ";
    $result = mysqli_query($conn, $sql);
    $labels = mysqli_fetch_all($result, MYSQLI_ASSOC);

    //Get việc cần làm
    $sql = "SELECT v.*, tv.TV_TEN FROM danhsachcongviec v LEFT JOIN thanhvien tv ON v.TV_MA = tv.TV_MA  WHERE v.DA_MA = '$code' AND v.PARENT_ID IS NULL ORDER BY v.DSCV_MA DESC ";
    $result = mysqli_query($conn, $sql);
    $works = mysqli_fetch_all($result, MYSQLI_ASSOC);

    //Get nhận xét
    $sql = "SELECT c.*, v.TV_TEN FROM binhluan c INNER JOIN thanhvien v ON c.TV_MA = v.TV_MA WHERE c.DA_MA = '$code' ORDER BY c.ID DESC ";
    $result = mysqli_query($conn, $sql);
    $comments = mysqli_fetch_all($result, MYSQLI_ASSOC);


    $data = [
        'project' => $project,
        'members' => $members,
        'labels' => $labels,
        'works' => $works,
        'comments' => $comments,
    ];

    // Render the view and pass the data
    echo renderView('modal_edit_inner.php', $data);
} else {
    echo "This endpoint accepts only AJAX requests.";
}

/**
 * Function to render a PHP view with data
 *
 * @param string $view The path to the view file
 * @param array $data Data to be passed to the view
 * @return string Rendered HTML
 */
function renderView($view, $data)
{
    extract($data);
    ob_start();
    include $view;
    return ob_get_clean();
}

?>
