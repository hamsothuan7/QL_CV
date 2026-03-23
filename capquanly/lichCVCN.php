<?php
try {
    include('../config.php');
    session_start();

    $data = array();
    $tvCode = $_SESSION['code'];
    if($_SESSION['active'] == 0)
        $query = "SELECT * FROM danhsachcongviec WHERE DSCV_NGAYBATDAU IS NOT NULL AND TV_MA = '$tvCode' ORDER BY DSCV_MA";
    else
        $query = "SELECT * FROM danhsachcongviec WHERE DSCV_NGAYBATDAU IS NOT NULL AND DA_MA IS NOT NULL ORDER BY DSCV_MA";

    $queryResult = mysqli_query($conn, $query);
    $result = mysqli_fetch_all($queryResult, MYSQLI_ASSOC);

    foreach ($result as $row) {
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

        $data[] = array(
            'id'    => $row['DSCV_MA'],             
            'title' => 'Tên: '.$row['DSCV_TEN'],
            'status' => 'T.thái: '.$nameStatus,
            'start' => $row['DSCV_NGAYBATDAU'],     
            'end'   => $row['DSCV_NGAYKETTHUC'],
            'date_start'   => 'Bắt đầu: '.date('d/m/y', strtotime($row['DSCV_NGAYBATDAU'])),
            'date_end'   => 'Kết thúc: '.date('d/m/y', strtotime($row['DSCV_NGAYBATDAU'])),
            'description' => 'Nội dung: '.$row['DSCV_MOTA'] ?? "",
            'color' => $color
        );
    }

    
    echo json_encode($data);

} catch (PDOException $e) {
    echo 'Kết nối thất bại: ' . $e->getMessage();
}

?>
