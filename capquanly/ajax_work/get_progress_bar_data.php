<?php
include('../../config.php');
session_start();
header('Content-Type: application/json');

// Input
$mode = $_GET['mode'] ?? 'month';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$projectId = $_GET['project_id'] ?? '';
$phongbanId = $_GET['phongban_id'] ?? '';

// Role info
$userCode = $_SESSION['code'] ?? '';

$nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = quản lý (manager)
$isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || $nndMa == 4;
// Base conditions: only active tasks, belong to a project, completed with finished date
$baseWhere = "dcv.dscv_trangthaihd = 1 AND (dcv.DSCV_TRANGTHAI = 2 OR dcv.DSCV_TRANGTHAI = 6) AND dcv.DSCV_NGAYHOANTHANH IS NOT NULL";

// Thêm điều kiện lọc theo dự án nếu có
if (!empty($projectId)) {
    $baseWhere .= " AND dcv.DA_MA = '" . mysqli_real_escape_string($conn, $projectId) . "'";
} else {
    $baseWhere .= " AND dcv.DA_MA IS NULL";
    if (!empty($phongbanId)) {
        $baseWhere .= " AND dcv.PB_MA = '" . strval($phongbanId) . "'";
    }
}


// Build role-based filter
$roleWhere = '';
if (!$isAdmin) {
    $escUser = mysqli_real_escape_string($conn, $userCode);
    if ($nndMa == 2) {
        // Quản lý: bản thân + cùng phòng ban, và các dự án mình phụ trách hoặc do người cùng phòng phụ trách
        $roleWhere = " AND (\n"
            . "        dcv.TV_MA = '{$escUser}'\n"
            . "     OR EXISTS (\n"
            . "            SELECT 1 FROM thanhvien tv2\n"
            . "            WHERE tv2.TV_MA = dcv.TV_MA\n"
            . "              AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n"
            . "        )\n"
            . "     OR EXISTS (\n"
            . "            SELECT 1 FROM duan da2\n"
            . "            WHERE da2.DA_MA = dcv.DA_MA\n"
            . "              AND (\n"
            . "                    da2.DA_NGUOIPHUTRACH = '{$escUser}'\n"
            . "                 OR EXISTS (\n"
            . "                        SELECT 1 FROM thanhvien tv3\n"
            . "                        WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH\n"
            . "                          AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n"
            . "                    )\n"
            . "              )\n"
            . "        )\n"
            . "     OR EXISTS (\n"
            . "            SELECT 1\n"
            . "            FROM duan da3\n"
            . "            JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH\n"
            . "            WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n"
            . "              AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH\n"
            . "        )\n"
            . "    )";
    } else {
        // Nhân viên: bản thân, là thành viên dự án, hoặc là người phụ trách dự án
        $roleWhere = " AND (\n"
            . "        dcv.TV_MA = '{$escUser}'\n"
            . "     OR EXISTS (\n"
            . "            SELECT 1\n"
            . "            FROM duan da\n"
            . "            LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA\n"
            . "            WHERE da.DA_MA = dcv.DA_MA\n"
            . "              AND (dt.TV_MA = '{$escUser}' OR da.DA_NGUOIPHUTRACH = '{$escUser}')\n"
            . "        )\n"
            . "    )";
    }
}

// Helper to run a COUNT(*) with dynamic WHERE additions
function countTasks($conn, $extraWhere)
{
    $sql = "SELECT COUNT(*) AS c FROM danhsachcongviec dcv WHERE {$extraWhere}";
    $rs = mysqli_query($conn, $sql);
    if (!$rs)
        return 0;
    $row = mysqli_fetch_assoc($rs);
    return (int) ($row['c'] ?? 0);
}

// Mode: month
if ($mode === 'month') {
    $labels = [];
    $data_ontime = [];
    $data_late = [];
    for ($m = 1; $m <= 12; $m++) {
        $labels[] = "Tháng $m";
        $whereMonth = $baseWhere . $roleWhere .
            " AND YEAR(dcv.DSCV_NGAYHOANTHANH) = {$year} AND MONTH(dcv.DSCV_NGAYHOANTHANH) = {$m}";
        $ontimeWhere = $whereMonth . " AND dcv.DSCV_NGAYHOANTHANH < DATE_ADD(dcv.DSCV_NGAYKETTHUC, INTERVAL 1 DAY)";
        $lateWhere = $whereMonth . " AND dcv.DSCV_NGAYHOANTHANH > DATE_ADD(dcv.DSCV_NGAYKETTHUC, INTERVAL 1 DAY)";
        $data_ontime[] = countTasks($conn, $ontimeWhere);
        $data_late[] = countTasks($conn, $lateWhere);
    }
    echo json_encode(['labels' => $labels, 'ontime' => $data_ontime, 'late' => $data_late]);
    exit;
}

// Mode: quarter
if ($mode === 'quarter') {
    $labels = ['Quý 1', 'Quý 2', 'Quý 3', 'Quý 4'];
    $data_ontime = [];
    $data_late = [];
    $quarters = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12]
    ];
    foreach ($quarters as $q => $months) {
        $in = implode(',', $months);
        $whereQuarter = $baseWhere . $roleWhere .
            " AND YEAR(dcv.DSCV_NGAYHOANTHANH) = {$year} AND MONTH(dcv.DSCV_NGAYHOANTHANH) IN ({$in})";
        $ontimeWhere = $whereQuarter . " AND dcv.DSCV_NGAYHOANTHANH < DATE_ADD(dcv.DSCV_NGAYKETTHUC, INTERVAL 1 DAY)";
        $lateWhere = $whereQuarter . " AND dcv.DSCV_NGAYHOANTHANH > DATE_ADD(dcv.DSCV_NGAYKETTHUC, INTERVAL 1 DAY)";
        $data_ontime[] = countTasks($conn, $ontimeWhere);
        $data_late[] = countTasks($conn, $lateWhere);
    }
    echo json_encode(['labels' => $labels, 'ontime' => $data_ontime, 'late' => $data_late]);
    exit;
}

// Mode: year
if ($mode === 'year') {
    $labels = [];
    $data_ontime = [];
    $data_late = [];
    $sql_years = "SELECT DISTINCT YEAR(dcv.DSCV_NGAYHOANTHANH) AS y\n"
        . "FROM danhsachcongviec dcv\n"
        . "WHERE {$baseWhere}{$roleWhere}\n"
        . "ORDER BY y";
    $rs = mysqli_query($conn, $sql_years);
    $years = [];
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $years[] = (int) $row['y'];
        }
    }
    foreach ($years as $y) {
        $labels[] = $y;
        $whereYear = $baseWhere . $roleWhere . " AND YEAR(dcv.DSCV_NGAYHOANTHANH) = {$y}";
        $ontimeWhere = $whereYear . " AND dcv.DSCV_NGAYHOANTHANH < DATE_ADD(dcv.DSCV_NGAYKETTHUC, INTERVAL 1 DAY)";
        $lateWhere = $whereYear . " AND dcv.DSCV_NGAYHOANTHANH > DATE_ADD(dcv.DSCV_NGAYKETTHUC, INTERVAL 1 DAY)";
        $data_ontime[] = countTasks($conn, $ontimeWhere);
        $data_late[] = countTasks($conn, $lateWhere);
    }
    echo json_encode(['labels' => $labels, 'ontime' => $data_ontime, 'late' => $data_late]);
    exit;
}

echo json_encode(['labels' => [], 'ontime' => [], 'late' => []]);
