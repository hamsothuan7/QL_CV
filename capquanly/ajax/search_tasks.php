<?php
require_once __DIR__ . '/../../config.php';

// Kiểm tra kết nối database
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Lấy từ khóa tìm kiếm
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Tạo câu truy vấn tìm kiếm
$sql = "SELECT SQL_CALC_FOUND_ROWS 
            dscv.DSCV_MA as id, 
            CONCAT('[', da.DA_MA, '] ', dscv.DSCV_TEN) as text,
            da.DA_MA as da_ma
        FROM danhsachcongviec dscv
        LEFT JOIN duan da ON dscv.DA_MA = da.DA_MA
        WHERE dscv.DSCV_TEN LIKE ? OR da.DA_MA LIKE ?
        ORDER BY dscv.DSCV_TEN ASC
        LIMIT ? OFFSET ?";

$searchTerm = "%$search%";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'id' => $row['id'],
            'text' => $row['text'],
            'da_ma' => $row['da_ma']
        ];
    }
    
    // Lấy tổng số bản ghi
    $total_result = $conn->query("SELECT FOUND_ROWS() as total");
    $total_row = $total_result->fetch_assoc();
    $total = $total_row['total'];
    
    $stmt->close();
    
    // Trả về kết quả dạng JSON
    header('Content-Type: application/json');
    echo json_encode([
        'items' => $tasks,
        'total_count' => $total,
        'incomplete_results' => false
    ]);
} else {
    // Xử lý lỗi
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database query failed']);
}

$conn->close();
?>
