<?php
include('../../config.php');

try {
    // Kiểm tra xem có ID dự án được gửi lên không
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Thiếu ID dự án');
    }

    $projectId = $conn->real_escape_string($_GET['id']);
    
    // Chuẩn bị câu truy vấn
    $sql = "SELECT da_ten FROM duan WHERE da_ma = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        throw new Exception('Lỗi truy vấn: ' . $conn->error);
    }
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['success'] = true;
        $response['projectName'] = $row['da_ten'];
        $response['message'] = 'Lấy tên dự án thành công';
    } else {
        throw new Exception('Không tìm thấy dự án với ID: ' . $projectId);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Lỗi get_project_name: ' . $e->getMessage());
} finally {
    // Đóng kết nối
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}

// Trả về kết quả dưới dạng JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
