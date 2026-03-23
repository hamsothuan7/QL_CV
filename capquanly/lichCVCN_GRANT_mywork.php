<?php
include('../config.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    // Add error logging
    error_log('Session data: ' . print_r($_SESSION, true));
    
    if (!isset($_SESSION['code'])) {
        error_log('No session code found');
        http_response_code(401);
        echo json_encode(['error' => 'No session found']);
        exit;
    }
    
    $userCode = $_SESSION['code'];
    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = quản lý (manager)
    $isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || $nndMa == 4;

    error_log('User code: ' . $userCode);
    $data = array();
    // Lọc theo khoảng ngày bắt đầu nếu có from_date/to_date
    $fromDate = $_GET['from_date'] ?? '';
    $toDate = $_GET['to_date'] ?? '';
    $phongbanId = $_GET['phongban'] ?? '';
    $where = "dcv.DA_MA IS NULL AND dcv.DSCV_TRANGTHAI <> 0 AND dcv.DSCV_trangthaiHD = 1";
    $types = 's';
    $params = [];
    if ($isAdmin) {
        if (!empty($phongbanId)) {
            $where .= " AND dcv.PB_MA = ?";
            $params[] = strval($phongbanId);
        } else {
            $where .= " AND dcv.PB_MA like ?";
            $params[] = '%';
        }
    } elseif ($nndMa == 2) {
        $managerDept = null;
    
        $stmtDept = $conn->prepare("SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1");
        if ($stmtDept) {
            $stmtDept->bind_param('s', $userCode);
            $stmtDept->execute();
            $resDept = $stmtDept->get_result();
            $rowDept = $resDept ? $resDept->fetch_assoc() : null;
            $managerDept = $rowDept['PB_MA'] ?? null;
            $stmtDept->close();
        }
        
        $where .= " AND dcv.PB_MA = ?";
        $params[] = strval($managerDept);
    } else {
        $where .= " AND dcv.TV_MA = ?";
        $params[] = $userCode;
    }

    
    if (!empty($fromDate) && !empty($toDate)) {
        $where .= " AND DATE(dcv.DSCV_NGAYBATDAU) BETWEEN ? AND ?";
        $types .= 'ss';
        $params[] = $fromDate;
        $params[] = $toDate;
    } elseif (!empty($fromDate)) {
        $where .= " AND DATE(dcv.DSCV_NGAYBATDAU) >= ?";
        $types .= 's';
        $params[] = $fromDate;
    } elseif (!empty($toDate)) {
        $where .= " AND DATE(dcv.DSCV_NGAYBATDAU) <= ?";
        $types .= 's';
        $params[] = $toDate;
    }

    $query = "SELECT dcv.* FROM danhsachcongviec dcv WHERE $where ORDER BY dcv.DSCV_NGAYBATDAU ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $color = '';
        $nameStatus = '';
        switch ($row['DSCV_TRANGTHAI']) {
            case 1:
                $color = 'ganttBlue';
                $nameStatus = 'Đang tiến hành';
                break;
            case 2:
                $color = 'ganttGreen';
                $nameStatus = 'Hoàn thành';
                break;
            case 3:
                $color = 'ganttRed';
                $nameStatus = 'Trễ';
                break;
            case 4:
                $color = 'ganttStone';
                $nameStatus = 'Hủy';
                break;
            case 5:
                $color = 'ganttDark';
                $nameStatus = 'Chưa tiếp nhận';
                break;
            case 6:
                $color = 'ganttOrange';
                $nameStatus = 'Hoàn thành trể';
                break;
            default:
                $nameStatus = 'Không xác định';
                $color = 'ganttDark';
                break;
        }
        $description = 'Công việc: ' . $query . '<br>' .
            'Trạng thái: ' . $nameStatus . '<br>' .
            'Bắt đầu: ' . date('d/m/y', strtotime($row['DSCV_NGAYBATDAU'])) . '<br>' .
            'Kết thúc: ' . date('d/m/y', strtotime($row['DSCV_NGAYKETTHUC'])) . '<br>' .
            'Tiến độ: ' . $row['TIEN_DO'] . '%<br>' .
            'Nội dung: ' . strip_tags($row['DSCV_MOTA'] ?? "");
        $data[] = array(
            'name' => $row['DSCV_TEN'],
            'date_start' => $row['DSCV_NGAYBATDAU'],
            'date_end' => $row['DSCV_NGAYKETTHUC'],
            'values' => array([
                'from' => $row['DSCV_NGAYBATDAU'],
                'to' => $row['DSCV_NGAYKETTHUC'],
                'label' => $row['DSCV_TEN'],
                'desc' => $description,
                'customClass' => $color,
                'dataObj' => $row['DSCV_MA']
            ])
        );
    }
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode([]);
} 