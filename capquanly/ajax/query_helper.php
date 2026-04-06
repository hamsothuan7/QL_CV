<?php
/**
 * query_helper.php — Logic query dùng chung cho danh sách dự án
 * 
 * Tối ưu:
 * - Dùng chung logic phân quyền
 * - Subquery đếm comment -> loại bỏ N+1
 * - Hỗ trợ LIMIT/OFFSET phân trang
 */

function buildProjectQuery($conn, $status, $keywords = '', $from_date = '', $to_date = '')
{
    $userId = $_SESSION['code'] ?? null;
    if (!$userId) return null;

    $nndMa = $_SESSION['nnd_ma'] ?? ($_SESSION['NND_MA'] ?? null);
    $isAdmin = (isset($_SESSION['active']) && $_SESSION['active'] == 1) || ($nndMa == 4);
    // Role = 2 (Manager)
    $isManager = ($nndMa == 2);

    $params = [];
    $types = "";

    // Lấy label màu bằng GROUP_CONCAT để tránh N+1
    $selectCols = "d.DA_MA, d.DA_TEN, d.DA_TRANGTHAI, d.DA_NGAYBATDAU, d.DA_NGAYKETTHUC, 
                   d.DA_NGUOIPHUTRACH,
                   (SELECT COUNT(*) FROM binhluan c WHERE c.DA_MA = d.DA_MA) AS comment_count,
                   (SELECT GROUP_CONCAT(COLOR SEPARATOR ',') FROM nhan n WHERE n.DA_MA = d.DA_MA) as labels";

    if ($isAdmin) {
        $sql = "SELECT DISTINCT $selectCols FROM duan d WHERE 1=1";
    } elseif ($isManager) {
        $sql = "SELECT DISTINCT $selectCols FROM duan d
                WHERE (
                    d.DA_NGUOIPHUTRACH = ? 
                    OR EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = d.DA_MA AND dcv.TV_MA = ?)
                    OR EXISTS (
                        SELECT 1 FROM danhsachcongviec dcv2 
                        JOIN thanhvien tv2 ON tv2.TV_MA = dcv2.TV_MA 
                        WHERE dcv2.DA_MA = d.DA_MA 
                        AND tv2.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                    )
                    OR EXISTS (
                        SELECT 1 FROM thanhvien tv3 
                        WHERE tv3.TV_MA = d.DA_NGUOIPHUTRACH 
                        AND tv3.PB_MA = (SELECT PB_MA FROM thanhvien WHERE TV_MA = ? LIMIT 1)
                    )
                )";
        $params = array_merge($params, [$userId, $userId, $userId, $userId]);
        $types .= "ssss";
    } else {
        $sql = "SELECT DISTINCT $selectCols FROM duan d
                LEFT JOIN duan_thanhvien dt ON d.DA_MA = dt.DA_MA 
                WHERE (d.DA_NGUOIPHUTRACH = ? OR dt.TV_MA = ? 
                      OR EXISTS (SELECT 1 FROM danhsachcongviec dcv WHERE dcv.DA_MA = d.DA_MA AND dcv.TV_MA = ?))";
        $params = array_merge($params, [$userId, $userId, $userId]);
        $types .= "sss";
    }

    if ($status === 'all') {
        $sql .= " AND d.DA_TRANGTHAI != 0";
    } else {
        $sql .= " AND d.DA_TRANGTHAI = ?";
        $params[] = $status;
        $types .= "i";
    }

    // Filters
    if (!empty($keywords)) {
        $sql .= " AND d.DA_TEN LIKE ?";
        $params[] = "%$keywords%";
        $types .= "s";
    }
    if (!empty($from_date)) {
        $sql .= " AND DATE(d.DA_NGAYBATDAU) >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    if (!empty($to_date)) {
        $sql .= " AND DATE(d.DA_NGAYKETTHUC) <= ?";
        $params[] = $to_date;
        $types .= "s";
    }

    return ['sql' => $sql, 'params' => $params, 'types' => $types];
}

