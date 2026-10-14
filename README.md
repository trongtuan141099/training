# HỆ THỐNG QUẢN LÝ ĐÀO TẠO & ĐÁNH GIÁ NĂNG LỰC CÔNG NHÂN (SKILLMAP 4.0)

Hệ thống Web Full-stack chuyên biệt cho môi trường nhà máy sản xuất (Factory Floor / On-Premise LAN), tối ưu hóa trải nghiệm trên thiết bị di động (Mobile-First, Touch-friendly), hỗ trợ câu hỏi tương tác trực quan và tự động cập nhật Ma trận Năng lực (Skillmap).

---

## 1. THÔNG TIN TRUY CẬP HỆ THỐNG

- **Đường dẫn URL cục bộ**: [http://localhost/training/](http://localhost/training/)
- **Môi trường hoạt động**: Apache / PHP 8.0+ / MariaDB (XAMPP On-Premise)
- **Cơ sở dữ liệu**: `training_db`

### Tài khoản Demo (Hỗ trợ Đăng nhập 1 Chạm tại trang Login):

| Vai trò | Mã NV | Mật khẩu mẫu | Trạng thái ban đầu |
| :--- | :--- | :--- | :--- |
| **Worker 1 (Công nhân 1)** | `WRK001` | `worker123` | **Level 0 SMT**: Khóa SMT-101 mở khóa, Khóa SMT-201 **BỊ KHÓA** (do chưa qua cấp cơ bản). |
| **Worker 2 (Công nhân 2)** | `WRK002` | `worker123` | **Level 1 SMT**: Đã đạt SMT-101 (90 điểm) -> Khóa SMT-201 **ĐÃ MỞ KHÓA**. |
| **Trainer (Giảng viên)** | `TRN001` | `trainer123` | Có quyền vào **Exam Builder**, **SOP Manager**, **Bàn chấm bài Tự luận**. |
| **Admin (Quản trị)** | `ADM001` | `admin123` | Toàn quyền quản trị, theo dõi KPI đào tạo, reset dữ liệu kiểm thử. |

---

## 2. GIAO DIỆN & UI/UX CHUẨN NHÀ MÁY SẢN XUẤT (NEW)

- **Tone màu chủ đạo:** Xanh công nghiệp (Industrial Blue `#1E3A8A` / Dark Sidebar `#0B1120` & `#0F172A`) kết hợp viền thẻ card bo góc tinh tế, độ tương phản cao, chống chói dưới ánh sáng đèn xưởng.
- **Bố cục chuẩn:**
  - **Sidebar bên trái (Dark Industrial):** Phân nhóm chức năng rõ ràng:
    - *Tổng quan:* Lộ trình Đào tạo, Ma trận Kỹ năng (Skillmap).
    - *Quản lý Sản xuất & Đào tạo:* Thiết kế & Tạo bài thi (Exam Builder), Quản lý tài liệu SOP.
    - *Quản lý Chất lượng & Đánh giá:* Bàn chấm bài Tự luận, Lịch sử làm bài.
    - *Hệ thống & Báo cáo:* Quản trị Xưởng & KPI.
  - **Header trên cùng:**
    - Nút Toggle mở/đóng Sidebar trên điện thoại di động (Drawer cảm ứng).
    - Vị trí phân xưởng hiện tại (*Line SMT-01 - Panasonic NPM-D3*).
    - Bộ chuyển đổi ngôn ngữ nhanh **VI 🇻🇳 / EN 🇬🇧**.
    - Chuông thông báo có số đếm bài thi tự luận đang chờ chấm.
    - Tài khoản cá nhân với Role badge và Menu chuyển đổi vai trò Demo tức thì.

---

## 3. CÁC MODULE CHỨC NĂNG CỐT LÕI

### 1. Module Đa ngôn ngữ (Localization Engine - VI/EN)
- File xử lý: `includes/lang.php`.
- Hỗ trợ chuyển đổi ngôn ngữ tức thời giữa Tiếng Việt và Tiếng Anh trên toàn bộ hệ thống bằng 1 cú click trên thanh Header.
- Đảm bảo 100% tiếng Việt có dấu chuẩn Unicode UTF-8, không lỗi font.

### 2. Module Thiết kế & Tạo bài thi Mới (Exam Builder)
- File xử lý: `exam_builder.php`.
- Cho phép Admin và Trainer:
  - Khởi tạo khóa học và bài kiểm tra phân cấp: **Cơ bản (Cấp 1) -> Nâng cao (Cấp 2) -> Chuyên gia (Cấp 3)**.
  - Thiết lập logic chặn cấp độ: Chọn khóa học cấp trước (`prerequisite_course_id`) và điểm chuẩn đạt (`min_pass_score`, ví dụ: 80%).
  - Thêm đa dạng câu hỏi:
    - Trắc nghiệm đơn, trắc nghiệm nhiều đáp án, Đúng/Sai.
    - Tự luận (Essay) kèm tiêu chuẩn chấm (Rubric).
    - **Tương tác trực quan:** Chạm điểm linh kiện (Hotspot Pinpoint) và Khoanh vùng lỗi (Polygon Defect) có hỗ trợ tải ảnh máy thực tế hoặc dùng ảnh có sẵn trong hệ thống.
    - Kéo thả thứ tự quy trình chuẩn (Drag & Drop Sequence).

### 3. Module Thiết lập & Quản lý Tài liệu SOP (SOP Hub)
- File xử lý: `sop_manager.php`.
- Cho phép tải lên và quản lý tài liệu hướng dẫn công việc (SOP):
  - Định dạng: PDF quy chuẩn, Video thao tác (MP4/WebM), Hình ảnh sơ đồ SOP (SVG/PNG/JPG).
  - Tự động lưu vào thư mục `uploads/materials/`.
  - Liên kết trực tiếp với các bài học và bài thi để công nhân xem trước khi làm bài.
  - Xem trước file gốc hoặc xóa tài liệu nhanh chóng.

### 4. Lộ trình Đào tạo & Khóa Tiền đề (Prerequisite Workflow)
- Khóa học cấp sau chỉ mở khóa khi công nhân đã thi đỗ (&ge; 80%) ở cấp trước.
- Hiển thị trực quan trạng thái `MỞ KHÓA`, `BỊ KHÓA` (kèm lý do và điểm số đạt được), và `ĐÃ ĐẠT`.

### 5. Tự động Cập nhật Ma trận Năng lực (Skillmap Matrix)
- Đánh giá theo chuẩn Lean Dojo (Level 0 -> 1 -> 2 -> 3 / I-L-U-O):
  - **Level 0**: Chưa đào tạo.
  - **Level 1 (I)**: Có thể thao tác dưới sự kèm cặp/giám sát.
  - **Level 2 (L)**: Độc lập thao tác chuẩn SOP.
  - **Level 3 (U/O)**: Bậc thầy công đoạn, có thể bảo trì và đào tạo người khác.
- Chế độ xem cá nhân và Ma trận tổng hợp toàn nhà máy (Heatmap).

---

## 4. CẤU TRÚC THƯ MỤC HỆ THỐNG

```
d:\xampp\htdocs\training\
├── config/
│   └── db.php                  # Kết nối PDO MySQL/MariaDB (UTF-8 Collation)
├── includes/
│   ├── auth.php                # Quản lý Phiên & Phân quyền Role-based
│   ├── lang.php                # Bộ từ điển & Công cụ Đa ngôn ngữ (VI/EN)
│   ├── skillmap.php            # Logic Prerequisite & Chấm điểm tự động
│   ├── header.php              # Layout Industrial Blue Sidebar & Top Header
│   └── footer.php              # Footer nhà máy & Script Drawer cảm ứng
├── uploads/
│   ├── materials/              # Thư mục lưu file tài liệu SOP tải lên
│   └── questions/              # Thư mục lưu ảnh câu hỏi tương tác
├── assets/
│   ├── machine_panel.svg       # Bảng điều khiển SMT phục vụ câu hỏi Hotspot
│   ├── pcb_defect.svg          # Bo mạch PCB phục vụ câu hỏi Khoanh vùng Polygon
│   ├── sop_smt_safety.svg      # Áp phích An toàn SMT
│   └── js/
│       ├── interactive-canvas.js # Thư viện Canvas cảm ứng cho Hotspot & Polygon
│       └── drag-drop.js          # Thư viện sắp xếp thứ tự các bước quy trình
├── index.php                   # Trang chủ & Lộ trình đào tạo (Roadmap)
├── login.php                   # Đăng nhập chuẩn & Quick Switch Demo
├── logout.php                  # Đăng xuất
├── course.php                  # Giao diện học viên: Xem SOP & Bắt đầu thi
├── exam.php                    # Phòng thi tương tác (Timer, Canvas, Drag-drop)
├── exam_builder.php            # [MỚI] Module Thiết kế & Tạo bài thi
├── sop_manager.php             # [MỚI] Module Quản lý & Tải lên tài liệu SOP
├── exam_submit.php             # Xử lý nộp bài thi
├── exam_result.php             # Kết quả bài thi & Tự động nâng cấp Skillmap
├── skillmap.php                # Ma trận kỹ năng cá nhân & Toàn phân xưởng
├── history.php                 # Lịch sử thi & Đánh giá năng lực
├── trainer.php                 # Bàn chấm thi tự luận của Giảng viên
├── admin.php                   # Trung tâm Quản trị xưởng & KPI
├── schema.sql                  # CSDL MySQL 9 bảng
└── seed.sql                    # Dữ liệu khởi tạo mẫu
```
