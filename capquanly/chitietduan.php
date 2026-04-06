<?php
include('../config.php');

$da_ma = $_GET['da_ma'];
$month = $_GET['month'];
$year = $_GET['year'];

$sql = "SELECT * FROM danhsachcongviec 
        WHERE DA_MA = '$da_ma' 
        AND MONTH(DSCV_NGAYBATDAU) = '$month' 
        AND YEAR(DSCV_NGAYBATDAU) = '$year'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover">';
    echo '<thead class="table-light"><tr><th>Tên công việc</th><th>Ngày bắt đầu</th><th>Tiến độ</th></tr></thead>';
    echo '<tbody>';
    while ($row = mysqli_fetch_assoc($result)) {
        $td = $row['TIEN_DO'];
        $color = ($td == 100) ? 'text-success' : 'text-primary';
        echo "<tr>
                <td>{$row['DSCV_TEN']}</td>
                <td>" . date('d/m/Y', strtotime($row['DSCV_NGAYBATDAU'])) . "</td>
                <td>
                    <div class='d-flex align-items-center'>
                        <span class='me-2 fw-bold $color'>$td%</span>
                        <div class='progress w-100' style='height: 6px;'>
                            <div class='progress-bar bg-info' style='width: $td%'></div>
                        </div>
                    </div>
                </td>
              </tr>";
    }
    echo '</tbody></table></div>';
} else {
    echo '<p class="text-center">Không tìm thấy công việc chi tiết.</p>';
}
?>