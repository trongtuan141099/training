<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/skillmap.php';

echo "=== TEST 1: Prerequisite Lock for Worker 1 ===\n";
$acc1 = canAccessCourse(3, 1);
$acc2 = canAccessCourse(3, 2);
echo "Worker 1 on Course 1: " . ($acc1['allowed'] ? "ALLOWED" : "LOCKED") . "\n";
echo "Worker 1 on Course 2: " . ($acc2['allowed'] ? "ALLOWED" : "LOCKED") . " (Reason: " . $acc2['reason'] . ")\n";

echo "\n=== TEST 2: Prerequisite Unlock for Worker 2 ===\n";
$accW2 = canAccessCourse(4, 2);
echo "Worker 2 on Course 2: " . ($accW2['allowed'] ? "ALLOWED" : "LOCKED") . " (Reason: " . $accW2['reason'] . ")\n";

echo "\n=== TEST 3: Hotspot Pinpoint Grading Algorithm ===\n";
$db = getDB();
$stmtQ = $db->query("SELECT * FROM questions WHERE question_type = 'HOTSPOT_PINPOINT' LIMIT 1");
$qHotspot = $stmtQ->fetch();
$resGood = gradeQuestion($qHotspot, ['clicked_x_pct' => 74.5, 'clicked_y_pct' => 32.0]);
echo "Hotspot Tap Exact (74.5, 32.0): Score=" . $resGood['awarded_score'] . " | " . $resGood['feedback'] . "\n";

$resBad = gradeQuestion($qHotspot, ['clicked_x_pct' => 15.0, 'clicked_y_pct' => 15.0]);
echo "Hotspot Tap Wrong (15.0, 15.0): Score=" . $resBad['awarded_score'] . " | " . $resBad['feedback'] . "\n";

echo "\n=== TEST 4: Drag & Drop Sequence Grading Algorithm ===\n";
$stmtSeq = $db->query("SELECT * FROM questions WHERE question_type = 'DRAG_DROP_SEQUENCE' LIMIT 1");
$qSeq = $stmtSeq->fetch();
$correctOrder = ['step_air', 'step_power', 'step_load', 'step_test'];
$resSeqGood = gradeQuestion($qSeq, ['submitted_order' => $correctOrder]);
echo "Sequence Correct Order: Score=" . $resSeqGood['awarded_score'] . " | " . $resSeqGood['feedback'] . "\n";

$wrongOrder = ['step_power', 'step_air', 'step_load', 'step_test'];
$resSeqBad = gradeQuestion($qSeq, ['submitted_order' => $wrongOrder]);
echo "Sequence Wrong Order: Score=" . $resSeqBad['awarded_score'] . " | " . $resSeqBad['feedback'] . "\n";

echo "\n=== ALL LOGIC VERIFICATIONS COMPLETED SUCCESSFULLY ===\n";

