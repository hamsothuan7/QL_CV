<?php
include('../../config.php');

function generateProjectCode($conn)
{
    $today = date("Ymd");
    $query = "SELECT COUNT(*) AS count FROM `danhsachcongviec` WHERE `DSCV_MA` LIKE '$today%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];
    do {
        $count++;
        $project_code = $today . str_pad($count, 2, "0", STR_PAD_LEFT);
        $check_query = "SELECT DSCV_MA FROM `danhsachcongviec` WHERE `DSCV_MA` = '$project_code'";
        $check_result = mysqli_query($conn, $check_query);
    } while (mysqli_num_rows($check_result) > 0);

    return $project_code;
}

try {
    $cv = generateProjectCode($conn);
    $code = $_POST['code'];
    $name = $_POST['name'];
    $parent = ($_POST['parent'] != '') ? $_POST['parent'] : NULL;
    if($parent == NULL)
        $sql = "INSERT INTO danhsachcongviec(DSCV_MA, DSCV_TEN, DA_MA) VALUES('$cv', '$name', '$code') ";
    else
        $sql = "INSERT INTO danhsachcongviec(DSCV_MA, DSCV_TEN, DA_MA, PARENT_ID) VALUES('$cv', '$name', '$code', '$parent') ";
    $result = mysqli_query($conn, $sql);
    $conn->close();
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