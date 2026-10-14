<?php
// course.php - View Course SOP, Interactive Video & Training Content
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
        <h2 style="font-size:20px;font-weight:bold;margin-bottom:10px;"><i class="fa-solid fa-lock"></i> KHÓA HỌC BỊ KHÓA</h2>
        <p>' . htmlspecialchars($access['reason']) . '</p>
        <p style="margin-top:20px;"><a href="index.php" style="color:#38bdf8;font-weight:bold;">&larr; Quay lại danh sách khóa học</a></p>
    </div>');
}

// Lấy danh sách tài liệu SOP & Video đào tạo
$stmtMat = $db->prepare('SELECT * FROM course_materials WHERE course_id = :cid ORDER BY sort_order ASC, id ASC');
$stmtMat->execute(['cid' => $courseId]);
$materials = $stmtMat->fetchAll();

$pageTitle = $course['title'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Course Header -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 mb-6 shadow-sm">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-800 dark:text-blue-400 mb-2">
                <a href="index.php" class="hover:underline flex items-center gap-1">&larr; <?= __('nav_roadmap') ?></a>
                <span>/</span>
                <span><?= htmlspecialchars($course['skill_name']) ?></span>
                <span>/</span>
                <span class="text-amber-600 dark:text-amber-400 font-bold">Cấp độ <?= $course['level'] ?></span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white"><?= htmlspecialchars($course['title']) ?></h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-3xl leading-relaxed"><?= htmlspecialchars($course['description']) ?></p>
        </div>

        <?php if (!empty($course['exam_id'])): ?>
        <a href="exam.php?exam_id=<?= $course['exam_id'] ?>" class="touch-tap px-6 py-3.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md shadow-blue-700/20 transition flex items-center space-x-2 active:scale-95 shrink-0">
            <i class="fa-solid fa-play"></i>
            <span>BẮT ĐẦU LÀM BÀI THI (<?= $course['duration_minutes'] ?> Phút)</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Nội dung đào tạo & Tài liệu SOP trực quan -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Cột trái (2/3): Hiển thị SOP / Video / Hình ảnh tài liệu -->
    <div class="lg:col-span-2 space-y-6">
        <?php if (!empty($materials)): ?>
            <?php foreach ($materials as $mIdx => $mat): ?>
            <?php 
                $cues = !empty($mat['interactive_config']) ? json_decode($mat['interactive_config'], true) : [];
                $hasCues = !empty($cues) && is_array($cues);
                $matId = (int)$mat['id'];
            ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                
                <div class="px-5 py-4 bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-8 h-8 rounded-lg <?= $mat['material_type'] === 'VIDEO' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20' ?> flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid <?= $mat['material_type'] === 'VIDEO' ? 'fa-video' : ($mat['material_type'] === 'PDF' ? 'fa-file-pdf' : 'fa-file-invoice') ?>"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($mat['title']) ?></h3>
                            <span class="text-[10px] text-slate-400">Tài liệu số #<?= $mIdx + 1 ?></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <?php if ($hasCues): ?>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-300 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-700 animate-pulse">
                            <i class="fa-solid fa-bolt mr-1"></i> <?= count($cues) ?> Điểm Tương tác Pop-up
                        </span>
                        <?php endif; ?>
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-mono bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold uppercase">
                            <?= htmlspecialchars($mat['material_type']) ?>
                        </span>
                    </div>
                </div>

                <div class="p-4 sm:p-6 bg-white dark:bg-slate-950">
                    
                    <!-- 1. DẠNG VIDEO ĐÀO TẠO TƯƠNG TÁC (TASK 4 REQUIREMENT) -->
                    <?php if ($mat['material_type'] === 'VIDEO'): ?>
                    <div class="space-y-4">
                        
                        <?php if ($hasCues): ?>
                        <!-- Thanh thông báo các điểm dừng tương tác trên video -->
                        <div class="p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                <span class="font-bold text-amber-800 dark:text-amber-300">Tính năng Video Tương tác:</span>
                                <span class="text-slate-600 dark:text-slate-300">Video sẽ tự động tạm dừng ở các mốc thời gian để kiểm tra mức độ tập trung của bạn.</span>
                            </div>
                            <div class="hidden sm:flex items-center space-x-1.5 font-mono text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                <?php foreach ($cues as $cIdx => $c): ?>
                                <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700" title="<?= htmlspecialchars($c['question']) ?>">
                                    #<?= $cIdx + 1 ?>: <?= sprintf('%02d:%02d', floor($c['timestamp_sec']/60), $c['timestamp_sec']%60) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Video Player Container kèm Pop-up Quiz Overlay -->
                        <div id="videoContainer_<?= $matId ?>" class="relative w-full rounded-2xl overflow-hidden bg-black shadow-2xl border border-slate-800">
                            <video id="player_<?= $matId ?>" playsinline controls class="w-full max-h-[460px] object-contain block mx-auto">
                                <source src="<?= htmlspecialchars($mat['file_path']) ?>" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ phát thẻ video HTML5.
                            </video>

                            <!-- POP-UP INTERACTIVE MINI-QUIZ OVERLAY TRÊN VIDEO -->
                            <div id="quizOverlay_<?= $matId ?>" class="absolute inset-0 z-30 bg-slate-950/95 backdrop-blur-md p-6 flex flex-col justify-center items-center text-center hidden transition-all">
                                
                                <div class="max-w-md w-full bg-slate-900 border border-amber-500/40 rounded-2xl p-6 shadow-2xl space-y-4 text-left">
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-sm border border-amber-500/30">
                                                <i class="fa-solid fa-brain"></i>
                                            </span>
                                            <div>
                                                <h4 class="text-xs font-black text-amber-400 uppercase tracking-wider">Kiểm Tra Mức Độ Tập Trung</h4>
                                                <span id="quizTimestampBadge_<?= $matId ?>" class="text-[10px] text-slate-400 font-mono">Tại thời điểm 00:00</span>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-amber-950 text-amber-300 text-[10px] font-bold border border-amber-800">
                                            Tạm dừng video
                                        </span>
                                    </div>

                                    <!-- Câu hỏi -->
                                    <p id="quizQuestionText_<?= $matId ?>" class="text-sm font-bold text-white leading-snug">
                                        Đang tải câu hỏi kiểm tra...
                                    </p>

                                    <!-- Danh sách các lựa chọn đáp án -->
                                    <div id="quizOptionsContainer_<?= $matId ?>" class="space-y-2 text-xs"></div>

                                    <!-- Thông báo Kết quả trả lời (Đúng / Sai) -->
                                    <div id="quizFeedbackBox_<?= $matId ?>" class="hidden p-3 rounded-xl text-xs space-y-1"></div>

                                    <!-- Nút thao tác -->
                                    <div class="pt-2 flex items-center justify-between">
                                        <button type="button" id="btnRewind_<?= $matId ?>" onclick="rewindVideo(<?= $matId ?>, 5)" class="hidden touch-tap px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                                            <i class="fa-solid fa-rotate-left mr-1"></i> Xem lại 5s trước
                                        </button>
                                        <button type="button" id="btnSubmitQuiz_<?= $matId ?>" onclick="submitVideoQuiz(<?= $matId ?>)" class="touch-tap ml-auto px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-amber-950 flex items-center space-x-1.5">
                                            <span>Xác nhận trả lời</span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                        <button type="button" id="btnResumeVideo_<?= $matId ?>" onclick="resumeVideo(<?= $matId ?>)" class="hidden touch-tap ml-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-emerald-950 flex items-center space-x-1.5">
                                            <i class="fa-solid fa-play"></i>
                                            <span>Tiếp tục xem video</span>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                    <?php endif; ?>

                    <!-- 2. DẠNG HÌNH ẢNH SƠ ĐỒ SOP TRỰC QUAN -->
                    <?php if ($mat['material_type'] === 'IMAGE_SOP' && file_exists(__DIR__ . '/' . $mat['file_path'])): ?>
                        <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shadow-inner group relative">
                            <img src="<?= htmlspecialchars($mat['file_path']) ?>" alt="SOP Guide" class="w-full h-auto max-h-[500px] object-contain mx-auto cursor-zoom-in transition group-hover:opacity-95" onclick="window.open(this.src, '_blank')">
                            <div class="absolute bottom-2 right-2 bg-slate-900/80 backdrop-blur-sm text-white px-2.5 py-1 rounded-lg text-[11px] font-semibold opacity-0 group-hover:opacity-100 transition">
                                <i class="fa-solid fa-magnifying-glass-plus mr-1"></i> Phóng to ảnh
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 3. DẠNG TÀI LIỆU PDF -->
                    <?php if ($mat['material_type'] === 'PDF'): ?>
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-file-pdf text-rose-500 text-3xl"></i>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-white"><?= htmlspecialchars(basename($mat['file_path'])) ?></p>
                                    <p class="text-[11px] text-slate-400">Tài liệu hướng dẫn thao tác tiêu chuẩn quy chuẩn nhà máy</p>
                                </div>
                            </div>
                            <a href="<?= htmlspecialchars($mat['file_path']) ?>" target="_blank" class="touch-tap px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-600 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                                <i class="fa-solid fa-arrow-down mr-1"></i> Tải về / Đọc PDF
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Nội dung tóm tắt tiêu chuẩn SOP -->
                    <?php if (!empty($mat['content_text'])): ?>
                    <div class="mt-4 p-4 rounded-xl bg-blue-50/50 dark:bg-slate-900/90 border border-blue-100 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                        <p class="font-bold text-blue-800 dark:text-blue-300 mb-2 flex items-center space-x-1.5">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Nội dung hướng dẫn thao tác chuẩn (SOP Standard):</span>
                        </p>
                        <div class="whitespace-pre-line"><?= nl2br(htmlspecialchars($mat['content_text'])) ?></div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center text-slate-500 dark:text-slate-400">
                <i class="fa-solid fa-folder-open text-3xl text-slate-400 mb-2 block"></i>
                <p>Khóa học hiện đang cập nhật tài liệu SOP & Video.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cột phải (1/3): Thông tin kỳ thi & Tiêu chuẩn qua môn -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
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
                <a href="exam.php?exam_id=<?= $course['exam_id'] ?>" class="touch-tap w-full py-3.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl font-bold text-xs text-center transition block shadow-md shadow-blue-700/20 active:scale-95">
                    VÀO LÀM BÀI KIỂM TRA NGAY &rarr;
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Interactive Video Controller Script -->
<script>
    const interactiveVideoCues = {};
    const answeredCues = {}; // Lưu các điểm tương tác đã trả lời xong để không pop-up lại liên tục

    <?php foreach ($materials as $mat): ?>
        <?php if ($mat['material_type'] === 'VIDEO' && !empty($mat['interactive_config'])): ?>
            interactiveVideoCues[<?= $mat['id'] ?>] = <?= $mat['interactive_config'] ?>;
            answeredCues[<?= $mat['id'] ?>] = {};
        <?php endif; ?>
    <?php endforeach; ?>

    let currentActiveCue = null;

    // Khởi tạo trình lắng nghe video
    document.addEventListener('DOMContentLoaded', () => {
        Object.keys(interactiveVideoCues).forEach(matId => {
            const player = document.getElementById('player_' + matId);
            const cues = interactiveVideoCues[matId];
            if (!player || !cues || cues.length === 0) return;

            player.addEventListener('timeupdate', () => {
                const curTime = Math.floor(player.currentTime);
                // Tìm cue point khớp với giây hiện tại
                const matchedCue = cues.find(c => c.timestamp_sec === curTime);
                if (matchedCue && !answeredCues[matId][matchedCue.id]) {
                    // Tự động tạm dừng video và mở Pop-up Mini-Quiz
                    player.pause();
                    showInteractiveQuiz(matId, matchedCue);
                }
            });
        });
    });

    function showInteractiveQuiz(matId, cue) {
        currentActiveCue = { matId: matId, cue: cue };
        const overlay = document.getElementById('quizOverlay_' + matId);
        const qText = document.getElementById('quizQuestionText_' + matId);
        const timeBadge = document.getElementById('quizTimestampBadge_' + matId);
        const optContainer = document.getElementById('quizOptionsContainer_' + matId);
        const feedbackBox = document.getElementById('quizFeedbackBox_' + matId);
        const btnSubmit = document.getElementById('btnSubmitQuiz_' + matId);
        const btnResume = document.getElementById('btnResumeVideo_' + matId);
        const btnRewind = document.getElementById('btnRewind_' + matId);

        if (!overlay) return;

        // Reset trạng thái hiển thị
        feedbackBox.classList.add('hidden');
        feedbackBox.innerHTML = '';
        btnSubmit.classList.remove('hidden');
        btnResume.classList.add('hidden');
        btnRewind.classList.add('hidden');

        // Gán nội dung
        const mins = Math.floor(cue.timestamp_sec / 60);
        const secs = cue.timestamp_sec % 60;
        timeBadge.innerText = `Tại thời điểm ${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        qText.innerText = cue.question;

        // Render các phương án lựa chọn
        let optHtml = '';
        (cue.options || []).forEach(opt => {
            optHtml += `
                <label class="flex items-center p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-amber-500 cursor-pointer transition">
                    <input type="radio" name="video_quiz_opt_${matId}" value="${opt.id}" class="w-4 h-4 text-amber-500 bg-slate-900 border-slate-700">
                    <span class="ml-3 font-medium text-slate-200">
                        <strong class="text-amber-400 mr-1">${opt.id}.</strong> ${opt.text}
                    </span>
                </label>
            `;
        });
        optContainer.innerHTML = optHtml;

        overlay.classList.remove('hidden');
    }

    function submitVideoQuiz(matId) {
        if (!currentActiveCue || currentActiveCue.matId !== matId) return;
        const cue = currentActiveCue.cue;
        const selectedRadio = document.querySelector(`input[name="video_quiz_opt_${matId}"]:checked`);

        if (!selectedRadio) {
            alert('Vui lòng chọn một đáp án để xác nhận mức độ tập trung.');
            return;
        }

        const selectedVal = selectedRadio.value;
        const isCorrect = (selectedVal === cue.correct);
        const feedbackBox = document.getElementById('quizFeedbackBox_' + matId);
        const btnSubmit = document.getElementById('btnSubmitQuiz_' + matId);
        const btnResume = document.getElementById('btnResumeVideo_' + matId);
        const btnRewind = document.getElementById('btnRewind_' + matId);

        feedbackBox.classList.remove('hidden');
        btnSubmit.classList.add('hidden');

        if (isCorrect) {
            answeredCues[matId][cue.id] = true; // Đánh dấu đã vượt qua
            feedbackBox.className = 'p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-600 text-emerald-300 text-xs space-y-1';
            feedbackBox.innerHTML = `
                <p class="font-bold flex items-center gap-1.5 text-sm">
                    <i class="fa-solid fa-circle-check text-emerald-400"></i> CHÍNH XÁC! BẠN RẤT TẬP TRUNG.
                </p>
                <p class="text-emerald-200">${cue.explanation || 'Quy trình đã được ghi nhớ chính xác.'}</p>
            `;
            btnResume.classList.remove('hidden');
        } else {
            feedbackBox.className = 'p-3.5 rounded-xl bg-rose-950/80 border border-rose-600 text-rose-300 text-xs space-y-1';
            feedbackBox.innerHTML = `
                <p class="font-bold flex items-center gap-1.5 text-sm">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> CHƯA CHÍNH XÁC.
                </p>
                <p class="text-rose-200">${cue.explanation || 'Vui lòng xem lại đoạn video thao tác trước để nắm vững SOP.'}</p>
            `;
            btnRewind.classList.remove('hidden');
            btnResume.classList.remove('hidden');
            btnResume.innerText = 'Tiếp tục xem tiếp';
        }
    }

    function resumeVideo(matId) {
        const overlay = document.getElementById('quizOverlay_' + matId);
        const player = document.getElementById('player_' + matId);
        if (overlay) overlay.classList.add('hidden');
        if (player) {
            // Nhảy qua 1 giây để không bị kích hoạt lại timeupdate cùng giây
            player.currentTime += 1;
            player.play();
        }
    }

    function rewindVideo(matId, seconds) {
        const overlay = document.getElementById('quizOverlay_' + matId);
        const player = document.getElementById('player_' + matId);
        if (overlay) overlay.classList.add('hidden');
        if (player) {
            player.currentTime = Math.max(0, player.currentTime - seconds);
            player.play();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
