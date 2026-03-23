<?php
header('Content-Type: application/json');

// Kết nối CSDL
include(__DIR__ . '/../../config.php');

// Kiểm tra kết nối
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Không thể kết nối đến CSDL'
    ]);
    exit;
}

// Lấy mã phòng ban từ tham số
$pb_ma = isset($_GET['pb_ma']) ? mysqli_real_escape_string($conn, $_GET['pb_ma']) : '';

// Xây dựng câu truy vấn
$sql = "SELECT TV_MA, TV_TEN FROM thanhvien WHERE active = 0 ";

// Nếu có chọn phòng ban cụ thể
if (!empty($pb_ma)) {
    $sql .= " AND PB_MA = '$pb_ma' ";
}

$sql .= " ORDER BY TV_TEN";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi truy vấn CSDL: ' . mysqli_error($conn)
    ]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'TV_MA' => $row['TV_MA'],
        'TV_TEN' => $row['TV_TEN']
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => $data
]);

// Đóng kết nối
mysqli_close($conn);
?>
