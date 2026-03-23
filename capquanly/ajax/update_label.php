<?php
include('../../config.php');

try {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $color = $_POST['color'];
    $sql = "INSERT INTO nhan(NAME, COLOR, DA_MA) VALUES('$name', '$color', '$code') ";
    $result = mysqli_query($conn, $sql);

    echo json_encode([
        'status' => true,
        'data' => $code,
        'message' => 'Thêm nhãn thành công'
    ]);
    return;

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}

?>