<?php
try {
    include('../config.php');
    session_start();

    $data = array();
    $tvCode = $_SESSION['code'];
    $projectID = $_GET['id'];
    
    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = quản lý
    $isAdmin = isset($_SESSION['active']) && $_SESSION['active'] == 1 || $nndMa == 4;
    // Lấy mã người phụ trách dự án
    $sqlLeader = "SELECT DA_NGUOIPHUTRACH FROM duan WHERE DA_MA = '" . mysqli_real_escape_string($conn, $projectID) . "' LIMIT 1";
    $resultLeader = mysqli_query($conn, $sqlLeader);
    $rowLeader = mysqli_fetch_assoc($resultLeader);
    $projectLeader = $rowLeader['DA_NGUOIPHUTRACH'] ?? '';

    // Log để debug
error_log('tvCode: ' . print_r($tvCode, true));
error_log('projectLeader: ' . print_r($projectLeader, true));

    if ($isAdmin || trim((string)$tvCode) === trim((string)$projectLeader)) {
        // Admin hoặc người phụ trách dự án: xem toàn bộ công việc của dự án
        $query =
            "SELECT * FROM danhsachcongviec " .
            "WHERE DSCV_NGAYBATDAU IS NOT NULL " .
            "  AND DA_MA = '" . mysqli_real_escape_string($conn, $projectID) . "' " .
            "  AND dscv_trangthaihd = 1 " .
            "ORDER BY DSCV_MA";
    } elseif ($nndMa == 2) {
        // Quản lý: cho phép xem toàn bộ công việc của dự án khi vào Gantt chi tiết
        $query =
            "SELECT * FROM danhsachcongviec " .
            "WHERE DSCV_NGAYBATDAU IS NOT NULL " .
            "  AND DA_MA = '" . mysqli_real_escape_string($conn, $projectID) . "' " .
            "  AND dscv_trangthaihd = 1 " .
            "ORDER BY DSCV_MA";
    } else {
        // Nhân viên: xem công việc của mình trong dự án
        $query =
            "SELECT * FROM danhsachcongviec " .
            "WHERE DSCV_NGAYBATDAU IS NOT NULL " .
            "  AND TV_MA = '" . mysqli_real_escape_string($conn, $tvCode) . "' " .
            "  AND DA_MA = '" . mysqli_real_escape_string($conn, $projectID) . "' " .
            "  AND dscv_trangthaihd = 1 " .
            "ORDER BY DSCV_MA";
    }

    $queryResult = mysqli_query($conn, $query);
    if (!$queryResult) {
        // Trả về mảng rỗng để frontend không bị vỡ giao diện
        echo json_encode([]);
        exit;
    }
    $result = mysqli_fetch_all($queryResult, MYSQLI_ASSOC);

    if (!$result || count($result) == 0) {
        echo json_encode([]);
        exit;
    }

    $i = 1;
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

        // Tạo mô tả công việc với định dạng rõ ràng
        // Chỉ loại bỏ thẻ HTML khỏi trường mô tả (DSCV_MOTA)
        $description = 'Công việc: ' . $row['DSCV_TEN'] . '<br>' .
            'Trạng thái: ' . $nameStatus . '<br>' .
            'Bắt đầu: ' . date('d/m/y', strtotime($row['DSCV_NGAYBATDAU'])) . '<br>' .
            'Kết thúc: ' . date('d/m/y', strtotime($row['DSCV_NGAYKETTHUC'])) . '<br>' .
            'Tiến độ: ' . $row['TIEN_DO'] . '%<br>' .
            'Nội dung: ' . strip_tags($row['DSCV_MOTA'] ?? "");


        // Tạo mảng giá trị chứa cả hai thanh
        $values = array();

        // Thêm thanh công việc chính
        $values[] = [
            'from' => $row['DSCV_NGAYBATDAU'],
            'to' => $row['DSCV_NGAYKETTHUC'],
            'desc' => $description,
            'customClass' => $color,
            'height' => '80%',
            'top' => '10%',
            'width' => '48%',
            'left' => '1%'
        ];

        // Thêm thanh tiến độ nếu tiến độ > 0
        if (isset($row['TIEN_DO']) && $row['TIEN_DO'] > 0) {
            if ($row['DSCV_TRANGTHAI'] == 1 || $row['DSCV_TRANGTHAI'] == 2) {
                $progressColor = 'ganttGreen'; // màu sắc của thanh tiến độ
            } else {
                $progressColor = 'ganttOrange'; // màu sắc của thanh tiến độ
            }
            
            
            // Tính chính xác ngày kết thúc dựa trên tiến độ
            $startTimestamp = strtotime($row['DSCV_NGAYBATDAU']);
            $endTimestamp = strtotime($row['DSCV_NGAYKETTHUC']);
            $totalSeconds = $endTimestamp - $startTimestamp;
            $progressSeconds = $totalSeconds * ($row['TIEN_DO'] / 100);
            $progressEndDate = date('Y-m-d', $startTimestamp + $progressSeconds);

            $values[] = [
                'from' => $row['DSCV_NGAYBATDAU'],
                'to' => $progressEndDate,
                'label' => $row['TIEN_DO'] . '%',
                'desc' => $description,
                'customClass' => $progressColor,
                'height' => '80%',
                'top' => '10%',
                'width' => '48%',
                'left' => '51%'
            ];
        }


        // Thêm dataObj vào mỗi giá trị trong values
        foreach ($values as &$value) {
            $value['dataObj'] = $row['DSCV_MA'];
        }
        
        // Thêm vào mảng dữ liệu chính
        $data[] = array(
            'name' => $i. '. ' .$row['DSCV_TEN'],
            'values' => $values,
            'dataObj' => $row['DSCV_MA'] // Thêm DSCV_MA vào dữ liệu trả về
        );
        $i++;
    }


    echo json_encode($data);
} catch (PDOException $e) {
    echo 'Kết nối thất bại: ' . $e->getMessage();
}
