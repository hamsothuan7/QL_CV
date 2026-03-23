<?php
// Xóa liên kết mẫu công việc khỏi dự án và các công việc liên quan
define('AJAX', true);

// Báo lỗi chi tiết để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include file cấu hình
try {
    include(__DIR__ . '/../../config.php');
    if (!isset($conn) || !$conn) {
        throw new Exception('Không thể kết nối đến cơ sở dữ liệu');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'msg' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]));
}

header('Content-Type: application/json');

// Lấy và kiểm tra dữ liệu đầu vào
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Dữ liệu không hợp lệ!']);
    exit;
}

// Kiểm tra xem kết nối có hỗ trợ transaction không
if (!method_exists($conn, 'begin_transaction')) {
    die(json_encode(['success' => false, 'msg' => 'Phiên bản PHP/MySQLi không hỗ trợ transaction']));
}

// Lấy duan_ma và mamau từ bảng duan_maucv theo id
$sql_info = "SELECT duan_ma, mamau FROM duan_maucv WHERE id = ? LIMIT 1";
$stmt_info = $conn->prepare($sql_info);
if (!$stmt_info) {
    echo json_encode(['success' => false, 'msg' => 'Không lấy được thông tin mẫu']);
    exit;
}
$stmt_info->bind_param('i', $id);
$stmt_info->execute();
$stmt_info->bind_result($duan_ma, $mamau);
if (!$stmt_info->fetch()) {
    echo json_encode(['success' => false, 'msg' => 'Không tìm thấy mẫu công việc']);
    exit;
}
$stmt_info->close();

// Bắt đầu transaction
try {
    $conn->begin_transaction();
    // Cập nhật dscv_trangthaihd = 0 cho các công việc thuộc mẫu này
    $sql_update_tasks = "UPDATE danhsachcongviec SET dscv_trangthaihd = 0 WHERE DA_MA = ? AND parent_id = ?";
    $stmt_update_tasks = $conn->prepare($sql_update_tasks);
    if (!$stmt_update_tasks) {
        throw new Exception('Lỗi khi chuẩn bị cập nhật trạng thái công việc: ' . $conn->error);
    }
    $stmt_update_tasks->bind_param('si', $duan_ma, $mamau);
    if (!$stmt_update_tasks->execute()) {
        throw new Exception('Lỗi khi cập nhật trạng thái công việc: ' . $stmt_update_tasks->error);
    }
    $stmt_update_tasks->close();

    // Xóa liên kết mẫu công việc (update trạng thái theo id)
    $sql = "UPDATE duan_maucv SET trangthai = 0 WHERE id = ?";
    $stmt_unlink = $conn->prepare($sql);
    if (!$stmt_unlink) {
        throw new Exception('Lỗi khi chuẩn bị xóa liên kết: ' . $conn->error);
    }
    $stmt_unlink->bind_param('i', $id);
    if (!$stmt_unlink->execute()) {
        throw new Exception('Lỗi khi xóa liên kết: ' . $stmt_unlink->error);
    }
    $stmt_unlink->close();

    // Commit transaction nếu mọi thứ thành công
    $conn->commit();
    echo json_encode(['success' => true, 'msg' => 'Đã xóa liên kết và cập nhật trạng thái công việc thành công']);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    
    // Ghi log lỗi chi tiết
    $errorLog = "=== LỖI XÓA LIÊN KẾT MẪU CÔNG VIỆC ===\n";
    $errorLog .= date('[Y-m-d H:i:s]') . "\n";
    $errorLog .= "Lỗi: " . $e->getMessage() . "\n";
    $errorLog .= "Mã dự án: " . $duan_ma . " | Mã mẫu: " . $mamau . "\n"; // $mamau is no longer used here
    $errorLog .= "File: " . $e->getFile() . " | Dòng: " . $e->getLine() . "\n";
    $errorLog .= "Chi tiết lỗi: " . $e->getTraceAsString() . "\n\n";
    
    // Thử ghi log vào thư mục hiện tại trước
    $logFile = __DIR__ . '/error_log.txt';
    if (!file_put_contents($logFile, $errorLog, FILE_APPEND)) {
        // Nếu không ghi được, thử ghi vào thư mục tmp
        $logFile = sys_get_temp_dir() . '/qlcv_error_log.txt';
        file_put_contents($logFile, $errorLog, FILE_APPEND);
    }
    
    // Trả về thông báo lỗi chi tiết hơn cho debug
    $errorMsg = 'Có lỗi xảy ra khi xử lý yêu cầu. Vui lòng thử lại sau.';
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $errorMsg = 'Không thể xóa do ràng buộc dữ liệu. Vui lòng kiểm tra lại các công việc liên quan.';
    }
    
    echo json_encode([
        'success' => false, 
        'msg' => $errorMsg,
        'debug' => $e->getMessage() // Chỉ hiển thị trong môi trường dev
    ]);
}

// Đóng kết nối
if (isset($conn) && $conn) {
    $conn->close();
}
