<?php
// exam_submit.php - Process exam submission
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$currentUser = getAuthUser();
$examId = (int)($_POST['exam_id'] ?? 0);
$rawAnswers = $_POST['answers'] ?? [];

// Chuẩn hóa câu trả lời (nếu là chuỗi JSON do canvas/drag-drop gửi lên thì giải mã)
$formattedAnswers = [];
foreach ($rawAnswers as $qid => $val) {
    if (is_string($val)) {
        $decoded = json_decode($val, true);
        $formattedAnswers[$qid] = ($decoded !== null) ? $decoded : $val;
    } else {
        $formattedAnswers[$qid] = $val;
    }
}

$result = submitExamAttempt($currentUser['id'], $examId, $formattedAnswers);

if (!$result['success']) {
    die('<div style="padding:20px;background:#fef2f2;color:#991b1b;border-radius:8px;max-width:500px;margin:40px auto;font-family:sans-serif;">'
        . '<h3>Lỗi khi nộp bài thi</h3>'
        . '<p>' . htmlspecialchars($result['message']) . '</p>'
        . '<p><a href="index.php">Quay lại trang chủ</a></p>'
        . '</div>');
}

// Xóa cache bộ câu hỏi ngẫu nhiên của phiên thi này
unset($_SESSION['exam_rand_q_' . $examId . '_' . $currentUser['id']]);

// Chuyển hướng sang trang hiển thị kết quả
header('Location: exam_result.php?attempt_id=' . $result['attempt_id']);
exit;

