<?php
// index.php - Main Dashboard & Sequential Training Roadmap
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireLogin();

$currentUser = getAuthUser();
$db = getDB();
$pageTitle = 'Lộ trình Đào tạo';

// Lấy danh sách kỹ năng
$skills = $db->query('SELECT * FROM skills ORDER BY id ASC')->fetchAll();

// Lấy toàn bộ các khóa học kèm thông tin bài thi
$courses = $db->query('
    SELECT c.*, s.name AS skill_name, s.code AS skill_code,
           e.id AS exam_id, e.title AS exam_title, e.duration_minutes, e.total_score,
           (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) AS material_count
    FROM courses c
    JOIN skills s ON s.id = c.skill_id
    LEFT JOIN exams e ON e.course_id = c.id AND e.is_active = 1
    WHERE c.is_published = 1
    ORDER BY c.skill_id ASC, c.level ASC
')->fetchAll();

// Đánh giá quyền truy cập (Prerequisite status) cho từng khóa học đối với Worker hiện tại
$coursesBySkill = [];
foreach ($courses as $c) {
    $access = canAccessCourse($currentUser['id'], (int)$c['id']);
    
    // Kiểm tra lịch sử làm bài thi của chính khóa học này
    $stmtAtt = $db->prepare('
        SELECT MAX(score_obtained) AS highest_score,
               SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) AS passed_count,
               COUNT(id) AS total_attempts
        FROM exam_attempts 
        WHERE user_id = :uid AND exam_id = :eid
    ');
    $stmtAtt->execute(['uid' => $currentUser['id'], 'eid' => $c['exam_id']]);
    $attemptStat = $stmtAtt->fetch();

    $c['access'] = $access;
    $c['highest_score'] = (float)($attemptStat['highest_score'] ?? 0);
    $c['is_passed'] = ((int)($attemptStat['passed_count'] ?? 0) > 0);
    $c['total_attempts'] = (int)($attemptStat['total_attempts'] ?? 0);

    $coursesBySkill[$c['skill_id']][] = $c;
}

// Lấy Skillmap tóm tắt của user
$userSkills = getUserSkillmap($currentUser['id']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Worker Banner (Card-based Layout on Soft Gray Background) -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 sm:p-6 mb-8 shadow-sm">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shadow-inner">
                    <i class="fa-solid fa-user-gear text-base"></i>
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                    <?= htmlspecialchars($currentUser['full_name']) ?>
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                    <?= htmlspecialchars($currentUser['employee_code']) ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2 flex-wrap">
                <span><i class="fa-solid fa-industry text-slate-400 mr-1"></i><?= htmlspecialchars($currentUser['department'] ?? 'Phân xưởng') ?></span>
                <span class="text-slate-300 dark:text-slate-700">•</span>
                <span><i class="fa-solid fa-location-dot text-slate-400 mr-1"></i><?= htmlspecialchars($currentUser['line_station'] ?? 'Vị trí chuyền') ?></span>
            </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap w-full md:w-auto">
            <a href="skillmap.php" class="flex-1 md:flex-initial touch-tap px-4 py-2 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 transition flex items-center justify-center space-x-2 shadow-sm">
                <i class="fa-solid fa-chart-pie text-emerald-600"></i>
                <span>Xem Skillmap Cá Nhân</span>
            </a>
            <button onclick="simulateQRScan()" class="flex-1 md:flex-initial touch-tap px-4 py-2 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 shadow-md shadow-blue-700/20">
                <i class="fa-solid fa-qrcode"></i>
                <span>Quét QR Trạm Máy</span>
            </button>
        </div>
    </div>

    <!-- Skill Status Quick Chips -->
    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2.5 overflow-x-auto pb-1 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">Năng lực hiện tại:</span>
        <?php foreach ($userSkills as $us): ?>
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 whitespace-nowrap">
            <span class="text-slate-700 dark:text-slate-300 font-medium"><?= htmlspecialchars($us['skill_name']) ?>:</span>
            <?php if ($us['current_level'] == 0): ?>
                <span class="font-bold text-slate-400">Level 0</span>
            <?php elseif ($us['current_level'] == 1): ?>
                <span class="font-bold text-amber-600">Level 1 (I)</span>
            <?php elseif ($us['current_level'] == 2): ?>
                <span class="font-bold text-blue-700">Level 2 (L)</span>
            <?php else: ?>
                <span class="font-bold text-emerald-600">Level 3 (U/O)</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Lộ trình đào tạo theo từng Kỹ năng -->
<div class="space-y-8">
    <?php foreach ($skills as $skill): ?>
    <?php $skillCourses = $coursesBySkill[$skill['id']] ?? []; ?>
    <section class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl p-5 sm:p-7 shadow-sm">
        
        <!-- Skill Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6 gap-2">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-700"></span>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= htmlspecialchars($skill['name']) ?>
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= htmlspecialchars($skill['description']) ?></p>
            </div>
            <span class="self-start sm:self-center px-3 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-blue-900 dark:text-blue-300 text-xs font-mono font-bold rounded-lg">
                <?= htmlspecialchars($skill['code']) ?>
            </span>
        </div>

        <!-- Course Cards Workflow (Prerequisite Hierarchy) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
            <?php foreach ($skillCourses as $index => $c): ?>
            <?php 
                $isLocked = !$c['access']['allowed'];
                $isPassed = $c['is_passed'];
                $canTakeExam = $c['access']['allowed'] && !$isPassed;
            ?>
            <div class="relative flex flex-col justify-between rounded-xl p-5 border transition-all duration-200 
                <?= $isPassed 
                    ? 'bg-emerald-50/40 border-emerald-300 dark:bg-emerald-950/20 dark:border-emerald-800' 
                    : ($isLocked ? 'bg-slate-50/70 border-slate-200 opacity-75 dark:bg-slate-950/60 dark:border-slate-800' : 'bg-white border-blue-600 shadow-md shadow-blue-600/10 dark:bg-slate-900 dark:border-blue-700') ?>">
                
                <div>
                    <!-- Top Card Badge -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider
                            <?= $c['level'] == 1 ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : ($c['level'] == 2 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-purple-500/20 text-purple-300 border border-purple-500/30') ?>">
                            Cấp độ <?= $c['level'] ?>: <?= $c['level'] == 1 ? 'Cơ bản' : ($c['level'] == 2 ? 'Nâng cao' : 'Chuyên gia') ?>
                        </span>

                        <?php if ($isPassed): ?>
                            <span class="flex items-center text-xs font-bold text-emerald-400 bg-emerald-950 px-2.5 py-1 rounded-lg border border-emerald-700">
                                <i class="fa-solid fa-circle-check mr-1.5"></i> <?= __('status_passed') ?> (<?= round($c['highest_score'], 1) ?>%)
                            </span>
                        <?php elseif ($isLocked): ?>
                            <span class="flex items-center text-xs font-bold text-rose-400 bg-rose-950/80 px-2.5 py-1 rounded-lg border border-rose-800">
                                <i class="fa-solid fa-lock mr-1.5"></i> <?= __('status_locked') ?>
                            </span>
                        <?php else: ?>
                            <span class="flex items-center text-xs font-bold text-blue-400 bg-blue-950 px-2.5 py-1 rounded-lg border border-blue-700">
                                <i class="fa-solid fa-unlock mr-1.5"></i> <?= __('status_unlocked') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Course Title -->
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 leading-snug">
                        <?= htmlspecialchars($c['title']) ?>
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-4 line-clamp-3 leading-relaxed">
                        <?= htmlspecialchars($c['description']) ?>
                    </p>

                    <!-- Prerequisite rule note -->
                    <div class="p-2.5 rounded-lg mb-4 text-[11px] <?= $isLocked ? 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50' : 'bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-950 dark:text-slate-300 dark:border-slate-800' ?>">
                        <?php if ($c['level'] > 1): ?>
                            <i class="fa-solid fa-diagram-project mr-1 text-amber-500"></i>
                            <strong><?= __('prerequisite_course') ?>:</strong> <?= __('prerequisite_notice', ['min' => (float)$c['min_pass_score']]) ?>
                        <?php else: ?>
                            <i class="fa-solid fa-flag-checkered mr-1 text-emerald-600"></i>
                            <strong><?= __('min_pass_score') ?>:</strong> <?= (float)$c['min_pass_score'] ?>%
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bottom Actions -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-2">
                        <span><i class="fa-regular fa-file-lines mr-1 text-blue-700 dark:text-blue-400"></i> <?= $c['material_count'] ?> Tài liệu SOP</span>
                        <span><i class="fa-regular fa-clock mr-1 text-amber-600 dark:text-amber-400"></i> <?= $c['duration_minutes'] ?> phút</span>
                    </div>

                    <?php if ($isLocked): ?>
                        <div class="p-3 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl text-center">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <i class="fa-solid fa-lock text-rose-500 mr-1"></i> <?= htmlspecialchars($c['access']['reason']) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="course.php?id=<?= $c['id'] ?>" class="touch-tap py-2.5 px-3 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-white rounded-xl text-xs font-bold text-center transition flex items-center justify-center space-x-1.5 shadow-sm">
                                <i class="fa-solid fa-book-open text-blue-700 dark:text-blue-400"></i>
                                <span><?= __('btn_view_sop') ?></span>
                            </a>
                            <a href="exam.php?exam_id=<?= $c['exam_id'] ?>" class="touch-tap py-2.5 px-3 <?= $isPassed ? 'bg-slate-100 hover:bg-slate-200 text-blue-900 border border-slate-200 dark:bg-slate-800 dark:text-blue-300 dark:border-blue-800' : 'bg-[#1D4ED8] hover:bg-blue-700 text-white shadow-md shadow-blue-700/20' ?> rounded-xl text-xs font-bold text-center transition flex items-center justify-center space-x-1.5">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span><?= $isPassed ? __('btn_retake_exam') : __('btn_start_exam') ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </section>
    <?php endforeach; ?>
</div>

<!-- Modal Mô phỏng Quét QR Trạm Máy -->
<div id="qrModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-3xl shadow-inner">
            <i class="fa-solid fa-qrcode"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Quét mã QR Trạm máy Hiện trường</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Tại trạm máy, công nhân chỉ cần quét camera để mở ngay SOP và bài thi của máy đó.</p>

        <div class="space-y-2 text-left text-xs mb-6">
            <a href="course.php?id=1" class="block p-3 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 dark:bg-slate-800 dark:hover:bg-slate-700 transition">
                <p class="font-bold text-blue-700 dark:text-blue-400"><i class="fa-solid fa-barcode mr-1"></i> QR-SMT-LINE-01</p>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">Trạm máy Gắp chip SMT-101 (Cơ bản)</p>
            </a>
            <a href="course.php?id=2" class="block p-3 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 dark:bg-slate-800 dark:hover:bg-slate-700 transition">
                <p class="font-bold text-amber-600 dark:text-amber-400"><i class="fa-solid fa-barcode mr-1"></i> QR-SMT-LINE-02</p>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">Trạm máy Feeder Setup SMT-201 (Nâng cao)</p>
            </a>
            <a href="course.php?id=4" class="block p-3 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 dark:bg-slate-800 dark:hover:bg-slate-700 transition">
                <p class="font-bold text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-barcode mr-1"></i> QR-QC-INSPECTION</p>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">Trạm Soi lỗi Bo mạch QC-101</p>
            </a>
        </div>

        <button onclick="closeQRModal()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
            Đóng
        </button>
    </div>
</div>

<script>
    function simulateQRScan() {
        document.getElementById('qrModal').classList.remove('hidden');
    }
    function closeQRModal() {
        document.getElementById('qrModal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

