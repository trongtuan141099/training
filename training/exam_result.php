<?php
// exam_result.php - Comprehensive Exam Result & Auto-Skillmap Feedback
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireLogin();

$currentUser = getAuthUser();
$attemptId = (int)($_GET['attempt_id'] ?? 0);
$db = getDB();

// Lấy thông tin bài thi của attempt
$stmtAtt = $db->prepare('
    SELECT ea.*, e.title AS exam_title, e.total_score AS exam_total_score,
           c.id AS course_id, c.title AS course_title, c.level AS course_level, c.min_pass_score,
           s.name AS skill_name
    FROM exam_attempts ea
    JOIN exams e ON e.id = ea.exam_id
    JOIN courses c ON c.id = e.course_id
    JOIN skills s ON s.id = c.skill_id
    WHERE ea.id = :id AND ea.user_id = :uid
    LIMIT 1
');
$stmtAtt->execute(['id' => $attemptId, 'uid' => $currentUser['id']]);
$attempt = $stmtAtt->fetch();

if (!$attempt) {
    die('Không tìm thấy kết quả bài thi. <a href="index.php">Quay lại</a>');
}

// Lấy chi tiết câu trả lời
$stmtAns = $db->prepare('
    SELECT ans.*, q.prompt, q.question_type, q.score AS question_max_score, q.media_url, q.answer_config
    FROM exam_answers ans
    JOIN questions q ON q.id = ans.question_id
    WHERE ans.attempt_id = :aid
    ORDER BY q.sort_order ASC, q.id ASC
');
$stmtAns->execute(['aid' => $attemptId]);
$answers = $stmtAns->fetchAll();

$passPercentage = ($attempt['exam_total_score'] > 0) ? (($attempt['score_obtained'] / $attempt['exam_total_score']) * 100) : 0;
$isPassed = (bool)$attempt['passed'];
$isPending = ($attempt['status'] === 'PENDING_EVALUATION');

$pageTitle = 'Kết quả bài thi - ' . $attempt['exam_title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Outcome Card -->
    <div class="rounded-3xl p-6 sm:p-8 text-center border shadow-sm relative overflow-hidden
        <?= $isPending 
            ? 'bg-amber-50/60 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700' 
            : ($isPassed 
                ? 'bg-emerald-50/60 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700' 
                : 'bg-rose-50/60 dark:bg-rose-950/40 border-rose-300 dark:border-rose-700') ?>">
        
        <!-- Big Status Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl mb-4 shadow-sm
            <?= $isPending ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : ($isPassed ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 animate-bounce' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400') ?>">
            <?php if ($isPending): ?>
                <i class="fa-solid fa-hourglass-half text-3xl"></i>
            <?php elseif ($isPassed): ?>
                <i class="fa-solid fa-trophy text-4xl"></i>
            <?php else: ?>
                <i class="fa-solid fa-circle-xmark text-4xl"></i>
            <?php endif; ?>
        </div>

        <p class="text-xs uppercase font-bold tracking-widest text-slate-500 dark:text-slate-400 mb-1">
            <?= htmlspecialchars($attempt['course_title']) ?>
        </p>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mb-2">
            <?php if ($isPending): ?>
                BÀI THI ĐANG CHỜ GIẢNG VIÊN CHẤM ĐIỂM TỰ LUẬN
            <?php elseif ($isPassed): ?>
                CHÚC MỪNG! BẠN ĐÃ ĐẠT TIÊU CHUẨN NĂNG LỰC
            <?php else: ?>
                CHƯA ĐẠT TIÊU CHUẨN TỐI THIỂU
            <?php endif; ?>
        </h1>

        <!-- Score Stat Grid -->
        <div class="inline-flex items-center justify-center gap-6 my-5 px-6 py-3 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Điểm số đạt được</p>
                <p class="text-2xl sm:text-3xl font-black font-mono <?= $isPassed ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                    <?= round($attempt['score_obtained'], 1) ?> <span class="text-xs text-slate-400 font-normal">/ <?= round($attempt['exam_total_score'], 0) ?></span>
                </p>
            </div>
            <div class="w-px h-10 bg-slate-200 dark:bg-slate-800"></div>
            <div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Tỷ lệ chính xác</p>
                <p class="text-2xl sm:text-3xl font-black font-mono text-blue-700 dark:text-blue-400">
                    <?= round($passPercentage, 1) ?>%
                </p>
            </div>
            <div class="w-px h-10 bg-slate-200 dark:bg-slate-800"></div>
            <div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Điểm chuẩn qua môn</p>
                <p class="text-2xl sm:text-3xl font-black font-mono text-slate-700 dark:text-slate-300">
                    <?= (float)$attempt['min_pass_score'] ?>%
                </p>
            </div>
        </div>

        <!-- Automatic Skillmap Update Notification -->
        <?php if ($isPassed): ?>
        <div class="max-w-md mx-auto mb-6 p-4 rounded-2xl bg-white dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-600/60 text-emerald-800 dark:text-emerald-300 text-xs text-center shadow-sm">
            <p class="font-bold text-sm mb-1"><i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i> CẬP NHẬT SKILLMAP TỰ ĐỘNG THÀNH CÔNG!</p>
            <p class="text-slate-700 dark:text-emerald-300/90 leading-relaxed">
                Năng lực của bạn trong chuyên môn <strong><?= htmlspecialchars($attempt['skill_name']) ?></strong> đã chính thức được công nhận đạt <strong>CẤP ĐỘ <?= $attempt['course_level'] ?></strong>. Các khóa học cấp cao hơn đã được tự động mở khóa trên Lộ trình!
            </p>
        </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div class="flex items-center justify-center gap-3 flex-wrap">
            <a href="index.php" class="touch-tap px-5 py-2.5 bg-[#1D4ED8] hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-md shadow-blue-700/20">
                <i class="fa-solid fa-road"></i>
                <span>Xem Lộ Trình Cập Nhật</span>
            </a>
            <a href="skillmap.php" class="touch-tap px-5 py-2.5 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-emerald-300 rounded-xl text-xs font-bold transition flex items-center space-x-2 border border-slate-300 dark:border-slate-700">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Xem Ma Trận Năng Lực</span>
            </a>
            <?php if (!$isPassed && !$isPending): ?>
            <a href="exam.php?exam_id=<?= $attempt['exam_id'] ?>" class="touch-tap px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-md shadow-rose-700/20">
                <i class="fa-solid fa-rotate-right"></i>
                <span>Thi Lại Bài Này</span>
            </a>
            <?php endif; ?>
        </div>

    </div>

    <!-- Chi tiết từng câu hỏi & Đánh giá của Hệ thống / Trainer -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center space-x-2 pb-3 border-b border-slate-200 dark:border-slate-800">
            <i class="fa-solid fa-list-check text-blue-700 dark:text-blue-400"></i>
            <span>Chi tiết Đánh giá Từng Câu Hỏi</span>
        </h2>

        <div class="space-y-4">
            <?php foreach ($answers as $idx => $ans): ?>
            <?php 
                $isCorrect = $ans['is_correct'];
                $userResp = json_decode($ans['user_response'], true);
            ?>
            <div class="p-5 rounded-2xl border transition
                <?= $isCorrect === 1 
                    ? 'bg-emerald-50/30 dark:bg-slate-950/80 border-emerald-300 dark:border-emerald-800/80' 
                    : ($isCorrect === 0 ? 'bg-rose-50/30 dark:bg-slate-950/80 border-rose-300 dark:border-rose-800/80' : 'bg-amber-50/30 dark:bg-slate-950/80 border-amber-300 dark:border-amber-800/80') ?>">
                
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-start space-x-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-md font-bold text-xs shrink-0 mt-0.5
                            <?= $isCorrect === 1 ? 'bg-emerald-600 text-white' : ($isCorrect === 0 ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white') ?>">
                            <?= $idx + 1 ?>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($ans['prompt']) ?></h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($ans['question_type']) ?></span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold shrink-0
                        <?= $isCorrect === 1 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700' : ($isCorrect === 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-400 border border-rose-300 dark:border-rose-700' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400 border border-amber-300 dark:border-amber-700') ?>">
                        <?= (float)$ans['awarded_score'] ?> / <?= (float)$ans['question_max_score'] ?> Điểm
                    </span>
                </div>

                <!-- Phản hồi tự động / Nhận xét Trainer -->
                <div class="mt-3 p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="font-bold <?= $isCorrect === 1 ? 'text-emerald-700 dark:text-emerald-400' : ($isCorrect === 0 ? 'text-rose-700 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400') ?>">
                            <?= $isCorrect === 1 ? '<i class="fa-solid fa-circle-check"></i> ĐÚNG' : ($isCorrect === 0 ? '<i class="fa-solid fa-circle-xmark"></i> CHƯA ĐÚNG' : '<i class="fa-solid fa-clock"></i> ĐANG DUYỆT') ?>:
                        </span>
                        <span class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($ans['trainer_comment'] ?? 'Chưa có nhận xét.') ?></span>
                    </div>

                    <?php if ($ans['question_type'] === 'HOTSPOT_PINPOINT' && isset($userResp['clicked_x_pct'])): ?>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                        Tọa độ bạn đã chạm: X=<?= $userResp['clicked_x_pct'] ?>%, Y=<?= $userResp['clicked_y_pct'] ?>%
                    </p>
                    <?php endif; ?>

                    <?php if ($ans['question_type'] === 'ESSAY' && isset($userResp['text'])): ?>
                    <div class="mt-2 p-2.5 rounded bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-700 dark:text-slate-300">
                        <strong>Bài làm tự luận của bạn:</strong>
                        <p class="mt-1 italic"><?= nl2br(htmlspecialchars($userResp['text'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

