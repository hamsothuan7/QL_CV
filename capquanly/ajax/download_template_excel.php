<?php
require_once __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
// Đặt font mặc định Times New Roman cho toàn workbook
$spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template');

// Row 1: template name placeholder in B1
$sheet->setCellValue('B1', 'TÊN MẪU:');
$sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
$sheet->mergeCells('C1:D1');
$sheet->setCellValue('A1', ''); // trống

// Header row 2
$sheet->setCellValue('A2', 'STT');
$sheet->setCellValue('B2', 'Tên công việc');
$sheet->setCellValue('C2', 'Thời gian thực hiện (ngày)');
$sheet->setCellValue('D2', 'Công việc tiên quyết');

// Đặt độ rộng cột để hiển thị đầy đủ
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(40);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(30);

// Khóa header để người dùng không chỉnh sửa
use PhpOffice\PhpSpreadsheet\Style\Protection;
// Khóa chỉ hàng tiêu đề (A2:D2)
$sheet->getStyle('A2:D2')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
// Mở khóa ô tên mẫu B1 để người dùng nhập
$sheet->getStyle('B1')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
// Canh giữa tiêu đề
$sheet->getStyle('A2:D2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

$sheet->getProtection()->setSheet(true);
// Mở khóa vùng nhập liệu (A3:D2000)
$sheet->getStyle('A3:D2000')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);



// Prefill công thức STT cho 2000 dòng để tự tăng mà không cần kéo
for($r=3;$r<=2000;$r++){
    $formula = '=IF(B'.$r.'<>"",COUNTA($B$3:B'.$r.'),"")';
    $formula = '=IF(B'.$r.'<>"",COUNTA($B$3:B'.$r.'),"" )';
    $sheet->setCellValue("A{$r}", $formula);
}

// Nếu thư viện hỗ trợ Table, có thể tạo bảng (không bắt buộc)
if (class_exists('PhpOffice\\PhpSpreadsheet\\Worksheet\\Table')) {
    $table = new \PhpOffice\PhpSpreadsheet\Worksheet\Table('A2:D3', 'Tasks');
    $sheet->addTable($table);
} else {
    // Thư viện cũ, người dùng cần kéo công thức xuống để STT tăng
}


header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_tasks.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
