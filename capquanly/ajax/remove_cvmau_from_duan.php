<?php
// Xóa công việc mẫu khỏi mẫu liên kết với dự án
include(__DIR__ . '/../../config.php');
header('Content-Type: application/json');

$duan_ma = isset($_POST['duan_ma']) ? $_POST['duan_ma'] : null;
$task_name = isset($_POST['task_name']) ? trim($_POST['task_name']) : null;
$mamau = isset($_POST['mamau']) ? $_POST['mamau'] : null;

if (!$duan_ma || !$task_name || !$mamau) {
    echo json_encode(['success' => false, 'msg' => 'Thiếu dữ liệu!']);
    exit;
}

// Lấy macvmau hiện tại của mẫu này
$sql = "SELECT macvmau FROM maucv WHERE mamau = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'msg' => 'Lỗi hệ thống (prepare): ' . $conn->error]);
    $conn->close();
    exit;
}
$stmt->bind_param('i', $mamau);
$stmt->execute();
$stmt->bind_result($macvmau_json);
$stmt->fetch();
$stmt->close();

if ($macvmau_json === null) {
    echo json_encode(['success' => false, 'msg' => 'Không tìm thấy mẫu công việc!']);
    $conn->close();
    exit;
}

// Giải mã JSON và xóa task_name
$tasksData = json_decode($macvmau_json, true);
if (!isset($tasksData['tasks']) || !is_array($tasksData['tasks'])) {
    echo json_encode(['success' => false, 'msg' => 'Định dạng dữ liệu công việc không hợp lệ.']);
    $conn->close();
    exit;
}

$updatedTasks = [];
$taskFound = false;
foreach ($tasksData['tasks'] as $task) {
    if (isset($task['name']) && $task['name'] === $task_name) {
        $taskFound = true;
    } else {
        $updatedTasks[] = $task;
    }
}

if (!$taskFound) {
    echo json_encode(['success' => false, 'msg' => 'Không tìm thấy công việc cần xóa trong mẫu.']);
    $conn->close();
    exit;
}

$tasksData['tasks'] = $updatedTasks;
$new_macvmau_json = json_encode($tasksData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Cập nhật lại macvmau
$update = $conn->prepare("UPDATE maucv SET macvmau = ? WHERE mamau = ?");
if (!$update) {
    echo json_encode(['success' => false, 'msg' => 'Lỗi hệ thống (prepare update): ' . $conn->error]);
    $conn->close();
    exit;
}
$update->bind_param('si', $new_macvmau_json, $mamau);
if ($update->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Lỗi khi cập nhật công việc mẫu!']);
}
$update->close();
$conn->close();
