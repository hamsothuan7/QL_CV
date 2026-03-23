<?php
include('../config.php');
session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Đơn Vị Phối Hợp</title>
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="../style/style2.css">

    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet"
        type="text/css" />
    <style type="text/css">
        .ckeck {
            display: none;
        }

        table.table-bordered {
            background-color: #fff;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include("../menu.php"); ?>
        <div id="content">
            <div class="top-bar-block center" style="margin-left: 10px;">
                <div class="d-flex justify-content-between align-items-center" style="margin-top: 40px;">
                    <h3 class="m-0" style="color: #d30e0e; font-weight: 700;">Danh Sách Các Đơn Vị Phối Hợp</h3>
                    <form method="GET">
                        <div class="d-flex">
                            <input autocomplete="off" class="form-control input-sm" type="text" name="txtSearch" style="width: 300px; margin-left:10px" placeholder="Tìm kiếm theo tên đơn vị" value="<?php echo $_GET['txtSearch'] ?? ''; ?>">
                            <button type="submit" class="btn btn-sm btn-danger px-3">
                                <i class="fal fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="border-primary">
            <section class="content">
                <div class="row" style="margin-right: 10px;">
                    <div class="col-lg-12">
                        <div class="card card-outline card-success" style="border-top: 3px solid rgb(48, 162, 48); border-radius: 5px;">
                            <div class="card-header">
                                <a class="btn btn-sm btn-default btn-flat border-primary" href="themdonvi.php">
                                    <i class="fa fa-plus"></i> Thêm Đơn Vị
                                </a>
                            </div>
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên Đơn Vị</th>
                                        <th>Địa Chỉ</th>
                                        <th>Công Việc Tiếp Nhận</th>
                                        <th>Công Việc Hoàn Thành</th>
                                        <th>Công Việc Trễ</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $limit = 10;
                                    $cr_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $start = ($cr_page - 1) * $limit;

                                    $where = '';
                                    if (!empty($_GET['txtSearch'])) {
                                        $search = mysqli_real_escape_string($conn, $_GET['txtSearch']);
                                        $where = "WHERE ph.PH_TEN LIKE N'%$search%'";
                                    }

                                    // Truy vấn lấy tổng số đơn vị (dùng lại nếu bạn muốn phân trang đúng)
                                    $total_sql = "SELECT COUNT(DISTINCT ph.PH_MA) AS total 
              FROM donviphoihop ph 
              LEFT JOIN danhsachcongviec dscv ON ph.PH_MA = dscv.PH_MA 
              LEFT JOIN duan da ON dscv.DA_MA = da.DA_MA
              $where";
                                    $total_result = mysqli_query($conn, $total_sql);
                                    $total_row = mysqli_fetch_assoc($total_result);
                                    $total = $total_row['total'];
                                    $total_page = ceil($total / $limit);

                                    // Truy vấn chính có join để lấy thống kê
                                    $sql = "
   SELECT 
    ph.PH_MA,
    ph.PH_TEN,
    ph.PH_DIACHI,
    COUNT(dscv.DSCV_MA) AS PH_SLTIEPNHAN,
    COUNT(CASE WHEN da.DA_TRANGTHAI = 2 THEN dscv.DSCV_MA END) AS PH_SLHOANTHANH,
    COUNT(CASE WHEN da.DA_TRANGTHAI = 3 THEN dscv.DSCV_MA END) AS PH_SLTRE
FROM donviphoihop ph
LEFT JOIN danhsachcongviec dscv ON ph.PH_MA = dscv.PH_MA
LEFT JOIN duan da ON dscv.DA_MA = da.DA_MA
$where
GROUP BY ph.PH_MA, ph.PH_TEN, ph.PH_DIACHI
LIMIT $start, $limit


";
                                    $result = mysqli_query($conn, $sql);
                                    $stt = $start + 1;
                               while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . $stt++ . '</td>';
    echo '<td>' . htmlspecialchars($row['PH_TEN']) . '</td>';
    echo '<td>' . htmlspecialchars($row['PH_DIACHI']) . '</td>';

    // Số lượng tiếp nhận + icon
    echo '<td>';
    echo htmlspecialchars($row['PH_SLTIEPNHAN']);
    echo ' <a href="chitietduan.php?ph_ma=' . urlencode($row['PH_MA']) . '" class="ml-2 text-primary" title="Xem chi tiết">';
    echo '<i class="fas fa-info-circle"></i>';
    echo '</a></td>';

    // Số lượng hoàn thành + icon
    echo '<td>';
    echo htmlspecialchars($row['PH_SLHOANTHANH']);
    echo ' <a href="chitietduan.php?ph_ma=' . urlencode($row['PH_MA']) . '&trangthai=2" class="ml-2 text-success" title="Xem chi tiết hoàn thành">';
    echo '<i class="fas fa-info-circle"></i>';
    echo '</a></td>';

    // Số lượng trễ + icon
    echo '<td>';
    echo htmlspecialchars($row['PH_SLTRE']);
    echo ' <a href="chitietduan.php?ph_ma=' . urlencode($row['PH_MA']) . '&trangthai=3" class="ml-2 text-danger" title="Xem chi tiết trễ">';
    echo '<i class="fas fa-info-circle"></i>';
    echo '</a></td>';

    // Thao tác
    echo '<td>';
    echo '<a href="suadv.php?id=' . urlencode($row['PH_MA']) . '" class="btn btn-sm btn-primary"><i class="fas fa-pencil-alt"></i></a> ';
    echo '<a href="xoadv.php?id=' . urlencode($row['PH_MA']) . '" onclick="return Del();" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></a>';
    echo '</td>';

    echo '</tr>';
}


                                    ?>

                                </tbody>
                            </table>
                            <!-- pagination -->
                            <ul class="pagination justify-content-end m-3">
                                <li class="<?php echo ($cr_page <= 1) ? 'ckeck' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo max(1, $cr_page - 1); ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_page; $i++) { ?>
                                    <li class="page-item <?php echo ($cr_page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php } ?>
                                <li class="<?php echo ($cr_page >= $total_page) ? 'ckeck' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo min($cr_page + 1, $total_page); ?>">&raquo;</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
            
        </div>
    </div>

    <script>
        function Del() {
            return confirm("Bạn có chắc chắn muốn xóa?");
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
</body>

</html>