<?php
// skillmap.php - Visual Skill Matrix (Personal & Factory-Wide Overview)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireLogin();

$currentUser = getAuthUser();
$db = getDB();

$isTrainerOrAdmin = in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true);
$viewMode = ($_GET['mode'] ?? 'personal');
if (!$isTrainerOrAdmin) {
    $viewMode = 'personal';
}

$personalSkills = getUserSkillmap($currentUser['id']);
$factoryData = $isTrainerOrAdmin ? getFactorySkillmapOverview() : null;

$pageTitle = 'Ma trận Kỹ năng (Skillmap)';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Title & Mode Tabs -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center space-x-2">
            <i class="fa-solid fa-chart-pie text-blue-700 dark:text-blue-400"></i>
            <span>MA TRẬN NĂNG LỰC KỸ NĂNG (SKILLMAP MATRIX)</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Đánh giá năng lực theo chuẩn Lean Manufacturing Dojo (Cấp độ 0 - 3 / Tiêu chuẩn I-L-U-O)
        </p>
    </div>

    <?php if ($isTrainerOrAdmin): ?>
    <div class="flex items-center space-x-2 bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <a href="skillmap.php?mode=personal" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?= $viewMode === 'personal' ? 'bg-[#1D4ED8] text-white shadow' : 'text-slate-600 hover:text-blue-700 dark:text-slate-400' ?>">
            Cá nhân tôi
        </a>
        <a href="skillmap.php?mode=factory" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?= $viewMode === 'factory' ? 'bg-[#1D4ED8] text-white shadow' : 'text-slate-600 hover:text-blue-700 dark:text-slate-400' ?>">
            <i class="fa-solid fa-industry mr-1"></i> Toàn Phân Xưởng
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================== -->
<!-- 1. PERSONAL SKILLMAP VIEW                                      -->
<!-- ============================================================== -->
<?php if ($viewMode === 'personal'): ?>

<!-- Personal Info Card -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
    <div class="flex items-center space-x-3">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-xl font-bold shadow-inner">
            <?= substr($currentUser['full_name'], 0, 1) ?>
        </div>
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($currentUser['full_name']) ?></h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Mã NV: <strong class="text-blue-700 dark:text-blue-400 font-mono"><?= htmlspecialchars($currentUser['employee_code']) ?></strong> • <?= htmlspecialchars($currentUser['department'] ?? 'Xưởng sản xuất') ?></p>
        </div>
    </div>

    <!-- Dojo Legend -->
    <div class="flex items-center gap-2 text-[11px] bg-slate-50 dark:bg-slate-950 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 flex-wrap">
        <span class="text-slate-500 font-semibold">Quy chuẩn:</span>
        <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-600 font-bold">L0: Chưa đạt</span>
        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-bold">L1 (I): Có kèm cặp</span>
        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-bold">L2 (L): Độc lập</span>
        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">L3 (U/O): Đào tạo</span>
    </div>
</div>

