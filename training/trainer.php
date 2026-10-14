<?php
// trainer.php - Trainer Grading Portal for Manual Essay Evaluation
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireRole(['TRAINER', 'ADMIN']);

$currentUser = getAuthUser();
$db = getDB();

$successNotice = '';
$errorNotice = '';

// Xử lý nộp điểm chấm của Trainer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade_essay') {
    $attemptId = (int)$_POST['attempt_id'];
    $answerId = (int)$_POST['answer_id'];
    $awardedScore = (float)$_POST['awarded_score'];
    $feedback = trim($_POST['feedback'] ?? '');

    try {
        $db->beginTransaction();

        // Cập nhật câu trả lời
        $stmtUpdateAns = $db->prepare('
            UPDATE exam_answers 
            SET awarded_score = :score, is_correct = :is_corr, trainer_comment = :feedback
            WHERE id = :ans_id
        ');
        $stmtUpdateAns->execute([
            'score'    => $awardedScore,
            'is_corr'  => ($awardedScore > 0) ? 1 : 0,
            'feedback' => $feedback ?: 'Giảng viên đã duyệt và cho điểm.',
            'ans_id'   => $answerId
        ]);

        // Tính lại tổng điểm của bài thi
        $stmtSum = $db->prepare('SELECT SUM(awarded_score) FROM exam_answers WHERE attempt_id = :aid');
        $stmtSum->execute(['aid' => $attemptId]);
        $newTotal = (float)$stmtSum->fetchColumn();

        // Lấy thông tin bài thi & khóa học để kiểm tra xem đã qua chưa
        $stmtAtt = $db->prepare('
            SELECT ea.user_id, e.total_score, c.min_pass_score, c.skill_id, c.level
            FROM exam_attempts ea
            JOIN exams e ON e.id = ea.exam_id
            JOIN courses c ON c.id = e.course_id
            WHERE ea.id = :aid
        ');
        $stmtAtt->execute(['aid' => $attemptId]);
        $attInfo = $stmtAtt->fetch();

        $maxScore = (float)$attInfo['total_score'];
        $minPass = (float)$attInfo['min_pass_score'];
        $pct = ($maxScore > 0) ? (($newTotal / $maxScore) * 100) : 0;
        $isPassed = ($pct >= $minPass);

        // Cập nhật bài thi thành COMPLETED
        $stmtUpdateAtt = $db->prepare('
            UPDATE exam_attempts 
            SET score_obtained = :score, passed = :passed, status = "COMPLETED", graded_by = :gid, trainer_feedback = :fb
            WHERE id = :aid
        ');
        $stmtUpdateAtt->execute([
            'score'  => $newTotal,
            'passed' => $isPassed ? 1 : 0,
            'gid'    => $currentUser['id'],
            'fb'     => $feedback,
            'aid'    => $attemptId
        ]);

        // Nếu đạt -> Cập nhật Skillmap cho công nhân
        if ($isPassed) {
            $workerId = (int)$attInfo['user_id'];
            $skillId = (int)$attInfo['skill_id'];
            $targetLevel = (int)$attInfo['level'];

            $stmtCheckSkill = $db->prepare('SELECT * FROM user_skills WHERE user_id = :uid AND skill_id = :sid');
            $stmtCheckSkill->execute(['uid' => $workerId, 'sid' => $skillId]);
            $userSkill = $stmtCheckSkill->fetch();

            if ($userSkill) {
                if ($targetLevel > (int)$userSkill['current_level']) {
                    $stmtUpSkill = $db->prepare('UPDATE user_skills SET current_level = :lvl, certified_at = NOW(), certified_by = :cid WHERE id = :id');
                    $stmtUpSkill->execute(['lvl' => $targetLevel, 'cid' => $currentUser['id'], 'id' => $userSkill['id']]);
                }
            } else {
                $stmtInsSkill = $db->prepare('INSERT INTO user_skills (user_id, skill_id, current_level, certified_at, certified_by) VALUES (:uid, :sid, :lvl, NOW(), :cid)');
                $stmtInsSkill->execute(['uid' => $workerId, 'sid' => $skillId, 'lvl' => $targetLevel, 'cid' => $currentUser['id']]);
            }
        }

        $db->commit();
        $successNotice = 'Đã hoàn tất chấm điểm bài thi #' . $attemptId . '! ' . ($isPassed ? 'Công nhân đã ĐẠT và được nâng cấp Skillmap.' : 'Điểm số chưa đạt chuẩn.');
    } catch (Exception $e) {
        $db->rollBack();
        $errorNotice = 'Lỗi khi lưu điểm: ' . $e->getMessage();
    }
}

// Lấy danh sách các bài thi đang chờ chấm (PENDING_EVALUATION)
$pendingAttempts = $db->query('
    SELECT ea.*, u.full_name AS worker_name, u.employee_code, u.department,
           e.title AS exam_title, e.total_score AS exam_max_score,
           c.title AS course_title, c.level AS course_level, c.min_pass_score
    FROM exam_attempts ea
    JOIN users u ON u.id = ea.user_id
    JOIN exams e ON e.id = ea.exam_id
    JOIN courses c ON c.id = e.course_id
    WHERE ea.status = "PENDING_EVALUATION"
    ORDER BY ea.id ASC
')->fetchAll();

// Nếu có chọn xem chi tiết 1 bài thi để chấm
$selectedAttemptId = (int)($_GET['attempt_id'] ?? ($pendingAttempts[0]['id'] ?? 0));
$selectedAttempt = null;
$essayQuestions = [];

if ($selectedAttemptId > 0) {
    $stmtSel = $db->prepare('
        SELECT ea.*, u.full_name AS worker_name, u.employee_code, u.department,
               e.title AS exam_title, e.total_score AS exam_max_score,
               c.title AS course_title, c.min_pass_score
        FROM exam_attempts ea
        JOIN users u ON u.id = ea.user_id
        JOIN exams e ON e.id = ea.exam_id
        JOIN courses c ON c.id = e.course_id
        WHERE ea.id = :id
        LIMIT 1
    ');
    $stmtSel->execute(['id' => $selectedAttemptId]);
    $selectedAttempt = $stmtSel->fetch();

    if ($selectedAttempt) {
        // Lấy câu hỏi tự luận của bài này
        $stmtEssay = $db->prepare('
            SELECT ans.*, q.prompt, q.score AS max_q_score, q.answer_config
            FROM exam_answers ans
            JOIN questions q ON q.id = ans.question_id
            WHERE ans.attempt_id = :aid AND q.question_type = "ESSAY"
        ');
        $stmtEssay->execute(['aid' => $selectedAttemptId]);
        $essayQuestions = $stmtEssay->fetchAll();
    }
}

$pageTitle = 'Bàn chấm thi Giảng viên / Trainer';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center space-x-2">
        <i class="fa-solid fa-pen-nib text-amber-500"></i>
        <span>BÀN CHẤM BÀI THI & ĐÁNH GIÁ NĂNG LỰC (TRAINER DESK)</span>
    </h1>
    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
        Duyệt bài làm tự luận của công nhân, đánh giá câu trả lời và phê duyệt chứng chỉ Skillmap.
    </p>
</div>

<?php if ($successNotice): ?>
<div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-600 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2 shadow-sm">
    <i class="fa-solid fa-circle-check text-base"></i>
    <span><?= htmlspecialchars($successNotice) ?></span>
</div>
<?php endif; ?>

<?php if ($errorNotice): ?>
<div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-600 text-rose-800 dark:text-rose-300 text-xs flex items-center space-x-2 shadow-sm">
    <i class="fa-solid fa-triangle-exclamation text-base"></i>
    <span><?= htmlspecialchars($errorNotice) ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Cột trái (1/3): Danh sách hàng đợi bài thi chờ chấm -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
        <h2 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4 flex items-center justify-between">
            <span>Hàng đợi Chờ chấm (<?= count($pendingAttempts) ?>)</span>
            <span class="inline-block w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
        </h2>

        <?php if (empty($pendingAttempts)): ?>
        <div class="p-8 text-center text-slate-400 text-xs">
            <i class="fa-solid fa-clipboard-check text-3xl mb-2 text-slate-300 dark:text-slate-600"></i>
            <p class="font-medium text-slate-600 dark:text-slate-300">Hiện không có bài thi nào đang chờ chấm!</p>
        </div>
        <?php else: ?>
        <div class="space-y-2.5">
            <?php foreach ($pendingAttempts as $p): ?>
            <?php $isSelected = ($p['id'] == $selectedAttemptId); ?>
            <a href="trainer.php?attempt_id=<?= $p['id'] ?>" class="block p-3.5 rounded-xl border transition <?= $isSelected ? 'bg-blue-50/80 dark:bg-blue-950/80 border-blue-500 shadow-sm' : 'bg-slate-50 dark:bg-slate-950/70 border-slate-200 dark:border-slate-800 hover:border-blue-400' ?>">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-bold text-slate-900 dark:text-white text-xs"><?= htmlspecialchars($p['worker_name']) ?></span>
                    <span class="font-mono text-[10px] text-blue-700 dark:text-blue-400 font-bold"><?= htmlspecialchars($p['employee_code']) ?></span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($p['course_title']) ?></p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200/80 dark:border-slate-800/80 text-[10px] text-slate-400 font-mono">
                    <span>Mã bài: #<?= $p['id'] ?></span>
                    <span class="text-amber-600 dark:text-amber-400 font-bold">Chờ chấm tự luận</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cột phải (2/3): Khung chấm điểm chi tiết câu hỏi tự luận -->
    <div class="lg:col-span-2">
        <?php if ($selectedAttempt && !empty($essayQuestions)): ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
            
            <div class="pb-4 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between">
                <div>
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 uppercase">
                        Đang chấm: Bài thi #<?= $selectedAttempt['id'] ?>
                    </span>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white mt-1.5"><?= htmlspecialchars($selectedAttempt['exam_title']) ?></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Thí sinh: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($selectedAttempt['worker_name']) ?></strong> (<?= htmlspecialchars($selectedAttempt['employee_code']) ?>) • <?= htmlspecialchars($selectedAttempt['department']) ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Điểm tự động trước đó:</p>
                    <p class="text-base font-bold font-mono text-blue-700 dark:text-blue-400"><?= round($selectedAttempt['score_obtained'], 1) ?> đ</p>
                </div>
            </div>

            <?php foreach ($essayQuestions as $eq): ?>
            <?php 
                $respData = json_decode($eq['user_response'], true);
                $cfg = json_decode($eq['answer_config'], true);
            ?>
            <form method="POST" action="trainer.php" class="space-y-5">
                <input type="hidden" name="action" value="grade_essay">
                <input type="hidden" name="attempt_id" value="<?= $selectedAttempt['id'] ?>">
                <input type="hidden" name="answer_id" value="<?= $eq['id'] ?>">

                <!-- Prompt -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-400 mb-1">ĐỀ BÀI CÂU HỎI TỰ LUẬN:</p>
                    <p class="text-xs sm:text-sm text-slate-800 dark:text-white font-medium leading-relaxed"><?= htmlspecialchars($eq['prompt']) ?></p>
                </div>

                <!-- Worker's Answer -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 mb-1"><i class="fa-solid fa-align-left mr-1"></i> BÀI LÀM CỦA CÔNG NHÂN:</p>
                    <p class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 whitespace-pre-wrap leading-relaxed font-mono">
                        <?= !empty($respData['text']) ? htmlspecialchars($respData['text']) : '<em class="text-slate-400">Thí sinh để trống câu trả lời.</em>' ?>
                    </p>
                </div>

                <!-- Rubric / Suggested Standard -->
                <?php if (!empty($cfg['rubric'])): ?>
                <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-xs text-amber-900 dark:text-amber-200">
                    <p class="font-bold mb-1"><i class="fa-solid fa-lightbulb mr-1"></i> Tiêu chí chấm điểm chuẩn (Rubric):</p>
                    <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed"><?= htmlspecialchars($cfg['rubric']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Trainer Grading Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Điểm chấm (Tối đa <?= (float)$eq['max_q_score'] ?>đ):
                        </label>
                        <input type="number" step="0.5" min="0" max="<?= (float)$eq['max_q_score'] ?>" name="awarded_score" required
                               value="<?= (float)$eq['max_q_score'] ?>"
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-bold text-emerald-600 dark:text-emerald-400 focus:outline-none focus:border-blue-600">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Nhận xét của Giảng viên:
                        </label>
                        <input type="text" name="feedback" placeholder="VD: Thao tác đúng quy trình, trình bày rõ ràng."
                               class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white focus:outline-none focus:border-blue-600">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="submit" class="touch-tap px-6 py-2.5 bg-[#1D4ED8] hover:bg-blue-800 text-white rounded-xl font-bold text-xs shadow-md shadow-blue-700/20 transition flex items-center space-x-2">
                        <i class="fa-solid fa-stamp"></i>
                        <span>XÁC NHẬN CHO ĐIỂM & HOÀN TẤT DUYỆT</span>
                    </button>
                </div>

            </form>
            <?php endforeach; ?>

        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-400 shadow-sm">
            <i class="fa-solid fa-circle-check text-4xl text-slate-300 dark:text-slate-600 mb-3"></i>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Vui lòng chọn một bài thi từ cột bên trái để bắt đầu chấm điểm.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

