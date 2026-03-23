<?php
include('../../config.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $code = $_GET['code'] ?? '';
    if ($code === '') {
        echo json_encode([
            'status' => false,
            'message' => 'Thiếu mã dự án'
        ]);
        return;
    }

    // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();
    
    try {
        // 1. Cập nhật trạng thái tất cả công việc thuộc dự án
        $updateTasks = "UPDATE danhsachcongviec SET DSCV_trangthaiHD = 0 WHERE DA_MA = ?";
        $stmt = $conn->prepare($updateTasks);
        if (!$stmt) {
            throw new Exception('Không thể chuẩn bị câu lệnh cập nhật công việc');
        }
        $stmt->bind_param('s', $code);
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật trạng thái công việc: ' . $stmt->error);
        }
        $tasksAffected = $stmt->affected_rows;
        $stmt->close();

        // 2. Cập nhật trạng thái dự án
        $updateProject = "UPDATE duan SET DA_TRANGTHAI = 0 WHERE DA_MA = ?";
        $stmt = $conn->prepare($updateProject);
        if (!$stmt) {
            throw new Exception('Không thể chuẩn bị câu lệnh cập nhật dự án');
        }
        $stmt->bind_param('s', $code);
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật trạng thái dự án: ' . $stmt->error);
        }
        $projectAffected = $stmt->affected_rows;
        $stmt->close();

        // Commit transaction nếu mọi thứ thành công
        $conn->commit();
        $ok = true;
    } catch (Exception $e) {
        // Rollback nếu có lỗi xảy ra
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        return;
    }

    echo json_encode([
        'status' => true,
        'data' => $code,
        'affected' => $tasksAffected,
        'message' => 'Đã cập nhật trạng thái dự án và ' . $tasksAffected . ' công việc liên quan thành không hoạt động'
    ]);
    return;

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}

?>