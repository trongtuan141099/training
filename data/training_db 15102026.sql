-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 15, 2026 lúc 01:56 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `training_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `skill_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL COMMENT '1: Cơ bản, 2: Nâng cao, 3: Chuyên gia',
  `prerequisite_course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `min_pass_score` decimal(5,2) DEFAULT 80.00,
  `description` text DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `courses`
--

INSERT INTO `courses` (`id`, `skill_id`, `title`, `level`, `prerequisite_course_id`, `min_pass_score`, `description`, `is_published`, `created_at`) VALUES
(1, 1, 'SMT-101: An toàn & Vận hành SMT Cơ bản', 1, NULL, 80.00, 'Khóa đào tạo cơ bản về an toàn tĩnh điện ESD, bảng điều khiển máy gắp chip, quy tắc khởi động và dừng khẩn cấp.', 1, '2026-10-14 16:16:20'),
(2, 1, 'SMT-201: Cài đặt Feeder & Xử lý Kẹt cuộn', 2, 1, 80.00, 'Khóa nâng cao về kỹ thuật lắp đặt Feeder đúng mã linh kiện, quy trình xử lý lỗi Tape Jam và thay đổi chương trình mounter.', 1, '2026-10-14 16:16:20'),
(3, 1, 'SMT-301: Hiệu chuẩn & Bảo trì máy SMT Chuyên gia', 3, 2, 85.00, 'Khóa chuyên gia về bảo trì cấp độ 2, hiệu chuẩn camera nhận dạng linh kiện, bảo dưỡng nozzle và đào tạo người mới.', 1, '2026-10-14 16:16:20'),
(4, 2, 'QC-101: Nhận diện Lỗi hàn & Bo mạch Cơ bản', 1, NULL, 80.00, 'Học cách nhận biết các lỗi thường gặp: Thiếu chì hàn, Chập chân linh kiện, Hàn nguội, Lệch linh kiện theo tiêu chuẩn nhà máy.', 1, '2026-10-14 16:16:20'),
(5, 2, 'QC-201: Vận hành Máy soi quang học tự động (AOI)', 2, 4, 80.00, 'Khóa nâng cao về lập trình tọa độ kiểm tra bo mạch và phân tích cảnh báo lỗi từ hệ thống quang học.', 1, '2026-10-14 16:16:20'),
(6, 3, '5S-101: Tiêu chuẩn 5S & Phòng chống Tĩnh điện ESD', 1, NULL, 80.00, 'Đào tạo bắt buộc cho tất cả công nhân mới: Sàng lọc, Sắp xếp, Sạch sẽ, Săn sóc, Sẵn sàng và quy tắc nối đất chống giật/chống hỏng vi mạch.', 1, '2026-10-14 16:16:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `course_materials`
--

CREATE TABLE `course_materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `material_type` enum('PDF','VIDEO','IMAGE_SOP','HTML') NOT NULL,
  `file_path` text NOT NULL,
  `content_text` longtext DEFAULT NULL,
  `interactive_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interactive_config`)),
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `course_materials`
--

