<?php
include('../../config.php');

try {
    $id = $_GET['id'];
    $code = $_GET['code'];
    $sql = "DELETE FROM binhluan WHERE ID = $id ";
    $result = mysqli_query($conn, $sql);

    echo json_encode([
        'status' => true,
        'data' => $code,
        'message' => 'Xóa bình luận thành công'
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