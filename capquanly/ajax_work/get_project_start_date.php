<?php
session_start();
include '../../config.php';

header('Content-Type: application/json');

// Kiểm tra quyền truy cập
if (!isset($_SESSION['nnd_ma'])) {
    echo json_encode(['status' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$project_id = $_GET['project_id'] ?? '';

if (empty($project_id)) {
    echo json_encode(['status' => false, 'message' => 'Thiếu thông tin dự án']);
    exit;
}

// Lấy thông tin dự án
$sql = "SELECT DA_NGAYBATDAU FROM duan WHERE DA_MA = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => true,
        'start_date' => $row['DA_NGAYBATDAU']
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Không tìm thấy thông tin dự án'
    ]);
}

mysqli_stmt_close($stmt);
?>
