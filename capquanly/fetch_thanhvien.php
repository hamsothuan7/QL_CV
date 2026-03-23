<?php
header('Content-Type: application/json');
include('../config.php');

// Kiểm tra kết nối CSDL
if (!$conn) {
    die(json_encode(['error' => 'Không thể kết nối đến CSDL: ' . mysqli_connect_error()]));
}

// Lấy tham số từ GET hoặc POST
$phongban_id = isset($_REQUEST['phongban_id']) ? $_REQUEST['phongban_id'] : null;

if (!$phongban_id) {
    echo json_encode(['error' => 'Thiếu tham số phongban_id']);
    exit;
}

// Sử dụng prepared statement để tránh SQL Injection
$query = "SELECT TV_MA, TV_TEN FROM thanhvien WHERE PB_MA = ? AND active = 0 ORDER BY TV_TEN ASC";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $phongban_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $members = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $members[] = [
                'TV_MA' => $row['TV_MA'],
                'TV_TEN' => $row['TV_TEN']
            ];
        }
    }
    
    echo json_encode($members);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['error' => 'Lỗi truy vấn: ' . mysqli_error($conn)]);
}

// Đóng kết nối
mysqli_close($conn);
?>
