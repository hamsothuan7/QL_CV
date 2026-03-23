<?php
try {
    include('../../config.php');
    session_start();

    // Lấy tham số lọc từ request
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $chart_type = $_GET['chart_type'] ?? 'both'; // status, progress, hoặc both
    $project_id = $_GET['project_id'] ?? '';
    $depart = $_GET['depart'] ?? '';

    $userCode = $_SESSION['code'] ?? '';
    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý
    $isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || $nndMa == 4;
    $isManager = ($nndMa == 2);

    $response = [];

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

    // Helper: thêm điều kiện ngày cho câu query và params/types
    $appendDateFilters = function (&$sql, &$params, &$types, $aliasPrefix = '') use ($from_date, $to_date) {
        $prefix = $aliasPrefix ? $aliasPrefix . '.' : '';
        if (!empty($from_date)) {
            $sql .= " AND DATE({$prefix}DSCV_NGAYBATDAU) >= ?";
            $params[] = $from_date;
            $types .= 's';
        }
        if (!empty($to_date)) {
            $sql .= " AND DATE({$prefix}DSCV_NGAYKETTHUC) <= ?";
            $params[] = $to_date;
            $types .= 's';
        }
    };

    // ==== Biểu đồ trạng thái công việc ====
    if ($chart_type == 'status' || $chart_type == 'both') {
        $statusData = [];
        $statusLabels = [];
        $statusColors = [];

        $statuses = [
            ['id' => 5, 'label' => 'Chưa tiếp nhận', 'color' => '#64748B'],
            ['id' => 1, 'label' => 'Đang tiến hành', 'color' => '#3B82F6'],
            ['id' => 2, 'label' => 'Hoàn thành', 'color' => '#10B981'],
            ['id' => 3, 'label' => 'Trễ', 'color' => '#F43F5E'],
            ['id' => 4, 'label' => 'Hủy', 'color' => '#78716C'],
            ['id' => 6, 'label' => 'Hoàn thành trể', 'color' => '#F59E0B']
        ];

        foreach ($statuses as $status) {
            $params = [];
            $types = '';

            if ($isAdmin) {
                $sql = "SELECT COUNT(*) AS count FROM danhsachcongviec WHERE dscv_trangthaihd = 1 AND DSCV_TRANGTHAI = ?";
                $params[] = $status['id'];
                $types .= 'i';
                if (!empty($project_id)) {
                    $sql .= " AND DA_MA = ?";
                    $params[] = $project_id;
                    $types .= 's';
                } else {
                    $sql .= " AND DA_MA IS NULL";
                    $appendDateFilters($sql, $params, $types, '');
                    if (!empty($depart)) {
                        $sql .= " AND PB_MA = ?";
                        $params[] = $depart;
                        $types .= 's';
                    }
                }
            } elseif ($isManager) {
                $sql = "SELECT COUNT(DISTINCT dcv.DSCV_MA) AS count\n"
                    . "FROM danhsachcongviec dcv\n"
                    . "LEFT JOIN duan da ON da.DA_MA = dcv.DA_MA\n"
                    . "WHERE dcv.dscv_trangthaihd = 1\n"
                    . "  AND dcv.DSCV_TRANGTHAI = ? AND (da.PB_MA = ? OR dcv.PB_MA = ?)\n";
                $params = [$status['id'], $managerDept, $managerDept];
                $types = 'iss';

                if (!empty($project_id)) {
                    $sql .= " AND dcv.DA_MA = ?";
                    $params[] = $project_id;
                    $types .= 's';
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    $appendDateFilters($sql, $params, $types, 'dcv');
                }
            } else { // Người dùng thường
                $sql = "SELECT COUNT(*) AS count\n";
                $sql .= "FROM danhsachcongviec dcv\n";
                $sql .= "INNER JOIN duan da ON dcv.DA_MA = da.DA_MA\n";
                $sql .= "LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA\n";
                $sql .= "WHERE dcv.dscv_trangthaihd = 1\n";
                $sql .= "  AND dcv.DA_MA IS NOT NULL\n";
                $sql .= "  AND dcv.DSCV_TRANGTHAI = ?\n";
                $sql .= "  AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                $params = [$status['id'], $userCode, $userCode, $userCode];
                $types = 'isss';
                if (!empty($project_id)) {
                    $sql .= " AND dcv.DA_MA = ?";
                    $params[] = $project_id;
                    $types .= 's';
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    $appendDateFilters($sql, $params, $types, 'dcv');
                }

            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $count = (int) ($row['count'] ?? 0);
            $statusData[] = $count;
            $statusLabels[] = $status['label'] . ' (' . $count . ')';
            $statusColors[] = $status['color'];
        }

        $response['status'] = [
            'data' => $statusData,
            'labels' => $statusLabels,
            'colors' => $statusColors,
        ];
    }

    // ==== Biểu đồ tiến độ công việc ====
    if ($chart_type == 'progress' || $chart_type == 'both') {
        $progressData = [];
        $progressLabels = [];
        $progressColors = [];

        $progressLevels = [
            ['min' => 0, 'max' => 49, 'label' => 'Dưới 50%', 'color' => '#e74c3c'],
            ['min' => 50, 'max' => 79, 'label' => '50-79%', 'color' => '#e67e22'],
            ['min' => 80, 'max' => 99, 'label' => '80-99%', 'color' => '#3498db'],
            ['min' => 100, 'max' => 100, 'label' => 'Đã hoàn thành', 'color' => '#2ecc71']
        ];

        foreach ($progressLevels as $level) {
            $params = [];
            $types = '';

            if ($isAdmin) {
                $sql = "SELECT COUNT(*) AS count FROM danhsachcongviec WHERE dscv_trangthaihd = 1 AND DA_MA IS NOT NULL AND TIEN_DO >= ? AND TIEN_DO <= ?";
                $params = [$level['min'], $level['max']];
                $types = 'ii';
                if (!empty($project_id)) {
                    $sql .= " AND DA_MA = ?";
                    $params[] = $project_id;
                    $types .= 's';
                }
                $appendDateFilters($sql, $params, $types, '');
            } elseif ($isManager) {
                $sql = "SELECT COUNT(DISTINCT dcv.DSCV_MA) AS count\n"
                    . "FROM danhsachcongviec dcv\n"
                    . "LEFT JOIN thanhvien tv_nguoitao ON tv_nguoitao.TV_MA = dcv.TV_MA\n"
                    . "LEFT JOIN duan da ON da.DA_MA = dcv.DA_MA\n"
                    . "WHERE dcv.dscv_trangthaihd = 1\n"
                    . "  AND dcv.DA_MA IS NOT NULL\n"
                    . "  AND dcv.TIEN_DO >= ? AND dcv.TIEN_DO <= ?\n"
                    . "  AND (\n"
                    . "       dcv.TV_MA = ?\n"
                    . "    OR tv_nguoitao.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)\n"
                    . "    OR da.DA_NGUOIPHUTRACH = ?\n"
                    . "    OR EXISTS (\n"
                    . "        SELECT 1 FROM thanhvien tv_phutrach\n"
                    . "        WHERE tv_phutrach.TV_MA = da.DA_NGUOIPHUTRACH\n"
                    . "          AND tv_phutrach.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)\n"
                    . "    )\n"
                    . "    OR EXISTS (\n"
                    . "        SELECT 1 FROM duan_thanhvien dt\n"
                    . "        JOIN thanhvien tv_thanhvien ON tv_thanhvien.TV_MA = dt.TV_MA\n"
                    . "        WHERE dt.DA_MA = dcv.DA_MA\n"
                    . "          AND tv_thanhvien.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)\n"
                    . "    )\n"
                    . "  )";
                $params = [$level['min'], $level['max'], $userCode, $userCode, $userCode, $userCode, $userCode];
                $types = 'iisssss';
                if (!empty($project_id)) {
                    $sql .= " AND dcv.DA_MA = ?";
                    $params[] = $project_id;
                    $types .= 's';
                }
                $appendDateFilters($sql, $params, $types, 'dcv');
            } else { // Người dùng thường
                $sql = "SELECT COUNT(*) AS count\n";
                $sql .= "FROM danhsachcongviec dcv\n";
                $sql .= "INNER JOIN duan da ON dcv.DA_MA = da.DA_MA\n";
                $sql .= "LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA\n";
                $sql .= "WHERE dcv.dscv_trangthaihd = 1\n";
                $sql .= "  AND dcv.DA_MA IS NOT NULL\n";
                $sql .= "  AND dcv.TIEN_DO >= ? AND dcv.TIEN_DO <= ?\n";
                $sql .= "  AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                $params = [$level['min'], $level['max'], $userCode, $userCode, $userCode];
                $types = 'iisss';
                if (!empty($project_id)) {
                    $sql .= " AND dcv.DA_MA = ?";
                    $params[] = $project_id;
                    $types .= 's';
                }
                $appendDateFilters($sql, $params, $types, 'dcv');
            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $count = (int) ($row['count'] ?? 0);
            $progressData[] = $count;
            $progressLabels[] = $level['label'] . ' (' . $count . ')';
            $progressColors[] = $level['color'];
        }

        $response['progress'] = [
            'data' => $progressData,
            'labels' => $progressLabels,
            'colors' => $progressColors,
        ];
    }

    // ==== Biểu đồ giải ngân (tổng giá trị giải ngân so với tổng mức đầu tư) ====
    if ($chart_type == 'disbursement') {
        $disbursement = [
            'data' => [],
            'labels' => ['Đã giải ngân', 'Còn lại'],
            'colors' => ['#3498db', '#bdc3c7'],
            'project' => [
                'id' => $project_id,
                'total_investment' => 0,
                'total_disbursed' => 0,
            ],
        ];

        // Điều kiện lọc theo ngày cho các truy vấn giải ngân
        $dateFilterSql = '';
        $dateFilterParams = [];
        $dateFilterTypes = '';
        

        if (!empty($project_id)) {
            // Tổng mức đầu tư của dự án
            if (!empty($dateFilterParams)) {
                $sqlInv = "SELECT COALESCE(da.DA_TONGMUCDAUTU, 0) AS total_investment 
                          FROM duan da 
                          WHERE da.DA_MA = ? 
                          AND EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.dscv_trangthaihd = 1";
                $sqlInv .= $dateFilterSql;
                $sqlInv .= ") LIMIT 1";

                $paramsInv = array_merge([$project_id], $dateFilterParams);
                $typesInv = 's' . $dateFilterTypes;

                $stmt = $conn->prepare($sqlInv);
                $stmt->bind_param($typesInv, ...$paramsInv);
            } else {
                $stmt = $conn->prepare("SELECT COALESCE(DA_TONGMUCDAUTU, 0) AS total_investment FROM duan WHERE DA_MA = ? LIMIT 1");
                $stmt->bind_param('s', $project_id);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $totalInvestment = (int) ($row['total_investment'] ?? 0);
            $stmt->close();

            // Lấy tổng giá trị đã giải ngân (DSCV_TRANGTHAIGIAINGAN = 1) và chưa giải ngân (DSCV_TRANGTHAIGIAINGAN = 0 hoặc NULL)
            // Chỉ tính các công việc có DSCV_YEUCAUGIAINGAN = 1
            $sqlDisbursed = "SELECT 
                COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 1 THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_disbursed,
                COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 0 OR dcv.DSCV_TRANGTHAIGIAINGAN IS NULL THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_pending
                FROM danhsachcongviec dcv 
                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA = ? AND dcv.DSCV_YEUCAUGIAINGAN = 1";
            $sqlDisbursed .= $dateFilterSql;
            $paramsDisbursed = array_merge([$project_id], $dateFilterParams);
            $typesDisbursed = 's' . $dateFilterTypes;

            $stmt2 = $conn->prepare($sqlDisbursed);
            $stmt2->bind_param($typesDisbursed, ...$paramsDisbursed);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row2 = $result2->fetch_assoc();
            $totalDisbursed = (int) ($row2['total_disbursed'] ?? 0);
            $totalPending = (int) ($row2['total_pending'] ?? 0);
            $stmt2->close();

            $totalUsed = $totalDisbursed + $totalPending;
            $remainingRaw = $totalInvestment - $totalUsed;
            $overBudget = ($totalUsed > $totalInvestment);
            $overAmount = $overBudget ? ($totalUsed - $totalInvestment) : 0;
            $remaining = $remainingRaw;
            if ($remaining < 0) {
                $remaining = 0;
            }

            $disbursement['project']['total_investment'] = $totalInvestment;
            $disbursement['project']['total_disbursed'] = $totalDisbursed;
            $disbursement['project']['total_pending'] = $totalPending;
            $disbursement['project']['remaining_raw'] = $remainingRaw;
            $disbursement['project']['over_budget'] = $overBudget;
            $disbursement['project']['over_amount'] = $overAmount;

            if ($overBudget) {
                $disbursement['labels'] = ['Vượt ngân sách', 'Đã giải ngân', 'Chưa giải ngân'];
                $disbursement['colors'] = ['#ff7f50', '#2ecc71', '#dc3545'];
                $disbursement['data'] = [$overAmount, $totalDisbursed, $totalPending];
                $disbursement['warning'] = 'Giá trị giải ngân đã vượt tổng mức đầu tư.';
            } else {
                $disbursement['labels'] = ['Đã giải ngân', 'Chưa giải ngân', 'Còn lại'];
                $disbursement['colors'] = ['#2ecc71', '#dc3545', '#bdc3c7'];
                $disbursement['data'] = [$totalDisbursed, $totalPending, $remaining];
            }
        } else {
            if (!empty($from_date) && !empty($to_date)) {
                // Lấy tất cả công việc có thời gian thực hiện giao với khoảng thời gian lọc
                // Tức là: (bắt đầu trước ngày kết thúc lọc) VÀ (kết thúc sau ngày bắt đầu lọc)
                $dateFilterSql .= " AND (dcv.DSCV_NGAYBATDAU IS NULL OR DATE(dcv.DSCV_NGAYBATDAU) <= ?) ";
                $dateFilterSql .= " AND (dcv.DSCV_NGAYKETTHUC IS NULL OR DATE(dcv.DSCV_NGAYKETTHUC) >= ?)";
                $dateFilterParams[] = $to_date;  // Ngày kết thúc lọc
                $dateFilterParams[] = $from_date; // Ngày bắt đầu lọc
                $dateFilterTypes .= 'ss';
            } else {
                if (!empty($from_date)) {
                    $dateFilterSql .= " AND (dcv.DSCV_NGAYKETTHUC IS NULL OR DATE(dcv.DSCV_NGAYKETTHUC) >= ?)";
                    $dateFilterParams[] = $from_date;
                    $dateFilterTypes .= 's';
                }
                if (!empty($to_date)) {
                    $dateFilterSql .= " AND (dcv.DSCV_NGAYBATDAU IS NULL OR DATE(dcv.DSCV_NGAYBATDAU) <= ?)";
                    $dateFilterParams[] = $to_date;
                    $dateFilterTypes .= 's';
                }
            }
            // Tổng hợp theo vai trò
            if ($isAdmin) {
                // Admin: toàn bộ hệ thống
                // Tổng mức đầu tư của các dự án có công việc trong khoảng thời gian được chọn
                if (!empty($dateFilterParams)) {
                    $sqlTotalInv = "SELECT COALESCE(SUM(da.DA_TONGMUCDAUTU), 0) AS total_investment 
                                    FROM duan da 
                                    WHERE da.da_trangthai != 0 
                                    AND EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.dscv_trangthaihd = 1";
                    $sqlTotalInv .= $dateFilterSql;
                    $sqlTotalInv .= ")";

                    $stmtInv = $conn->prepare($sqlTotalInv);
                    $stmtInv->bind_param($dateFilterTypes, ...$dateFilterParams);
                    $stmtInv->execute();
                    $resInv = $stmtInv->get_result();
                    $rowInv = $resInv->fetch_assoc();
                    $stmtInv->close();
                } else {
                    $sqlTotalInv = "SELECT COALESCE(SUM(DA_TONGMUCDAUTU), 0) AS total_investment FROM duan WHERE da_trangthai != 0";
                    $resInv = $conn->query($sqlTotalInv);
                    $rowInv = $resInv ? $resInv->fetch_assoc() : null;
                }
                $totalInvestment = (int) ($rowInv['total_investment'] ?? 0);

                $sqlTotalDis = "SELECT 
                    COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 1 THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_disbursed,
                    COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 0 OR dcv.DSCV_TRANGTHAIGIAINGAN IS NULL THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_pending
                    FROM danhsachcongviec dcv 
                    WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND dcv.DSCV_YEUCAUGIAINGAN = 1";
                $sqlTotalDis .= $dateFilterSql;

                if (!empty($dateFilterParams)) {
                    $stmtDis = $conn->prepare($sqlTotalDis);
                    $stmtDis->bind_param($dateFilterTypes, ...$dateFilterParams);
                    $stmtDis->execute();
                    $resDis = $stmtDis->get_result();
                    $rowDis = $resDis->fetch_assoc();
                } else {
                    $stmtDis = $conn->prepare($sqlTotalDis);
                }
                $stmtDis->execute();
                $resDis = $stmtDis->get_result();
                $rowDis = $resDis->fetch_assoc();
                $totalDisbursed = (int) ($rowDis['total_disbursed'] ?? 0);
                $totalPending = (int) ($rowDis['total_pending'] ?? 0);
                $stmtDis->close();
            } else if ($isManager) {
                // Quản lý: tất cả dự án trong phòng ban và của bản thân
                // Lấy PB_MA của quản lý
                $stmtDept = $conn->prepare("SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1");
                $stmtDept->bind_param('s', $userCode);
                $stmtDept->execute();
                $resDept = $stmtDept->get_result();
                $rowDept = $resDept->fetch_assoc();
                $managerDept = $rowDept['PB_MA'] ?? null;
                $stmtDept->close();

                // Tổng mức đầu tư các dự án thuộc phòng ban hoặc thuộc cá nhân quản lý
                // Tổng mức đầu tư của các dự án có công việc trong khoảng thời gian được chọn
                $sqlInv = "SELECT COALESCE(SUM(da.DA_TONGMUCDAUTU), 0) AS total_investment
                           FROM duan da
                           WHERE da.da_trangthai != 0 AND (
                               da.DA_NGUOIPHUTRACH = ?
                               OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                               OR (SELECT PB_MA FROM thanhvien WHERE TV_MA = da.DA_NGUOIPHUTRACH LIMIT 1) = ?
                               OR EXISTS (
                                    SELECT 1 FROM duan_thanhvien dt
                                    JOIN thanhvien tv ON tv.TV_MA = dt.TV_MA
                                    WHERE dt.DA_MA = da.DA_MA AND tv.PB_MA = ?
                               )
                           )";

                if (!empty($dateFilterParams)) {
                    $sqlInv .= " AND EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.dscv_trangthaihd = 1";
                    $sqlInv .= $dateFilterSql;
                    $sqlInv .= ")";

                    $paramsInv = array_merge([$userCode, $userCode, $managerDept, $managerDept], $dateFilterParams);
                    $typesInv = 'ssss' . $dateFilterTypes;

                    $stmtInv = $conn->prepare($sqlInv);
                    $stmtInv->bind_param($typesInv, ...$paramsInv);
                } else {
                    $stmtInv = $conn->prepare($sqlInv);
                    $stmtInv->bind_param('ssss', $userCode, $userCode, $managerDept, $managerDept);
                }

                $stmtInv->execute();
                $rowInv = $stmtInv->get_result()->fetch_assoc();
                $totalInvestment = (int) ($rowInv['total_investment'] ?? 0);
                $stmtInv->close();

                // Tổng giải ngân và chưa giải ngân các công việc thuộc các dự án trên
                $sqlDis = "SELECT 
                    COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 1 THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_disbursed,
                    COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 0 OR dcv.DSCV_TRANGTHAIGIAINGAN IS NULL THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_pending
                    FROM danhsachcongviec dcv
                    JOIN duan da ON da.DA_MA = dcv.DA_MA
                    WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND dcv.DSCV_YEUCAUGIAINGAN = 1 AND (
                        da.DA_NGUOIPHUTRACH = ?
                        OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                        OR (SELECT PB_MA FROM thanhvien WHERE TV_MA = da.DA_NGUOIPHUTRACH LIMIT 1) = ?
                        OR EXISTS (
                            SELECT 1 FROM duan_thanhvien dt
                            JOIN thanhvien tv ON tv.TV_MA = dt.TV_MA
                            WHERE dt.DA_MA = da.DA_MA AND tv.PB_MA = ?
                        )
                    )";
                $sqlDis .= $dateFilterSql;
                $paramsDis = array_merge([$userCode, $userCode, $managerDept, $managerDept], $dateFilterParams);
                $typesDis = 'ssss' . $dateFilterTypes;

                $stmtDis = $conn->prepare($sqlDis);
                $stmtDis->bind_param($typesDis, ...$paramsDis);
                $stmtDis->execute();
                $rowDis = $stmtDis->get_result()->fetch_assoc();
                $totalDisbursed = (int) ($rowDis['total_disbursed'] ?? 0);
                $stmtDis->close();
            } else {
                // Người dùng thường: dự án tham gia hoặc phụ trách
                // Tổng mức đầu tư của các dự án có công việc trong khoảng thời gian được chọn
                $sqlInv = "SELECT COALESCE(SUM(da.DA_TONGMUCDAUTU), 0) AS total_investment
                           FROM duan da
                           WHERE da.da_trangthai != 0 AND (
                               da.DA_NGUOIPHUTRACH = ?
                               OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                           )";

                if (!empty($dateFilterParams)) {
                    $sqlInv .= " AND EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.dscv_trangthaihd = 1";
                    $sqlInv .= $dateFilterSql;
                    $sqlInv .= ")";

                    $paramsInv = array_merge([$userCode, $userCode], $dateFilterParams);
                    $typesInv = 'ss' . $dateFilterTypes;

                    $stmtInv = $conn->prepare($sqlInv);
                    $stmtInv->bind_param($typesInv, ...$paramsInv);
                } else {
                    $stmtInv = $conn->prepare($sqlInv);
                    $stmtInv->bind_param('ss', $userCode, $userCode);
                }

                $stmtInv->execute();
                $rowInv = $stmtInv->get_result()->fetch_assoc();
                $totalInvestment = (int) ($rowInv['total_investment'] ?? 0);
                $stmtInv->close();

                $sqlDis = "SELECT 
                    COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 1 THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_disbursed,
                    COALESCE(SUM(CASE WHEN dcv.DSCV_TRANGTHAIGIAINGAN = 0 OR dcv.DSCV_TRANGTHAIGIAINGAN IS NULL THEN dcv.DSCV_GIATRIGIAINGAN ELSE 0 END), 0) AS total_pending
                    FROM danhsachcongviec dcv
                    JOIN duan da ON da.DA_MA = dcv.DA_MA
                    WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND dcv.DSCV_YEUCAUGIAINGAN = 1 AND (
                        da.DA_NGUOIPHUTRACH = ?
                        OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                    )";
                $sqlDis .= $dateFilterSql;
                $paramsDis = array_merge([$userCode, $userCode], $dateFilterParams);
                $typesDis = 'ss' . $dateFilterTypes;

                $stmtDis = $conn->prepare($sqlDis);
                $stmtDis->bind_param($typesDis, ...$paramsDis);
                $stmtDis->execute();
                $rowDis = $stmtDis->get_result()->fetch_assoc();
                $totalDisbursed = (int) ($rowDis['total_disbursed'] ?? 0);
                $stmtDis->close();
            }

            $remainingRaw = $totalInvestment - $totalDisbursed;
            $overBudget = ($totalDisbursed > $totalInvestment);
            $overAmount = $overBudget ? ($totalDisbursed - $totalInvestment) : 0;
            $remaining = $remainingRaw;
            if ($remaining < 0) {
                $remaining = 0;
            }

            $disbursement['project']['total_investment'] = $totalInvestment;
            $disbursement['project']['total_disbursed'] = $totalDisbursed;
            $disbursement['project']['remaining_raw'] = $remainingRaw;
            $disbursement['project']['over_budget'] = $overBudget;
            $disbursement['project']['over_amount'] = $overAmount;
            if ($overBudget) {
                $disbursement['labels'] = ['Vượt ngân sách', 'Trong hạn mức'];
                $disbursement['colors'] = ['#e74c3c', '#3498db'];
                $disbursement['data'] = [$overAmount, $totalInvestment];
                $disbursement['warning'] = 'Giá trị giải ngân đã vượt tổng mức đầu tư.';
            } else {
                $disbursement['labels'] = ['Đã giải ngân', 'Còn lại'];
                $disbursement['colors'] = ['#3498db', '#bdc3c7'];
                $disbursement['data'] = [$totalDisbursed, $remaining];
            }
        }

        $response['disbursement'] = $disbursement;
    }

    // Ghi log dữ liệu trả về
    error_log('Chart data response: ' . print_r($response, true));

    // Kiểm tra xem có lỗi SQL không
    if (isset($conn) && $conn->error) {
        error_log('SQL Error: ' . $conn->error);
        $response['sql_error'] = $conn->error;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (\Exception $e) {
    // Ghi log lỗi
    error_log('Exception in get_chart_data.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Lỗi: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>