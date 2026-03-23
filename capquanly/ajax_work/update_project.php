<?php
include('../../config.php');
session_start();

try {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $status = $_POST['status'];
    $content = $_POST['editor'];
    $progress = $_POST['progress'] ?? 0;

    $nndMa = $_SESSION['nnd_ma'] ?? null; // 2 = Quản lý
    if ($nndMa == 4) {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại. Bạn không có quyền thực hiện thao tác.'
        ]);
        return;
    }

    // Nếu chuyển trạng thái Hoàn thành (2) thì ghi ngày hoàn thành hiện tại (kiểu DATE)
    $dateDoneSql = ($status == 2) ? ", DSCV_NGAYHOANTHANH = CURDATE()" : '';
    $privateProject = $_POST['project_id'] == 0 ? "": ", DA_MA = '". $_POST['project_id']."'" ;

    $gia_tri_giai_ngan = isset($_POST['gia_tri_giai_ngan']) ? intval($_POST['gia_tri_giai_ngan']) : 0;
    $trangthai_giaingan = isset($_POST['trangthai_giaingan']) ? 1 : 0;
    $yeucau_giaingan = isset($_POST['yeucau_giaingan']) ? 1 : 0;
    
    $sql = "UPDATE danhsachcongviec 
            SET DSCV_TEN = '$name', 
                DSCV_MOTA = '$content', 
                DSCV_TRANGTHAI = $status, 
                TIEN_DO = $progress,
                DSCV_GIATRIGIAINGAN = $gia_tri_giai_ngan,
                DSCV_TRANGTHAIGIAINGAN = $trangthai_giaingan,
                DSCV_YEUCAUGIAINGAN = $yeucau_giaingan
                $privateProject $dateDoneSql
            WHERE DSCV_MA = '$code' ";

    $result = mysqli_query($conn, $sql);

    $conn->close();
    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'Cập nhật công việc thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Cập nhật công việc thất bại'
        ]);
        return;
    }

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}

?>