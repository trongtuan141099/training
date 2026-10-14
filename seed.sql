-- Seed Data for Skillmap System

USE training_db;

-- 1. Insert Users
-- Passwords:
-- ADM001 -> admin123
-- TRN001 -> trainer123
-- WRK001 -> worker123
-- WRK002 -> worker123
-- WRK003 -> worker123
INSERT INTO users (employee_code, full_name, password_hash, role, department, line_station) VALUES
('ADM001', 'Admin Quản Trị Hệ Thống', '$2y$10$wtS9CuOwsaWCOEY2cagDuOnflcbAFQ/433f9b29zeNdC8Cy5KZDBq', 'ADMIN', 'Phòng Kỹ thuật & Đào tạo', 'HQ-Office'),
('TRN001', 'Phạm Minh Trainer (Giảng Viên)', '$2y$10$piSzaG8ty36crA.oRq2a2Og6rjTTNOLYoinFvhs.70nHLwMPBbDQ.', 'TRAINER', 'Trung tâm Đào tạo Dojo', 'Dojo-Room-01'),
('WRK001', 'Nguyễn Văn An (Công Nhân 1)', '$2y$10$8smSM.fWiygeilSEZYJN4.XDJD/OP8.vYEJgFAq.jHtrpWj/2Q/d6', 'WORKER', 'Xưởng SMT - Dây chuyền 01', 'Line SMT-01 (Mounter)'),
('WRK002', 'Trần Thị Bình (Công Nhân 2)', '$2y$10$8smSM.fWiygeilSEZYJN4.XDJD/OP8.vYEJgFAq.jHtrpWj/2Q/d6', 'WORKER', 'Xưởng Lắp Ráp - Dây chuyền 02', 'Line Assy-02 (Soldering)'),
('WRK003', 'Lê Hoàng Cường (Công Nhân 3)', '$2y$10$8smSM.fWiygeilSEZYJN4.XDJD/OP8.vYEJgFAq.jHtrpWj/2Q/d6', 'WORKER', 'Xưởng Kiểm Định QC', 'Station AOI-QC');

-- 2. Insert Skills
INSERT INTO skills (id, code, name, description) VALUES
(1, 'SKILL_SMT', 'Vận hành Máy gắp chip SMT tự động', 'Kỹ năng vận hành, nạp cuộn linh kiện, căn chỉnh feeder và xử lý sự cố máy SMT tốc độ cao.'),
(2, 'SKILL_QC', 'Kiểm tra Ngoại quan Bo mạch (AOI & QC)', 'Kỹ năng phát hiện lỗi hàn, linh kiện kênh chân, đoản mạch và sai cực tính theo chuẩn IPC-A-610.'),
(3, 'SKILL_5S', 'An toàn lao động & Tiêu chuẩn 5S Nhà máy', 'Quy tắc trang bị bảo hộ, an toàn tĩnh điện (ESD), vệ sinh nhà xưởng và phản ứng khi có sự cố.');

-- 3. Insert Courses (Prerequisite Hierarchy)
-- Skill 1: SMT
-- Level 1: Cơ bản (Prerequisite: NULL)
-- Level 2: Nâng cao (Prerequisite: Course 1)
-- Level 3: Chuyên gia (Prerequisite: Course 2)
INSERT INTO courses (id, skill_id, title, level, prerequisite_course_id, min_pass_score, description, is_published) VALUES
(1, 1, 'SMT-101: An toàn & Vận hành SMT Cơ bản', 1, NULL, 80.00, 'Khóa đào tạo cơ bản về an toàn tĩnh điện ESD, bảng điều khiển máy gắp chip, quy tắc khởi động và dừng khẩn cấp.', TRUE),
(2, 1, 'SMT-201: Cài đặt Feeder & Xử lý Kẹt cuộn', 2, 1, 80.00, 'Khóa nâng cao về kỹ thuật lắp đặt Feeder đúng mã linh kiện, quy trình xử lý lỗi Tape Jam và thay đổi chương trình mounter.', TRUE),
(3, 1, 'SMT-301: Hiệu chuẩn & Bảo trì máy SMT Chuyên gia', 3, 2, 85.00, 'Khóa chuyên gia về bảo trì cấp độ 2, hiệu chuẩn camera nhận dạng linh kiện, bảo dưỡng nozzle và đào tạo người mới.', TRUE),

