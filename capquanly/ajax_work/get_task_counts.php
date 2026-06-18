<?php
// Kết nối database
require_once('../../config.php');

// Kiểm tra xem có phải là AJAX request không
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die(json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ']));
}

// Lấy project_id từ request nếu có
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");
$depart = isset($_GET['depart']) ? strval($_GET['depart']) : null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();    
}
$userCode  = $_SESSION['code'] ?? '';

$nndMa     = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý
$isAdmin   = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || $nndMa == 4;
$isManager = ($nndMa == 2);
error_log("Nhận request với project_id: " . ($projectId ?: 'Tất cả dự án') . ", user=" . $userCode . ", admin=" . ($isAdmin ? '1' : '0') . ", manager=" . ($isManager ? '1' : '0'));

// Thêm điều kiện lọc theo project nếu có, loại trừ công việc riêng (không thuộc dự án)
$baseCondition = "danhsachcongviec.dscv_trangthaihd = 1";
// Điều kiện phân quyền tổng hợp (khi không chọn dự án cụ thể)
$permissionCondition = '';
$permissionParams = [];

// Lấy PB_MA cho quản lý nếu cần
$managerDept = null;
if ($isManager) {
    $stmtDept = $conn->prepare("SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1");
    if ($stmtDept) {
        $stmtDept->bind_param('s', $userCode);
        $stmtDept->execute();
        $resDept = $stmtDept->get_result();
        $rowDept = $resDept ? $resDept->fetch_assoc() : null;
        $managerDept = $rowDept['PB_MA'] ?? null;
        $stmtDept->close();
    }
}

// Nếu không chọn dự án thì áp điều kiện phân quyền theo vai trò
if (!$projectId) {
    $baseCondition .= " AND danhsachcongviec.DA_MA IS NULL AND YEAR(danhsachcongviec.DSCV_NGAYBATDAU) <= $year AND YEAR(danhsachcongviec.DSCV_NGAYKETTHUC) >= $year";
    if ($isAdmin) {
        $permissionCondition .= $depart ? " AND danhsachcongviec.PB_MA = ?" : "";
        $permissionParams = $depart ? [$depart] : [];
    } elseif ($isManager && $managerDept) {
        /* $permissionCondition = " AND EXISTS (\n"
            . "   SELECT 1 FROM duan da\n"
            . "   WHERE da.DA_MA = danhsachcongviec.DA_MA AND (\n"
            . "       da.DA_NGUOIPHUTRACH = ?\n"
            . "       OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)\n"
            . "       OR (SELECT PB_MA FROM thanhvien WHERE TV_MA = da.DA_NGUOIPHUTRACH LIMIT 1) = ?\n"
            . "       OR EXISTS (\n"
            . "            SELECT 1 FROM duan_thanhvien dt\n"
            . "            JOIN thanhvien tv ON tv.TV_MA = dt.TV_MA\n"
            . "            WHERE dt.DA_MA = da.DA_MA AND tv.PB_MA = ?\n"
            . "       )\n"
            . "   )\n"
            . ")"; */
            $permissionCondition = " AND danhsachcongviec.PB_MA =?";
        //$permissionParams = [$userCode, $userCode, $managerDept, $managerDept];
        $permissionParams = [$managerDept];
    } else {
        // Người dùng thường: dự án mình phụ trách hoặc tham gia
        $permissionCondition = " AND EXISTS (\n"
            . "   SELECT 1 FROM duan da\n"
            . "   WHERE da.DA_MA = danhsachcongviec.DA_MA AND (\n"
            . "       da.DA_NGUOIPHUTRACH = ?\n"
            . "       OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)\n"
            . "   )\n"
            . ")";
        $permissionParams = [$userCode, $userCode];
    }
}

}

// Khởi tạo mảng kết quả TRƯỜC khi kiểm tra canView
$response = [
    'status' => 'success',
    'data'   => [
        'total'       => 0,
        'pending'     => 0,
        'in_progress' => 0,
        'completed'   => 0,
        'overdue'     => 0,
        'disbursed'   => 0
    ]
];

