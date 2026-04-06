<?php
include('../config.php');

$ph_ma = $_GET['ph_ma'];
$trangthai = isset($_GET['trangthai']) ? (int)$_GET['trangthai'] : null;  // Có thể null nếu không truyền

// Lấy tên đơn vị phối hợp
$dv_query = mysqli_query($conn, "SELECT PH_TEN FROM donviphoihop WHERE PH_MA = '$ph_ma'");
$dv_row = mysqli_fetch_assoc($dv_query);
$ten_donvi = $dv_row['PH_TEN'];

$trangthai_text = match ($trangthai) {
    2 => "Hoàn thành",
    3 => "Trễ",
    default => "Tiếp nhận"
};

// Tạo câu truy vấn
$query = "
    SELECT da.DA_TEN, dscv.DSCV_TEN, da.DA_NGAYBATDAU, da.DA_NGAYKETTHUC
    FROM danhsachcongviec dscv
    JOIN duan da ON dscv.DA_MA = da.DA_MA
    WHERE dscv.PH_MA = '$ph_ma'
";

if ($trangthai !== null) {
    $query .= " AND da.DA_TRANGTHAI = $trangthai";
}

$result = mysqli_query($conn, $query);
?>


<!DOCTYPE html>
<html>
<head>
    <title>Chi Tiết Dự Án - <?php echo $ten_donvi; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h4 class="mb-3">Chi tiết dự án <strong><?php echo $trangthai_text; ?></strong> của đơn vị: <span style="color: red"><?php echo $ten_donvi; ?></span></h4>
        <a href="donviphoihop.php" class="btn btn-secondary mb-3">Quay lại</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tên Dự Án</th>
                    <th>Tên Công Việc</th>
                    <th>Ngày Bắt Đầu</th>
                    <th>Ngày Kết Thúc</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['DA_TEN']; ?></td>
                    <td><?php echo $row['DSCV_TEN']; ?></td>
                    <td><?php echo $row['DA_NGAYBATDAU']; ?></td>
                    <td><?php echo $row['DA_NGAYKETTHUC']; ?></td>

                </tr>
                <?php } ?>
                <?php if (mysqli_num_rows($result) == 0) { ?>
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu phù hợp.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
