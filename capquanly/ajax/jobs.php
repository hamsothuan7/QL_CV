<?php
if (!function_exists('isValidDate')) {
    function isValidDate($date) {
        return ($date && $date !== '0000-00-00' && strtotime($date) !== false);
    }
}
?>
<style>
.job-name {
    white-space: normal;      /* Cho phép xuống dòng */
    word-break: break-word;   /* Tự động ngắt từ khi quá dài */
    overflow-wrap: break-word;/* Hỗ trợ ngắt từ */
}
</style>

<div class="jobs-list-wrapper">
    <div class="jobs-list">
        <h2 class="jobs-list-heading">Chưa tiếp nhận
            <hr>
        <button data-status="5" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm dự án</button>
        </h2>
        <div class="jobs-list-body" id="new-jobs">
            <ul id="new-jobs-list">
                <?php if (!empty($projectsStart)): ?>
                    <?php foreach ($projectsStart as $item): ?>
                        <li data-id="<?php echo $item['DA_MA']; ?>">
                            <div class="job-block" >
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DA_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DA_MA']; ?>"  ><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DA_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                    //Get số comment
                                    $id = $item['DA_MA'];
                                    $sql = "SELECT COUNT(c.ID) as total FROM binhluan c WHERE DA_MA = '$id'  ";
                                    $result = mysqli_query($conn, $sql);
                                    $comment = mysqli_fetch_assoc($result);
                                    //Get label
                                    $sql = "SELECT COLOR FROM nhan WHERE DA_MA = '$id'  ";
                                    $result = mysqli_query($conn, $sql);
                                    $labels = mysqli_fetch_all($result, MYSQLI_ASSOC);
                                    
                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"></i>
                                    <div class="viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <?php if(!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) :?>
    <div><i title="Thẻ đã hết hạn" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
<?php endif; ?>
<div title="<?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>">
    <?php
        echo date('d/m', strtotime($item['DA_NGAYBATDAU']));
        if (isValidDate($item['DA_NGAYKETTHUC'])) {
            echo ' - ' . date('d/m', strtotime($item['DA_NGAYKETTHUC']));
        }
    ?>
</div>
                                    <div class="mt-2">
                                        <?php if (!empty($labels)): ?>
                                            <?php foreach ($labels as $itemL): ?>
                                                <a style="background:<?php echo $itemL['COLOR']; ?> !important;" href="javascript:;"
                                                   class="badge badge-primary">
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            </ul>
        </div>
        <div class="jobs-list-footer"></div>
    </div>


    <div class="jobs-list">
        <h2 class="jobs-list-heading">Đang tiến hành
        <hr>
        <button data-status="1" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm dự án</button>
        </h2>
        <div class="jobs-list-body" id="in-progress">
            <ul id="in-progress-list">
                <?php if (!empty($projectsInProgress)): ?>
                    <?php foreach ($projectsInProgress as $item): ?>
                        <li data-id="<?php echo $item['DA_MA']; ?>">
                            <div class="job-block" >
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DA_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DA_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DA_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan c WHERE DA_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);
                                //Get label
                                $sql = "SELECT COLOR FROM nhan WHERE DA_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $labels = mysqli_fetch_all($result, MYSQLI_ASSOC);
                                
                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"></i>
                                    <div class="viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <?php if(!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) :?>
    <div><i title="Thẻ đã hết hạn" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
<?php endif; ?>
<div title="<?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>">
    <?php
        echo date('d/m', strtotime($item['DA_NGAYBATDAU']));
        if (isValidDate($item['DA_NGAYKETTHUC'])) {
            echo ' - ' . date('d/m', strtotime($item['DA_NGAYKETTHUC']));
        }
    ?>
