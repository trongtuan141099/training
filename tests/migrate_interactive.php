<?php
// migrate_interactive.php - Add columns for Random Exam & Interactive Video SOP
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "Running migrations...\n";

// 1. Add is_randomized, random_question_count, question_bank_mode to exams
$colsExams = $pdo->query("SHOW COLUMNS FROM exams")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('is_randomized', $colsExams, true)) {
    $pdo->exec("ALTER TABLE exams ADD COLUMN is_randomized TINYINT(1) DEFAULT 0 AFTER max_attempts");
    echo "Added is_randomized to exams.\n";
}
if (!in_array('random_question_count', $colsExams, true)) {
    $pdo->exec("ALTER TABLE exams ADD COLUMN random_question_count INT DEFAULT 5 AFTER is_randomized");
    echo "Added random_question_count to exams.\n";
}
if (!in_array('question_bank_mode', $colsExams, true)) {
    $pdo->exec("ALTER TABLE exams ADD COLUMN question_bank_mode VARCHAR(50) DEFAULT 'EXAM_POOL' AFTER random_question_count");
    echo "Added question_bank_mode to exams.\n";
}

// 2. Add interactive_config to course_materials
$colsMat = $pdo->query("SHOW COLUMNS FROM course_materials")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('interactive_config', $colsMat, true)) {
    $pdo->exec("ALTER TABLE course_materials ADD COLUMN interactive_config JSON NULL AFTER content_text");
    echo "Added interactive_config to course_materials.\n";
}

// 3. Insert or update a sample Interactive Video SOP for Course 1
$stmtCheck = $pdo->query("SELECT id FROM course_materials WHERE material_type = 'VIDEO' LIMIT 1");
$videoMat = $stmtCheck->fetch();

$sampleInteractiveConfig = json_encode([
    [
        'id'            => 'quiz_1',
        'timestamp_sec' => 3,
        'question'      => 'Theo quy chuẩn An toàn SOP-SMT-001, bước kiểm tra đầu tiên trước khi bật công tắc nguồn là gì?',
        'options'       => [
            ['id' => 'A', 'text' => 'Đeo vòng tay khử tĩnh điện ESD có dây nối đất'],
            ['id' => 'B', 'text' => 'Mở ngay cửa kính bảo vệ máy'],
            ['id' => 'C', 'text' => 'Tăng tốc độ băng chuyền lên tối đa']
        ],
        'correct'       => 'A',
        'explanation'   => 'Chính xác! Công nhân bắt buộc phải kiểm tra và đeo vòng tay khử tĩnh điện ESD trước tiên để tránh phóng điện làm hỏng vi mạch.'
    ],
    [
        'id'            => 'quiz_2',
        'timestamp_sec' => 7,
        'question'      => 'Khi phát hiện sự cố linh kiện bị kẹt băng nạp (Tape Jam), thao tác tức thời nào là ĐÚNG?',
        'options'       => [
            ['id' => 'A', 'text' => 'Thò tay vào gắp cuộn băng khi máy vẫn đang chạy'],
            ['id' => 'B', 'text' => 'Nhấn nút PAUSE hoặc nút DỪNG KHẨN CẤP trước khi can thiệp'],
            ['id' => 'C', 'text' => 'Tắt cầu dao điện tòa nhà']
        ],
        'correct'       => 'B',
        'explanation'   => 'Rất tốt! Luôn luôn dừng máy an toàn bằng nút PAUSE hoặc E-STOP trước khi mở nắp can thiệp kỹ thuật.'
    ]
], JSON_UNESCAPED_UNICODE);

if ($videoMat) {
    $stmtUp = $pdo->prepare("UPDATE course_materials SET interactive_config = :cfg, file_path = 'uploads/materials/sample_sop_video.mp4' WHERE id = :id");
    $stmtUp->execute(['cfg' => $sampleInteractiveConfig, 'id' => $videoMat['id']]);
    echo "Updated existing video material with sample interactive config.\n";
} else {
    $stmtIns = $pdo->prepare("INSERT INTO course_materials (course_id, title, material_type, file_path, content_text, interactive_config, sort_order) VALUES (1, 'Video Đào tạo Thao tác Chuẩn & Xử lý sự cố SMT (Tương tác Pop-up)', 'VIDEO', 'uploads/materials/sample_sop_video.mp4', 'Video huấn luyện trực quan quy trình thao tác máy SMT thực tế có lồng ghép các câu hỏi tương tác kiểm tra độ tập trung.', :cfg, 3)");
    $stmtIns->execute(['cfg' => $sampleInteractiveConfig]);
    echo "Inserted sample interactive video material.\n";
}

// 4. Update Exam 1 to test Random Drawing
$pdo->exec("UPDATE exams SET is_randomized = 1, random_question_count = 4, question_bank_mode = 'EXAM_POOL' WHERE id = 1");
echo "Configured Exam 1 with random drawing.\n";

echo "Migration finished successfully.\n";

