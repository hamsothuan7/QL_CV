<?php
// getView.php
include('../../config.php');

// Bắt đầu session để lấy thông tin người dùng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the request is made via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Sample data to be passed to the view

    //1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
    function querySql($conn, $status)
    {
        $tvCode = $_SESSION['code'] ?? null;
        $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = quản lý
        $isAdmin = isset($_SESSION['active']) && $_SESSION['active'] == 1 || $nndMa == 4;

        $escStatus = intval($status);
        $sql = '';

        if ($isAdmin) {
            // Admin: thấy tất cả dự án theo trạng thái
            $sql = "SELECT * FROM duan WHERE DA_TRANGTHAI = {$escStatus} ORDER BY DA_MA DESC";
        } elseif ($nndMa == 2 && $tvCode) {
            // Quản lý: bản thân + cùng phòng ban
            $escUser = mysqli_real_escape_string($conn, $tvCode);
            $sql =
                "SELECT * FROM duan da\n" .
                "WHERE da.DA_TRANGTHAI = {$escStatus}\n" .
                "  AND (\n" .
                "       EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.TV_MA = '{$escUser}')\n" .
                "    OR da.DA_NGUOIPHUTRACH = '{$escUser}'\n" .
                "    OR EXISTS (\n" .
                "           SELECT 1\n" .
                "           FROM danhsachcongviec dcv2\n" .
                "           JOIN thanhvien tv2 ON tv2.TV_MA = dcv2.TV_MA\n" .
                "           WHERE dcv2.DA_MA = da.DA_MA\n" .
                "             AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n" .
                "       )\n" .
                "    OR EXISTS (\n" .
                "           SELECT 1 FROM thanhvien tv3\n" .
                "           WHERE tv3.TV_MA = da.DA_NGUOIPHUTRACH\n" .
                "             AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n" .
                "       )\n" .
                "  )\n" .
                "ORDER BY da.DA_MA DESC";
        } else {
            // Nhân viên: dự án có công việc của mình hoặc mình là người phụ trách
            $escUser = mysqli_real_escape_string($conn, $tvCode ?? '');
            $sql =
                "SELECT * FROM duan da\n" .
                "WHERE da.DA_TRANGTHAI = {$escStatus}\n" .
                "  AND (\n" .
                "       EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.TV_MA = '{$escUser}')\n" .
                "    OR da.DA_NGUOIPHUTRACH = '{$escUser}'\n" .
                "  )\n" .
                "ORDER BY da.DA_MA DESC";
        }

        $result = mysqli_query($conn, $sql);
        return $result;
    }

    //Data trả về theo thứ tự ở view
    $projectsStart = mysqli_fetch_all(querySql($conn, 5), MYSQLI_ASSOC);

    $projectsInProgress = mysqli_fetch_all(querySql($conn, 1), MYSQLI_ASSOC);

    $projectsMove = mysqli_fetch_all(querySql($conn, 3), MYSQLI_ASSOC);

    $projectsFinish = mysqli_fetch_all(querySql($conn, 2), MYSQLI_ASSOC);

    $projectsCancel = mysqli_fetch_all(querySql($conn, 4), MYSQLI_ASSOC);

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