</div>
                                    <div class="mt-2">
                                        <?php if (!empty($labels)): ?>
                                            <?php foreach ($labels as $itemL): ?>
                                                <a style="background:<?php echo $itemL['COLOR']; ?> !important;" href="javascript:;"
                                                   class="badge badge-primary">
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="jobs-list-footer"></div>
    </div>

    <div class="jobs-list">
        <h2 class="jobs-list-heading">Hoàn thành
        <hr>
        <button data-status="2" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm dự án</button>
        </h2>
        <div class="jobs-list-body" id="complete">
            <ul id="complete-jobs-list">
                <?php if (!empty($projectsFinish)): ?>
                    <?php foreach ($projectsFinish as $item): ?>
                        <li data-id="<?php echo $item['DA_MA']; ?>">
                            <div class="job-block" >
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DA_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DA_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DA_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan c WHERE DA_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);
                                //Get label
                                $sql = "SELECT COLOR FROM nhan WHERE DA_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $labels = mysqli_fetch_all($result, MYSQLI_ASSOC);

                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"></i>
                                    <div class="viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <?php if($item['DA_NGAYKETTHUC'] < date('Y-m-d')) :?>
                                        <div><i title="Thẻ đã hết hạn" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div title="<?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>">
    <?php
        echo date('d/m', strtotime($item['DA_NGAYBATDAU']));
        if (isValidDate($item['DA_NGAYKETTHUC'])) {
            echo ' - ' . date('d/m', strtotime($item['DA_NGAYKETTHUC']));
        }
    ?>
</div>
                                    <div class="mt-2">
                                        <?php if (!empty($labels)): ?>
                                            <?php foreach ($labels as $itemL): ?>
                                                <a style="background:<?php echo $itemL['COLOR']; ?> !important;" href="javascript:;"
                                                   class="badge badge-primary">
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="jobs-list-footer"></div>
    </div>

    <div class="jobs-list">
        <h2 class="jobs-list-heading">Trễ
        <hr>
        <button data-status="3" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm dự án</button>
        </h2>
        <div class="jobs-list-body" id="waiting">
            <ul id="waiting-jobs-list">
                <?php if (!empty($projectsMove)): ?>
                    <?php foreach ($projectsMove as $item): ?>
                        <li data-id="<?php echo $item['DA_MA']; ?>">
                            <div class="job-block" >
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DA_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DA_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DA_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan c WHERE DA_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);
                                //Get label
                                $sql = "SELECT COLOR FROM nhan WHERE DA_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $labels = mysqli_fetch_all($result, MYSQLI_ASSOC);
                                
                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"></i>
                                    <div><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <?php if($item['DA_NGAYKETTHUC'] < date('Y-m-d')) :?>
                                        <div><i title="Thẻ đã hết hạn" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div title="<?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>">
    <?php
        echo date('d/m', strtotime($item['DA_NGAYBATDAU']));
        if (isValidDate($item['DA_NGAYKETTHUC'])) {
            echo ' - ' . date('d/m', strtotime($item['DA_NGAYKETTHUC']));
        }
    ?>
</div>
                                    <div class="mt-2">
                                        <?php if (!empty($labels)): ?>
                                            <?php foreach ($labels as $itemL): ?>
                                                <a style="background:<?php echo $itemL['COLOR']; ?> !important;" href="javascript:;"
                                                   class="badge badge-primary">
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="jobs-list-footer"></div>
    </div>

    <div class="jobs-list">
        <h2 class="jobs-list-heading">Hủy
        <hr>
        <button data-status="4" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm dự án</button>
        </h2>
        <div class="jobs-list-body" id="rework">
            <ul id="rework-jobs-list">
                <?php if (!empty($projectsCancel)): ?>
                    <?php foreach ($projectsCancel as $item): ?>
                        <li data-id="<?php echo $item['DA_MA']; ?>">
                            <div class="job-block" >
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DA_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DA_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DA_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"></i>
                                    <div class="viewDetail2" data-id="<?php echo $item['DA_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <?php if($item['DA_NGAYKETTHUC'] < date('Y-m-d')) :?>
                                        <div><i title="Thẻ đã hết hạn" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div title="<?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo (!empty($item['DA_NGAYKETTHUC']) && $item['DA_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>">
    <?php
        echo date('d/m', strtotime($item['DA_NGAYBATDAU']));
        if (isValidDate($item['DA_NGAYKETTHUC'])) {
            echo ' - ' . date('d/m', strtotime($item['DA_NGAYKETTHUC']));
        }
    ?>
</div>
                                    <div class="mt-2">
                                        <?php if (!empty($labels)): ?>
                                            <?php foreach ($labels as $itemL): ?>
                                                <a style="background:<?php echo $itemL['COLOR']; ?> !important;" href="javascript:;"
                                                   class="badge badge-primary">
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="jobs-list-footer"></div>
    </div>
</div>

