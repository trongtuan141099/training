<?php
// course.php - View Course SOP & Training Content
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireLogin();

$currentUser = getAuthUser();
$courseId = (int)($_GET['id'] ?? 0);

$db = getDB();
$stmt = $db->prepare('
    SELECT c.*, s.name AS skill_name, s.code AS skill_code,
           e.id AS exam_id, e.title AS exam_title, e.duration_minutes, e.total_score, e.max_attempts
    FROM courses c
    JOIN skills s ON s.id = c.skill_id
    LEFT JOIN exams e ON e.course_id = c.id AND e.is_active = 1
    WHERE c.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $courseId]);
$course = $stmt->fetch();

if (!$course) {
    die('Khóa học không tồn tại. <a href="index.php">Quay lại</a>');
}

// Kiểm tra điều kiện mở khóa
$access = canAccessCourse($currentUser['id'], $courseId);
if (!$access['allowed']) {
    die('<div style="padding:30px;font-family:sans-serif;background:#450a0a;color:#fecaca;max-width:600px;margin:40px auto;border-radius:12px;border:1px solid #dc2626;">
        <h2 style="font-size:20px;font-weight:bold;margin-bottom:10px;">KHÓA HỌC BỊ KHÓA</h2>
        <p>' . htmlspecialchars($access['reason']) . '</p>
        <p style="margin-top:20px;"><a href="index.php" style="color:#38bdf8;font-weight:bold;">&larr; Quay lại danh sách khóa học</a></p>
    </div>');
}

// Lấy danh sách tài liệu SOP
$stmtMat = $db->prepare('SELECT * FROM course_materials WHERE course_id = :cid ORDER BY sort_order ASC');
$stmtMat->execute(['cid' => $courseId]);
$materials = $stmtMat->fetchAll();

$pageTitle = $course['title'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Course Header -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 mb-6 shadow-sm">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-800 dark:text-blue-400 mb-2">
                <a href="index.php" class="hover:underline">&larr; <?= __('nav_roadmap') ?></a>
                <span>/</span>
                <span><?= htmlspecialchars($course['skill_name']) ?></span>
                <span>/</span>
                <span class="text-amber-600 font-bold">Cấp độ <?= $course['level'] ?></span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white"><?= htmlspecialchars($course['title']) ?></h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-3xl leading-relaxed"><?= htmlspecialchars($course['description']) ?></p>
        </div>

        <?php if (!empty($course['exam_id'])): ?>
        <a href="exam.php?exam_id=<?= $course['exam_id'] ?>" class="touch-tap px-6 py-3.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md shadow-blue-700/20 transition flex items-center space-x-2 active:scale-95">
            <i class="fa-solid fa-play"></i>
            <span>BẮT ĐẦU LÀM BÀI THI (<?= $course['duration_minutes'] ?> Phút)</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Nội dung đào tạo & Tài liệu SOP trực quan -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Cột trái (2/3): Hiển thị SOP / Hình ảnh tài liệu -->
    <div class="lg:col-span-2 space-y-6">
        <?php if (!empty($materials)): ?>
            <?php foreach ($materials as $mat): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                <div class="px-5 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/80 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-file-invoice text-blue-700 dark:text-blue-400"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($mat['title']) ?></h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold">
                        <?= htmlspecialchars($mat['material_type']) ?>
                    </span>
                </div>

                <div class="p-4 sm:p-6 bg-white dark:bg-slate-950">
                    <?php if ($mat['material_type'] === 'IMAGE_SOP' && file_exists(__DIR__ . '/' . $mat['file_path'])): ?>
                        <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shadow-inner">
                            <img src="<?= htmlspecialchars($mat['file_path']) ?>" alt="SOP Guide" class="w-full h-auto max-h-[500px] object-contain mx-auto">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($mat['content_text'])): ?>
                    <div class="mt-4 p-4 rounded-xl bg-blue-50/50 dark:bg-slate-900/90 border border-blue-100 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                        <p class="font-bold text-blue-800 dark:text-blue-300 mb-2"><i class="fa-solid fa-list-check mr-1.5"></i> Nội dung tóm tắt chuẩn:</p>
                        <p><?= nl2br(htmlspecialchars($mat['content_text'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center text-slate-500 dark:text-slate-400">
                <p>Khóa học hiện đang cập nhật tài liệu SOP.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cột phải (1/3): Thông tin kỳ thi & Tiêu chuẩn qua môn -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-circle-info text-blue-700 dark:text-blue-400"></i>
                <span>Quy định Đánh giá Năng lực</span>
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Điểm tối thiểu để ĐẠT:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm"><?= (float)$course['min_pass_score'] ?> / 100</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Thời gian làm bài:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400 text-sm"><?= $course['duration_minutes'] ?> phút</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Số lần thi tối đa:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?= $course['max_attempts'] ?> lần</span>
                </div>

                <div class="p-3.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-800/50 text-blue-900 dark:text-blue-300">
                    <p class="font-bold mb-1"><i class="fa-solid fa-medal mr-1"></i> Sau khi vượt qua bài thi:</p>
                    <p class="text-[11px] text-blue-800/90 dark:text-blue-400/90 leading-relaxed">
                        Hệ thống sẽ <strong>tự động cập nhật Ma trận Năng lực (Skillmap)</strong> của bạn lên Level <?= $course['level'] ?> và mở khóa cấp độ tiếp theo.
                    </p>
                </div>
            </div>

            <?php if (!empty($course['exam_id'])): ?>
            <div class="mt-6">
                <a href="exam.php?exam_id=<?= $course['exam_id'] ?>" class="touch-tap w-full py-3.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl font-bold text-xs text-center transition block shadow-md shadow-blue-700/20">
                    VÀO LÀM BÀI KIỂM TRA NGAY &rarr;
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