-- Skill 2: QC
(4, 2, 'QC-101: Nhận diện Lỗi hàn & Bo mạch Cơ bản', 1, NULL, 80.00, 'Học cách nhận biết các lỗi thường gặp: Thiếu chì hàn, Chập chân linh kiện, Hàn nguội, Lệch linh kiện theo tiêu chuẩn nhà máy.', TRUE),
(5, 2, 'QC-201: Vận hành Máy soi quang học tự động (AOI)', 2, 4, 80.00, 'Khóa nâng cao về lập trình tọa độ kiểm tra bo mạch và phân tích cảnh báo lỗi từ hệ thống quang học.', TRUE),

-- Skill 3: 5S & An toàn
(6, 3, '5S-101: Tiêu chuẩn 5S & Phòng chống Tĩnh điện ESD', 1, NULL, 80.00, 'Đào tạo bắt buộc cho tất cả công nhân mới: Sàng lọc, Sắp xếp, Sạch sẽ, Săn sóc, Sẵn sàng và quy tắc nối đất chống giật/chống hỏng vi mạch.', TRUE);

-- 4. Insert Course Materials
INSERT INTO course_materials (course_id, title, material_type, file_path, content_text, sort_order) VALUES
(1, 'SOP-SMT-001: Quy trình Khởi động & Vận hành an toàn', 'IMAGE_SOP', 'assets/sop_smt_safety.svg', 'Tài liệu hướng dẫn trực quan các vị trí nút bấm, công tắc khí nén và cảnh báo an toàn trên máy SMT.', 1),
(1, 'Hướng dẫn thao tác chuẩn (PDF Tóm tắt)', 'PDF', 'materials/SOP_SMT_Basic_2026.pdf', 'Quy tắc 5 bước an toàn: 1. Đeo vòng tay ESD; 2. Bật khí nén; 3. Khởi động nguồn; 4. Nạp chương trình; 5. Kiểm tra Nozzle.', 2),
(2, 'SOP-SMT-002: Hướng dẫn tháo lắp Feeder', 'IMAGE_SOP', 'assets/sop_feeder_guide.svg', 'Sơ đồ các vị trí chốt khóa Feeder và cảm biến nhận diện cuộn chip.', 1),
(4, 'SOP-QC-001: Bảng màu và hình ảnh mẫu lỗi mối hàn IPC', 'IMAGE_SOP', 'assets/sop_qc_defects.svg', 'Hình ảnh thực tế so sánh mối hàn chuẩn và mối hàn bị rỗ khí/ngắn mạch.', 1);

-- 5. Insert Exams
INSERT INTO exams (id, course_id, title, duration_minutes, total_score, max_attempts, is_active) VALUES
(1, 1, 'Bài thi Đánh giá Năng lực SMT - Cấp độ 1 (Cơ bản)', 15, 100.00, 5, TRUE),
(2, 2, 'Bài thi Đánh giá Năng lực SMT - Cấp độ 2 (Nâng cao)', 20, 100.00, 5, TRUE),
(3, 4, 'Bài thi Đánh giá Nhận diện Lỗi Bo Mạch QC - Cấp độ 1', 15, 100.00, 5, TRUE);

-- 6. Insert Questions for Exam 1 (SMT-101: Cơ bản - 5 câu hỏi đa dạng)
-- Câu 1: Trắc nghiệm 1 đáp án (20đ)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(1, 1, 'SINGLE_CHOICE', 
'Trước khi nhấn nút START khởi động máy gắp chip SMT, công nhân vận hành BẮT BUỘC phải thực hiện thao tác an toàn nào sau đây?', 
NULL, 
JSON_OBJECT(
    'options', JSON_ARRAY(
        JSON_OBJECT('id', 'A', 'text', 'Đeo găng tay cách nhiệt dày'),
        JSON_OBJECT('id', 'B', 'text', 'Đeo vòng tay khử tĩnh điện ESD có gắn dây nối đất và đóng kín cửa bảo vệ máy'),
        JSON_OBJECT('id', 'C', 'text', 'Tắt hoàn toàn van cấp khí nén'),
        JSON_OBJECT('id', 'D', 'text', 'Mở nắp che cơ cấu nhặt linh kiện để quan sát bằng mắt thường')
    ),
    'correct', JSON_ARRAY('B')
), 20.00, 1);

