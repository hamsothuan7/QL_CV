-- ============================================================
-- add_indexes.sql
-- Thêm INDEX cho bảng danhsachcongviec để tối ưu query
-- 
-- Cách chạy: Import file này vào phpMyAdmin hoặc chạy qua CLI:
--   mysql -u root -p quanlyduan < add_indexes.sql
--
-- LƯU Ý: Chạy trên database production cần backup trước
-- ============================================================

-- Index cho cột trạng thái (WHERE DSCV_TRANGTHAI = ?)
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_dscv_trangthai` (`DSCV_TRANGTHAI`);

-- Index cho cột trạng thái hoạt động (WHERE dscv_trangthaihd = 1)
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_dscv_trangthaihd` (`dscv_trangthaihd`);

-- Index cho cột mã dự án (WHERE DA_MA = ? và JOIN)
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_dscv_da_ma` (`DA_MA`);

-- Index cho cột mã thành viên (WHERE TV_MA = ?)
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_dscv_tv_ma` (`TV_MA`);

-- Composite index cho query phổ biến: lọc theo trạng thái + hoạt động
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_status_active` (`DSCV_TRANGTHAI`, `dscv_trangthaihd`);

-- Index cho bảng binhluan_cv (tối ưu subquery đếm comment)
ALTER TABLE `binhluan_cv` ADD INDEX `idx_blcv_dscv_ma` (`DSCV_MA`);

-- Index cho bảng duan_thanhvien (tối ưu JOIN)
ALTER TABLE `duan_thanhvien` ADD INDEX `idx_dtv_da_ma` (`DA_MA`);
ALTER TABLE `duan_thanhvien` ADD INDEX `idx_dtv_tv_ma` (`TV_MA`);

-- Index cho ngày (lọc theo khoảng thời gian)
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_dscv_ngaybatdau` (`DSCV_NGAYBATDAU`);
ALTER TABLE `danhsachcongviec` ADD INDEX `idx_dscv_ngayketthuc` (`DSCV_NGAYKETTHUC`);