INSERT INTO `course_materials` (`id`, `course_id`, `title`, `material_type`, `file_path`, `content_text`, `interactive_config`, `sort_order`, `created_at`) VALUES
(4, 4, 'SOP-QC-001: Bảng màu và hình ảnh mẫu lỗi mối hàn IPC', 'IMAGE_SOP', 'assets/sop_qc_defects.svg', 'Hình ảnh thực tế so sánh mối hàn chuẩn và mối hàn bị rỗ khí/ngắn mạch.', NULL, 1, '2026-10-14 16:16:20'),
(5, 1, 'Video Đào tạo Thao tác Chuẩn & Xử lý sự cố SMT (Tương tác Pop-up)', 'VIDEO', 'uploads/materials/sample_sop_video.mp4', 'Video huấn luyện trực quan quy trình thao tác máy SMT thực tế có lồng ghép các câu hỏi tương tác kiểm tra độ tập trung.', '[{\"id\":\"quiz_1\",\"timestamp_sec\":3,\"question\":\"Theo quy chuẩn An toàn SOP-SMT-001, bước kiểm tra đầu tiên trước khi bật công tắc nguồn là gì?\",\"options\":[{\"id\":\"A\",\"text\":\"Đeo vòng tay khử tĩnh điện ESD có dây nối đất\"},{\"id\":\"B\",\"text\":\"Mở ngay cửa kính bảo vệ máy\"},{\"id\":\"C\",\"text\":\"Tăng tốc độ băng chuyền lên tối đa\"}],\"correct\":\"A\",\"explanation\":\"Chính xác! Công nhân bắt buộc phải kiểm tra và đeo vòng tay khử tĩnh điện ESD trước tiên để tránh phóng điện làm hỏng vi mạch.\"},{\"id\":\"quiz_2\",\"timestamp_sec\":7,\"question\":\"Khi phát hiện sự cố linh kiện bị kẹt băng nạp (Tape Jam), thao tác tức thời nào là ĐÚNG?\",\"options\":[{\"id\":\"A\",\"text\":\"Thò tay vào gắp cuộn băng khi máy vẫn đang chạy\"},{\"id\":\"B\",\"text\":\"Nhấn nút PAUSE hoặc nút DỪNG KHẨN CẤP trước khi can thiệp\"},{\"id\":\"C\",\"text\":\"Tắt cầu dao điện tòa nhà\"}],\"correct\":\"B\",\"explanation\":\"Rất tốt! Luôn luôn dừng máy an toàn bằng nút PAUSE hoặc E-STOP trước khi mở nắp can thiệp kỹ thuật.\"}]', 3, '2026-10-14 16:20:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exams`
--

CREATE TABLE `exams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_minutes` int(11) DEFAULT 20,
  `total_score` decimal(5,2) DEFAULT 100.00,
  `max_attempts` int(11) DEFAULT 5,
  `is_randomized` tinyint(1) DEFAULT 0,
  `random_question_count` int(11) DEFAULT 5,
  `question_bank_mode` varchar(50) DEFAULT 'EXAM_POOL',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exams`
--

INSERT INTO `exams` (`id`, `course_id`, `title`, `duration_minutes`, `total_score`, `max_attempts`, `is_randomized`, `random_question_count`, `question_bank_mode`, `is_active`, `created_at`) VALUES
(1, 1, 'Bài thi Đánh giá Năng lực SMT - Cấp độ 1 (Cơ bản)', 15, 100.00, 5, 1, 4, 'EXAM_POOL', 1, '2026-10-14 16:16:20'),
(2, 2, 'Bài thi Đánh giá Năng lực SMT - Cấp độ 2 (Nâng cao)', 20, 100.00, 5, 0, 5, 'EXAM_POOL', 1, '2026-10-14 16:16:20'),
(3, 4, 'Bài thi Đánh giá Nhận diện Lỗi Bo Mạch QC - Cấp độ 1', 15, 100.00, 5, 0, 5, 'EXAM_POOL', 1, '2026-10-14 16:16:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_answers`
--

CREATE TABLE `exam_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attempt_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `user_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`user_response`)),
  `is_correct` tinyint(1) DEFAULT NULL,
  `awarded_score` decimal(5,2) DEFAULT 0.00,
  `trainer_comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exam_answers`
--