function getProjectsByStatus($conn, $status, $keywords = '', $from_date = '', $to_date = '', $limit = 0, $offset = 0)
{
    $base = buildProjectQuery($conn, $status, $keywords, $from_date, $to_date);
    if (!$base) return [];

    $sql = $base['sql'] . " ORDER BY d.DA_MA DESC";

    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $base['params'][] = $limit;
        $base['params'][] = $offset;
        $base['types'] .= "ii";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) return [];
    
    if (!empty($base['params'])) {
        $stmt->bind_param($base['types'], ...$base['params']);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function countProjectsByStatus($conn, $status, $keywords = '', $from_date = '', $to_date = '')
{
    $base = buildProjectQuery($conn, $status, $keywords, $from_date, $to_date);
    if (!$base) return 0;

    $countSql = preg_replace('/SELECT DISTINCT .+? FROM duan d/s', 'SELECT COUNT(DISTINCT d.DA_MA) as total FROM duan d', $base['sql']);

    $stmt = $conn->prepare($countSql);
    if ($stmt === false) return 0;

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

function getProjectsForTable($conn, $keywords = '', $from_date = '', $to_date = '', $page = 1, $perPage = 20)
{
    $offset = ($page - 1) * $perPage;
    $total = countProjectsByStatus($conn, 'all', $keywords, $from_date, $to_date);
    $data = getProjectsByStatus($conn, 'all', $keywords, $from_date, $to_date, $perPage, $offset);
    $pages = ($perPage > 0) ? (int)ceil($total / $perPage) : 1;

    return [
        'data' => $data,
        'total' => $total,
        'pages' => $pages,
        'page' => $page,
        'perPage' => $perPage
    ];
}

function getProjectsForKanban($conn, $status, $keywords = '', $from_date = '', $to_date = '', $limit = 0, $offset = 0)
{
    return getProjectsByStatus($conn, $status, $keywords, $from_date, $to_date, $limit, $offset);
}

function countProjectsForKanban($conn, $status, $keywords = '', $from_date = '', $to_date = '')
{
    return countProjectsByStatus($conn, $status, $keywords, $from_date, $to_date);
}

function renderPagination($page, $totalPages, $perPage, $total)
{
    if ($totalPages <= 1 && $total > 0) {
        $html = '<div class="cv-pagination">';
        $html .= '<div class="cv-pagination-info">Hiển thị tất cả <strong>' . $total . '</strong> dự án</div>';
        $html .= '<div class="cv-pagination-controls">';
        // Vẫn hiện select box
        $html .= '<div class="cv-pagination-perpage"><select class="cv-perpage-select" id="perPageSelect">';
        foreach ([20, 50, 100] as $opt) {
            $selected = ($opt == $perPage) ? ' selected' : '';
            $html .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '/trang</option>';
        }
        $html .= '</select></div></div></div>';
        return $html;
    }
    
    if ($total == 0) return '';

    $html = '<div class="cv-pagination">';
    $html .= '<div class="cv-pagination-info">Hiển thị trang <strong>' . $page . '</strong> / ' . $totalPages . ' (' . $total . ' dự án)</div>';
    $html .= '<div class="cv-pagination-controls">';

    $html .= '<div class="cv-pagination-perpage">';
    $html .= '<select class="cv-perpage-select" id="perPageSelect">';
    foreach ([20, 50, 100] as $opt) {
        $selected = ($opt == $perPage) ? ' selected' : '';
        $html .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '/trang</option>';
    }
    $html .= '</select></div>';

    if ($page > 1) {
        $html .= '<a href="#" class="cv-page-btn cv-page-prev" data-page="' . ($page - 1) . '"><i class="fal fa-chevron-left"></i> Trước</a>';
    } else {
        $html .= '<span class="cv-page-btn cv-page-prev disabled"><i class="fal fa-chevron-left"></i> Trước</span>';
    }

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

    if ($page < $totalPages) {
        $html .= '<a href="#" class="cv-page-btn cv-page-next" data-page="' . ($page + 1) . '">Sau <i class="fal fa-chevron-right"></i></a>';
    } else {
        $html .= '<span class="cv-page-btn cv-page-next disabled">Sau <i class="fal fa-chevron-right"></i></span>';
    }

    $html .= '</div></div>';
    return $html;
}
?>
