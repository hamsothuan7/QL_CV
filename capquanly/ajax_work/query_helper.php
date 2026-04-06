<?php
/**
 * query_helper.php — Logic query dùng chung cho danh sách công việc
 * 
 * Tối ưu:
 * - SELECT chỉ các cột cần thiết (không SELECT *)
 * - LEFT JOIN duan để lấy DA_TEN → loại bỏ N+1 getProjectName()
 * - Subquery đếm comment → loại bỏ N+1 getCommentCount()
 * - Hỗ trợ phân trang LIMIT/OFFSET
 * - Code dùng chung cho cả danhsachcv.php và get_view.php
 */

/**
 * Xây dựng câu SQL cơ sở theo phân quyền user
 * Trả về: ['sql' => string, 'params' => array, 'types' => string]
 */
function buildBaseQuery($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '')
{
    $userId = $_SESSION['code'] ?? null;
    if (!$userId) return null;

    // Lấy thông tin user
    $nndMa = $_SESSION['nnd_ma'] ?? ($_SESSION['NND_MA'] ?? null);
    $isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || ($nndMa == 4);

    // Lấy thêm info từ DB nếu cần (cho manager check)
    $userPB = $_SESSION['PB_MA'] ?? null;
    $isManager = false;

    if (!$isAdmin) {
        $userQuery = "SELECT NND_MA, PB_MA FROM thanhvien WHERE TV_MA = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();
        if ($userResult->num_rows > 0) {
            $userInfo = $userResult->fetch_assoc();
            $isManager = ($userInfo['NND_MA'] ?? 0) == 2;
            $userPB = $userInfo['PB_MA'] ?? $userPB;
        }
        $stmt->close();
    }

    // --- Các cột cần SELECT (không SELECT *, tránh lấy DSCV_MOTA longtext) ---
    $selectCols = "d.DSCV_MA, d.DSCV_TEN, d.DSCV_TRANGTHAI, d.DSCV_NGAYBATDAU, 
                   d.DSCV_NGAYKETTHUC, d.DSCV_NGAYKETTHUC_TV, d.DA_MA, d.TV_MA,
                   d.dscv_trangthaihd,
                   du.DA_TEN,
                   (SELECT COUNT(*) FROM binhluan_cv c WHERE c.DSCV_MA = d.DSCV_MA) AS comment_count";

    $params = [];
    $types = "";

    // --- Xây dựng WHERE theo phân quyền ---
    if ($isAdmin) {
        $sql = "SELECT DISTINCT $selectCols FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                WHERE d.dscv_trangthaihd = 1";

        if ($status == 2) {
            $sql .= " AND (d.DSCV_TRANGTHAI = 2 OR d.DSCV_TRANGTHAI = 6)";
        } elseif ($status === 'all') {
            // Cho table view: lấy tất cả trạng thái
            $sql .= " AND d.DSCV_TRANGTHAI IN (1,2,3,4,5,6)";
        } else {
            $sql .= " AND d.DSCV_TRANGTHAI = ?";
            $params[] = $status;
            $types .= "i";
        }
    } elseif ($isManager) {
        $sql = "SELECT DISTINCT $selectCols FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                LEFT JOIN thanhvien tvA ON d.TV_MA = tvA.TV_MA 
                LEFT JOIN thanhvien tvDT ON dt.TV_MA = tvDT.TV_MA 
                WHERE d.dscv_trangthaihd = 1";

        if ($status == 2) {
            $sql .= " AND (d.DSCV_TRANGTHAI = 2 OR d.DSCV_TRANGTHAI = 6)";
        } elseif ($status === 'all') {
            $sql .= " AND d.DSCV_TRANGTHAI IN (1,2,3,4,5,6)";
        } else {
            $sql .= " AND d.DSCV_TRANGTHAI = ?";
            $params[] = $status;
            $types .= "i";
        }

        $sql .= " AND (
                    d.TV_MA = ? 
                    OR du.DA_NGUOIPHUTRACH = ? 
                    OR tvA.PB_MA = ? 
                    OR tvDT.PB_MA = ? 
                    OR EXISTS (SELECT 1 FROM thanhvien tvL WHERE tvL.TV_MA = du.DA_NGUOIPHUTRACH AND tvL.PB_MA = ?))";
        $params = array_merge($params, [$userId, $userId, $userPB, $userPB, $userPB]);
        $types .= "sssss";
    } else {
        // Thành viên thông thường
        $sql = "SELECT DISTINCT $selectCols FROM danhsachcongviec d 
                LEFT JOIN duan du ON d.DA_MA = du.DA_MA 
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                WHERE d.dscv_trangthaihd = 1
                AND (d.TV_MA = ? OR dt.TV_MA = ? OR du.DA_NGUOIPHUTRACH = ?)";
        $params = [$userId, $userId, $userId];
        $types = "sss";

        if ($status == 2) {
            $sql .= " AND (d.DSCV_TRANGTHAI = 2 OR d.DSCV_TRANGTHAI = 6)";
        } elseif ($status === 'all') {
            $sql .= " AND d.DSCV_TRANGTHAI IN (1,2,3,4,5,6)";
        } else {
            $sql .= " AND d.DSCV_TRANGTHAI = ?";
            $params[] = $status;
            $types .= "i";
        }
    }

    // --- Thêm filter ---
    if (!empty($keywords)) {
        $sql .= " AND d.DSCV_TEN LIKE ?";
        $params[] = "%$keywords%";
        $types .= "s";
    }
    if (!empty($from_date)) {
        $sql .= " AND DATE(d.DSCV_NGAYBATDAU) >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    if (!empty($to_date)) {
        $sql .= " AND DATE(d.DSCV_NGAYKETTHUC) <= ?";
        $params[] = $to_date;
        $types .= "s";
    }
    if ($project_id === 'private') {
        $sql .= " AND (d.DA_MA IS NULL OR d.DA_MA = '')";
    } elseif (!empty($project_id)) {
        $sql .= " AND d.DA_MA = ?";
        $params[] = $project_id;
        $types .= "s";
    }

    return ['sql' => $sql, 'params' => $params, 'types' => $types];
}

