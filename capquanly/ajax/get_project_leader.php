<?php
include('../../config.php');
header('Content-Type: application/json');

if (!isset($_GET['duan_ma'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã dự án']);
    exit;
}

$duan_ma = $_GET['duan_ma'];
$sql = "SELECT DA_NGUOIPHUTRACH FROM duan WHERE DA_MA = '" . mysqli_real_escape_string($conn, $duan_ma) . "' LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $leader = $row['DA_NGUOIPHUTRACH'];
    echo json_encode([
        'status' => 'success',
        'has_leader' => !empty($leader),
        'leader_id' => $leader
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Không tìm thấy dự án hoặc lỗi truy vấn'
    ]);
}