<!-- Personal Skills Detailed Table -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="py-4 px-5">Chuyên môn / Kỹ năng</th>
                    <th class="py-4 px-4 text-center">Level 0<br><span class="text-[9px] text-slate-400 font-normal">Chưa đào tạo</span></th>
                    <th class="py-4 px-4 text-center">Level 1 (I)<br><span class="text-[9px] text-slate-400 font-normal">Có giám sát</span></th>
                    <th class="py-4 px-4 text-center">Level 2 (L)<br><span class="text-[9px] text-slate-400 font-normal">Độc lập vận hành</span></th>
                    <th class="py-4 px-4 text-center">Level 3 (U/O)<br><span class="text-[9px] text-slate-400 font-normal">Chuyên gia / Đào tạo</span></th>
                    <th class="py-4 px-5 text-right">Ngày công nhận</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200">
                <?php foreach ($personalSkills as $s): ?>
                <?php $lvl = (int)$s['current_level']; ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                    <td class="py-4 px-5">
                        <p class="font-bold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($s['skill_name']) ?></p>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-mono"><?= htmlspecialchars($s['code']) ?></p>
                    </td>

                    <!-- Level 0 Column -->
                    <td class="py-4 px-4 text-center">
                        <?php if ($lvl === 0): ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 font-bold border border-slate-200">
                                <i class="fa-solid fa-circle-dot"></i>
                            </span>
                        <?php else: ?>
                            <span class="text-slate-300">-</span>
                        <?php endif; ?>
                    </td>

                    <!-- Level 1 Column -->
                    <td class="py-4 px-4 text-center">
                        <?php if ($lvl >= 1): ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 text-amber-600 border border-amber-200 font-bold" title="Cấp 1: Có thể thao tác dưới sự kèm cặp">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        <?php else: ?>
                            <span class="text-slate-300"><i class="fa-solid fa-lock text-xs"></i></span>
                        <?php endif; ?>
                    </td>

                    <!-- Level 2 Column -->
                    <td class="py-4 px-4 text-center">
                        <?php if ($lvl >= 2): ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold" title="Cấp 2: Độc lập thao tác chuẩn">
                                <i class="fa-solid fa-check-double"></i>
                            </span>
                        <?php else: ?>
                            <span class="text-slate-300"><i class="fa-solid fa-lock text-xs"></i></span>
                        <?php endif; ?>
                    </td>

                    <!-- Level 3 Column -->
                    <td class="py-4 px-4 text-center">
                        <?php if ($lvl >= 3): ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 font-bold" title="Cấp 3: Chuyên gia Dojo">
                                <i class="fa-solid fa-award"></i>
                            </span>
                        <?php else: ?>
                            <span class="text-slate-300"><i class="fa-solid fa-lock text-xs"></i></span>
                        <?php endif; ?>
                    </td>

                    <td class="py-4 px-5 text-right font-mono text-[11px] text-slate-500">
                        <?= !empty($s['certified_at']) ? date('d/m/Y H:i', strtotime($s['certified_at'])) : 'Chưa cấp chứng chỉ' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================== -->
<!-- 2. FACTORY-WIDE SKILLMAP OVERVIEW (Trainer / Admin)            -->
<!-- ============================================================== -->
<?php else: ?>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
    <div class="p-5 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                <i class="fa-solid fa-table-cells text-blue-700 dark:text-blue-400 mr-1.5"></i> Bảng tổng hợp Năng lực Công nhân toàn Nhà máy
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Dữ liệu thời gian thực được đồng bộ trực tiếp từ kết quả bài thi hiện trường</p>
        </div>
        <span class="text-xs text-slate-500 font-semibold">
            Tổng số công nhân: <strong class="text-slate-900 dark:text-white"><?= count($factoryData['workers']) ?></strong>
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Mã NV</th>
                    <th class="py-3 px-4">Họ và Tên</th>
                    <th class="py-3 px-4">Chuyền / Vị trí</th>
                    <?php foreach ($factoryData['skills'] as $sk): ?>
                    <th class="py-3 px-4 text-center"><?= htmlspecialchars($sk['code']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-200">
                <?php foreach ($factoryData['workers'] as $w): ?>
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-mono font-bold text-cyan-400"><?= htmlspecialchars($w['employee_code']) ?></td>
                    <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($w['full_name']) ?></td>
                    <td class="py-3.5 px-4 text-slate-400"><?= htmlspecialchars($w['department'] . ' - ' . $w['line_station']) ?></td>

                    <?php foreach ($factoryData['skills'] as $sk): ?>
                    <?php 
                        $workerSkill = $factoryData['matrix'][$w['id']][$sk['id']] ?? null;
                        $wLevel = $workerSkill ? $workerSkill['level'] : 0;
                    ?>
                    <td class="py-3.5 px-4 text-center">
                        <?php if ($wLevel === 0): ?>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-800 text-slate-500">L0 (Chưa)</span>
                        <?php elseif ($wLevel === 1): ?>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">L1 (I)</span>
                        <?php elseif ($wLevel === 2): ?>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">L2 (L)</span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">L3 (U/O)</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

