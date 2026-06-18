<?php
include('../../config.php');
session_start();

try {
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $status = intval($_POST['status'] ?? 0);
    $content = $_POST['editor'] ?? '';
    $progress = intval($_POST['progress'] ?? 0);
    $project_id_raw = $_POST['project_id'] ?? '';

    if (empty($code)) {
        echo json_encode(['status' => false, 'message' => 'Mã công việc không hợp lệ']);
        return;
    }

    $nndMa = $_SESSION['nnd_ma'] ?? null;
    if ($nndMa == 4) {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại. Bạn không có quyền thực hiện thao tác.'
        ]);
        return;
    }

    $gia_tri_giai_ngan = isset($_POST['gia_tri_giai_ngan']) ? intval($_POST['gia_tri_giai_ngan']) : 0;
    $trangthai_giaingan = isset($_POST['trangthai_giaingan']) ? 1 : 0;
    $yeucau_giaingan = isset($_POST['yeucau_giaingan']) ? 1 : 0;

    // Xác định DA_MA: 0 hoặc rỗng → NULL (gỡ khỏi dự án)
    $da_ma = (empty($project_id_raw) || $project_id_raw == '0' || $project_id_raw == 'NULL') ? null : $project_id_raw;

    // Nếu chuyển trạng thái Hoàn thành (2) hoặc Hoàn thành trễ (6) thì ghi ngày hoàn thành
    $dateDoneSql = ($status == 2 || $status == 6) ? ", DSCV_NGAYHOANTHANH = CURDATE()" : '';

    // Sử dụng Prepared Statement
    $sql = "UPDATE danhsachcongviec 
            SET DSCV_TEN = ?, 
                DSCV_MOTA = ?, 
                DSCV_TRANGTHAI = ?,
                TIEN_DO = ?,
                DA_MA = ?,
                DSCV_GIATRIGIAINGAN = ?,
                DSCV_TRANGTHAIGIAINGAN = ?,
                DSCV_YEUCAUGIAINGAN = ?
                $dateDoneSql
            WHERE DSCV_MA = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu lệnh: ' . $conn->error);
    }

    $stmt->bind_param(
        'ssiisisss',
        $name,
        $content,
        $status,
        $progress,
        $da_ma,
        $gia_tri_giai_ngan,
        $trangthai_giaingan,
        $yeucau_giaingan,
        $code
    );

    $result = $stmt->execute();
    $stmt->close();
    $conn->close();

    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'Cập nhật công việc thành công'
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại'
        ]);
    }

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
?>