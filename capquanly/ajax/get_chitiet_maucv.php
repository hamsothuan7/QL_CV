<?php
header('Content-Type: application/json');

// Kết nối database
include(__DIR__ . '/../../config.php');

// Kiểm tra quyền truy cập nếu cần
// if (!check_permission()) {
//     echo json_encode(['status' => 'error', 'message' => 'Không có quyền truy cập']);
//     exit;
// }


// Lấy mã mẫu từ request
$mamau = isset($_GET['mamau']) ? (int)$_GET['mamau'] : 0;

if ($mamau <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mã mẫu không hợp lệ']);
    exit;
}

try {
    // Lấy thông tin chi tiết các công việc mẫu
    $sql = "SELECT ma_cv, ten_cv, thoi_gian_du_kien, ma_cv_tien_quyet 
            FROM cv_mau 
            WHERE ma_mau = ? AND trang_thai = 1 
            ORDER BY id ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $mamau);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $tasks = [];
        $index = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $index++;
            $prereq='';
            if(!empty($row['ma_cv_tien_quyet'])){
                if(preg_match('/CV(\d+)/',$row['ma_cv_tien_quyet'],$m)){
                    $prereq = intval(ltrim($m[1],'0'));
                }
            }
            $tasks[] = [
                'stt' => $index,
                'ten_cv' => htmlspecialchars($row['ten_cv']),
                'thoi_gian_du_kien' => (int)$row['thoi_gian_du_kien'],
                'prereq' => $prereq
            ];
        }
        
        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'status' => 'success',
            'data' => $tasks
        ]);
    } else {
        throw new Exception('Lỗi truy vấn cơ sở dữ liệu');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}

// Đóng kết nối
mysqli_close($conn);
?>
