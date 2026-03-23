<?php
include('../../config.php');
header('Content-Type: application/json');

$sql = "SELECT PB_MA, PB_TEN FROM phongban ORDER BY PB_TEN";
$result = mysqli_query($conn, $sql);
if(!$result){
    echo json_encode(['status'=>'error','message'=>'Query error']);
    exit;
}
$data = [];
while($row = mysqli_fetch_assoc($result)){
    $data[] = ['PB_MA'=>$row['PB_MA'],'PB_TEN'=>$row['PB_TEN']];
}

echo json_encode(['status'=>'success','data'=>$data]);
?>
