<?php
include(__DIR__ . '/../../config.php');
$sql="SELECT mamau, tenmau, created_at FROM maucv WHERE trangthai=1 ORDER BY created_at DESC";
$result=mysqli_query($conn,$sql);
if($result && mysqli_num_rows($result)>0){
    $i=1;
    while($row=mysqli_fetch_assoc($result)){
        echo '<tr>';
        echo '<td class="text-center">'.$i++.'</td>';
        echo '<td>'.htmlspecialchars($row['tenmau']).'</td>';
        echo '<td class="text-center">'.htmlspecialchars(date('d/m/Y',strtotime($row['created_at']))).'</td>';
        echo '<td class="text-center">'
            .'<button class="btn btn-sm btn-info btn-edit-mau" data-mamau="'.$row['mamau'].'"><i class="fas fa-edit"></i> Edit</button>'
            .'<button class="btn btn-sm btn-danger btn-delete-mau ms-1" data-mamau="'.$row['mamau'].'"><i class="fas fa-trash"></i> Xóa</button>'
            .'</td>';
        echo '</tr>';
    }
}else{
    echo '<tr><td colspan="4" class="text-center">Không có mẫu nào</td></tr>';
}
mysqli_close($conn);
?>
