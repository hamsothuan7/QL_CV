<?php
include('../config.php');

$selected_month = 5;
$selected_year = 2026;
$selected_pb = '';

$where_clause = "WHERE MONTH(cv.DSCV_NGAYBATDAU) = ? AND YEAR(cv.DSCV_NGAYBATDAU) = ?";
$params = [$selected_month, $selected_year];
$types = "ii";

$sql_count = "SELECT COUNT(DISTINCT d.DA_MA) as total FROM duan d JOIN danhsachcongviec cv ON d.DA_MA = cv.DA_MA $where_clause";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param($types, $params[0], $params[1]);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$stmt_count->close();

$limit = 6;
$offset = 0;
$sql_main = "SELECT d.DA_MA, d.DA_TEN, AVG(cv.TIEN_DO) as tien_do_tb, COUNT(cv.DSCV_MA) as so_cong_viec
             FROM duan d JOIN danhsachcongviec cv ON d.DA_MA = cv.DA_MA
             $where_clause GROUP BY d.DA_MA LIMIT ? OFFSET ?";
$stmt_main = $conn->prepare($sql_main);
$types_main = $types . "ii";
$stmt_main->bind_param($types_main, $params[0], $params[1], $limit, $offset);
$stmt_main->execute();
$result_projects = $stmt_main->get_result();
// Intentionally NOT closing stmt_main

$sql_all_filtered = "SELECT DISTINCT d.DA_MA FROM duan d JOIN danhsachcongviec cv ON d.DA_MA = cv.DA_MA $where_clause";
$stmt_all = $conn->prepare($sql_all_filtered);
if (!$stmt_all) {
    echo "Prepare all failed: " . $conn->error . "\n";
} else {
    echo "Prepare all success\n";
    $stmt_all->bind_param($types, $params[0], $params[1]);
    $stmt_all->execute();
    $res_all_filtered = $stmt_all->get_result();
    echo "All filtered rows: " . mysqli_num_rows($res_all_filtered) . "\n";
}
?>
