<?php
include('../../config.php');

// Bắt đầu session
session_start();
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
    //Get mã thành viên
    $username = $_SESSION['username'];
    $sql = "SELECT TV_MA FROM thanhvien WHERE TV_TEN = '$username' ";
    // Thực thi câu truy vấn và gán vào $result
    $result = mysqli_query($conn, $sql);
    $member = mysqli_fetch_assoc($result);
    $memberCode = $member['TV_MA'] ?? "";

    $code = generateProjectCode($conn);
    $name = $_POST['name'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $content = $_POST['editor2'] ?? "";

    if($end_date < $start_date){
        echo json_encode([
            'status' => false,
            'message' => 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu'
        ]);
        return;
    }

    $sql = "INSERT INTO danhsachcongviec(DSCV_MA, DSCV_TEN, DSCV_NGAYBATDAU, DSCV_NGAYKETTHUC, DSCV_TRANGTHAI, TV_MA, DSCV_MOTA) VALUES('$code', '$name', '$start_date', '$end_date', $status, '$memberCode', '$content') ";

    $result = mysqli_query($conn, $sql);

    $conn->close();
    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'Thêm công việc thành công'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Thêm công việc thất bại'
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