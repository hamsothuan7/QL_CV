<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../config.php';

// Check connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die(json_encode([
        'success' => false,
        'message' => 'Kết nối cơ sở dữ liệu thất bại',
        'error' => $conn->connect_error
    ]));
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Log the request data for debugging
error_log('POST data: ' . print_r($_POST, true));

// Initialize response array with debug info
$response = [
    'success' => false,
    'message' => '',
    'data' => [],
    'debug' => [
        'post_data' => $_POST,
        'files' => isset($_FILES) ? array_keys($_FILES) : []
    ]
];

try {
    // Log received data for debugging
    error_log('=== START DEBUG ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . (isset($_FILES) ? print_r($_FILES, true) : 'No files'));
    error_log('Database connection: ' . (isset($conn) && $conn ? 'Connected' : 'Not connected'));
    if (isset($conn) && $conn) {
        error_log('Database host info: ' . mysqli_get_host_info($conn));
        error_log('Database server version: ' . mysqli_get_server_info($conn));
    }
    error_log('=== END DEBUG ===');

// Check if required fields are present
if (!isset($_POST['code']) || empty($_POST['code'])) {
    throw new Exception('Mã công việc không hợp lệ (Missing or empty code)');
}

    $code = $_POST['code'];
    
    // Get the gia_tri_giai_ngan value and ensure it's a number
    $gia_tri_giai_ngan = isset($_POST['gia_tri_giai_ngan']) ? floatval($_POST['gia_tri_giai_ngan']) : 0;
    
    // Get other form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1; // Default to 1 (Đang tiến hành)
    $progress = isset($_POST['progress']) ? intval($_POST['progress']) : 0;
    $description = isset($_POST['editor']) ? trim($_POST['editor']) : '';
    
    // Validate required fields
    if (empty($name)) {
    throw new Exception('Tên công việc không được để trống');
}

// Log the values we're about to use
error_log("Updating task with values - Name: $name, Gia tri giai ngan: $gia_tri_giai_ngan, Progress: $progress, Status: $status, Code: $code");
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update the main task
        $sql = "UPDATE danhsachcongviec 
                SET DSCV_TEN = ?,
                    DSCV_GIATRIGIAINGAN = ?,
                    TIEN_DO = ?,
                    DSCV_TRANGTHAI = ?,
                    DSCV_MOTA = ?
                WHERE DSCV_MA = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL: ' . mysqli_error($conn));
        }
        
        // Bind parameters
        mysqli_stmt_bind_param($stmt, 'sdiiss', 
            $name,
            $gia_tri_giai_ngan,
            $progress,
            $status,
            $description,
            $code
        );
        
        if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_error($conn);
    error_log("Database error: " . $error);
    throw new Exception('Có lỗi xảy ra khi cập nhật công việc: ' . $error);
}
        
        // Handle file upload if a file was provided
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $uploadDir = '../../uploads/';
            $fileName = time() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;
            
            // Create uploads directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Move the uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Update the file path in the database
                $filePath = 'uploads/' . $fileName;
                $updateFileSql = "UPDATE danhsachcongviec SET FILE = ? WHERE DSCV_MA = ?";
                $updateFileStmt = mysqli_prepare($conn, $updateFileSql);
                if ($updateFileStmt === false) {
                    throw new Exception('Lỗi chuẩn bị câu lệnh cập nhật file: ' . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($updateFileStmt, 'ss', $filePath, $code);
                
                if (!mysqli_stmt_execute($updateFileStmt)) {
                    throw new Exception('Có lỗi khi cập nhật file đính kèm: ' . mysqli_error($conn));
                }
            } else {
                throw new Exception('Có lỗi khi tải lên file');
            }
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        $response['success'] = true;
        $response['message'] = 'Cập nhật thành công!';
        $response['data'] = [
            'code' => $code
        ];
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        if (isset($conn)) {
            mysqli_rollback($conn);
        }
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400); // Bad request
}

// Return JSON response
echo json_encode($response);
?>
