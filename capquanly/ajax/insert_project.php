<?php
include('../../config.php');
function generateProjectCode($conn) {
    $today = date("Ymd").rand(1,300);
    $query = "SELECT COUNT(*) AS count FROM `duan` WHERE `DA_MA` LIKE '$today%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];
    $project_code = $today . str_pad(($count + 1), 2, "0", STR_PAD_LEFT);
    return $project_code;
}

try {
    $code = generateProjectCode($conn);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $status = intval($_POST['status']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $leader_id = isset($_POST['leader']) ? mysqli_real_escape_string($conn, $_POST['leader']) : '';
    $total_investment = isset($_POST['DA_TONGMUCDAUTU']) ? intval($_POST['DA_TONGMUCDAUTU']) : 0;

    $sql = "INSERT INTO duan(DA_MA, DA_TEN, DA_NGAYBATDAU, DA_NGAYKETTHUC, DA_TRANGTHAI, DA_NGUOIPHUTRACH, DA_TONGMUCDAUTU) 
            VALUES('$code', '$name', '$start_date', NULL, $status, " . (!empty($leader_id) ? "'$leader_id'" : "NULL") . ", $total_investment)";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'Thêm dự án thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Thêm dự án thất bại'
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