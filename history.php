<?php
// history.php - Audit Log of all Exam Attempts & Scores
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$currentUser = getAuthUser();
$db = getDB();

$isAdminOrTrainer = in_array($currentUser['role'], ['ADMIN', 'TRAINER'], true);

// Query lịch sử
if ($isAdminOrTrainer && isset($_GET['all']) && $_GET['all'] === '1') {
    $stmt = $db->query('
        SELECT ea.*, e.title AS exam_title, e.total_score AS exam_max_score,
               u.full_name, u.employee_code, u.department,
               c.min_pass_score, c.level AS course_level
        FROM exam_attempts ea
        JOIN exams e ON e.id = ea.exam_id
        JOIN courses c ON c.id = e.course_id
        JOIN users u ON u.id = ea.user_id
        ORDER BY ea.id DESC
    ');
    $attempts = $stmt->fetchAll();
    $viewingAll = true;
} else {
    $stmt = $db->prepare('
        SELECT ea.*, e.title AS exam_title, e.total_score AS exam_max_score,
               u.full_name, u.employee_code, u.department,
               c.min_pass_score, c.level AS course_level
        FROM exam_attempts ea
        JOIN exams e ON e.id = ea.exam_id
        JOIN courses c ON c.id = e.course_id
        JOIN users u ON u.id = ea.user_id
        WHERE ea.user_id = :uid
        ORDER BY ea.id DESC
    ');
    $stmt->execute(['uid' => $currentUser['id']]);
    $attempts = $stmt->fetchAll();
    $viewingAll = false;
}

$pageTitle = 'Lịch sử Làm bài Thi';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center space-x-2">
            <i class="fa-solid fa-clock-rotate-left text-blue-700 dark:text-blue-400"></i>
            <span>LỊCH SỬ ĐÁNH GIÁ & ĐIỂM SỐ (EXAM AUDIT LOG)</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Toàn bộ nhật ký các lần làm bài, thời gian, kết quả đạt/không đạt và điểm số chính thức.
        </p>
    </div>

    <?php if ($isAdminOrTrainer): ?>
    <div class="flex items-center space-x-1.5 bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <a href="history.php" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?= !$viewingAll ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-blue-700' ?>">
            Lịch sử của tôi
        </a>
        <a href="history.php?all=1" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?= $viewingAll ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-blue-700' ?>">
            <i class="fa-solid fa-users mr-1"></i> Tất cả Công nhân
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <?php if (empty($attempts)): ?>
        <div class="p-12 text-center text-slate-400">
            <i class="fa-regular fa-folder-open text-4xl mb-3 text-slate-300 dark:text-slate-600"></i>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Chưa có lịch sử làm bài thi nào được ghi nhận.</p>
            <p class="text-xs text-slate-400 mt-1"><a href="index.php" class="text-blue-700 dark:text-blue-400 font-semibold hover:underline">Vào lộ trình đào tạo</a> để bắt đầu làm bài thi đầu tiên.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Lần thi</th>
                        <?php if ($viewingAll): ?>
                        <th class="py-3.5 px-4">Công nhân</th>
                        <?php endif; ?>
                        <th class="py-3.5 px-4">Bài thi / Khóa học</th>
                        <th class="py-3.5 px-4 text-center">Điểm số</th>
                        <th class="py-3.5 px-4 text-center">Tỷ lệ</th>
                        <th class="py-3.5 px-4 text-center">Trạng thái</th>
                        <th class="py-3.5 px-4">Thời gian nộp</th>
                        <th class="py-3.5 px-4 text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                    <?php foreach ($attempts as $att): ?>
                    <?php 
                        $pct = ($att['exam_max_score'] > 0) ? (($att['score_obtained'] / $att['exam_max_score']) * 100) : 0;
                        $isPass = (bool)$att['passed'];
                        $isPending = ($att['status'] === 'PENDING_EVALUATION');
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-500 dark:text-slate-400">
                            #<?= $att['id'] ?> (Lần <?= $att['attempt_number'] ?>)
                        </td>

                        <?php if ($viewingAll): ?>
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($att['full_name']) ?></p>
                            <p class="text-[10px] text-blue-700 dark:text-blue-400 font-mono"><?= htmlspecialchars($att['employee_code']) ?> - <?= htmlspecialchars($att['department']) ?></p>
                        </td>
                        <?php endif; ?>

                        <td class="py-3.5 px-4">
                            <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($att['exam_title']) ?></p>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 mt-0.5">
                                Cấp độ <?= $att['course_level'] ?>
                            </span>
                        </td>

                        <td class="py-3.5 px-4 text-center font-mono font-bold text-sm <?= $isPass ? 'text-emerald-600 dark:text-emerald-400' : ($isPending ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') ?>">
                            <?= round($att['score_obtained'], 1) ?> <span class="text-slate-400 text-xs font-normal">/ <?= round($att['exam_max_score'], 0) ?></span>
                        </td>

                        <td class="py-3.5 px-4 text-center font-mono font-bold text-xs text-blue-700 dark:text-blue-400">
                            <?= round($pct, 1) ?>%
                        </td>

                        <td class="py-3.5 px-4 text-center">
                            <?php if ($isPending): ?>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30">
                                    <i class="fa-solid fa-hourglass-half mr-1"></i> Chờ chấm
                                </span>
                            <?php elseif ($isPass): ?>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
                                    <i class="fa-solid fa-circle-check mr-1"></i> ĐẠT (Passed)
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30">
                                    <i class="fa-solid fa-circle-xmark mr-1"></i> Chưa đạt
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                            <?= date('d/m/Y H:i', strtotime($att['created_at'])) ?>
                        </td>

                        <td class="py-3.5 px-4 text-right">
                            <a href="exam_result.php?attempt_id=<?= $att['id'] ?>" class="touch-tap px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-blue-700 dark:text-blue-400 font-bold text-xs border border-slate-200 dark:border-slate-700 transition inline-flex items-center space-x-1">
                                <span>Xem lại</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