if ($projectId) {
    // Kiểm tra quyền truy cập dự án cụ thể
    $canView = true;
    if (!$isAdmin) {
        if ($isManager) {
            // Quản lý: theo phòng ban hoặc của bản thân
            $sqlCheck = "SELECT 1 FROM duan da WHERE da.DA_MA = ? AND (
 da.DA_NGUOIPHUTRACH = ?
 OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
 OR (SELECT PB_MA FROM thanhvien WHERE TV_MA = da.DA_NGUOIPHUTRACH LIMIT 1) = ?
 OR EXISTS (SELECT 1 FROM duan_thanhvien dt JOIN thanhvien tv ON tv.TV_MA = dt.TV_MA WHERE dt.DA_MA = da.DA_MA AND tv.PB_MA = ?)
) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            if ($stmtCheck) {
                $stmtCheck->bind_param('sssss', $projectId, $userCode, $userCode, $managerDept, $managerDept);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                $canView  = ($resCheck && $resCheck->num_rows > 0);
                $stmtCheck->close();
            }
        } else {
            // Người dùng thường: phụ trách hoặc tham gia hoặc được giao việc
            $sqlCheck = "SELECT 1 FROM duan da 
                WHERE da.DA_MA = ? AND (
                    da.DA_NGUOIPHUTRACH = ?
                    OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                    OR EXISTS (SELECT 1 FROM danhsachcongviec cv WHERE cv.DA_MA = da.DA_MA AND cv.TV_MA = ?)
                ) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            if ($stmtCheck) {
                $stmtCheck->bind_param('ssss', $projectId, $userCode, $userCode, $userCode);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                $canView  = ($resCheck && $resCheck->num_rows > 0);
                $stmtCheck->close();
            }
        }
    }

    if (!$canView) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $baseCondition .= " AND danhsachcongviec.DA_MA = " . $projectId;
}
error_log("Điều kiện truy vấn: " . $baseCondition);

