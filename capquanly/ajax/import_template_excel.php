<?php
// Bật buffer sớm để chặn mọi output phụ
ob_start();
// Đánh dấu script để config không xuất HTML
define('IN_SCRIPT', 1);

// Xử lý upload Excel, đọc và thêm mẫu công việc
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Cần cài đặt phpoffice/phpspreadsheet thông qua composer

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Tắt hiển thị lỗi ra ngoài để tránh phá vỡ JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);



header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ']);
    exit;
}

try {
    if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Vui lòng chọn file Excel hợp lệ.');
    }

    $fileTmp  = $_FILES['excel']['tmp_name'];
    $fileName = $_FILES['excel']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'])) {
        throw new Exception('Chỉ hỗ trợ định dạng .xlsx, .xls');
    }

        // Đọc file
    $spreadsheet = IOFactory::load($fileTmp);
    $sheet       = $spreadsheet->getActiveSheet();

    // Lấy tên mẫu từ ô B1 (giá trị sau dấu ":")
    $rawTemplate = (string) $sheet->getCell('B1')->getValue();
    $tenMau = trim(str_replace('TÊN MẪU:', '', $rawTemplate)); // loại bỏ nhãn và giữ nguyên chữ hoa/thường
    $tenMau = trim($tenMau);
    if ($tenMau === '') {
        throw new Exception('Không tìm thấy tên mẫu trong ô B1.');
    }

    // Lấy trạng thái checkbox nếu gửi kèm
    $trangthai = isset($_POST['trangthai']) && $_POST['trangthai'] == '1' ? 1 : 0;

    $rows = $sheet->toArray(null, true, true, true);
    if (count($rows) < 3) {
        throw new Exception('File không có danh sách công việc.');
    }

    // Duyệt từ hàng 3 trở đi
    $tasks = [];
    foreach ($rows as $index => $row) {
        if ($index < 3) continue; // bỏ 2 hàng đầu
        $name = trim($row['B'] ?? '');
        if ($name === '') continue;
        $time = (int) ($row['C'] ?? 0);
        if ($time <= 0) $time = 1;
        $prereq = trim($row['D'] ?? '');
        $tasks[] = [
            'ten_cv' => $name,
            'thoi_gian_du_kien' => $time,
            'cong_viec_tien_quyet' => $prereq
        ];
    }

    if (empty($tasks)) {
        throw new Exception('Không tìm thấy công việc hợp lệ trong file.');
    }

    if (empty($tasks)) {
        throw new Exception('Không tìm thấy công việc hợp lệ trong file.');
    }

    //$tenMau   = $_POST['tenmau'] ?? '';
    $trangthai = isset($_POST['trangthai']) && $_POST['trangthai'] == '1' ? 1 : 0;
    //if ($tenMau === '') {
      //  throw new Exception('Thiếu tên mẫu công việc.');
    //}

    // Bắt đầu transaction
    mysqli_begin_transaction($conn);

    // Insert vào maucv
    // Sinh macvmau duy nhất để đáp ứng ràng buộc UNIQUE
    $macvmau = 'MAU_' . date('YmdHis') . rand(100,999);

    $stmtMau = mysqli_prepare($conn, "INSERT INTO maucv (tenmau, macvmau, trangthai) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmtMau, 'ssi', $tenMau, $macvmau, $trangthai);
    if (!mysqli_stmt_execute($stmtMau)) throw new Exception('Lỗi thêm mẫu: ' . mysqli_error($conn));
    $mamau = mysqli_insert_id($conn);
    mysqli_stmt_close($stmtMau);

    // Insert tasks
    // Xác định xem bảng có cột ma_cv_tien_quyet hay không
$hasPrereqCol = false;
$q = mysqli_query($conn, "SHOW COLUMNS FROM cv_mau LIKE 'ma_cv_tien_quyet'");
if($q && mysqli_num_rows($q) > 0) $hasPrereqCol = true;

// Chuẩn bị câu lệnh phù hợp (đều kèm ma_cv để tránh trùng UNIQUE)
if($hasPrereqCol){
    $stmtTask = mysqli_prepare($conn, "INSERT INTO cv_mau (ma_mau, ma_cv, ten_cv, thoi_gian_du_kien, ma_cv_tien_quyet, trang_thai) VALUES (?, ?, ?, ?, ?, 1)");
}else{
    $stmtTask = mysqli_prepare($conn, "INSERT INTO cv_mau (ma_mau, ma_cv, ten_cv, thoi_gian_du_kien, trang_thai) VALUES (?, ?, ?, ?, 1)");
}

if(!$stmtTask){
    throw new Exception('Lỗi chuẩn bị câu lệnh task: '.mysqli_error($conn));
}

// Duyệt và insert từng task, sinh ma_cv duy nhất theo thứ tự CV001, CV002...
$counter = 1;
// Tạo mảng ánh xạ STT sang mã công việc
$sttToMaCV = [];
foreach($tasks as $idx => $t){
    $ma_cv = 'CV'.str_pad($counter++, 3, '0', STR_PAD_LEFT);
    $sttToMaCV[$idx+1] = $ma_cv; // STT thực tế bắt đầu từ 1 (dòng đầu tiên trong tasks)
    $tasks[$idx]['ma_cv'] = $ma_cv;
}

// Insert từng task với ánh xạ ma_cv_tien_quyet
foreach($tasks as $idx => $t){
    $prereq = trim($t['cong_viec_tien_quyet']);
    $ma_cv_tien_quyet = '';
    if ($prereq !== '') {
        if (preg_match('/^CV\\d{3}$/i', $prereq)) {
            $ma_cv_tien_quyet = strtoupper($prereq); // Nếu nhập đúng mã CVxxx thì giữ nguyên
        } elseif (is_numeric($prereq) && isset($sttToMaCV[(int)$prereq])) {
            $ma_cv_tien_quyet = $sttToMaCV[(int)$prereq]; // Nếu nhập số, ánh xạ sang mã
        } else {
            $ma_cv_tien_quyet = $prereq; // Trường hợp khác, lưu nguyên văn
        }
    }
    if($hasPrereqCol){
        mysqli_stmt_bind_param($stmtTask, 'issis', $mamau, $t['ma_cv'], $t['ten_cv'], $t['thoi_gian_du_kien'], $ma_cv_tien_quyet);
    }else{
        mysqli_stmt_bind_param($stmtTask, 'issi', $mamau, $t['ma_cv'], $t['ten_cv'], $t['thoi_gian_du_kien']);
    }
    if(!mysqli_stmt_execute($stmtTask)){
        throw new Exception('Lỗi thêm công việc: '.mysqli_error($conn));
    }
}
    mysqli_stmt_close($stmtTask);

    mysqli_commit($conn);

    // Xoá mọi thứ có thể đã được đệm (cảnh báo, BOM...)
ob_end_clean();

echo json_encode(['status' => 'success', 'message' => 'Đã import mẫu công việc thành công'], JSON_UNESCAPED_UNICODE);
exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    ob_end_clean();
echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
exit;
}
