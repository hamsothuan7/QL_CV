<?php
session_start();
include('../../config.php');

// Hàm tạo mã công việc tự động
function generateWorkCode($conn) {
    $datePrefix = date('Ymd');
    $microtime = str_replace('.', '', microtime(true));
    $query = "SELECT MAX(CAST(SUBSTRING(DSCV_MA, 15) AS UNSIGNED)) as max_num FROM danhsachcongviec WHERE DSCV_MA LIKE 'CV" . $datePrefix . $microtime . "%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $taskCounter = ($row['max_num'] !== null) ? $row['max_num'] + 1 : 1;
    $new_code = 'CV' . $datePrefix . substr($microtime, -6) . str_pad($taskCounter, 3, '0', STR_PAD_LEFT);
    return $new_code;
}

try {
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['nnd_ma'])) {
        echo json_encode(['status' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }

    // Lấy dữ liệu từ form
    $name = trim($_POST['name'] ?? '');
    $projectId = ($_POST['project_id'] === 'NULL' || empty($_POST['project_id'])) ? null : trim($_POST['project_id']);
    $roomId = trim($_POST['room_id'] ?? '');
    $memberID = trim($_POST['member_id'] ?? '');
    $ph_id = ($_POST['ph_id'] ?? '') === 'NULL' ? null : trim($_POST['ph_id'] ?? '');
    $prerequisite_task = trim($_POST['prerequisite_task'] ?? '');
    $duration = intval($_POST['duration'] ?? 1);
    $content = trim($_POST['editor'] ?? '');
    $gia_tri_giai_ngan = isset($_POST['DSCV_GIATRIGIAINGAN']) ? intval($_POST['DSCV_GIATRIGIAINGAN']) : 0;
    $status = 1; // Mặc định là chưa bắt đầu

    // Validate dữ liệu bắt buộc
    if (empty($name)) {
        echo json_encode(['status' => false, 'message' => 'Vui lòng điền tên công việc']);
        exit;
    }

    // Sửa lại phần kiểm tra dự án
    $start_date = $_POST['start_date'] ?? date('Y-m-d'); // Lấy ngày bắt đầu từ form
    $end_date = $_POST['end_date'] ?? ''; // Lấy ngày kết thúc từ form

    if ($projectId !== null) {
        // Kiểm tra dự án chỉ khi có project_id
        $sql = "SELECT * FROM duan WHERE DA_MA = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $projectId);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$project) {
            echo json_encode(['status' => false, 'message' => 'Không tìm thấy thông tin dự án']);
            exit;
        }
    }

    // Nếu không có công việc tiên quyết hoặc không lấy được ngày từ công việc tiên quyết
    if (empty($start_date)) {
        $start_date = isset($project['DA_NGAYBATDAU']) ? $project['DA_NGAYBATDAU'] : date('Y-m-d');
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $duration . ' days'));
    }


    // Tạo mã công việc mới
    $code = generateWorkCode($conn);

    // Bắt đầu transaction
    $conn->begin_transaction();

    try {
        // Thêm công việc mới
        $sql = "INSERT INTO danhsachcongviec(
            DSCV_MA, DSCV_TEN, DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, 
            DSCV_TRANGTHAI, DA_MA, DSCV_MOTA, TV_MA, PB_MA, PH_MA, DSCV_GIATRIGIAINGAN
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Chuẩn bị câu lệnh SQL
        $stmt = $conn->prepare($sql);
        
        // Sử dụng NULL trong câu lệnh SQL và bind_param
        if ($ph_id === null) {
            $sql = "INSERT INTO danhsachcongviec(
                DSCV_MA, DSCV_TEN, DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, 
                DSCV_TRANGTHAI, DA_MA, DSCV_MOTA, TV_MA, PB_MA, PH_MA, DSCV_GIATRIGIAINGAN
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssissssi', 
                $code, $name, $start_date, $end_date, 
                $status, $projectId, $content, $memberID, $roomId, 
                $gia_tri_giai_ngan
            );
        } else {
            $stmt->bind_param('ssssisssssi', 
                $code, $name, $start_date, $end_date, 
                $status, $projectId, $content, $memberID, $roomId, $ph_id,
                $gia_tri_giai_ngan
            );
        }
        
        $result = $stmt->execute();
        $stmt->close();

        if (!$result) {
            throw new Exception('Lỗi khi thêm công việc mới');
        }

        // Nếu có công việc tiên quyết, cập nhật trường DSCV_TIENQUYET của công việc mới
        if (!empty($prerequisite_task)) {
            $sql = "UPDATE danhsachcongviec SET DSCV_TIENQUYET = ? WHERE DSCV_MA = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $prerequisite_task, $code);
            $result = $stmt->execute();
            $stmt->close();

            if (!$result) {
                throw new Exception('Lỗi khi cập nhật công việc tiên quyết');
            }
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => true,
            'message' => 'Thêm công việc thành công',
            'code' => $code
        ]);

    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>