INSERT INTO `exam_answers` (`id`, `attempt_id`, `question_id`, `user_response`, `is_correct`, `awarded_score`, `trainer_comment`, `created_at`) VALUES
(1, 101, 1, '{\"selected\":\"A\"}', 0, 0.00, 'Đáp án chưa chính xác.', '2026-10-14 16:17:20'),
(2, 101, 2, '{\"selected\":[\"A\",\"B\"]}', 0, 0.00, 'Chưa chọn đúng hoặc thiếu phương án.', '2026-10-14 16:17:20'),
(3, 101, 3, '{\"selected\":\"TRUE\"}', 0, 0.00, 'Đáp án chưa chính xác.', '2026-10-14 16:17:20'),
(4, 101, 4, '{\"submitted_order\":[\"step_test\",\"step_air\",\"step_load\",\"step_power\"]}', 0, 0.00, 'Thứ tự các bước quy trình chưa chính xác.', '2026-10-14 16:17:21'),
(5, 101, 5, '{\"clicked_x_pct\":75.09,\"clicked_y_pct\":33.26}', 1, 20.00, 'Chính xác! Vị trí chạm lệch 1.4% (trong phạm vi cho phép 8.0%).', '2026-10-14 16:17:21'),
(6, 102, 1, '{\"selected\":\"B\"}', 1, 20.00, 'Đáp án chính xác.', '2026-10-14 16:24:56'),
(7, 102, 2, '{\"selected\":[\"A\",\"B\",\"C\"]}', 1, 20.00, 'Đã chọn đầy đủ các đáp án đúng.', '2026-10-14 16:24:56'),
(8, 102, 3, '{\"selected\":\"FALSE\"}', 1, 20.00, 'Đáp án chính xác.', '2026-10-14 16:24:56'),
(9, 102, 4, '{\"submitted_order\":[\"step_air\",\"step_power\",\"step_load\",\"step_test\"]}', 1, 20.00, 'Hoàn hảo! Thứ tự các bước chuẩn 100% theo quy chuẩn SOP.', '2026-10-14 16:24:56'),
(10, 102, 5, '{\"clicked_x_pct\":74.5,\"clicked_y_pct\":32}', 1, 20.00, 'Chính xác! Vị trí chạm lệch 0.0% (trong phạm vi cho phép 8.0%).', '2026-10-14 16:24:56'),
(11, 103, 1, '{\"selected\":\"C\"}', 0, 0.00, 'Đáp án chưa chính xác.', '2026-10-14 16:35:28'),
(12, 103, 2, '{\"selected\":[\"A\",\"B\",\"C\",\"D\"]}', 0, 0.00, 'Chưa chọn đúng hoặc thiếu phương án.', '2026-10-14 16:35:28'),
(13, 103, 4, '{\"submitted_order\":[\"step_air\",\"step_load\",\"step_test\",\"step_power\"]}', 0, 0.00, 'Thứ tự các bước quy trình chưa chính xác.', '2026-10-14 16:35:28'),
(14, 103, 5, '{\"clicked_x_pct\":75.86,\"clicked_y_pct\":32.64}', 1, 20.00, 'Chính xác! Vị trí chạm lệch 1.5% (trong phạm vi cho phép 8.0%).', '2026-10-14 16:35:28'),
(15, 104, 6, '{\"clicked_x_pct\":49.19,\"clicked_y_pct\":45.85,\"drawn_polygon\":[{\"x\":49.19,\"y\":45.85}]}', 1, 50.00, 'Chính xác! Bạn đã khoanh đúng vị trí linh kiện bị kênh chân/lỗi hàn.', '2026-10-14 16:39:16'),
(16, 104, 7, '{\"text\":\"đúng hay sai\"}', 1, 50.00, 'đúng', '2026-10-14 16:39:16'),
(17, 105, 8, '{\"clicked_x_pct\":49.1,\"clicked_y_pct\":44.13}', 0, 0.00, 'Chưa chính xác. Điểm chạm lệch 36.1% so với vị trí linh kiện chuẩn.', '2026-10-14 16:41:41'),
(18, 105, 9, '{\"selected\":\"B\"}', 1, 50.00, 'Đáp án chính xác.', '2026-10-14 16:41:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_attempts`
--

CREATE TABLE `exam_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `score_obtained` decimal(5,2) DEFAULT 0.00,
  `passed` tinyint(1) DEFAULT 0,
  `status` enum('IN_PROGRESS','PENDING_EVALUATION','COMPLETED') DEFAULT 'IN_PROGRESS',
  `graded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `trainer_feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `user_id`, `exam_id`, `attempt_number`, `start_time`, `end_time`, `score_obtained`, `passed`, `status`, `graded_by`, `trainer_feedback`, `created_at`) VALUES
(100, 4, 1, 1, '2026-02-10 14:00:00', '2026-02-10 14:25:00', 90.00, 1, 'COMPLETED', 2, NULL, '2026-10-14 16:16:21'),
(101, 1, 1, 1, '2026-10-14 23:17:20', '2026-10-14 23:17:21', 20.00, 0, 'COMPLETED', NULL, NULL, '2026-10-14 16:17:20'),
(102, 3, 1, 1, '2026-10-14 23:24:56', '2026-10-14 23:24:56', 100.00, 1, 'COMPLETED', NULL, NULL, '2026-10-14 16:24:56'),
(103, 2, 1, 1, '2026-10-14 23:35:28', '2026-10-14 23:35:28', 20.00, 0, 'COMPLETED', NULL, NULL, '2026-10-14 16:35:28'),
(104, 3, 2, 1, '2026-10-14 23:39:16', '2026-10-14 23:39:16', 100.00, 1, 'COMPLETED', 2, 'đúng', '2026-10-14 16:39:16'),
(105, 1, 3, 1, '2026-10-14 23:41:41', '2026-10-14 23:41:41', 50.00, 0, 'COMPLETED', NULL, NULL, '2026-10-14 16:41:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `questions`
--

CREATE TABLE `questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `question_type` enum('SINGLE_CHOICE','MULTI_CHOICE','TRUE_FALSE','ESSAY','HOTSPOT_PINPOINT','POLYGON_DEFECT','DRAG_DROP_SEQUENCE') NOT NULL,
  `prompt` text NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `answer_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answer_config`)),
  `score` decimal(5,2) DEFAULT 20.00,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_type`, `prompt`, `media_url`, `answer_config`, `score`, `sort_order`) VALUES
(1, 1, 'SINGLE_CHOICE', 'Trước khi nhấn nút START khởi động máy gắp chip SMT, công nhân vận hành BẮT BUỘC phải thực hiện thao tác an toàn nào sau đây?', NULL, '{\"options\": [{\"id\": \"A\", \"text\": \"Đeo găng tay cách nhiệt dày\"}, {\"id\": \"B\", \"text\": \"Đeo vòng tay khử tĩnh điện ESD có gắn dây nối đất và đóng kín cửa bảo vệ máy\"}, {\"id\": \"C\", \"text\": \"Tắt hoàn toàn van cấp khí nén\"}, {\"id\": \"D\", \"text\": \"Mở nắp che cơ cấu nhặt linh kiện để quan sát bằng mắt thường\"}], \"correct\": [\"B\"]}', 20.00, 1),
(2, 1, 'MULTI_CHOICE', 'Những trang bị bảo hộ (PPE) nào sau đây là BẮT BUỘC khi bước vào khu vực dây chuyền SMT? (Chọn tất cả các đáp án đúng)', NULL, '{\"options\": [{\"id\": \"A\", \"text\": \"Áo khoác chống tĩnh điện ESD\"}, {\"id\": \"B\", \"text\": \"Giày bảo hộ tiêu chuẩn ESD có dải tán điện\"}, {\"id\": \"C\", \"text\": \"Mũ trùm tóc công nghiệp\"}, {\"id\": \"D\", \"text\": \"Kính râm chống chói\"}], \"correct\": [\"A\", \"B\", \"C\"]}', 20.00, 2),
(3, 1, 'TRUE_FALSE', 'Khi máy đang vận hành ở chế độ tự động (AUTO RUN), nếu linh kiện bị rơi trên băng chuyền, công nhân ĐƯỢC PHÉP thò tay vào gắp ra ngay mà không cần dừng máy.', NULL, '{\"options\": [{\"id\": \"TRUE\", \"text\": \"ĐÚNG (Cho phép nếu thao tác nhanh)\"}, {\"id\": \"FALSE\", \"text\": \"SAI (Tuyệt đối cấm - Phải tạm dừng máy trước khi can thiệp)\"}], \"correct\": [\"FALSE\"]}', 20.00, 3),
(4, 1, 'DRAG_DROP_SEQUENCE', 'Hãy kéo thả và sắp xếp 4 bước dưới đây theo đúng trình tự khởi động máy SMT chuẩn:', NULL, '{\"items\": [{\"id\": \"step_air\", \"text\": \"1. Mở van khí nén chính và kiểm tra đồng hồ áp suất (đạt >= 0.5 MPa)\"}, {\"id\": \"step_power\", \"text\": \"2. Bật Aptomat nguồn tổng và xoay chìa khóa MAIN POWER sang vị trí ON\"}, {\"id\": \"step_load\", \"text\": \"3. Đăng nhập hệ điều hành máy và Load File chương trình sản xuất của Model cần chạy\"}, {\"id\": \"step_test\", \"text\": \"4. Chạy kiểm tra không tải (Dry Run) 1 chu trình để xác nhận chuyển động ổn định\"}], \"correct_order\": [\"step_air\", \"step_power\", \"step_load\", \"step_test\"]}', 20.00, 4),
(5, 1, 'HOTSPOT_PINPOINT', 'Quan sát hình ảnh Bảng điều khiển máy SMT: Hãy CHẠM NGÓN TAY CHÍNH XÁC vào NÚT DỪNG KHẨN CẤP (EMERGENCY STOP - Nút tròn màu đỏ lớn).', 'assets/machine_panel.svg', '{\"target_x_pct\": 74.5, \"target_y_pct\": 32.0, \"tolerance_radius_pct\": 8.0, \"hint\": \"Nút dạng nấm màu đỏ ở góc trên bên phải bảng điều khiển\"}', 20.00, 5),
(6, 2, 'POLYGON_DEFECT', 'Quan sát hình ảnh bo mạch SMT: Dùng ngón tay KHOANH VÙNG vị trí linh kiện IC bị KÊNH CHÂN / LỆCH VỊ TRÍ HÀN (Bridging / Misalignment).', 'assets/pcb_defect.svg', '{\"target_polygon\": [{\"x\": 41.0, \"y\": 33.0}, {\"x\": 59.0, \"y\": 33.0}, {\"x\": 59.0, \"y\": 55.0}, {\"x\": 41.0, \"y\": 55.0}], \"center_x_pct\": 50.0, \"center_y_pct\": 44.0, \"tolerance_radius_pct\": 10.0}', 50.00, 1),
(7, 2, 'ESSAY', 'Khi máy mounter cảnh báo lỗi \"TAPE FEEDER JAM #14\" (kẹt cuộn băng nạp linh kiện), anh/chị hãy trình bày các bước xử lý an toàn để không làm rách cuộn linh kiện và không gây xước đầu Nozzle.', NULL, '{\"min_words\": 20, \"rubric\": \"Cần nêu đủ: 1. Bấm Pause tạm dừng; 2. Tháo Feeder ra ngoài bàn bảo trì; 3. Dùng nhíp gắp phế liệu; 4. Test bước nạp tay trước khi gắn lại.\"}', 50.00, 2),
(8, 3, 'HOTSPOT_PINPOINT', 'Quan sát hình ảnh bo mạch: Hãy CHẠM NGÓN TAY vào điểm có MỐI HÀN NGUỘI / RỖ KHÍ (Cold Solder Joint).', 'assets/pcb_defect.svg', '{\"target_x_pct\": 22.0, \"target_y_pct\": 68.0, \"tolerance_radius_pct\": 7.5}', 50.00, 1),
(9, 3, 'SINGLE_CHOICE', 'Lỗi nào sau đây bị xếp vào mức độ NGHIÊM TRỌNG (CRITICAL DEFECT) buộc phải dừng toàn bộ chuyền sản xuất?', NULL, '{\"options\": [{\"id\": \"A\", \"text\": \"Bụi bẩn nhẹ trên vỏ hộp carton\"}, {\"id\": \"B\", \"text\": \"Ngắn mạch (Chập nguồn) giữa chân VCC và GND của vi điều khiển\"}, {\"id\": \"C\", \"text\": \"Tem nhãn mã vạch bị dán hơi lệch 1mm\"}, {\"id\": \"D\", \"text\": \"Màu sắc điện trở hơi nhạt\"}], \"correct\": [\"B\"]}', 50.00, 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `skills`
--

CREATE TABLE `skills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `skills`
--

