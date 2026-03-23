<?php
// getView.php
include('../../config.php');
session_start();

// Check if the request is made via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Sample data to be passed to the view

    //1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
    function querySql($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '')
    {
        // Kiểm tra đăng nhập
        $userId = $_SESSION['code'] ?? null;
        $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý
        $isAdmin = isset($_SESSION['active']) && $_SESSION['active'] == 1 || ($nndMa == 4);
        if (!$userId) return [];
        
        // Lấy thông tin người dùng hiện tại
        $userInfo = [];
        $userQuery = "SELECT NND_MA, PB_MA FROM thanhvien WHERE TV_MA = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();
        if ($userResult->num_rows > 0) {
            $userInfo = $userResult->fetch_assoc();
        }
        $isManager = ($userInfo['NND_MA'] ?? 0) == 2;
        $userPB = $userInfo['PB_MA'] ?? null;
        
        // Kiểm tra xem trường dscv_trangthaihd có tồn tại không
        $checkField = "SHOW COLUMNS FROM danhsachcongviec LIKE 'dscv_trangthaihd'";
        $fieldExists = mysqli_query($conn, $checkField);
        $hasTrangThaiHD = mysqli_num_rows($fieldExists) > 0;
        
        if ($isAdmin) {
            if ($hasTrangThaiHD) {
                $sql = "SELECT d.* FROM danhsachcongviec d 
                        LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                        WHERE d.dscv_trangthaihd = 1 ";
                if ($status == 2) {
                    $sql .= "AND (d.DSCV_TRANGTHAI = ? OR d.DSCV_TRANGTHAI = 6)";
                } else {
                    $sql .= "AND d.DSCV_TRANGTHAI = ?";
                }
            } else {
                $sql = "SELECT d.* FROM danhsachcongviec d 
                        LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                        WHERE d.DSCV_TRANGTHAI = ?";
                if ($status == 2) {
                    $sql .= " OR d.DSCV_TRANGTHAI = 6)";
                }
            }
            $params = [$status];
            $types = "i";
        } elseif ($isManager) {
            if ($hasTrangThaiHD) {
                $sql = "SELECT DISTINCT d.* FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                LEFT JOIN thanhvien tvA ON d.TV_MA = tvA.TV_MA 
                LEFT JOIN thanhvien tvDT ON dt.TV_MA = tvDT.TV_MA 
                WHERE d.dscv_trangthaihd = 1
                AND"; 
                if ($status == 2) {
                    $sql .= " (d.DSCV_TRANGTHAI = ? OR d.DSCV_TRANGTHAI = 6)";
                } else {
                    $sql .= " d.DSCV_TRANGTHAI = ?";
                }

                $sql .= " AND (
                    d.TV_MA = ? 
                OR du.DA_NGUOIPHUTRACH = ? 
                OR tvA.PB_MA = ? 
                OR tvDT.PB_MA = ? 
                OR EXISTS (SELECT 1 FROM thanhvien tvL WHERE tvL.TV_MA = du.DA_NGUOIPHUTRACH AND tvL.PB_MA = ?))";
            } else {
                $sql = "SELECT DISTINCT d.* FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                LEFT JOIN thanhvien tvA ON d.TV_MA = tvA.TV_MA 
                LEFT JOIN thanhvien tvDT ON dt.TV_MA = tvDT.TV_MA 
                WHERE ";
                
                if ($status == 2) {
                    $sql .= " AND (d.DSCV_TRANGTHAI = ? OR d.DSCV_TRANGTHAI = 6))";
                } else {
                    $sql .= " AND d.DSCV_TRANGTHAI = ?)";
                }

                $sql .= "(
                    d.TV_MA = ? 
                OR du.DA_NGUOIPHUTRACH = ? 
                OR tvA.PB_MA = ? 
                OR tvDT.PB_MA = ? 
                OR EXISTS (SELECT 1 FROM thanhvien tvL WHERE tvL.TV_MA = du.DA_NGUOIPHUTRACH AND tvL.PB_MA = ?)";
                
            }
            $params = [$status, $userId, $userId, $userPB, $userPB, $userPB];
            $types = "isssss";
        } else {
            if ($hasTrangThaiHD) {
                $sql = "SELECT DISTINCT d.* FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                WHERE d.dscv_trangthaihd = 1
                AND (d.TV_MA = ? OR dt.TV_MA = ? OR du.DA_NGUOIPHUTRACH = ? )";
                if ($status == 2) {
                    $sql .= "AND (d.DSCV_TRANGTHAI = ? OR d.DSCV_TRANGTHAI = 6)";
                } else {
                    $sql .= "AND d.DSCV_TRANGTHAI = ?";
                }
            } else {
                $sql = "SELECT DISTINCT d.* FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                WHERE (d.TV_MA = ? OR dt.TV_MA = ? OR du.DA_NGUOIPHUTRACH = ?)";
                if ($status == 2) {
                    $sql .= "AND (d.DSCV_TRANGTHAI = ? OR d.DSCV_TRANGTHAI = 6)";
                } else {
                    $sql .= "AND d.DSCV_TRANGTHAI = ?";
                }
            }
            $params = [$status, $userId, $userId, $userId];
            $types = "isss";
        }
        
        if (!empty($keywords)) {
            $sql .= " AND DSCV_TEN LIKE ?";
            $params[] = "%$keywords%";
            $types .= "s";
        }
        if (!empty($from_date)) {
            $sql .= " AND DATE(DSCV_NGAYBATDAU) >= ?";
            $params[] = $from_date;
            $types .= "s";
        }
        if (!empty($to_date)) {
            $sql .= " AND DATE(DSCV_NGAYKETTHUC) <= ?";
            $params[] = $to_date;
            $types .= "s";
        }
        if ($project_id === 'private') {
            $sql .= " AND (d.DA_MA IS NULL OR d.DA_MA = '' OR du.DA_MA IS NULL OR du.DA_MA = '')";
        } else if (!empty($project_id)) {
            $sql .= " AND d.DA_MA = ?";
            $params[] = $project_id;
            $types .= "s";
        }
        
        $sql .= " ORDER BY DSCV_MA DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) return [];
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) { $stmt->close(); return []; }
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    // Lấy các tham số lọc từ request
    $keywords = $_GET['keywords'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $project_id = $_GET['project_id'] ?? '';

    //Data trả về theo thứ tự ở view
    $projectsStart = querySql($conn, 5, $keywords, $from_date, $to_date, $project_id);
    $projectsInProgress = querySql($conn, 1, $keywords, $from_date, $to_date, $project_id);
    $projectsMove = querySql($conn, 3, $keywords, $from_date, $to_date, $project_id);
    $projectsFinish = querySql($conn, 2, $keywords, $from_date, $to_date, $project_id);
    $projectsCancel = querySql($conn, 4, $keywords, $from_date, $to_date, $project_id);

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
