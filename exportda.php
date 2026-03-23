<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

include('./config.php');

session_start();

// Function to apply background color based on date range
function applyBackgroundColorByDateRange($sheet, $row, $startDate, $endDate, $color) {
    $startMonth = date('n', strtotime($startDate));
    $endMonth = date('n', strtotime($endDate));

    for ($month = 1; $month <= 12; $month++) {
        if ($month >= $startMonth && $month <= $endMonth) {
            $col = chr(65 + $month + 4); // Columns 1 to 12 start at F (index 5)
            $sheet->getStyle($col . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => ltrim($color, '#'),
                    ],
                ],
            ]);
        }
    }
}

function exportToExcel($data, $headers, $title, $filename = 'projects_export.xlsx') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add title
    $sheet->setCellValue('A1', $title);
    $sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // Add headers
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '2', $header);
        $sheet->getStyle($col . '2')->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }

    // Add data
    $row = 3;
    foreach ($data as $rowData) {
        $col = 'A';
        foreach ($rowData as $index => $cellData) {
            $sheet->setCellValue($col . $row, $cellData);
            $sheet->setCellValue('F' . $row, null); // clear old value if needed
            $sheet->getStyle($col . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $col++;
        }
        applyBackgroundColorByDateRange($sheet, $row, $rowData[3], $rowData[4], $rowData[5]);
        $row++;
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);

    // Prompt download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Cache-Control: max-age=0');
    header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filename));
    flush();
    readfile($filename);
    unlink($filename);
}



$tvCode = mysqli_real_escape_string($conn, $_SESSION['code']);
$ids = [];

if ($_SESSION['active'] == 0) {
    $query = "SELECT DA_MA FROM danhsachcongviec WHERE TV_MA = '$tvCode'";
    
    $queryResult = mysqli_query($conn, $query);
    $result = mysqli_fetch_all($queryResult, MYSQLI_ASSOC);

    foreach ($result as $row) {
        $ids[] = $row['DA_MA'];
    }
}

if ($_SESSION['active'] == 0) {
    $ids_string = (!empty($ids)) ? implode(',', array_map('intval', $ids)) : "0";
    $query = "SELECT * FROM duan WHERE DA_NGAYBATDAU IS NOT NULL AND DA_MA IN ($ids_string) ORDER BY DA_MA";
} else 
    $query = "SELECT * FROM duan WHERE DA_NGAYBATDAU IS NOT NULL ORDER BY DA_MA";


$result = mysqli_query($conn, $query);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

$data = [];
if (!empty($jobs)) {
    $stt = 1;
    foreach ($jobs as $row) {
        $color = '';
        $nameStatus = '';
        switch ($row['DA_TRANGTHAI']) {
            case 1: $color = '#5190d2'; $nameStatus = 'Đang tiến hành'; break;
            case 2: $color = '#32ff7e'; $nameStatus = 'Hoàn thành'; break;
            case 3: $color = '#ff9f1a'; $nameStatus = 'Trễ'; break;
            case 4: $color = '#ff3838'; $nameStatus = 'Hủy'; break;
            case 5: $color = '#18dcff'; $nameStatus = 'Chưa tiếp nhận'; break;
            default: $nameStatus = 'Không xác định'; $color = '#4b4b4b'; break;
        }

        $data[] = [
            $stt++, $row['DA_TEN'], $nameStatus,
            $row['DA_NGAYBATDAU'], $row['DA_NGAYKETTHUC'], $color,
            '', '', '', '', '', '', '', '', '', '', ''
        ];
    }
}

$headers = ['STT', 'Tên dự án', 'Trạng thái', 'Ngày bắt đầu', 'Ngày kết thúc', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
$title = "Danh sách dự án";
exportToExcel($data, $headers, $title);
?>