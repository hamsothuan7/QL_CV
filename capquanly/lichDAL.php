<?php
include('../config.php');

$data = array();
$query = "SELECT * FROM duan ORDER BY id";
$queryResult = mysqli_query($conn, $query);
$result = mysqli_fetch_all($queryResult, MYSQLI_ASSOC);

foreach($result as $row)
{
 $data[] = array(
  'id'   => $row["id"],
  'title'   => $row["DA_TEN"],
  'start'   => $row["DA_NGAYBATDAU"],
  'end'   => $row["DA_NGAYKETTHUC"],
 );
}
echo json_encode($data);
//{"9", "code php Thuần", ".."}
?>