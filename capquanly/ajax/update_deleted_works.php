<?php
// Thêm 1 công việc vào cột deleted_works của duan_maucv
include(__DIR__ . '/../../config.php');
header('Content-Type: application/json');

$duan_ma = isset($_POST['duan_ma']) ? $_POST['duan_ma'] : null;
$mamau = isset($_POST['mamau']) ? $_POST['mamau'] : null;
$task_name = isset($_POST['task_name']) ? trim($_POST['task_name']) : null;

if (!$duan_ma || !$mamau || !$task_name) {
    echo json_encode(['success' => false, 'msg' => 'Thiếu dữ liệu!']);
    exit;
}

// Lấy deleted_works hiện tại
$sql = "SELECT deleted_works FROM duan_maucv WHERE duan_ma = ? AND mamau = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'msg' => 'Lỗi hệ thống (prepare): ' . $conn->error]);
    $conn->close();
    exit;
}
$stmt->bind_param('si', $duan_ma, $mamau);
$stmt->execute();
$stmt->bind_result($deleted_works);
$stmt->fetch();
$stmt->close();

$deletedArr = array_filter(array_map('trim', explode('|', $deleted_works)));
if (!in_array($task_name, $deletedArr)) {
    $deletedArr[] = $task_name;
}
$new_deleted_works = implode('|', $deletedArr);

// Update lại deleted_works
$update = $conn->prepare("UPDATE duan_maucv SET deleted_works = ? WHERE duan_ma = ? AND mamau = ?");
if (!$update) {
    echo json_encode(['success' => false, 'msg' => 'Lỗi hệ thống (prepare update): ' . $conn->error]);
    $conn->close();
    exit;
}
$update->bind_param('ssi', $new_deleted_works, $duan_ma, $mamau);
if ($update->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Lỗi khi cập nhật deleted_works!']);
}
$update->close();
$conn->close();
