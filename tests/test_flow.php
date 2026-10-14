<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/skillmap.php';

echo "=== TEST COMPLETE EXAMINATION & AUTO-SKILLMAP UPGRADE FLOW ===\n";

// Worker 1 (id=3) làm bài thi Exam 1 (SMT-101: Cơ bản)
$workerId = 3;
$examId = 1;

// Chuẩn bị câu trả lời hoàn hảo cho 5 câu hỏi
$answers = [
    1 => ['selected' => 'B'], // Trắc nghiệm: Vòng tay ESD
    2 => ['selected' => ['A', 'B', 'C']], // Nhiều lựa chọn: Áo, giày, mũ
    3 => ['selected' => 'FALSE'], // Đúng/Sai: Cấm thò tay khi máy chạy
    4 => ['submitted_order' => ['step_air', 'step_power', 'step_load', 'step_test']], // Kéo thả 4 bước
    5 => ['clicked_x_pct' => 74.5, 'clicked_y_pct' => 32.0] // Chạm E-Stop
];

$result = submitExamAttempt($workerId, $examId, $answers);

echo "Submission Result: " . ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
echo "Score Obtained: " . $result['score_obtained'] . " / " . $result['total_max_score'] . " (" . $result['pass_percentage'] . "%)\n";
echo "Passed: " . ($result['passed'] ? "YES" : "NO") . "\n";
echo "Skill Upgraded: " . ($result['skill_upgraded'] ? "YES to Level " . $result['new_level'] : "NO") . "\n";

// Kiểm tra lại Skillmap của Worker 1
$skillmap = getUserSkillmap($workerId);
foreach ($skillmap as $sk) {
    echo "Skill [" . $sk['code'] . "]: Current Level = " . $sk['current_level'] . "\n";
}

// Kiểm tra lại khóa học Cấp độ 2 (SMT-201) đối với Worker 1
$accCourse2 = canAccessCourse($workerId, 2);
echo "\nChecking Course 2 access after passing Course 1:\n";
echo "Worker 1 on Course 2: " . ($accCourse2['allowed'] ? "NOW UNLOCKED!" : "STILL LOCKED") . "\n";
echo "Reason: " . $accCourse2['reason'] . "\n";

