<?php
// getView.php
include('../../config.php');

// Check if the request is made via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Sample data to be passed to the view

    $code = $_GET['code'];

    // Khởi tạo session và lấy thông tin người dùng
    session_start();
    
    // Lấy thông tin user hiện tại
    $userId = $_SESSION['code'] ?? null; 
    $isAdmin = isset($_SESSION['active']) && $_SESSION['active'] == 1;
    
    // Get user department and role
    $userQuery = "SELECT NND_MA, PB_MA FROM thanhvien WHERE TV_MA = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userInfo = $userResult->fetch_assoc();
    $isManager = ($userInfo['NND_MA'] ?? 0) == 2;
    $userPB = $userInfo['PB_MA'] ?? null;

    // Xây dựng câu query dựa trên vai trò
    if ($isAdmin) {
        // Admin xem tất cả dự án đang hoạt động
        $projectsSql = "SELECT * FROM duan WHERE DA_TRANGTHAI = 1";
        $stmt = $conn->prepare($projectsSql);
    } 
    elseif ($isManager) {
        // Manager xem dự án của phòng ban mình
        $projectsSql = "SELECT DISTINCT d.* FROM duan d 
            LEFT JOIN thanhvien tv ON d.DA_NGUOIPHUTRACH = tv.TV_MA 
            LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA
            LEFT JOIN thanhvien tv2 ON dt.TV_MA = tv2.TV_MA
            LEFT JOIN danhsachcongviec cv ON d.DA_MA = cv.DA_MA
            LEFT JOIN thanhvien tv3 ON cv.TV_MA = tv3.TV_MA
            WHERE d.DA_TRANGTHAI = 1 
            AND (
                tv.PB_MA = ? 
                OR tv2.PB_MA = ? 
                OR d.DA_NGUOIPHUTRACH = ?
                OR tv3.PB_MA = ?
                OR d.DA_MA = (SELECT DA_MA FROM danhsachcongviec WHERE DSCV_MA = ?)
            )";
        $stmt = $conn->prepare($projectsSql);
        $stmt->bind_param('sssss', $userPB, $userPB, $userId, $userPB, $code);
    }
    else {
        // User thường: xem dự án mình tham gia, phụ trách hoặc có công việc trong đó
        $projectsSql = "SELECT DISTINCT d.* FROM duan d 
            WHERE d.DA_TRANGTHAI = 1 
            AND (
                EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = d.DA_MA AND dt.TV_MA = ?)
                OR d.DA_NGUOIPHUTRACH = ?
                OR EXISTS (SELECT 1 FROM danhsachcongviec cv WHERE cv.DA_MA = d.DA_MA AND cv.TV_MA = ?)
                OR d.DA_MA = (SELECT DA_MA FROM danhsachcongviec WHERE DSCV_MA = ?)
            )";
        $stmt = $conn->prepare($projectsSql);
        $stmt->bind_param('ssss', $userId, $userId, $userId, $code);
    }

    $stmt->execute();
    $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get data dự án - sửa lại câu query này
    $taskSql = "SELECT v.*, t.TV_TEN, d.DA_MA, d.DA_TEN 
                FROM danhsachcongviec v 
                LEFT JOIN thanhvien t ON v.TV_MA = t.TV_MA
                LEFT JOIN duan d ON v.DA_MA = d.DA_MA 
                WHERE v.DSCV_MA = ?";
    $taskStmt = $conn->prepare($taskSql);
    $taskStmt->bind_param('s', $code);
    $taskStmt->execute();
    $project = $taskStmt->get_result()->fetch_assoc();

    // Lấy nhận xét — dùng Prepared Statement (tránh SQL Injection)
    $sqlComment = "SELECT c.*, v.TV_TEN FROM binhluan_cv c INNER JOIN thanhvien v ON c.TV_MA = v.TV_MA WHERE c.DSCV_MA = ? ORDER BY c.ID DESC";
    $stmtComment = $conn->prepare($sqlComment);
    $stmtComment->bind_param('s', $code);
    $stmtComment->execute();
    $comments = $stmtComment->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtComment->close();

    $data = [
        'project' => $project,
        'projects' => $projects,
        'comments' => $comments,
    ];

    // Cập nhật trạng thái đã xem — dùng Prepared Statement
    $sqlViewed = "UPDATE binhluan_cv SET TRANGTHAI = 1 WHERE DSCV_MA = ? AND TV_MA <> ?";
    $stmtViewed = $conn->prepare($sqlViewed);
    $stmtViewed->bind_param('ss', $code, $userId);
    $stmtViewed->execute();
    $stmtViewed->close();

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