try {
    // Kiểm tra kết nối
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Không thể kết nối đến cơ sở dữ liệu");
    }

    // Hàm thực thi truy vấn đếm
    function getTaskCount($conn, $condition, $params = [], $ignorePermissions = false) {
        global $isAdmin, $isManager, $userCode;
        
        $sql = "SELECT COUNT(*) as count 
               FROM danhsachcongviec 
               LEFT JOIN duan ON danhsachcongviec.DA_MA = duan.DA_MA 
               WHERE $condition";
        
        // Thêm điều kiện phân quyền nếu không bỏ qua
        if (!$ignorePermissions && !$isAdmin && !$isManager) {
            // Với người dùng thường, chỉ đếm công việc của họ trong dự án
            $sql .= " AND (danhsachcongviec.TV_MA = ? OR (duan.DA_NGUOIPHUTRACH = ? AND danhsachcongviec.TV_MA IS NOT NULL))";
            array_unshift($params, $userCode, $userCode);
        }
        
        error_log("SQL: " . $sql); // Log câu lệnh SQL
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi thực thi truy vấn: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)$row['count'];
    }

    // Lấy tổng số công việc (bỏ qua điều kiện phân quyền)
    $response['data']['total'] = getTaskCount($conn, $baseCondition. $permissionCondition, $permissionParams);
    //$response['data']['total'] = $baseCondition . $permissionCondition;

    // Lấy số công việc đang chờ (chưa tiếp nhận)
    $response['data']['pending'] = getTaskCount($conn, "$baseCondition AND danhsachcongviec.DSCV_TRANGTHAI = 5" . $permissionCondition, $permissionParams);

    // Lấy số công việc đang thực hiện
    $response['data']['in_progress'] = getTaskCount($conn, "$baseCondition AND danhsachcongviec.DSCV_TRANGTHAI = 1" . $permissionCondition, $permissionParams);

    // Lấy số công việc đã hoàn thành
    $response['data']['completed'] = getTaskCount($conn, "$baseCondition AND (danhsachcongviec.DSCV_TRANGTHAI = 2 OR danhsachcongviec.DSCV_TRANGTHAI = 6)" . $permissionCondition, $permissionParams);

    // Lấy số công việc trễ hạn
    $today = date('Y-m-d');
    $response['data']['overdue'] = getTaskCount($conn, "$baseCondition AND danhsachcongviec.DSCV_TRANGTHAI = 3" . $permissionCondition, $permissionParams);

    // Tính tổng giá trị giải ngân
    if ($projectId) {
        $sumSql = "SELECT COALESCE(SUM(DSCV_GIATRIGIAINGAN), 0) AS total_disbursed FROM danhsachcongviec WHERE dscv_trangthaihd = 1 AND DSCV_TRANGTHAIGIAINGAN = 1 AND DA_MA = ?";
        $stmtSum = $conn->prepare($sumSql);
        if ($stmtSum) {
            $stmtSum->bind_param('i', $projectId);
        } else {
            throw new Exception("Lỗi chuẩn bị câu lệnh tính giải ngân: " . $conn->error);
        }
    } else {
        if ($isAdmin) {
            $sumSql = "SELECT COALESCE(SUM(DSCV_GIATRIGIAINGAN), 0) AS total_disbursed FROM danhsachcongviec WHERE dscv_trangthaihd = 1 AND DSCV_TRANGTHAIGIAINGAN = 1 AND danhsachcongviec.DA_MA IS NULL AND YEAR(danhsachcongviec.DSCV_NGAYBATDAU) <= $year AND YEAR(danhsachcongviec.DSCV_NGAYKETTHUC) >= $year";
            $sumSql .= $depart ? " AND danhsachcongviec.PB_MA = '$depart'" : "";
            $stmtSum = $conn->prepare($sumSql);
        } elseif ($isManager && $managerDept) {
            $sumSql = "SELECT COALESCE(SUM(dcv.DSCV_GIATRIGIAINGAN), 0) AS total_disbursed\n"
                . "FROM danhsachcongviec dcv\n"
                . "JOIN duan da ON da.DA_MA = dcv.DA_MA\n"
                . "WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NULL AND YEAR(dcv.DSCV_NGAYBATDAU) <= $year AND YEAR(dcv.DSCV_NGAYKETTHUC) >= $year";
            $stmtSum = $conn->prepare($sumSql);
        } else {
            $sumSql = "SELECT COALESCE(SUM(dcv.DSCV_GIATRIGIAINGAN), 0) AS total_disbursed
                FROM danhsachcongviec dcv
                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NULL
                AND dcv.DSCV_TRANGTHAIGIAINGAN = 1
                AND YEAR(dcv.DSCV_NGAYBATDAU) <= $year AND YEAR(dcv.DSCV_NGAYKETTHUC) >= $year
                AND dcv.TV_MA = ?";
            $stmtSum = $conn->prepare($sumSql);
            if ($stmtSum) {
                $stmtSum->bind_param('s', $userCode);
            }
        }
        if (!$stmtSum) {
            throw new Exception("Lỗi chuẩn bị câu lệnh tính giải ngân: " . $conn->error);
        }
    }

    if (!$stmtSum->execute()) {
        throw new Exception("Lỗi thực thi truy vấn tính giải ngân: " . $stmtSum->error);
    }
    $resultSum = $stmtSum->get_result();
    $rowSum = $resultSum->fetch_assoc();
    $response['data']['disbursed'] = (int)($rowSum['total_disbursed'] ?? 0);
    $stmtSum->close();

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    error_log("Lỗi get_task_counts: " . $e->getMessage());
}

// Đóng kết nối
if (isset($conn)) {
    $conn->close();
}

// Trả về JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;