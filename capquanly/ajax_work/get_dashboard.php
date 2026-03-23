<?php
include('../../config.php');
session_start();

try {
    $type = $_GET['type'];
    $userCode = $_SESSION['code'];

    $nndMa = $_SESSION['nnd_ma'] ?? null;
    $isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || $nndMa == 4;
    $projectId = $_GET['da_ma'] ?? null;
    $phongbanId = $_GET['pb_ma'] ?? null;// Get project ID from query parameter

    $sql = '';
    $params = [];
    $types = '';

    switch ($type) {
        case 1:
            // Số công việc (có ngày bắt đầu)
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_NGAYBATDAU IS NOT NULL";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";

                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }

            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_NGAYBATDAU IS NOT NULL";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }



                $sql .= " AND (
                               dcv.TV_MA = ?
                            OR EXISTS (
                                SELECT 1 FROM thanhvien tv2
                                WHERE tv2.TV_MA = dcv.TV_MA
                                  AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                            )
                            OR EXISTS (
                                SELECT 1 FROM duan da2
                                WHERE da2.DA_MA = dcv.DA_MA
                                  AND (
                                        da2.DA_NGUOIPHUTRACH = ?
                                     OR EXISTS (
                                            SELECT 1 FROM thanhvien tv3
                                            WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                              AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                       )
                                  )
                            )
                            OR EXISTS (
                                SELECT 1
                                FROM duan da3
                                JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                  AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                            )
                          )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                        LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_NGAYBATDAU IS NOT NULL";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                    $types = 'sss';
                    $params = [$userCode, $userCode, $userCode];
                }


            }
            break;
        case 2:
            // Chưa tiếp nhận (DSCV_TRANGTHAI = 5)
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 5";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 5";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $sql .= " AND (
                               dcv.TV_MA = ?
                            OR EXISTS (
                                SELECT 1 FROM thanhvien tv2
                                WHERE tv2.TV_MA = dcv.TV_MA
                                  AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                            )
                            OR EXISTS (
                                SELECT 1 FROM duan da2
                                WHERE da2.DA_MA = dcv.DA_MA
                                  AND (
                                        da2.DA_NGUOIPHUTRACH = ?
                                     OR EXISTS (
                                            SELECT 1 FROM thanhvien tv3
                                            WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                              AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                       )
                                  )
                            )
                            OR EXISTS (
                                SELECT 1
                                FROM duan da3
                                JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                  AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                            )
                          )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                        LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 5
                          AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;
        case 3:
            // Đang tiến hành (DSCV_TRANGTHAI = 1)
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 1";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 1";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }



                $sql .= " AND (
                               dcv.TV_MA = ?
                            OR EXISTS (
                                SELECT 1 FROM thanhvien tv2
                                WHERE tv2.TV_MA = dcv.TV_MA
                                  AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                            )
                            OR EXISTS (
                                SELECT 1 FROM duan da2
                                WHERE da2.DA_MA = dcv.DA_MA
                                  AND (
                                        da2.DA_NGUOIPHUTRACH = ?
                                     OR EXISTS (
                                            SELECT 1 FROM thanhvien tv3
                                            WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                              AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                       )
                                  )
                            )
                            OR EXISTS (
                                SELECT 1
                                FROM duan da3
                                JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                  AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                            )
                          )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                        LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 1
                          AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;
        case 4:
            // Đã hoàn thành (DSCV_TRANGTHAI = 2)
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND (dcv.DSCV_TRANGTHAI = 2 OR dcv.DSCV_TRANGTHAI = 6)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        WHERE dcv.dscv_trangthaihd = 1 AND (dcv.DSCV_TRANGTHAI = 2 OR dcv.DSCV_TRANGTHAI = 6)";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }



                $sql .= " AND (
                               dcv.TV_MA = ?
                            OR EXISTS (
                                SELECT 1 FROM thanhvien tv2
                                WHERE tv2.TV_MA = dcv.TV_MA
                                  AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                            )
                            OR EXISTS (
                                SELECT 1 FROM duan da2
                                WHERE da2.DA_MA = dcv.DA_MA
                                  AND (
                                        da2.DA_NGUOIPHUTRACH = ?
                                     OR EXISTS (
                                            SELECT 1 FROM thanhvien tv3
                                            WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                              AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                       )
                                  )
                            )
                            OR EXISTS (
                                SELECT 1
                                FROM duan da3
                                JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                  AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                            )
                          )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                        LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                        WHERE dcv.dscv_trangthaihd = 1 AND (dcv.DSCV_TRANGTHAI = 2 OR dcv.DSCV_TRANGTHAI = 6)
                          AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;
        case 5:
            // Chậm tiến độ (DSCV_TRANGTHAI = 3)
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 3";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 3";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $sql .= " AND (
                               dcv.TV_MA = ?
                            OR EXISTS (
                                SELECT 1 FROM thanhvien tv2
                                WHERE tv2.TV_MA = dcv.TV_MA
                                  AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                            )
                            OR EXISTS (
                                SELECT 1 FROM duan da2
                                WHERE da2.DA_MA = dcv.DA_MA
                                  AND (
                                        da2.DA_NGUOIPHUTRACH = ?
                                     OR EXISTS (
                                            SELECT 1 FROM thanhvien tv3
                                            WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                              AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                       )
                                  )
                            )
                            OR EXISTS (
                                SELECT 1
                                FROM duan da3
                                JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                  AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                            )
                          )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                        LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_TRANGTHAI = 3
                          AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;
        case 6:
            // Danh sách công việc có giá trị giải ngân
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_GIATRIGIAINGAN > 0";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_GIATRIGIAINGAN > 0";

                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }



                $sql .= " AND (
                               dcv.TV_MA = ?
                            OR EXISTS (
                                SELECT 1 FROM thanhvien tv2
                                WHERE tv2.TV_MA = dcv.TV_MA
                                  AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                            )
                            OR EXISTS (
                                SELECT 1 FROM duan da2
                                WHERE da2.DA_MA = dcv.DA_MA
                                  AND (
                                        da2.DA_NGUOIPHUTRACH = ?
                                     OR EXISTS (
                                            SELECT 1 FROM thanhvien tv3
                                            WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                              AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                       )
                                  )
                            )
                            OR EXISTS (
                                SELECT 1
                                FROM duan da3
                                JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                  AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                            )
                          )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                        FROM danhsachcongviec dcv
                        INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                        LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                        WHERE dcv.dscv_trangthaihd = 1 AND dcv.DSCV_GIATRIGIAINGAN > 0
                          AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                } else {
                    $sql .= " AND dcv.DA_MA IS NULL";
                    if ($phongbanId) {
                        $sql .= " AND dcv.PB_MA = '" . strval($phongbanId)."'";
                    }
                }


                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;

        case 8:
            // Chờ xét duyệt (theo nghiệp vụ hiện tại: đã có DSCV_NGAYKETTHUC_TV)
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND dcv.DSCV_NGAYKETTHUC_TV IS NOT NULL";
            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                            FROM danhsachcongviec dcv
                            WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND dcv.DSCV_NGAYKETTHUC_TV IS NOT NULL
                              AND (
                                   dcv.TV_MA = ?
                                OR EXISTS (
                                    SELECT 1 FROM thanhvien tv2
                                    WHERE tv2.TV_MA = dcv.TV_MA
                                      AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                )
                                OR EXISTS (
                                    SELECT 1 FROM duan da2
                                    WHERE da2.DA_MA = dcv.DA_MA
                                      AND (
                                            da2.DA_NGUOIPHUTRACH = ?
                                         OR EXISTS (
                                                SELECT 1 FROM thanhvien tv3
                                                WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                                  AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                           )
                                      )
                                )
                                OR EXISTS (
                                    SELECT 1
                                    FROM duan da3
                                    JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                    WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                      AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                                )
                              )";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                }
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT dcv.*
                            FROM danhsachcongviec dcv
                            INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                            LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                            WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL AND dcv.DSCV_NGAYKETTHUC_TV IS NOT NULL
                              AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                }
                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;
        case 9:
            // Công việc có nhận xét mới (mở rộng cùng logic vai trò)
            if ($isAdmin) {
                $sql = "SELECT DISTINCT c.*
                                FROM danhsachcongviec c
                                INNER JOIN binhluan_cv b ON c.DSCV_MA = b.DSCV_MA
                                WHERE c.dscv_trangthaihd = 1 AND c.DA_MA IS NOT NULL AND b.TRANGTHAI = 0";
            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT c.*
                                FROM danhsachcongviec c
                                INNER JOIN binhluan_cv b ON c.DSCV_MA = b.DSCV_MA
                                WHERE c.dscv_trangthaihd = 1 AND c.DA_MA IS NOT NULL AND b.TRANGTHAI = 0
                                  AND (
                                       c.TV_MA = ?
                                    OR EXISTS (
                                        SELECT 1 FROM thanhvien tv2
                                        WHERE tv2.TV_MA = c.TV_MA
                                          AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                    )
                                    OR EXISTS (
                                        SELECT 1 FROM duan da2
                                        WHERE da2.DA_MA = c.DA_MA
                                          AND (
                                                da2.DA_NGUOIPHUTRACH = ?
                                             OR EXISTS (
                                                    SELECT 1 FROM thanhvien tv3
                                                    WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                                      AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                               )
                                          )
                                    )
                                    OR EXISTS (
                                        SELECT 1
                                        FROM duan da3
                                        JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                        WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                          AND c.TV_MA = da3.DA_NGUOIPHUTRACH
                                    )
                                  )";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];
            } else {
                $sql = "SELECT DISTINCT c.*
                                FROM danhsachcongviec c
                                INNER JOIN duan da ON c.DA_MA = da.DA_MA
                                LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                                INNER JOIN binhluan_cv b ON c.DSCV_MA = b.DSCV_MA
                                WHERE c.dscv_trangthaihd = 1 AND c.DA_MA IS NOT NULL AND b.TRANGTHAI = 0
                                  AND (c.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                }
                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;
        default:
            // Mặc định: tất cả công việc đang hoạt động theo phạm vi vai trò
            if ($isAdmin) {
                $sql = "SELECT * FROM danhsachcongviec dcv WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL ORDER BY dcv.DSCV_MA";
            } elseif ($nndMa == 2) {
                $sql = "SELECT DISTINCT dcv.*
                                FROM danhsachcongviec dcv
                                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL
                                  AND (
                                       dcv.TV_MA = ?
                                    OR EXISTS (
                                        SELECT 1 FROM thanhvien tv2
                                        WHERE tv2.TV_MA = dcv.TV_MA
                                          AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                    )
                                    OR EXISTS (
                                        SELECT 1 FROM duan da2
                                        WHERE da2.DA_MA = dcv.DA_MA
                                          AND (
                                                da2.DA_NGUOIPHUTRACH = ?
                                             OR EXISTS (
                                                    SELECT 1 FROM thanhvien tv3
                                                    WHERE tv3.TV_MA = da2.DA_NGUOIPHUTRACH
                                                      AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                               )
                                          )
                                    )
                                    OR EXISTS (
                                        SELECT 1
                                        FROM duan da3
                                        JOIN thanhvien tv4 ON tv4.TV_MA = da3.DA_NGUOIPHUTRACH
                                        WHERE tv4.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                                          AND dcv.TV_MA = da3.DA_NGUOIPHUTRACH
                                    )
                                  )
                                ORDER BY dcv.DSCV_MA";
                $types = 'sssss';
                $params = [$userCode, $userCode, $userCode, $userCode, $userCode];

            } else {
                $sql = "SELECT DISTINCT dcv.*
                                FROM danhsachcongviec dcv
                                INNER JOIN duan da ON dcv.DA_MA = da.DA_MA
                                LEFT JOIN duan_thanhvien dt ON da.DA_MA = dt.DA_MA
                                WHERE dcv.dscv_trangthaihd = 1 AND dcv.DA_MA IS NOT NULL
                                  AND (dcv.TV_MA = ? OR dt.TV_MA = ? OR da.DA_NGUOIPHUTRACH = ?)
                                ORDER BY dcv.DSCV_MA";
                if ($projectId) {
                    $sql .= " AND dcv.DA_MA = " . intval($projectId);
                }
                $types = 'sss';
                $params = [$userCode, $userCode, $userCode];
            }
            break;


    }

    if ($sql === '') {
        throw new Exception('Không xác định được câu truy vấn.');
    }

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Lỗi prepare SQL');
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $jobs = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    } else {
        $result = mysqli_query($conn, $sql);
        $jobs = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    $conn->close();
    $data = [
        'jobs' => $jobs,
        'type' => $type
    ];
    // Render the view and pass the data
    echo renderView('modal_dashboard_inner.php', $data);

} catch (\Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    return;
}
function renderView($view, $data)
{
    extract($data);
    ob_start();
    include $view;
    return ob_get_clean();
}

?>