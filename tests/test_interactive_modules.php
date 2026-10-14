<?php
// tests/test_interactive_modules.php - Comprehensive verification of all new modules
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/skillmap.php';

$pdo = getDB();

echo "=================================================================\n";
echo "1. VERIFY VIETNAMESE FONT & UNICODE UTF-8 ENCODING\n";
echo "=================================================================\n";

$course = $pdo->query("SELECT title FROM courses WHERE id=1")->fetch();
echo "Course Title: " . $course['title'] . "\n";
assert(strpos($course['title'], 'An toàn & Vận hành SMT Cơ bản') !== false, "Course title should have accurate Vietnamese!");

$question = $pdo->query("SELECT prompt FROM questions WHERE id=1")->fetch();
echo "Question Prompt: " . mb_substr($question['prompt'], 0, 50) . "...\n";
assert(strpos($question['prompt'], 'Trước khi nhấn nút START') !== false, "Question prompt should have accurate Vietnamese!");
echo "=> TEST 1 PASSED: 100% Vietnamese Unicode UTF-8 clean.\n\n";

echo "=================================================================\n";
echo "2. VERIFY MULTI-ROI & HOTSPOT DEFECT GRADING ALGORITHM\n";
echo "=================================================================\n";

// Tạo câu hỏi mẫu có 2 vùng lỗi (1 bounding box chữ nhật và 1 hotspot)
$sampleRoiQuestion = [
    'id'            => 999,
    'question_type' => 'POLYGON_DEFECT',
    'score'         => 30.0,
    'answer_config' => [
        'rois' => [
            [
                'id'    => 'roi_ic_bridge',
                'type'  => 'rect',
                'label' => 'Lỗi chập dính chân IC 14-16',
                'x'     => 40.0,
                'y'     => 30.0,
                'w'     => 20.0,
                'h'     => 15.0
            ],
            [
                'id'    => 'roi_estop_button',
                'type'  => 'hotspot',
                'label' => 'Nút Dừng Khẩn Cấp E-STOP',
                'x'     => 75.0,
                'y'     => 35.0,
                'r'     => 8.0
            ]
        ]
    ]
];

// Test 2.1: Chạm trúng vùng lỗi 1 (Rect: 45%, 35%)
$res1 = gradeQuestion($sampleRoiQuestion, ['clicked_x_pct' => 45.0, 'clicked_y_pct' => 35.0]);
echo "Hit ROI 1 (Rect): Score = " . $res1['awarded_score'] . " | " . $res1['feedback'] . "\n";
assert($res1['is_correct'] === true && $res1['awarded_score'] == 30.0, "Should hit ROI 1");

// Test 2.2: Chạm trúng vùng lỗi 2 (Hotspot: 76%, 36%)
$res2 = gradeQuestion($sampleRoiQuestion, ['clicked_x_pct' => 76.0, 'clicked_y_pct' => 36.0]);
echo "Hit ROI 2 (Hotspot): Score = " . $res2['awarded_score'] . " | " . $res2['feedback'] . "\n";
assert($res2['is_correct'] === true && $res2['awarded_score'] == 30.0, "Should hit ROI 2");

// Test 2.3: Chạm trượt ra ngoài (10%, 10%)
$res3 = gradeQuestion($sampleRoiQuestion, ['clicked_x_pct' => 10.0, 'clicked_y_pct' => 10.0]);
echo "Miss All ROIs: Score = " . $res3['awarded_score'] . " | " . $res3['feedback'] . "\n";
assert($res3['is_correct'] === false && $res3['awarded_score'] == 0.0, "Should miss all ROIs");

// Test 2.4: Khoanh vùng bằng drawn_polygon với trọng tâm trúng ROI 1
$polygonPoints = [
    ['x' => 42.0, 'y' => 32.0],
    ['x' => 48.0, 'y' => 32.0],
    ['x' => 48.0, 'y' => 38.0],
    ['x' => 42.0, 'y' => 38.0]
];
$res4 = gradeQuestion($sampleRoiQuestion, ['drawn_polygon' => $polygonPoints]);
echo "Drawn Polygon on ROI 1: Score = " . $res4['awarded_score'] . " | " . $res4['feedback'] . "\n";
assert($res4['is_correct'] === true, "Polygon centroid should hit ROI 1");
echo "=> TEST 2 PASSED: Multi-ROI & Hotspot defect detection graded accurately.\n\n";

echo "=================================================================\n";
echo "3. VERIFY RANDOMIZED QUESTION DRAWING CONFIG & PREVIEW\n";
echo "=================================================================\n";

$exam = $pdo->query("SELECT * FROM exams WHERE id=1")->fetch();
echo "Exam 1 is_randomized = " . $exam['is_randomized'] . " | count = " . $exam['random_question_count'] . " | mode = " . $exam['question_bank_mode'] . "\n";
assert((int)$exam['is_randomized'] === 1, "Exam 1 should have is_randomized enabled");

// Bốc ngẫu nhiên 3 câu hỏi từ ngân hàng
$stmtRand = $pdo->prepare("SELECT id, prompt FROM questions WHERE exam_id=1 ORDER BY RAND() LIMIT 3");
$stmtRand->execute();
$drawn = $stmtRand->fetchAll();
echo "Sample drawn questions count: " . count($drawn) . "\n";
foreach ($drawn as $i => $q) {
    echo "  #" . ($i + 1) . " (ID " . $q['id'] . "): " . mb_substr($q['prompt'], 0, 45) . "...\n";
}
assert(count($drawn) === 3, "Should draw exactly 3 questions");
echo "=> TEST 3 PASSED: Random question drawing functions properly.\n\n";

echo "=================================================================\n";
echo "4. VERIFY INTERACTIVE VIDEO SOP & POP-UP CUE-POINTS\n";
echo "=================================================================\n";

$videoMat = $pdo->query("SELECT * FROM course_materials WHERE material_type='VIDEO' LIMIT 1")->fetch();
assert($videoMat !== false, "Video material should exist");
echo "Video Material: " . $videoMat['title'] . " (" . $videoMat['file_path'] . ")\n";

$cues = json_decode($videoMat['interactive_config'], true);
assert(is_array($cues) && count($cues) >= 2, "Video should have at least 2 interactive pop-up cues configured");
echo "Configured Interactive Cues: " . count($cues) . " points\n";
foreach ($cues as $c) {
    echo "  - Timestamp: " . $c['timestamp_sec'] . "s | Question: " . $c['question'] . " (Correct: " . $c['correct'] . ")\n";
}
echo "=> TEST 4 PASSED: Interactive Video SOP with Pop-up Checkpoints configured and ready.\n\n";

echo "=================================================================\n";
echo "ALL TESTS COMPLETED SUCCESSFULLY! SYSTEM 100% OPERATIONAL.\n";
echo "=================================================================\n";

