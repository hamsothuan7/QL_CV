<?php
try {
    include('../../config.php');
    session_start();

    // Lấy tham số lọc từ request
    $from_date  = $_GET['from_date']  ?? '';
    $to_date    = $_GET['to_date']    ?? '';
    $project_id = $_GET['project_id'] ?? '';

    $userCode = $_SESSION['code'] ?? '';
    $isAdmin  = (isset($_SESSION['active']) && $_SESSION['active'] == 1);
    $nndMa    = $_SESSION['nnd_ma'] ?? null;
    $isManager = ($nndMa == 2);

    $response = [];

    // Điều kiện lọc theo ngày
    $dateFilterSql = '';
    $dateFilterParams = [];
    $dateFilterTypes = '';
    
    if (!empty($from_date) && !empty($to_date)) {
        $dateFilterSql .= " AND (dcv.DSCV_NGAYBATDAU IS NULL OR DATE(dcv.DSCV_NGAYBATDAU) <= ?) ";
        $dateFilterSql .= " AND (dcv.DSCV_NGAYKETTHUC IS NULL OR DATE(dcv.DSCV_NGAYKETTHUC) >= ?)";
        $dateFilterParams[] = $to_date;
        $dateFilterParams[] = $from_date;
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

    // ==== Biểu đồ khối lượng thực hiện ====
    $workload = [
        'data' => [],
        'labels' => [],
        'colors' => ['#2ecc71', '#dc3545', '#bdc3c7'],
        'project' => [
            'id' => $project_id,
            'total_investment' => 0,
            'total_workload' => 0,
            'remaining' => 0,
        ],
    ];

    if (!empty($project_id)) {
        // Lấy tổng mức đầu tư của dự án
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
        $totalInvestment = (int)($row['total_investment'] ?? 0);
        $stmt->close();
        
        // Lấy tổng DSCV_GIATRIGIAINGAN của tất cả công việc trong dự án
        $sqlWorkload = "SELECT 
            COALESCE(SUM(dcv.DSCV_GIATRIGIAINGAN), 0) AS total_workload
            FROM danhsachcongviec dcv 
            WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA = ?";
        $sqlWorkload .= $dateFilterSql;
        
        $paramsWorkload = array_merge([$project_id], $dateFilterParams);
        $typesWorkload = 's' . $dateFilterTypes;
        
        $stmt = $conn->prepare($sqlWorkload);
        $stmt->bind_param($typesWorkload, ...$paramsWorkload);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $totalWorkload = (int)($row['total_workload'] ?? 0);
        $stmt->close();
        
        // Tính phần còn lại
        $remaining = $totalInvestment - $totalWorkload;
        if ($remaining < 0) { $remaining = 0; }
        
        $workload['project']['total_investment'] = $totalInvestment;
        $workload['project']['total_workload'] = $totalWorkload;
        $workload['project']['remaining'] = $remaining;
        
        $workload['data'] = [$totalWorkload, $remaining];
        $workload['labels'] = [
            'Khối lượng thực hiện',
            'Còn lại'
        ];
        $workload['colors'] = ['#3498db', '#bdc3c7'];
        
    } else {
        // Tổng hợp theo vai trò (Admin/Manager/User)
        if ($isAdmin) {
            // Lấy tổng mức đầu tư
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
            $totalInvestment = (int)($rowInv['total_investment'] ?? 0);
            
            // Lấy tổng khối lượng thực hiện
            $sqlWorkload = "SELECT 
                COALESCE(SUM(dcv.DSCV_GIATRIGIAINGAN), 0) AS total_workload
                FROM danhsachcongviec dcv 
                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL";
            $sqlWorkload .= $dateFilterSql;
            
            if (!empty($dateFilterParams)) {
                $stmt = $conn->prepare($sqlWorkload);
                $stmt->bind_param($dateFilterTypes, ...$dateFilterParams);
            } else {
                $stmt = $conn->prepare($sqlWorkload);
            }
            
        } else if ($isManager) {
            // Lấy PB_MA của quản lý
            $stmtDept = $conn->prepare("SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1");
            $stmtDept->bind_param('s', $userCode);
            $stmtDept->execute();
            $resDept = $stmtDept->get_result();
            $rowDept = $resDept->fetch_assoc();
            $managerDept = $rowDept['PB_MA'] ?? null;
            $stmtDept->close();
            
            // Lấy tổng mức đầu tư
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
            $totalInvestment = (int)($rowInv['total_investment'] ?? 0);
            $stmtInv->close();
            
            // Lấy tổng khối lượng thực hiện
            $sqlWorkload = "SELECT 
                COALESCE(SUM(dcv.DSCV_GIATRIGIAINGAN), 0) AS total_workload
                FROM danhsachcongviec dcv
                JOIN duan da ON da.DA_MA = dcv.DA_MA
                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND (
                    da.DA_NGUOIPHUTRACH = ?
                    OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                    OR (SELECT PB_MA FROM thanhvien WHERE TV_MA = da.DA_NGUOIPHUTRACH LIMIT 1) = ?
                    OR EXISTS (
                        SELECT 1 FROM duan_thanhvien dt
                        JOIN thanhvien tv ON tv.TV_MA = dt.TV_MA
                        WHERE dt.DA_MA = da.DA_MA AND tv.PB_MA = ?
                    )
                )";
            $sqlWorkload .= $dateFilterSql;
            $paramsWorkload = array_merge([$userCode, $userCode, $managerDept, $managerDept], $dateFilterParams);
            $typesWorkload = 'ssss' . $dateFilterTypes;
            
            $stmt = $conn->prepare($sqlWorkload);
            $stmt->bind_param($typesWorkload, ...$paramsWorkload);
            
        } else {
            // User thường
            // Lấy tổng mức đầu tư
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
            $totalInvestment = (int)($rowInv['total_investment'] ?? 0);
            $stmtInv->close();
            
            // Lấy tổng khối lượng thực hiện
            $sqlWorkload = "SELECT 
                COALESCE(SUM(dcv.DSCV_GIATRIGIAINGAN), 0) AS total_workload
                FROM danhsachcongviec dcv
                JOIN duan da ON da.DA_MA = dcv.DA_MA
                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND (
                    da.DA_NGUOIPHUTRACH = ?
                    OR EXISTS (SELECT 1 FROM duan_thanhvien dt WHERE dt.DA_MA = da.DA_MA AND dt.TV_MA = ?)
                )";
            $sqlWorkload .= $dateFilterSql;
            $paramsWorkload = array_merge([$userCode, $userCode], $dateFilterParams);
            $typesWorkload = 'ss' . $dateFilterTypes;
            
            $stmt = $conn->prepare($sqlWorkload);
            $stmt->bind_param($typesWorkload, ...$paramsWorkload);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $totalWorkload = (int)($row['total_workload'] ?? 0);
        $stmt->close();
        
        // Tính phần còn lại
        $remaining = $totalInvestment - $totalWorkload;
        if ($remaining < 0) { $remaining = 0; }
        
        $workload['project']['total_investment'] = $totalInvestment;
        $workload['project']['total_workload'] = $totalWorkload;
        $workload['project']['remaining'] = $remaining;
        
        $workload['data'] = [$totalWorkload, $remaining];
        $workload['labels'] = [
            'Khối lượng thực hiện',
            'Còn lại'
        ];
        $workload['colors'] = ['#3498db', '#bdc3c7'];
    }

    $response['workload'] = $workload;

    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (\Exception $e) {
    error_log('Exception in get_workload_chart.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Lỗi: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
