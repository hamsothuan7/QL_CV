<?php
// Bật báo lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Khởi tạo response mặc định
$response = ['success' => false, 'message' => ''];

try {
    // Đường dẫn tới file config.php trong thư mục gốc của dự án
    $rootPath = dirname(dirname(dirname(__FILE__)));
    $configPath = $rootPath . '/config.php';
    
    if (!file_exists($configPath)) {
        throw new Exception('Không tìm thấy file cấu hình (config.php) tại: ' . $configPath);
    }
    include($configPath);
    
    // Kiểm tra session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra đăng nhập
    if (!isset($_SESSION['username'])) {
        throw new Exception('Chưa đăng nhập');
    }

    // Kiểm tra có mã công việc được truyền vào không
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Không tìm thấy mã công việc');
    }

    $maCV = trim($_GET['id']);
    $userCode = $_SESSION['code'];
    $isManager = isset($_SESSION['nnd_ma']) && $_SESSION['nnd_ma'] == 2;

    // Lấy mã dự án từ công việc
    $sqlGetProject = "SELECT DA_MA FROM danhsachcongviec WHERE DSCV_MA = ? LIMIT 1";
    $stmtGetProject = mysqli_prepare($conn, $sqlGetProject);
    mysqli_stmt_bind_param($stmtGetProject, 's', $maCV);
    mysqli_stmt_execute($stmtGetProject);
    $resultProject = mysqli_stmt_get_result($stmtGetProject);
    $rowProject = mysqli_fetch_assoc($resultProject);
    $maDuAn = $rowProject['DA_MA'] ?? null;
    mysqli_stmt_close($stmtGetProject);

    // Lấy người phụ trách dự án từ bảng duan
    $nguoiPhuTrach = null;
    if ($maDuAn) {
        $sqlGetLeader = "SELECT DA_NGUOIPHUTRACH FROM duan WHERE DA_MA = ? LIMIT 1";
        $stmtGetLeader = mysqli_prepare($conn, $sqlGetLeader);
        mysqli_stmt_bind_param($stmtGetLeader, 's', $maDuAn);
        mysqli_stmt_execute($stmtGetLeader);
        $resultLeader = mysqli_stmt_get_result($stmtGetLeader);
        $rowLeader = mysqli_fetch_assoc($resultLeader);
        $nguoiPhuTrach = $rowLeader['DA_NGUOIPHUTRACH'] ?? null;
        mysqli_stmt_close($stmtGetLeader);
    }

    //1 đang tiến hành, 2 hoàn thành, 3 dời, 4 hủy, 5 bắt đầu
    // Chuẩn bị câu truy vấn với prepared statement
    $sql = "SELECT 
                cv.*, 
                da.DA_TEN, 
                tv.TV_TEN,
                cv_tq.DSCV_MA AS MA_CONGVIEC_TQ,
                cv_tq.DSCV_TEN AS TEN_CONGVIEC_TQ,
                CASE 
                    WHEN cv.DSCV_TRANGTHAI = 1 THEN 'Đang tiến hành' 
                    WHEN cv.DSCV_TRANGTHAI = 2 THEN 'Đã hoàn thành' 
                    WHEN cv.DSCV_TRANGTHAI = 3 THEN 'Đang trễ' 
                    WHEN cv.DSCV_TRANGTHAI = 4 THEN 'Đã hủy' 
                    WHEN cv.DSCV_TRANGTHAI = 5 THEN 'Chưa tiếp nhận' 
                    WHEN cv.DSCV_TRANGTHAI = 6 THEN 'Hoàn thành trể' 
                    ELSE 'Không xác định' 
                END AS TEN_TRANGTHAI,
                da.DA_MA 
            FROM danhsachcongviec cv
            LEFT JOIN duan da ON cv.DA_MA = da.DA_MA
            LEFT JOIN thanhvien tv ON cv.TV_MA = tv.TV_MA
            LEFT JOIN danhsachcongviec cv_tq ON cv.DSCV_TIENQUYET = cv_tq.DSCV_MA
            WHERE cv.DSCV_MA = ?";

    // Thêm điều kiện kiểm tra quyền truy cập:
    // Chỉ hạn chế về công việc của chính mình nếu KHÔNG phải admin, KHÔNG phải người phụ trách dự án, và KHÔNG phải quản lý
    $restrictToSelf = ($_SESSION['active'] != 1 && $userCode !== $nguoiPhuTrach && !$isManager);
    if ($restrictToSelf) {
        $sql .= " AND cv.TV_MA = ?";
    }

    // Sử dụng prepared statement để tránh SQL injection
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn));
    }
    
    // Bind các tham số
    if ($restrictToSelf) {
        mysqli_stmt_bind_param($stmt, 'ss', $maCV, $userCode);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $maCV);
    }
    
    // Thực thi truy vấn
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi thực thi truy vấn: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception('Lỗi lấy kết quả: ' . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception('Không tìm thấy thông tin công việc hoặc bạn không có quyền truy cập');
    }
    
    $row = mysqli_fetch_assoc($result);
    
    // Trả về dữ liệu dạng JSON
    $response = [
        'success' => true,
        'data' => $row,
        'canEdit' => ($_SESSION['active'] == 1 || $row['TV_MA'] == $userCode || $userCode === $nguoiPhuTrach)
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    // Bạn có thể log thêm thông tin ở đây nếu muốn
    ];
    http_response_code(500);
} finally {
    // Đóng kết nối nếu cần
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    
    // Trả về kết quả dưới dạng JSON
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
