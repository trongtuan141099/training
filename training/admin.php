<?php
// admin.php - Factory Plant Administrator Dashboard
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireRole(['ADMIN']);

$currentUser = getAuthUser();
$db = getDB();

// Reset data cho mục đích kiểm thử nếu Admin yêu cầu
$msg = '';
if (isset($_POST['reset_worker_id'])) {
    $rUid = (int)$_POST['reset_worker_id'];
    $db->prepare('DELETE FROM exam_attempts WHERE user_id = :uid')->execute(['uid' => $rUid]);
    $db->prepare('UPDATE user_skills SET current_level = 0, certified_at = NULL WHERE user_id = :uid AND skill_id = 1')->execute(['uid' => $rUid]);
    $msg = 'Đã reset toàn bộ bài thi và cấp độ SMT của nhân viên về Level 0 để kiểm thử lại.';
}

// Thống kê tổng quan
$totalWorkers = (int)$db->query('SELECT COUNT(*) FROM users WHERE role = "WORKER"')->fetchColumn();
$totalAttempts = (int)$db->query('SELECT COUNT(*) FROM exam_attempts')->fetchColumn();
$totalPassed = (int)$db->query('SELECT COUNT(*) FROM exam_attempts WHERE passed = 1')->fetchColumn();
$passRate = ($totalAttempts > 0) ? round(($totalPassed / $totalAttempts) * 100, 1) : 0;

$workers = $db->query('SELECT * FROM users WHERE role = "WORKER" ORDER BY employee_code ASC')->fetchAll();

$pageTitle = 'Quản trị Toàn Xưởng (Admin Console)';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center space-x-2">
            <i class="fa-solid fa-gears text-purple-600 dark:text-purple-400"></i>
            <span>TRUNG TÂM ĐIỀU HÀNH ĐÀO TẠO & NĂNG LỰC NHÀ MÁY</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Báo cáo KPI đào tạo, tỷ lệ đạt chuẩn Lean Dojo và quản lý phân quyền công nhân.
        </p>
    </div>
</div>

<?php if ($msg): ?>
<div class="mb-6 p-4 rounded-xl bg-purple-50 dark:bg-purple-950/80 border border-purple-200 dark:border-purple-600 text-purple-800 dark:text-purple-300 text-xs flex items-center space-x-2 shadow-sm">
    <i class="fa-solid fa-circle-check text-base"></i>
    <span><?= htmlspecialchars($msg) ?></span>
</div>
<?php endif; ?>

<!-- KPI Metrics Grid (Card-based Layout) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tổng số Công nhân</p>
        <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1"><?= $totalWorkers ?></p>
        <span class="text-[11px] text-blue-700 dark:text-blue-400 mt-1 inline-block font-semibold"><i class="fa-solid fa-users"></i> Đang hoạt động</span>
    </div>

    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Lượt thi đã thực hiện</p>
        <p class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1"><?= $totalAttempts ?></p>
        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 inline-block"><i class="fa-solid fa-file-pen"></i> Toàn bộ các cấp</span>
    </div>

    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Số lượt Đạt chuẩn</p>
        <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?= $totalPassed ?></p>
        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 inline-block font-semibold"><i class="fa-solid fa-award"></i> Cấp chứng chỉ</span>
    </div>

    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tỷ lệ Đạt bài thi</p>
        <p class="text-2xl sm:text-3xl font-black text-blue-700 dark:text-blue-400 mt-1"><?= $passRate ?>%</p>
        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 inline-block"><i class="fa-solid fa-chart-line"></i> Benchmark &gt; 80%</span>
    </div>
</div>

<!-- Workers Management Table with Reset Action -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            <i class="fa-solid fa-user-shield text-purple-600 dark:text-purple-400 mr-2"></i> Danh sách Nhân viên & Trạng thái Kiểm thử
        </h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-bold">
                <tr>
                    <th class="py-3 px-4">Mã NV</th>
                    <th class="py-3 px-4">Họ và Tên</th>
                    <th class="py-3 px-4">Phân xưởng / Chuyền</th>
                    <th class="py-3 px-4">Vị trí trạm máy</th>
                    <th class="py-3 px-4 text-center">Thao tác Kiểm thử</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                <?php foreach ($workers as $w): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-mono font-bold text-blue-700 dark:text-blue-400"><?= htmlspecialchars($w['employee_code']) ?></td>
                    <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($w['full_name']) ?></td>
                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($w['department']) ?></td>
                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($w['line_station']) ?></td>
                    <td class="py-3.5 px-4 text-center">
                        <form method="POST" action="admin.php" onsubmit="return confirm('Bạn có chắc muốn Reset kết quả thi của công nhân này để kiểm tra lại từ đầu?');">
                            <input type="hidden" name="reset_worker_id" value="<?= $w['id'] ?>">
                            <button type="submit" class="touch-tap px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/80 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-[11px] font-bold transition">
                                <i class="fa-solid fa-rotate-left mr-1"></i> Reset về Level 0
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

