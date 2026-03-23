<?php
try {
    include('../config.php');

    session_start();

    $data = array();
    $tvCode = $_SESSION['code'];
    
    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = quản lý
    $isAdmin = isset($_SESSION['active']) && $_SESSION['active'] == 1 || $nndMa == 4;
    // Lấy tham số lọc từ request
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $status_filter = $_GET['status'] ?? '';

    // Xây dựng câu query với điều kiện lọc theo vai trò
    if ($isAdmin) {
        // Admin: thấy tất cả dự án đang hoạt động và có ngày bắt đầu
        $query = "SELECT * FROM duan WHERE DA_NGAYBATDAU IS NOT NULL AND DA_TRANGTHAI <> 0";
    } elseif ($nndMa == 2) {
        // Quản lý: bản thân + cùng phòng ban (dựa trên PB_MA của người dùng)
        // Các tiêu chí liên quan tới dự án:
        // - Có công việc thuộc dự án được giao cho người dùng
        // - Người dùng là người phụ trách dự án
        // - Có công việc thuộc dự án được giao cho thành viên cùng phòng ban với người dùng
        // - Dự án do người phụ trách thuộc cùng phòng ban với người dùng
        $escUser = mysqli_real_escape_string($conn, $tvCode);
        $query = "SELECT * FROM duan da\n"
               . "WHERE da.DA_NGAYBATDAU IS NOT NULL AND da.DA_TRANGTHAI <> 0\n"
               . "  AND (\n"
               . "       EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = da.DA_MA AND dcv.TV_MA = '{$escUser}')\n"
               . "    OR da.DA_NGUOIPHUTRACH = '{$escUser}'\n"
               . "    OR EXISTS (\n"
               . "           SELECT 1\n"
               . "           FROM danhsachcongviec dcv2\n"
               . "           JOIN thanhvien tv2 ON tv2.TV_MA = dcv2.TV_MA\n"
               . "           WHERE dcv2.DA_MA = da.DA_MA\n"
               . "             AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n"
               . "       )\n"
               . "    OR EXISTS (\n"
               . "           SELECT 1 FROM thanhvien tv3\n"
               . "           WHERE tv3.TV_MA = da.DA_NGUOIPHUTRACH\n"
               . "             AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = '{$escUser}' LIMIT 1)\n"
               . "       )\n"
               . "  )";
    } else {
        // Nhân viên: chỉ các dự án có công việc của mình hoặc mình là người phụ trách
        // Lấy danh sách dự án liên quan tới người dùng (cách cũ)
        $ids = [];
        // 1. Công việc được giao cho người dùng (thuộc dự án)
        $q1 = "SELECT DA_MA FROM danhsachcongviec WHERE TV_MA = '" . mysqli_real_escape_string($conn, $tvCode) . "' AND DA_MA IS NOT NULL";
        $rs1 = mysqli_query($conn, $q1);
        $dataDA = $rs1 ? mysqli_fetch_all($rs1, MYSQLI_ASSOC) : [];
        foreach($dataDA as $row) { $ids[] = $row['DA_MA']; }
        // 2. Người dùng là người phụ trách dự án
        $q2 = "SELECT DA_MA FROM duan WHERE DA_NGUOIPHUTRACH = '" . mysqli_real_escape_string($conn, $tvCode) . "'";
        $rs2 = mysqli_query($conn, $q2);
        $leaderRows = $rs2 ? mysqli_fetch_all($rs2, MYSQLI_ASSOC) : [];
        foreach($leaderRows as $row){ $ids[] = $row['DA_MA']; }
        // Xây dựng query cuối cùng
        $ids = array_unique(array_filter($ids));
        $ids_string = (!empty($ids)) ? implode(',', array_map('intval', $ids)) : 0;
        $query = "SELECT * FROM duan WHERE DA_NGAYBATDAU IS NOT NULL AND DA_TRANGTHAI <> 0 AND DA_MA IN ($ids_string)";
    }
    
    // Thêm điều kiện lọc theo thời gian theo NGÀY BẮT ĐẦU của dự án
    if (!empty($from_date) && !empty($to_date)) {
        $from = mysqli_real_escape_string($conn, $from_date);
        $to = mysqli_real_escape_string($conn, $to_date);
        $query .= " AND DATE(DA_NGAYBATDAU) BETWEEN '$from' AND '$to'";
    } else if (!empty($from_date)) {
        $query .= " AND DATE(DA_NGAYBATDAU) >= '" . mysqli_real_escape_string($conn, $from_date) . "'";
    } else if (!empty($to_date)) {
        $query .= " AND DATE(DA_NGAYBATDAU) <= '" . mysqli_real_escape_string($conn, $to_date) . "'";
    }
    
    // Thêm điều kiện lọc theo trạng thái
    if (!empty($status_filter)) {
        $query .= " AND DA_TRANGTHAI = " . intval($status_filter);
    }
    
    $query .= " ORDER BY DA_MA";
    
    $queryResult = mysqli_query($conn, $query);
    $result = mysqli_fetch_all($queryResult, MYSQLI_ASSOC);

    foreach ($result as $row) {
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

        $description = 'Kế hoạch: ' . $row['DA_TEN'] . '<br>' .
        'Trạng thái: ' . $nameStatus . '<br>' .
        'Bắt đầu: ' . date('d/m/y', strtotime($row['DA_NGAYBATDAU'])) . '<br>' .
        'Kết thúc: ' . date('d/m/y', strtotime($row['DA_NGAYKETTHUC'])) . '<br>' .
        'Nội dung: ' . $row['DA_MOTA'] ?? "";


        $data[] = array(
            'name' => $row['DA_TEN'],
            'date_start' => $row['DA_NGAYBATDAU'],
            'date_end' => $row['DA_NGAYKETTHUC'],
            'values' => array([
                'from' => $row['DA_NGAYBATDAU'],
                'to' => $row['DA_NGAYKETTHUC'],
                'label' => $row['DA_TEN'],
                'desc' => $description,
                'customClass' => $color,
                'dataObj' => $row['DA_MA']
            ])
        );
    }

    echo json_encode($data);

} catch (\Exception $e) {
    echo 'Kết nối thất bại: ' . $e->getMessage();
}

?>