INSERT INTO `skills` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'SKILL_SMT', 'Vận hành Máy gắp chip SMT tự động', 'Kỹ năng vận hành, nạp cuộn linh kiện, căn chỉnh feeder và xử lý sự cố máy SMT tốc độ cao.', '2026-10-14 16:16:20'),
(2, 'SKILL_QC', 'Kiểm tra Ngoại quan Bo mạch (AOI & QC)', 'Kỹ năng phát hiện lỗi hàn, linh kiện kênh chân, đoản mạch và sai cực tính theo chuẩn IPC-A-610.', '2026-10-14 16:16:20'),
(3, 'SKILL_5S', 'An toàn lao động & Tiêu chuẩn 5S Nhà máy', 'Quy tắc trang bị bảo hộ, an toàn tĩnh điện (ESD), vệ sinh nhà xưởng và phản ứng khi có sự cố.', '2026-10-14 16:16:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_code` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ADMIN','TRAINER','WORKER') DEFAULT 'WORKER',
  `department` varchar(100) DEFAULT NULL,
  `line_station` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `employee_code`, `full_name`, `password_hash`, `role`, `department`, `line_station`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ADM001', 'Admin Quản Trị Hệ Thống', '$2y$10$wtS9CuOwsaWCOEY2cagDuOnflcbAFQ/433f9b29zeNdC8Cy5KZDBq', 'ADMIN', 'Phòng Kỹ thuật & Đào tạo', 'HQ-Office', 1, '2026-10-14 16:16:20', '2026-10-14 16:16:20'),
