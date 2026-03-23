<?php
include('../../config.php');
header('Content-Type: application/json');

$duan_ma = isset($_POST['duan_ma']) ? mysqli_real_escape_string($conn, $_POST['duan_ma']) : '';
$leader_id = isset($_POST['leader_id']) ? mysqli_real_escape_string($conn, $_POST['leader_id']) : '';

if (empty($duan_ma) || empty($leader_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu']);
    exit;
}

$sql = "UPDATE duan SET DA_NGUOIPHUTRACH = '$leader_id' WHERE DA_MA = '$duan_ma'";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Cập nhật người phụ trách thành công']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . mysqli_error($conn)]);
}
?>
