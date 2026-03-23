<?php
// getView.php
include('../../config.php');

// Check if the request is made via AJAX
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Sample data to be passed to the view

    $code = $_GET['code'];

    //Get danh sách dự án
    $sql = "SELECT * FROM duan ORDER BY DA_MA DESC";
    $result = mysqli_query($conn, $sql);
    $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);

    //Get data dự án
    $sql = "SELECT v.*, t.TV_TEN, p.DA_TEN FROM danhsachcongviec v LEFT JOIN thanhvien t ON v.TV_MA = t.TV_MA LEFT JOIN duan p ON v.DA_MA = p.DA_MA WHERE v.DSCV_MA = '$code' ";
    $result = mysqli_query($conn, $sql);
    $project = mysqli_fetch_assoc($result);
    
    //Get nhận xét
    $sql = "SELECT c.*, v.TV_TEN FROM binhluan_cv c INNER JOIN thanhvien v ON c.TV_MA = v.TV_MA WHERE c.DSCV_MA = '$code' ORDER BY c.ID DESC ";
    $result = mysqli_query($conn, $sql);
    $comments = mysqli_fetch_all($result, MYSQLI_ASSOC);


    $data = [
        'project' => $project,
        'projects' => $projects,
        'comments' => $comments,
    ];

    //update đã xem
    $sql = "UPDATE binhluan_cv 
            SET TRANGTHAI = 1 
            WHERE DSCV_MA = '$code' AND TV_MA <> '$userId' ";

    $result = mysqli_query($conn, $sql);
    
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