/**
 * Lấy danh sách công việc theo trạng thái, có phân trang
 * 
 * @param mysqli $conn
 * @param int|string $status  Status code (1-5) hoặc 'all'
 * @param string $keywords
 * @param string $from_date
 * @param string $to_date
 * @param string $project_id
 * @param int $limit   Số bản ghi mỗi trang (0 = không giới hạn)
 * @param int $offset  Vị trí bắt đầu
 * @return array
 */
function getTasksByStatus($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '', $limit = 0, $offset = 0)
{
    $base = buildBaseQuery($conn, $status, $keywords, $from_date, $to_date, $project_id);
    if (!$base) return [];

    $sql = $base['sql'] . " ORDER BY d.DSCV_MA DESC";

    // Phân trang
    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $base['params'][] = $limit;
        $base['params'][] = $offset;
        $base['types'] .= "ii";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL Prepare Error: " . $conn->error . " | SQL: " . $sql);
        return [];
    }
    if (!empty($base['params'])) {
        $stmt->bind_param($base['types'], ...$base['params']);
    }
    if (!$stmt->execute()) {
        error_log("SQL Execute Error: " . $stmt->error);
        $stmt->close();
        return [];
    }
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

/**
 * Đếm tổng số bản ghi (cho phân trang)
 */
function countTasksByStatus($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '')
{
    $base = buildBaseQuery($conn, $status, $keywords, $from_date, $to_date, $project_id);
    if (!$base) return 0;

    // Thay SELECT cols bằng COUNT
    $countSql = preg_replace('/SELECT DISTINCT .+? FROM danhsachcongviec/s', 'SELECT COUNT(DISTINCT d.DSCV_MA) as total FROM danhsachcongviec', $base['sql']);

    $stmt = $conn->prepare($countSql);
    if ($stmt === false) {
        error_log("Count SQL Error: " . $conn->error . " | SQL: " . $countSql);
        return 0;
    }
    if (!empty($base['params'])) {
        $stmt->bind_param($base['types'], ...$base['params']);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/**
 * Lấy tất cả công việc cho Table View (1 query thay vì 5), có phân trang
 * Trả về: ['data' => array, 'total' => int, 'pages' => int]
 */
function getTasksForTable($conn, $keywords = '', $from_date = '', $to_date = '', $project_id = '', $page = 1, $perPage = 20)
{
    $offset = ($page - 1) * $perPage;
    $total = countTasksByStatus($conn, 'all', $keywords, $from_date, $to_date, $project_id);
    $data = getTasksByStatus($conn, 'all', $keywords, $from_date, $to_date, $project_id, $perPage, $offset);
    $pages = ($perPage > 0) ? (int)ceil($total / $perPage) : 1;

    return [
        'data' => $data,
        'total' => $total,
        'pages' => $pages,
        'page' => $page,
        'perPage' => $perPage
    ];
}

/**
 * Lấy công việc cho Kanban View (theo từng status, có giới hạn cho lazy load)
 * @param int $limit  Số card tải lần đầu (0 = tất cả)
 * @param int $offset Bắt đầu từ vị trí
 */
function getTasksForKanban($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '', $limit = 0, $offset = 0)
{
    return getTasksByStatus($conn, $status, $keywords, $from_date, $to_date, $project_id, $limit, $offset);
}

/**
 * Đếm tổng task theo 1 status (cho kanban lazy load)
 */
function countTasksForKanban($conn, $status, $keywords = '', $from_date = '', $to_date = '', $project_id = '')
{
    return countTasksByStatus($conn, $status, $keywords, $from_date, $to_date, $project_id);
}

/**
 * Render HTML phân trang
 */
function renderPagination($page, $totalPages, $perPage, $total)
{
    if ($totalPages <= 1) return '';

    $html = '<div class="cv-pagination">';
    $html .= '<div class="cv-pagination-info">Hiển thị trang <strong>' . $page . '</strong> / ' . $totalPages . ' (' . $total . ' công việc)</div>';
    $html .= '<div class="cv-pagination-controls">';

    // Per-page selector
    $html .= '<div class="cv-pagination-perpage">';
    $html .= '<select class="cv-perpage-select" id="perPageSelect">';
    foreach ([20, 50, 100] as $opt) {
        $selected = ($opt == $perPage) ? ' selected' : '';
        $html .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '/trang</option>';
    }
    $html .= '</select></div>';

    // Previous
    if ($page > 1) {
        $html .= '<a href="#" class="cv-page-btn cv-page-prev" data-page="' . ($page - 1) . '"><i class="fal fa-chevron-left"></i> Trước</a>';
    } else {
        $html .= '<span class="cv-page-btn cv-page-prev disabled"><i class="fal fa-chevron-left"></i> Trước</span>';
    }

    // Page numbers
    $html .= '<div class="cv-page-numbers">';
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);

    if ($startPage > 1) {
        $html .= '<a href="#" class="cv-page-num" data-page="1">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="cv-page-ellipsis">…</span>';
        }
    }
    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $page) ? ' active' : '';
        $html .= '<a href="#" class="cv-page-num' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
    }
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span class="cv-page-ellipsis">…</span>';
        }
        $html .= '<a href="#" class="cv-page-num" data-page="' . $totalPages . '">' . $totalPages . '</a>';
    }
    $html .= '</div>';

    // Next
    if ($page < $totalPages) {
        $html .= '<a href="#" class="cv-page-btn cv-page-next" data-page="' . ($page + 1) . '">Sau <i class="fal fa-chevron-right"></i></a>';
    } else {
        $html .= '<span class="cv-page-btn cv-page-next disabled">Sau <i class="fal fa-chevron-right"></i></span>';
    }

    $html .= '</div></div>';
    return $html;
}
?>
