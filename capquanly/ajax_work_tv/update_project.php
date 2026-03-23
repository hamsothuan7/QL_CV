<?php
include('../../config.php');

try {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $status = $_POST['status'];
    $content = $_POST['editor'];
    $progress = $_POST['progress'] ?? 0;

    // Directory where you want to save the uploaded file
    $targetDirectory = "../uploads/";

    // Ensure the directory exists
    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);
    }

    if(!empty($_FILES['file'])){
        // Path of the uploaded file
        $targetDirectoryURL = 'uploads/';
        $targetURL = $targetDirectoryURL . basename($_FILES["file"]["name"]);

        $targetFile = $targetDirectory . basename($_FILES["file"]["name"]);
        move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile);
        $sql = "UPDATE danhsachcongviec SET FILE = '$targetURL' WHERE DSCV_MA = '$code' ";
        $result = mysqli_query($conn, $sql);
    }


    $trangthai_giaingan = isset($_POST['trangthai_giaingan']) ? 1 : 0;
    $giatri_giaingan = $_POST['gia_tri_giai_ngan'] ?? 0;
    $yeucau_giaingan = isset($_POST['yeucau_giaingan']) ? 1 : 0;
    
    $sql = "UPDATE danhsachcongviec SET 
            DSCV_TEN = '$name', 
            DSCV_MOTA = '$content', 
            DSCV_TRANGTHAI = $status, 
            TIEN_DO = $progress,
            DSCV_TRANGTHAIGIAINGAN = $trangthai_giaingan,
            DSCV_GIATRIGIAINGAN = $giatri_giaingan,
            DSCV_YEUCAUGIAINGAN = $yeucau_giaingan 
            WHERE DSCV_MA = '$code'";

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