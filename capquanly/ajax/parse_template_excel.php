<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    if(!isset($_FILES['excel']) || $_FILES['excel']['error']!==UPLOAD_ERR_OK){
        throw new Exception('Vui lòng chọn file Excel hợp lệ');
    }
    $fileTmp = $_FILES['excel']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['excel']['name'], PATHINFO_EXTENSION));
    if(!in_array($ext,['xlsx','xls'])) throw new Exception('Định dạng file không hợp lệ');

    $spreadsheet = IOFactory::load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();

    // lấy tên mẫu ở ô B1 (sau chuỗi "TÊN MẪU:") nếu có
    $raw = (string)$sheet->getCell('B1')->getValue();
    $tenMau = trim(str_replace('TÊN MẪU:', '', $raw));

    $rowsArr = $sheet->toArray(null,true,true,true);

    $tasks=[];
    foreach($rowsArr as $idx=>$row){
        if($idx<3) continue; // bỏ header
        $name = trim($row['B']??'');
        if($name==='') continue;
        $duration = (int)($row['C']??0);
        if($duration<=0) $duration = 1;
        $prereq = trim($row['D']??'');
        $tasks[]=[
            'ten_cv'=>$name,
            'thoi_gian_du_kien'=>$duration,
            'prereq'=>$prereq
        ];
    }
    if(empty($tasks)) throw new Exception('Không tìm thấy công việc hợp lệ trong file');

    echo json_encode(['status'=>'success','data'=>['ten_mau'=>$tenMau,'tasks'=>$tasks]]);
} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}