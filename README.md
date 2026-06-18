# Hệ Thống Quản Lý Công Việc & Dự Án

Hệ thống Quản lý Công việc và Dự án là một ứng dụng web được xây dựng bằng PHP thuần (Native PHP) và MySQL, nhằm hỗ trợ việc quản lý, theo dõi và phân công công việc, dự án, phòng ban và nhân sự trong tổ chức.

## Các tính năng chính (Features)

*   **Đăng nhập hệ thống:** Quản lý truy cập bằng tài khoản (Mã cán bộ & Mật khẩu mã hóa MD5).
*   **Quản lý Dự án (Projects):** Thêm, sửa, xóa, và xem danh sách các dự án.
*   **Quản lý Công việc (Tasks):** 
    *   Phân công công việc, theo dõi tiến độ.
    *   Xem danh sách công việc chung và công việc theo từng cá nhân.
    *   Xem lịch công việc cá nhân.
*   **Quản lý Nhân sự & Phòng ban:**
    *   Quản lý phòng ban.
    *   Quản lý danh sách thành viên/cán bộ.
    *   Quản lý thông tin cá nhân.
*   **Quản lý Đơn vị phối hợp:** Theo dõi các đơn vị tham gia phối hợp trong dự án/công việc.
*   **Báo cáo & Xuất dữ liệu:** Hỗ trợ xuất dữ liệu ra file Excel thông qua thư viện `PhpSpreadsheet`.

## Công nghệ sử dụng (Tech Stack)

*   **Backend:** PHP (Native)
*   **Database:** MySQL (File SQL đính kèm: `quanlyduan.sql`)
*   **Frontend:** HTML, CSS, JavaScript (AJAX tích hợp)
*   **Thư viện bên thứ ba (Third-party Libraries):** `phpoffice/phpspreadsheet` (Quản lý qua Composer)

## Cấu trúc thư mục chính (Directory Structure)

```
TT_QLyCViec/
│
├── capquanly/              # Chứa toàn bộ các chức năng (CRUD, hiển thị) của hệ thống
│   ├── ajax/               # Các xử lý AJAX (thêm, cập nhật dữ liệu bất đồng bộ)
│   ├── assets/ & css/      # File CSS, hình ảnh, tài nguyên giao diện
│   ├── uploads/            # Thư mục lưu trữ file tải lên
│   ├── index.php           # Trang chủ (Dashboard) sau khi đăng nhập
│   └── ...                 # Các file giao diện quản lý (danhsachcv, themda, suatv...)
│
├── style/                  # Các file style / hình ảnh dùng ngoài trang đăng nhập
├── vendor/                 # Thư viện tải về bởi Composer (PhpSpreadsheet)
├── add_indexes.sql         # Script thêm index database tối ưu tốc độ truy vấn
├── composer.json           # Cấu hình cài đặt thư viện PHP
├── config.php              # Chứa thông tin kết nối Database
├── export.php              # Xử lý xuất file báo cáo công việc
├── exportda.php            # Xử lý xuất file báo cáo dự án
├── index.php               # Trang đăng nhập của hệ thống
└── quanlyduan.sql          # File Cơ sở dữ liệu (Database) để import
```

## Hướng dẫn Cài đặt & Sử dụng (Installation & Usage)

1.  **Môi trường yêu cầu:** XAMPP, WAMP hoặc bất kỳ phần mềm nào hỗ trợ PHP & MySQL.
2.  **Cài đặt thư viện:** Nếu chưa có thư mục `vendor/`, hãy mở Terminal/Command Prompt tại thư mục dự án và chạy lệnh:
    ```bash
    composer install
    ```
3.  **Cơ sở dữ liệu (Database):**
    *   Tạo một database mới có tên là `quanlyduan` trong phpMyAdmin (hoặc công cụ tương tự).
    *   Import file `quanlyduan.sql` vào database vừa tạo.
    *   (Tùy chọn) Chạy thêm file `add_indexes.sql` để tối ưu hóa truy vấn cho các bảng.
4.  **Cấu hình kết nối (Config):**
    *   Mở file `config.php` tại thư mục gốc và điều chỉnh lại thông tin kết nối nếu cần thiết (Mặc định: User `root`, Password rỗng).
5.  **Chạy ứng dụng:** Truy cập ứng dụng qua trình duyệt bằng đường dẫn tương ứng, ví dụ: `http://localhost/TT_QLyCViec/`.
