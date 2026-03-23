
    
    <?php
    // Sắp xếp công việc theo số ngày còn lại
    function sortJobsByRemainingDays(&$jobs) {
        if (!is_array($jobs)) return;
        
        usort($jobs, function($a, $b) {
            $today = new DateTime();
            $dateA = new DateTime($a['DSCV_NGAYKETTHUC']);
            $dateB = new DateTime($b['DSCV_NGAYKETTHUC']);
            
            $daysA = $today->diff($dateA)->days * ($dateA < $today ? -1 : 1);
            $daysB = $today->diff($dateB)->days * ($dateB < $today ? -1 : 1);
            
            return $daysA - $daysB;
        });
    }

    // Sắp xếp các mảng công việc
    sortJobsByRemainingDays($projectsStart);
    sortJobsByRemainingDays($projectsInProgress);
    sortJobsByRemainingDays($projectsMove);
    sortJobsByRemainingDays($projectsFinish);
    sortJobsByRemainingDays($projectsCancel);
    ?>

<div class="jobs-list-wrapper">
    <div class="jobs-list">
        <h2 class="jobs-list-heading">Chưa tiếp nhận
            <hr>
        <button data-status="5" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm công việc</button>
        </h2>
        <div class="jobs-list-body" id="new-jobs">
            <ul id="new-jobs-list">
                <?php if (!empty($projectsStart)): ?>
                    <?php foreach ($projectsStart as $item): ?>
                        <li data-id="<?php echo $item['DSCV_MA']; ?>">
                            <div class="job-block<?php echo empty($item['DA_MA']) ? ' job-private-task' : ''; ?>" style="<?php echo empty($item['DA_MA']) ? 'background-color:#8d5524;color:#fff;' : ''; ?>">
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DSCV_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DSCV_MA']; ?>"  ><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DSCV_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DSCV_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan_cv c WHERE DSCV_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);

                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"></i>
                                    <?php if($item['DSCV_NGAYKETTHUC_TV'] != NULL) :?>
                                        <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>" data-date="<?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>"><i title="Thay đổi ngày kết thúc <?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <div title="<?php echo($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>"><?php echo date('d/m', strtotime($item['DSCV_NGAYBATDAU'])); ?>-<?php echo date('d/m', strtotime($item['DSCV_NGAYKETTHUC'])); ?></div>
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
        <button data-status="1" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm công việc</button>
        </h2>
        <div class="jobs-list-body" id="in-progress">
            <ul id="in-progress-list">
                <?php if (!empty($projectsInProgress)): ?>
                    <?php foreach ($projectsInProgress as $item): ?>
                        <li data-id="<?php echo $item['DSCV_MA']; ?>">
                            <div class="job-block<?php echo empty($item['DA_MA']) ? ' job-private-task' : ''; ?>" style="<?php echo empty($item['DA_MA']) ? 'background-color:#8d5524;color:#fff;' : ''; ?>">
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DSCV_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DSCV_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DSCV_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan_cv c WHERE DSCV_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);

                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"></i>
                                    <?php if($item['DSCV_NGAYKETTHUC_TV'] != NULL) :?>
                                        <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>" data-date="<?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>"><i title="Thay đổi ngày kết thúc <?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <div title="<?php echo($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>"><?php echo date('d/m', strtotime($item['DSCV_NGAYBATDAU'])); ?>-<?php echo date('d/m', strtotime($item['DSCV_NGAYKETTHUC'])); ?></div>
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
        <button data-status="2" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm công việc</button>
        </h2>
        <div class="jobs-list-body" id="complete">
            <ul id="complete-jobs-list">
                <?php if (!empty($projectsFinish)): ?>
                    <?php foreach ($projectsFinish as $item): ?>
                        <li data-id="<?php echo $item['DSCV_MA']; ?>">
                            <div class="job-block<?php echo empty($item['DA_MA']) ? ' job-private-task' : ''; ?>" style="<?php echo empty($item['DA_MA']) ? 'background-color:#8d5524;color:#fff;' : ''; ?>">
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DSCV_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DSCV_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DSCV_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan_cv c WHERE DSCV_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);

                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"></i>
                                    <?php if($item['DSCV_NGAYKETTHUC_TV'] != NULL) :?>
                                        <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>" data-date="<?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>"><i title="Thay đổi ngày kết thúc <?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <div title="<?php echo($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>"><?php echo date('d/m', strtotime($item['DSCV_NGAYBATDAU'])); ?>-<?php echo date('d/m', strtotime($item['DSCV_NGAYKETTHUC'])); ?></div>
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
        <button data-status="3" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm công việc</button>
        </h2>
        <div class="jobs-list-body" id="waiting">
            <ul id="waiting-jobs-list">
                <?php if (!empty($projectsMove)): ?>
                    <?php foreach ($projectsMove as $item): ?>
                        <li data-id="<?php echo $item['DSCV_MA']; ?>">
                            <div class="job-block<?php echo empty($item['DA_MA']) ? ' job-private-task' : ''; ?>" style="<?php echo empty($item['DA_MA']) ? 'background-color:#8d5524;color:#fff;' : ''; ?>">
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DSCV_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DSCV_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DSCV_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan_cv c WHERE DSCV_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);

                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"></i>
                                    <?php if($item['DSCV_NGAYKETTHUC_TV'] != NULL) :?>
                                        <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>" data-date="<?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>"><i title="Thay đổi ngày kết thúc <?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <div title="<?php echo($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>"><?php echo date('d/m', strtotime($item['DSCV_NGAYBATDAU'])); ?>-<?php echo date('d/m', strtotime($item['DSCV_NGAYKETTHUC'])); ?></div>
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
        <button data-status="4" class="btn btn-block btn-sm btn-default btn-flat border-primary btnInsert"><i class="fa fa-plus"></i> Thêm công việc</button>
        </h2>
        <div class="jobs-list-body" id="rework">
            <ul id="rework-jobs-list">
                <?php if (!empty($projectsCancel)): ?>
                    <?php foreach ($projectsCancel as $item): ?>
                        <li data-id="<?php echo $item['DSCV_MA']; ?>">
                            <div class="job-block<?php echo empty($item['DA_MA']) ? ' job-private-task' : ''; ?>" style="<?php echo empty($item['DA_MA']) ? 'background-color:#8d5524;color:#fff;' : ''; ?>">
                                <div class="job-name-block">
                                    <div class="job-name"><?php echo $item['DSCV_TEN']; ?></div>
                                    <div class="job-edit menuJob" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-edit"></i>
                                    </div>
                                    <ul class="list-group-edit menuDiv_<?php echo $item['DSCV_MA']; ?>">
                                        <li class="list-group-item viewDetail" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-id-card"></i>&nbsp;Mở thẻ</li>
                                        <li class="list-group-item removeProject" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="fal fa-trash-alt"></i>&nbsp;Xóa thẻ</li>
                                    </ul>
                                </div>
                                <?php
                                //Get số comment
                                $id = $item['DSCV_MA'];
                                $sql = "SELECT COUNT(c.ID) as total FROM binhluan_cv c WHERE DSCV_MA = '$id'  ";
                                $result = mysqli_query($conn, $sql);
                                $comment = mysqli_fetch_assoc($result);

                                ?>
                                <div class="job-info-block">
                                    <i class="fal fa-eye viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"></i>
                                    <?php if($item['DSCV_NGAYKETTHUC_TV'] != NULL) :?>
                                        <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>" data-date="<?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>"><i title="Thay đổi ngày kết thúc <?php echo $item['DSCV_NGAYKETTHUC_TV']; ?>" class="btn btn-sm btn-danger far fa-bell">&nbsp;1</i></div>
                                    <?php endif; ?>
                                    <div class="viewDetail2" data-id="<?php echo $item['DSCV_MA']; ?>"><i class="far fa-comment"></i>&nbsp;<?php echo $comment['total']; ?></div>
                                    <div title="<?php echo($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'Thẻ đã hết hạn' : 'Ngày bắt đầu';  ?>" class="job-date <?php echo ($item['DSCV_NGAYKETTHUC'] < date('Y-m-d')) ? 'bg-danger' : '';  ?>"><?php echo date('d/m', strtotime($item['DSCV_NGAYBATDAU'])); ?>-<?php echo date('d/m', strtotime($item['DSCV_NGAYKETTHUC'])); ?></div>
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