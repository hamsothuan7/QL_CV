<?php
    include('../../config.php');
?>
<table class="table table-hover">
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col">Công việc</th>
        <?php if ($type == 8): ?>
            <th scope="col">Nội dung</th>
        <?php endif; ?>
        <?php if ($_SESSION['active'] == 0): ?>
        <th scope="col">Nhận xét</th>
        <?php endif; ?>
        <th scope="col">Ngày bắt đầu</th>
        <th scope="col">Ngày kết thúc</th>
    </tr>
    </thead>
    <tbody>
    <?php if (isset($jobs) && !empty($jobs)) : ?>
        <?php $stt = 1; ?>
        <?php foreach ($jobs as $item): ?>
            <tr <?php echo ($type == 6 && isset($item['DSCV_TRANGTHAIGIAINGAN']) && $item['DSCV_TRANGTHAIGIAINGAN'] == 0) ? 'style="color:red"' : ''; ?>>
                <th scope="row"><?php echo $stt++; ?></th>
                <?php if ($_SESSION['active'] == 1): ?>
                    <td><a href="danhsachcv.php?id=<?php echo $item['DSCV_MA']; ?>"
                           target="_blank" <?php echo ($type == 6 && isset($item['DSCV_TRANGTHAIGIAINGAN']) && $item['DSCV_TRANGTHAIGIAINGAN'] == 0) ? 'style="color:red"' : ''; ?>><?php echo $item['DSCV_TEN']; ?></a></td>
                <?php else: ?>
                    <td><a href="danhsachcvtv.php?id=<?php echo $item['DSCV_MA']; ?>"
                           target="_blank" <?php echo ($type == 6 && isset($item['DSCV_TRANGTHAIGIAINGAN']) && $item['DSCV_TRANGTHAIGIAINGAN'] == 0) ? 'style="color:red"' : ''; ?>><?php echo $item['DSCV_TEN']; ?></a></td>
                <?php endif; ?>
                <?php if ($type == 8): ?>
                    <td>
                        <?php if ($item['DSCV_NGAYKETTHUC_TV'] != null): ?>
                            Thay đổi ngày kết thúc <?php echo date('d/m/Y', strtotime($item['DSCV_NGAYKETTHUC_TV'])); ?>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <?php if ($_SESSION['active'] == 0): ?>
                <td>
                    <?php
                    //Get số comment
                    $id = $item['DSCV_MA'];
                    $userid = $_SESSION['code'];
                    $sql = "SELECT COUNT(c.ID) as total FROM binhluan_cv c WHERE DSCV_MA = '$id' AND TRANGTHAI = 0 AND TV_MA <> '$userid'";
                    $result = mysqli_query($conn, $sql);
                    $comment = mysqli_fetch_assoc($result);
                    ?>
                    <?php if ($comment['total'] > 0): ?>
                        <?php if ($_SESSION['active'] == 1 || $_SESSION['nnd_ma'] == 4 || $_SESSION['nnd_ma'] == 2): ?>
                            <a href="danhsachcv.php?id=<?php echo $item['DSCV_MA']; ?>"
                               data-id="<?php echo $item['DSCV_MA']; ?>" class="btn btn-sm btn-danger btnComment"
                               target="_blank">Có <?php echo $comment['total']; ?> <i class="fas fa-comments"></i> nhận
                                xét mới</a>
                        <?php else: ?>
                            <a href="danhsachcvtv.php?id=<?php echo $item['DSCV_MA']; ?>"
                               data-id="<?php echo $item['DSCV_MA']; ?>" class="btn btn-sm btn-danger btnComment"
                               target="_blank">Có <?php echo $comment['total']; ?> <i class="fas fa-comments"></i> nhận
                                xét mới</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
                <td><?php echo date('d/m/Y', strtotime($item['DSCV_NGAYBATDAU'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($item['DSCV_NGAYKETTHUC'])); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>