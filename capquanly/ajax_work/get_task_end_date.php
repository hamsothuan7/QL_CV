<?php
session_start();
include '../../config.php';

header('Content-Type: application/json');

// Kiểm tra quyền truy cập
if (!isset($_SESSION['nnd_ma'])) {
    echo json_encode(['status' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$task_id = $_GET['task_id'] ?? '';

if (empty($task_id)) {
    echo json_encode(['status' => false, 'message' => 'Thiếu thông tin công việc']);
    exit;
}

// Lấy thông tin công việc
$sql = "SELECT DSCV_NGAYKETTHUC FROM danhsachcongviec WHERE DSCV_MA = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $task_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => true,
        'end_date' => $row['DSCV_NGAYKETTHUC']
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Không tìm thấy thông tin công việc'
    ]);
}

mysqli_stmt_close($stmt);
?>
