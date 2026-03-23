<?php
require 'vendor/autoload.php';

require './config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

function exportToExcel($data, $headers, $title, $filename = 'export.xlsx') {
    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add title
    $sheet->setCellValue('A1', $title);
    $sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1'); // Adjust the range based on the number of columns
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 14,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
    ]);

    // Add headers
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '2', $header);
        $sheet->getStyle($col . '2')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $sheet->getColumnDimension($col)->setAutoSize(true); // Auto-size column width
        $col++;
    }

    // Add data to the spreadsheet
    $row = 3;
    foreach ($data as $rowData) {
        $col = 'A';
        foreach ($rowData as $index => $cellData) {
            // Set cell value to null
            $sheet->setCellValue($col . $row, $cellData);
            $sheet->setCellValue('F'. $row, null);
            // Apply border style for all cells
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
        // Apply background color based on date range
        applyBackgroundColorByDateRange($sheet, $row, $rowData[3], $rowData[4], $rowData[5]); // Assuming date_start is at index 3, date_end at index 4, and color at index 5
        $row++;
    }

    // Write the spreadsheet to a file
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);

    // Set headers to prompt download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Cache-Control: max-age=0');
    header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filename));
    flush(); // Flush system output buffer
    readfile($filename);

    // Delete the file after download
    unlink($filename);
}

// Sample data to export
$tvCode = $_SESSION['code'];
if ($_SESSION['active'] == 1)
    $sql = "SELECT * FROM `danhsachcongviec` WHERE DSCV_NGAYBATDAU IS NOT NULL AND DA_MA IS NOT NULL ";
else
    $sql = "SELECT * FROM `danhsachcongviec` WHERE DSCV_NGAYBATDAU IS NOT NULL AND TV_MA = '$tvCode' ";

$result = mysqli_query($conn, $sql);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

$data = [];
if(!empty($jobs)){
    $stt = 1;
    foreach ($jobs as $row) {
        $color = '';
        $nameStatus = '';
        switch ($row['DSCV_TRANGTHAI']) {
            case 1:
                $color = '#5190d2';
                $nameStatus = 'Đang tiến hành';
                break;
            case 2:
                $color = '#32ff7e';
                $nameStatus = 'Hoàn thành';
                break;
            case 3:
                $color = '#ff9f1a';
                $nameStatus = 'Trễ';
                break;
            case 4:
                $color = '#ff3838';
                $nameStatus = 'Hủy';
                break;
            case 5:
                $color = '#18dcff';
                $nameStatus = 'Chưa tiếp nhận';
                break;
            default:
                $nameStatus = 'Không xác định';
                $color = '#4b4b4b';
                break;
        }

        $data[] = [
          $stt++, $row['DSCV_TEN'], $nameStatus, $row['DSCV_NGAYBATDAU'], $row['DSCV_NGAYKETTHUC'], $color, '', '', '', '', '', '', '', '', '', '', ''
        ];

    }
}

// Headers for the data
$headers = ['STT', 'Công việc', 'Trạng thái', 'Ngày bắt đầu', 'Ngày kết thúc', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

// Title for the spreadsheet
$title = "Danh sách công việc";

// Export data to Excel
exportToExcel($data, $headers, $title);

?>
