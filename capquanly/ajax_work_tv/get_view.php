<?php
// getView.php
include('../../config.php');
session_start();

// Check if the request is made via AJAX (an toàn khi thiếu header)
$requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
if (strcasecmp($requestedWith, 'XMLHttpRequest') === 0) {
    // Sample data to be passed to the view

    //Get mã thành viên
    $username = $_SESSION['username'];
    $sql = "SELECT TV_MA FROM thanhvien WHERE TV_TEN = '$username' ";
    $result = mysqli_query($conn, $sql);
    $member = mysqli_fetch_assoc($result);
    $memberCode = $member['TV_MA'] ?? "";


    //1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
    function querySql($conn, $status, $memberCode)
    {
        // Kiểm tra xem trường dscv_trangthaihd có tồn tại không
        $checkField = "SHOW COLUMNS FROM danhsachcongviec LIKE 'dscv_trangthaihd'";
        $fieldExists = mysqli_query($conn, $checkField);
        $hasTrangThaiHD = mysqli_num_rows($fieldExists) > 0;
        
        if ($hasTrangThaiHD) {
            // Trường tồn tại - chỉ hiển thị công việc có dscv_trangthaihd = 1
            $sql = "SELECT * FROM danhsachcongviec WHERE TV_MA = '$memberCode' AND dscv_trangthaihd = 1";
        } else {
            // Trường không tồn tại - hiển thị tất cả công việc của thành viên
            $sql = "SELECT * FROM danhsachcongviec WHERE TV_MA = '$memberCode'";
        }
        if ($status == 2) {
            $sql .= " AND (DSCV_TRANGTHAI = $status OR DSCV_TRANGTHAI = 6) ORDER BY DSCV_MA DESC";
        } else {
            $sql .= " AND DSCV_TRANGTHAI = $status ORDER BY DSCV_MA DESC";
        }
        // Thực thi câu truy vấn và gán vào $result
        $result = mysqli_query($conn, $sql);
        return $result;
    }

    //Data trả về theo thứ tự ở view
    $projectsStart = mysqli_fetch_all(querySql($conn, 5, $memberCode), MYSQLI_ASSOC);

    $projectsInProgress = mysqli_fetch_all(querySql($conn, 1, $memberCode), MYSQLI_ASSOC);

    $projectsMove = mysqli_fetch_all(querySql($conn, 3, $memberCode), MYSQLI_ASSOC);

    $projectsFinish = mysqli_fetch_all(querySql($conn, 2, $memberCode), MYSQLI_ASSOC);

    $projectsCancel = mysqli_fetch_all(querySql($conn, 4, $memberCode), MYSQLI_ASSOC);

    $data = [
        'conn' => $conn,
        'projectsStart' => $projectsStart,
        'projectsInProgress' => $projectsInProgress,
        'projectsMove' => $projectsMove,
        'projectsFinish' => $projectsFinish,
        'projectsCancel' => $projectsCancel,
    ];

    // Render the view and pass the data
    echo renderView('jobs.php', $data);
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