(2, 'TRN001', 'Phạm Minh Trainer (Giảng Viên)', '$2y$10$piSzaG8ty36crA.oRq2a2Og6rjTTNOLYoinFvhs.70nHLwMPBbDQ.', 'TRAINER', 'Trung tâm Đào tạo Dojo', 'Dojo-Room-01', 1, '2026-10-14 16:16:20', '2026-10-14 16:16:20'),
(3, 'WRK001', 'Nguyễn Văn An (Công Nhân 1)', '$2y$10$8smSM.fWiygeilSEZYJN4.XDJD/OP8.vYEJgFAq.jHtrpWj/2Q/d6', 'WORKER', 'Xưởng SMT - Dây chuyền 01', 'Line SMT-01 (Mounter)', 1, '2026-10-14 16:16:20', '2026-10-14 16:16:20'),
(4, 'WRK002', 'Trần Thị Bình (Công Nhân 2)', '$2y$10$8smSM.fWiygeilSEZYJN4.XDJD/OP8.vYEJgFAq.jHtrpWj/2Q/d6', 'WORKER', 'Xưởng Lắp Ráp - Dây chuyền 02', 'Line Assy-02 (Soldering)', 1, '2026-10-14 16:16:20', '2026-10-14 16:16:20'),
(5, 'WRK003', 'Lê Hoàng Cường (Công Nhân 3)', '$2y$10$8smSM.fWiygeilSEZYJN4.XDJD/OP8.vYEJgFAq.jHtrpWj/2Q/d6', 'WORKER', 'Xưởng Kiểm Định QC', 'Station AOI-QC', 1, '2026-10-14 16:16:20', '2026-10-14 16:16:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_skills`
--

CREATE TABLE `user_skills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `skill_id` bigint(20) UNSIGNED NOT NULL,
  `current_level` tinyint(3) UNSIGNED DEFAULT 0 COMMENT '0: Chưa đạt, 1: Cơ bản (I), 2: Vận hành độc lập (L), 3: Nâng cao/Đào tạo (U/O)',
  `certified_at` datetime DEFAULT NULL,
  `certified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_skills`
--

INSERT INTO `user_skills` (`id`, `user_id`, `skill_id`, `current_level`, `certified_at`, `certified_by`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 2, '2026-10-14 23:39:47', 2, '2026-10-14 16:16:21', '2026-10-14 16:39:47'),
(2, 3, 2, 0, NULL, NULL, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(3, 3, 3, 1, '2026-01-15 09:00:00', 2, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(4, 4, 1, 1, '2026-02-10 14:30:00', 2, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(5, 4, 2, 0, NULL, NULL, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(6, 4, 3, 2, '2026-02-15 10:00:00', 2, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(7, 5, 1, 2, '2026-03-01 11:00:00', 2, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(8, 5, 2, 2, '2026-03-05 16:00:00', 2, '2026-10-14 16:16:21', '2026-10-14 16:16:21'),
(9, 5, 3, 3, '2026-03-10 08:30:00', 2, '2026-10-14 16:16:21', '2026-10-14 16:16:21');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `prerequisite_course_id` (`prerequisite_course_id`);

--
-- Chỉ mục cho bảng `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Chỉ mục cho bảng `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `graded_by` (`graded_by`);

--
-- Chỉ mục cho bảng `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Chỉ mục cho bảng `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`);

--
-- Chỉ mục cho bảng `user_skills`
--
ALTER TABLE `user_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_skill` (`user_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `certified_by` (`certified_by`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `exams`
--
ALTER TABLE `exams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `exam_answers`
--
ALTER TABLE `exam_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `exam_attempts`
--
ALTER TABLE `exam_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT cho bảng `questions`
--
ALTER TABLE `questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `skills`
--
ALTER TABLE `skills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`prerequisite_course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD CONSTRAINT `exam_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD CONSTRAINT `exam_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_attempts_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_attempts_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_skills`
--
ALTER TABLE `user_skills`
  ADD CONSTRAINT `user_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_skills_ibfk_3` FOREIGN KEY (`certified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