-- Câu 2: Trắc nghiệm nhiều đáp án (20đ)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(2, 1, 'MULTI_CHOICE', 
'Những trang bị bảo hộ (PPE) nào sau đây là BẮT BUỘC khi bước vào khu vực dây chuyền SMT? (Chọn tất cả các đáp án đúng)', 
NULL, 
JSON_OBJECT(
    'options', JSON_ARRAY(
        JSON_OBJECT('id', 'A', 'text', 'Áo khoác chống tĩnh điện ESD'),
        JSON_OBJECT('id', 'B', 'text', 'Giày bảo hộ tiêu chuẩn ESD có dải tán điện'),
        JSON_OBJECT('id', 'C', 'text', 'Mũ trùm tóc công nghiệp'),
        JSON_OBJECT('id', 'D', 'text', 'Kính râm chống chói')
    ),
    'correct', JSON_ARRAY('A', 'B', 'C')
), 20.00, 2);

-- Câu 3: Đúng / Sai (20đ)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(3, 1, 'TRUE_FALSE', 
'Khi máy đang vận hành ở chế độ tự động (AUTO RUN), nếu linh kiện bị rơi trên băng chuyền, công nhân ĐƯỢC PHÉP thò tay vào gắp ra ngay mà không cần dừng máy.', 
NULL, 
JSON_OBJECT(
    'options', JSON_ARRAY(
        JSON_OBJECT('id', 'TRUE', 'text', 'ĐÚNG (Cho phép nếu thao tác nhanh)'),
        JSON_OBJECT('id', 'FALSE', 'text', 'SAI (Tuyệt đối cấm - Phải tạm dừng máy trước khi can thiệp)')
    ),
    'correct', JSON_ARRAY('FALSE')
), 20.00, 3);

-- Câu 4: Kéo thả sắp xếp quy trình (20đ)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(4, 1, 'DRAG_DROP_SEQUENCE', 
'Hãy kéo thả và sắp xếp 4 bước dưới đây theo đúng trình tự khởi động máy SMT chuẩn:', 
NULL, 
JSON_OBJECT(
    'items', JSON_ARRAY(
        JSON_OBJECT('id', 'step_air', 'text', '1. Mở van khí nén chính và kiểm tra đồng hồ áp suất (đạt >= 0.5 MPa)'),
        JSON_OBJECT('id', 'step_power', 'text', '2. Bật Aptomat nguồn tổng và xoay chìa khóa MAIN POWER sang vị trí ON'),
        JSON_OBJECT('id', 'step_load', 'text', '3. Đăng nhập hệ điều hành máy và Load File chương trình sản xuất của Model cần chạy'),
        JSON_OBJECT('id', 'step_test', 'text', '4. Chạy kiểm tra không tải (Dry Run) 1 chu trình để xác nhận chuyển động ổn định')
    ),
    'correct_order', JSON_ARRAY('step_air', 'step_power', 'step_load', 'step_test')
), 20.00, 4);

-- Câu 5: Tương tác chạm điểm (Hotspot Pinpoint) trên bảng điều khiển máy (20đ)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(5, 1, 'HOTSPOT_PINPOINT', 
'Quan sát hình ảnh Bảng điều khiển máy SMT: Hãy CHẠM NGÓN TAY CHÍNH XÁC vào NÚT DỪNG KHẨN CẤP (EMERGENCY STOP - Nút tròn màu đỏ lớn).', 
'assets/machine_panel.svg', 
JSON_OBJECT(
    'target_x_pct', 74.5,
    'target_y_pct', 32.0,
    'tolerance_radius_pct', 8.0,
    'hint', 'Nút dạng nấm màu đỏ ở góc trên bên phải bảng điều khiển'
), 20.00, 5);

-- 7. Insert Questions for Exam 2 (SMT-201: Nâng cao - Câu hỏi khoanh vùng Polygon & Tự luận)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(6, 2, 'POLYGON_DEFECT', 
'Quan sát hình ảnh bo mạch SMT: Dùng ngón tay KHOANH VÙNG vị trí linh kiện IC bị KÊNH CHÂN / LỆCH VỊ TRÍ HÀN (Bridging / Misalignment).', 
'assets/pcb_defect.svg', 
JSON_OBJECT(
    'target_polygon', JSON_ARRAY(
        JSON_OBJECT('x', 41.0, 'y', 33.0),
        JSON_OBJECT('x', 59.0, 'y', 33.0),
        JSON_OBJECT('x', 59.0, 'y', 55.0),
        JSON_OBJECT('x', 41.0, 'y', 55.0)
    ),
    'center_x_pct', 50.0,
    'center_y_pct', 44.0,
    'tolerance_radius_pct', 10.0
), 50.00, 1);

INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(7, 2, 'ESSAY', 
'Khi máy mounter cảnh báo lỗi "TAPE FEEDER JAM #14" (kẹt cuộn băng nạp linh kiện), anh/chị hãy trình bày các bước xử lý an toàn để không làm rách cuộn linh kiện và không gây xước đầu Nozzle.', 
NULL, 
JSON_OBJECT(
    'min_words', 20,
    'rubric', 'Cần nêu đủ: 1. Bấm Pause tạm dừng; 2. Tháo Feeder ra ngoài bàn bảo trì; 3. Dùng nhíp gắp phế liệu; 4. Test bước nạp tay trước khi gắn lại.'
), 50.00, 2);

-- 8. Insert Questions for Exam 3 (QC-101)
INSERT INTO questions (id, exam_id, question_type, prompt, media_url, answer_config, score, sort_order) VALUES
(8, 3, 'HOTSPOT_PINPOINT', 
'Quan sát hình ảnh bo mạch: Hãy CHẠM NGÓN TAY vào điểm có MỐI HÀN NGUỘI / RỖ KHÍ (Cold Solder Joint).', 
'assets/pcb_defect.svg', 
JSON_OBJECT(
    'target_x_pct', 22.0,
    'target_y_pct', 68.0,
    'tolerance_radius_pct', 7.5
), 50.00, 1),
(9, 3, 'SINGLE_CHOICE', 
'Lỗi nào sau đây bị xếp vào mức độ NGHIÊM TRỌNG (CRITICAL DEFECT) buộc phải dừng toàn bộ chuyền sản xuất?', 
NULL, 
JSON_OBJECT(
    'options', JSON_ARRAY(
        JSON_OBJECT('id', 'A', 'text', 'Bụi bẩn nhẹ trên vỏ hộp carton'),
        JSON_OBJECT('id', 'B', 'text', 'Ngắn mạch (Chập nguồn) giữa chân VCC và GND của vi điều khiển'),
        JSON_OBJECT('id', 'C', 'text', 'Tem nhãn mã vạch bị dán hơi lệch 1mm'),
        JSON_OBJECT('id', 'D', 'text', 'Màu sắc điện trở hơi nhạt')
    ),
    'correct', JSON_ARRAY('B')
), 50.00, 2);

-- 9. Insert Initial User Skills (Ma trận năng lực ban đầu)
-- Worker 1 (Nguyễn Văn An):
-- - SMT: Level 0 (Chưa đạt, cần học từ SMT-101)
-- - QC: Level 0
-- - 5S: Level 1 (Đã đạt cơ bản)
INSERT INTO user_skills (user_id, skill_id, current_level, certified_at, certified_by) VALUES
(3, 1, 0, NULL, NULL),
(3, 2, 0, NULL, NULL),
(3, 3, 1, '2026-01-15 09:00:00', 2),

-- Worker 2 (Trần Thị Bình):
-- - SMT: Level 1 (Đã hoàn thành SMT-101 với 90 điểm)
-- - QC: Level 0
-- - 5S: Level 2
(4, 1, 1, '2026-02-10 14:30:00', 2),
(4, 2, 0, NULL, NULL),
(4, 3, 2, '2026-02-15 10:00:00', 2),

-- Worker 3 (Lê Hoàng Cường - Giỏi QC):
-- - SMT: Level 2
-- - QC: Level 2
-- - 5S: Level 3
(5, 1, 2, '2026-03-01 11:00:00', 2),
(5, 2, 2, '2026-03-05 16:00:00', 2),
(5, 3, 3, '2026-03-10 08:30:00', 2);

-- Tạo sẵn 1 bản ghi lịch sử thi cho Worker 2 (để Worker 2 mở khóa được SMT-201)
INSERT INTO exam_attempts (id, user_id, exam_id, attempt_number, start_time, end_time, score_obtained, passed, status, graded_by) VALUES
(100, 4, 1, 1, '2026-02-10 14:00:00', '2026-02-10 14:25:00', 90.00, TRUE, 'COMPLETED', 2);

