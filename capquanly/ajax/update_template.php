<?php
// Đảm bảo không có output nào trước khi gửi header
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
include(__DIR__ . '/../../config.php');

// Expect: mamau (int), tenmau (string), tasks (json array [{name,duration,prereq}])
$mamau = isset($_POST['mamau']) ? intval($_POST['mamau']) : 0;
$tenmau = isset($_POST['tenmau']) ? trim($_POST['tenmau']) : '';
$tasksJson = $_POST['tasks'] ?? '[]';
$tasksArr = json_decode($tasksJson, true);

if($mamau<=0){
    echo json_encode(['status'=>'error','message'=>'Mã mẫu không hợp lệ']);
    exit;
}
if($tenmau===''){
    echo json_encode(['status'=>'error','message'=>'Tên mẫu không được để trống']);
    exit;
}
if(!is_array($tasksArr) || count($tasksArr)==0){
    echo json_encode(['status'=>'error','message'=>'Phải có ít nhất một công việc']);
    exit;
}

mysqli_begin_transaction($conn);
try{
    // Update tenmau
    $stmt = mysqli_prepare($conn, 'UPDATE maucv SET tenmau=?, updated_at=NOW() WHERE mamau=?');
    if(!$stmt) throw new Exception(mysqli_error($conn));
    mysqli_stmt_bind_param($stmt, 'si', $tenmau, $mamau);
    if(!mysqli_stmt_execute($stmt)) throw new Exception(mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    // Xoá cv_mau cũ
    mysqli_query($conn, 'DELETE FROM cv_mau WHERE ma_mau='.intval($mamau));

    // Thêm mới tasks
    $stt=1;
    foreach($tasksArr as $t){
        $name = mb_substr(trim($t['name'] ?? ''),0,255,'UTF-8');
        $duration = intval($t['duration'] ?? 1);
        $prereqStt = trim($t['prereq'] ?? '');
        if($name==='') continue;
        $ma_cv = 'CV'.str_pad($stt,3,'0',STR_PAD_LEFT);
        $ma_cv_tien_quyet='';
        if($prereqStt!=='' && ctype_digit($prereqStt)){
            $ma_cv_tien_quyet = 'CV'.str_pad($prereqStt,3,'0',STR_PAD_LEFT);
        }
        $stmt2 = mysqli_prepare($conn,'INSERT INTO cv_mau (ma_mau, ma_cv, ten_cv, thoi_gian_du_kien, ma_cv_tien_quyet, trang_thai) VALUES (?,?,?,?,?,1)');
        if(!$stmt2) throw new Exception(mysqli_error($conn));
        mysqli_stmt_bind_param($stmt2,'issss',$mamau,$ma_cv,$name,$duration,$ma_cv_tien_quyet);
        if(!mysqli_stmt_execute($stmt2)) throw new Exception(mysqli_stmt_error($stmt2));
        mysqli_stmt_close($stmt2);
        $stt++;
    }

    mysqli_commit($conn);
    $response = ['status' => 'success'];
} catch(Exception $e) {
    mysqli_rollback($conn);
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

if (isset($conn)) {
    mysqli_close($conn);
}

// Đảm bảo chỉ có JSON được trả về
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
