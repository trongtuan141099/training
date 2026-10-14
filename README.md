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

- **Tone màu chủ đạo:** Xanh công nghiệp (Industrial Blue `#1E3A8A` / Royal Blue `#1D4ED8`) kết hợp Dark mode `#0B1120`, viền bo góc tinh tế, độ tương phản cao, chống chói dưới ánh sáng đèn xưởng.
- **Bố cục Chuẩn Tối Ưu (Scroll & Fixed Layout):**
  - **Header trên cùng (Fixed Top):** Cố định toàn màn hình với Logo DX Plastic Group, Vị trí chuyền SMT-01, Chuông thông báo, Chuyển đổi ngôn ngữ VI/EN, Dark/Light mode và Menu Demo Switcher.
  - **Sidebar bên trái (Fixed Full-Height):** Cố định bên trái kéo dài toàn bộ chiều cao từ Header xuống sát Footer, sở hữu thanh cuộn riêng khi mở menu con, không bị trôi khi cuộn nội dung chính.
  - **Thanh cuộn riêng cho Nội dung chính (Independent Main Scroll):** Người dùng thoải mái cuộn xem thông tin, bảng biểu, ảnh sơ đồ mà Header, Sidebar và Footer luôn cố định ở vị trí chuẩn.
  - **Footer dưới cùng (Fixed Bottom):** Cố định ở đáy màn hình, luôn hiển thị bản quyền, tiêu chuẩn Lean Dojo và trạng thái mạng LAN.

---

## 3. CÁC MODULE CHỨC NĂNG MỚI NÂNG CAO

### 1. Rà soát & Chuẩn hóa Font Tiếng Việt (Unicode UTF-8)
- 100% CSDL và mã nguồn được chuẩn hóa `utf8mb4_unicode_ci`, loại bỏ triệt để tình trạng lỗi font `??` hay sai lệch dấu thanh.
- Tích hợp chuẩn phông chữ **Inter** tối ưu hiển thị tiếng Việt có dấu rõ nét trên cả màn hình máy trạm PC và điện thoại/máy tính bảng cảm ứng.

### 2. Module Thiết kế Đề thi & Câu hỏi Tương tác Nâng cao (Exam Builder)
- File xử lý: `exam_builder.php`, `assets/js/interactive-canvas.js`.
- **Chèn hình ảnh trực tiếp vào câu hỏi:** Hỗ trợ chèn sơ đồ máy, ảnh bo mạch, ảnh lỗi cho mọi dạng câu hỏi (Trắc nghiệm đơn, trắc nghiệm nhiều đáp án, Đúng/Sai, Kéo thả, Tự luận).
- **Trình Biên tập Vùng Lỗi / Chỉ Vị trí Lỗi Trực quan (Hotspot & ROI Editor):**
  - Cho phép Admin/Trainer tải ảnh lên, trực tiếp dùng chuột kéo/khoanh nhiều vùng khác nhau (Multi-ROI) trên cùng một bức ảnh (Khoanh vùng chữ nhật Bounding Box hoặc Chỉ điểm Hotspot).
  - Tự động tính toán tọa độ phần trăm (%) chuẩn hóa tương thích mọi kích thước màn hình.
  - Cho phép đặt nhãn từng vùng lỗi (VD: *Lỗi dính chân IC 14-16*, *Mối hàn rỗ khí*, *Nút dừng E-STOP*).
- **Chức năng Bốc đề ngẫu nhiên (Randomized Question Drawer):**
  - Tự động bốc ngẫu nhiên số lượng câu hỏi từ ngân hàng đề thi hoặc ngân hàng chuyên môn theo cấu hình đề.
  - Chức năng Bốc thử 1 đề mẫu ngẫu nhiên (Live Preview) ngay tại Bàn thiết kế đề thi.
  - Tự động chia tỷ lệ điểm chuẩn 100% theo đúng số lượng câu hỏi được bốc.

### 3. Module Tài liệu SOP & Video Đào tạo Tương tác
- File xử lý: `course.php`, `sop_manager.php`.
- **Hiển thị Video Đào tạo SOP Trực quan:** Hỗ trợ phát video hướng dẫn thao tác tiêu chuẩn trực tiếp trên giao diện bài học.
- **Nhúng Hiệu ứng Tương tác Động trên Video (Pop-up Mini-quiz):**
  - Tự động tạm dừng video tại các mốc giây quy định để bật câu hỏi pop-up kiểm tra độ tập trung của người học.
  - Phản hồi tức thì: Nếu trả lời đúng cho phép tiếp tục xem video; nếu trả lời sai cung cấp chức năng tua lại 5 giây trước để xem lại quy chuẩn thao tác.
  - Công cụ quản lý điểm dừng tương tác trực quan cho Trainer trong `sop_manager.php`.

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
