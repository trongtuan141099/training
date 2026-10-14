<?php
// exam.php - Interactive Examination Room
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
requireLogin();

$currentUser = getAuthUser();
$examId = (int)($_GET['exam_id'] ?? 0);
$db = getDB();

// Lấy thông tin bài thi
$stmtExam = $db->prepare('
    SELECT e.*, c.id AS course_id, c.title AS course_title, c.level AS course_level, c.min_pass_score,
           s.name AS skill_name
    FROM exams e
    JOIN courses c ON c.id = e.course_id
    JOIN skills s ON s.id = c.skill_id
    WHERE e.id = :id AND e.is_active = 1
    LIMIT 1
');
$stmtExam->execute(['id' => $examId]);
$exam = $stmtExam->fetch();

if (!$exam) {
    die('Bài thi không tồn tại hoặc đã bị khóa. <a href="index.php">Quay lại</a>');
}

// Bắt buộc kiểm tra điều kiện tiên quyết (Prerequisite check)
$access = canAccessCourse($currentUser['id'], (int)$exam['course_id']);
if (!$access['allowed']) {
    die('<div style="padding:30px;font-family:sans-serif;background:#450a0a;color:#fecaca;max-width:600px;margin:40px auto;border-radius:12px;border:1px solid #dc2626;">
        <h2 style="font-size:20px;font-weight:bold;margin-bottom:10px;"><i class="fa-solid fa-lock"></i> BÀI THI BỊ KHÓA</h2>
        <p>' . htmlspecialchars($access['reason']) . '</p>
        <p style="margin-top:20px;"><a href="index.php" style="color:#38bdf8;font-weight:bold;">&larr; Quay lại danh sách lộ trình</a></p>
    </div>');
}

// Lấy danh sách câu hỏi
$stmtQ = $db->prepare('SELECT * FROM questions WHERE exam_id = :eid ORDER BY sort_order ASC, id ASC');
$stmtQ->execute(['eid' => $examId]);
$questions = $stmtQ->fetchAll();

$pageTitle = 'Làm bài thi: ' . $exam['title'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Sticky Exam Header with Timer & Progress -->
<div class="sticky top-16 z-40 bg-slate-900/95 backdrop-blur-md border border-slate-800 rounded-2xl p-4 mb-6 shadow-2xl">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded bg-cyan-950 text-cyan-400 border border-cyan-800 text-[10px] font-bold uppercase">
                    Cấp độ <?= $exam['course_level'] ?>
                </span>
                <h1 class="text-sm sm:text-base font-black text-white truncate max-w-md">
                    <?= htmlspecialchars($exam['title']) ?>
                </h1>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">
                Tổng cộng: <strong class="text-white"><?= count($questions) ?> câu</strong> • Điểm chuẩn đạt: <strong class="text-emerald-400"><?= (float)$exam['min_pass_score'] ?>%</strong>
            </p>
        </div>

        <!-- Countdown Timer -->
        <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-amber-400 font-mono text-sm sm:text-base font-bold shadow-inner">
                <i class="fa-regular fa-clock animate-pulse"></i>
                <span id="countdownDisplay">--:--</span>
            </div>
            <button type="button" onclick="confirmSubmit()" class="touch-tap px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-lg shadow-emerald-950">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span>NỘP BÀI</span>
            </button>
        </div>
    </div>
</div>

<!-- Main Exam Form -->
<form id="examForm" method="POST" action="exam_submit.php" class="space-y-8">
    <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">

    <?php foreach ($questions as $idx => $q): ?>
    <?php 
        $qNum = $idx + 1;
        $qType = $q['question_type'];
        $config = json_decode($q['answer_config'], true) ?? [];
        $score = (float)$q['score'];
    ?>
    <div id="q_box_<?= $q['id'] ?>" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        
        <!-- Question Title Header -->
        <div class="px-5 py-4 bg-slate-800/80 border-b border-slate-700/80 flex items-start justify-between gap-3">
            <div class="flex items-start space-x-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-cyan-600 text-white font-bold text-xs shrink-0 mt-0.5">
                    <?= $qNum ?>
                </span>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-white leading-relaxed">
                        <?= htmlspecialchars($q['prompt']) ?>
                    </h2>
                    <span class="inline-block mt-1 text-[11px] font-semibold text-cyan-400">
                        <?php 
                            switch ($qType) {
                                case 'HOTSPOT_PINPOINT': echo '<i class="fa-solid fa-fingerprint mr-1"></i> Tương tác trực quan: Chạm điểm linh kiện trên ảnh'; break;
                                case 'POLYGON_DEFECT': echo '<i class="fa-solid fa-draw-polygon mr-1"></i> Tương tác cảm ứng: Khoanh vùng vị trí lỗi trên ảnh'; break;
                                case 'DRAG_DROP_SEQUENCE': echo '<i class="fa-solid fa-arrows-up-down mr-1"></i> Kéo thả / Đổi thứ tự quy trình các bước chuẩn'; break;
                                case 'ESSAY': echo '<i class="fa-solid fa-keyboard mr-1"></i> Trả lời tự luận (Trainer chấm điểm)'; break;
                                case 'MULTI_CHOICE': echo '<i class="fa-solid fa-square-check mr-1"></i> Trắc nghiệm nhiều lựa chọn'; break;
                                default: echo '<i class="fa-solid fa-circle-dot mr-1"></i> Trắc nghiệm 1 lựa chọn'; break;
                            }
                        ?>
                    </span>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-900 text-amber-400 border border-slate-700 shrink-0">
                <?= $score ?> Điểm
            </span>
        </div>

        <div class="p-5 sm:p-6 bg-slate-950/70">

            <!-- 1. SINGLE_CHOICE hoặc TRUE_FALSE -->
            <?php if ($qType === 'SINGLE_CHOICE' || $qType === 'TRUE_FALSE'): ?>
            <div class="space-y-3">
                <?php foreach ($config['options'] ?? [] as $opt): ?>
                <label class="flex items-center p-3.5 sm:p-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-cyan-600 cursor-pointer transition touch-manipulation">
                    <input type="radio" name="answers[<?= $q['id'] ?>][selected]" value="<?= htmlspecialchars($opt['id']) ?>"
                           class="w-5 h-5 text-cyan-600 bg-slate-950 border-slate-700 focus:ring-cyan-500 focus:ring-2">
                    <span class="ml-3.5 text-xs sm:text-sm font-medium text-slate-200">
                        <strong class="text-cyan-400 mr-1.5"><?= htmlspecialchars($opt['id']) ?>.</strong>
                        <?= htmlspecialchars($opt['text']) ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- 2. MULTI_CHOICE -->
            <?php elseif ($qType === 'MULTI_CHOICE'): ?>
            <div class="space-y-3">
                <p class="text-[11px] text-slate-400 italic mb-2">* Bạn có thể tích chọn nhiều hơn một đáp án đúng.</p>
                <?php foreach ($config['options'] ?? [] as $opt): ?>
                <label class="flex items-center p-3.5 sm:p-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-cyan-600 cursor-pointer transition touch-manipulation">
                    <input type="checkbox" name="answers[<?= $q['id'] ?>][selected][]" value="<?= htmlspecialchars($opt['id']) ?>"
                           class="w-5 h-5 rounded text-cyan-600 bg-slate-950 border-slate-700 focus:ring-cyan-500 focus:ring-2">
                    <span class="ml-3.5 text-xs sm:text-sm font-medium text-slate-200">
                        <strong class="text-cyan-400 mr-1.5"><?= htmlspecialchars($opt['id']) ?>.</strong>
                        <?= htmlspecialchars($opt['text']) ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- 3. DRAG_DROP_SEQUENCE -->
            <?php elseif ($qType === 'DRAG_DROP_SEQUENCE'): ?>
            <div>
                <p class="text-xs text-slate-400 mb-3">
                    <i class="fa-solid fa-hand-pointer text-cyan-400 mr-1"></i>
                    Dùng ngón tay <strong>kéo thả</strong> hoặc bấm nút <strong>mũi tên [Lên/Xuống]</strong> để sắp xếp thứ tự các bước:
                </p>
                <input type="hidden" id="seq_input_<?= $q['id'] ?>" name="answers[<?= $q['id'] ?>]" value="">

                <div id="seq_container_<?= $q['id'] ?>" class="space-y-2.5">
                    <?php 
                        // Đảo ngẫu nhiên các bước để người dùng tự sắp xếp
                        $shuffledItems = $config['items'] ?? [];
                        shuffle($shuffledItems);
                        foreach ($shuffledItems as $sIdx => $item):
                    ?>
                    <div class="sequence-item flex items-center justify-between p-3.5 rounded-xl bg-slate-900 border border-slate-700 hover:border-cyan-500 cursor-grab active:cursor-grabbing transition"
                         data-step-id="<?= htmlspecialchars($item['id']) ?>">
                        <div class="flex items-center space-x-3">
                            <span class="step-index-badge w-6 h-6 rounded-md bg-slate-800 text-cyan-400 font-mono font-bold text-xs flex items-center justify-center border border-slate-700">
                                <?= $sIdx + 1 ?>
                            </span>
                            <span class="text-xs sm:text-sm font-medium text-slate-200">
                                <?= htmlspecialchars($item['text']) ?>
                            </span>
                        </div>
                        <div class="flex items-center space-x-1 shrink-0 ml-2">
                            <button type="button" class="btn-move-up p-2 text-slate-400 hover:text-cyan-300 hover:bg-slate-800 rounded-lg touch-tap" title="Di chuyển lên">
                                <i class="fa-solid fa-chevron-up"></i>
                            </button>
                            <button type="button" class="btn-move-down p-2 text-slate-400 hover:text-cyan-300 hover:bg-slate-800 rounded-lg touch-tap" title="Di chuyển xuống">
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <span class="p-2 text-slate-500 cursor-grab hidden sm:inline-block">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. HOTSPOT_PINPOINT (Chạm điểm trên ảnh) -->
            <?php elseif ($qType === 'HOTSPOT_PINPOINT'): ?>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-slate-400">
                        <i class="fa-solid fa-circle-dot text-rose-500 mr-1"></i>
                        Hãy <strong>chạm hoặc bấm trực tiếp</strong> lên ảnh để đặt điểm định vị mục tiêu:
                    </p>
                    <button type="button" onclick="clearHotspot(<?= $q['id'] ?>)" class="text-xs text-slate-400 hover:text-rose-400 underline">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Đặt lại điểm chạm
                    </button>
                </div>

                <input type="hidden" id="hotspot_input_<?= $q['id'] ?>" name="answers[<?= $q['id'] ?>]" value="">

                <div class="relative w-full rounded-xl overflow-hidden border-2 border-slate-700 bg-slate-900 shadow-inner flex items-center justify-center">
                    <canvas id="canvas_<?= $q['id'] ?>" class="w-full cursor-crosshair touch-none"></canvas>
                </div>

                <div id="hotspot_status_<?= $q['id'] ?>" class="mt-2 text-[11px] text-slate-400 text-center font-mono">
                    Chưa chọn tọa độ. Chạm vào ảnh để ghim vị trí.
                </div>
            </div>

            <!-- 5. POLYGON_DEFECT (Khoanh vùng lỗi) -->
            <?php elseif ($qType === 'POLYGON_DEFECT'): ?>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-slate-400">
                        <i class="fa-solid fa-draw-polygon text-amber-400 mr-1"></i>
                        Dùng ngón tay <strong>vẽ khoanh vùng bao quanh</strong> khu vực phát hiện lỗi:
                    </p>
                    <button type="button" onclick="clearPolygon(<?= $q['id'] ?>)" class="text-xs text-slate-400 hover:text-rose-400 underline">
                        <i class="fa-solid fa-eraser mr-1"></i> Xóa vùng đã vẽ
                    </button>
                </div>

                <input type="hidden" id="polygon_input_<?= $q['id'] ?>" name="answers[<?= $q['id'] ?>]" value="">

                <div class="relative w-full rounded-xl overflow-hidden border-2 border-slate-700 bg-slate-900 shadow-inner flex items-center justify-center">
                    <canvas id="canvas_<?= $q['id'] ?>" class="w-full cursor-crosshair touch-none"></canvas>
                </div>

                <div id="polygon_status_<?= $q['id'] ?>" class="mt-2 text-[11px] text-slate-400 text-center font-mono">
                    Giữ và kéo ngón tay để vẽ vùng khoanh lỗi.
                </div>
            </div>

            <!-- 6. ESSAY (Tự luận) -->
            <?php elseif ($qType === 'ESSAY'): ?>
            <div>
                <p class="text-xs text-slate-400 mb-2">
                    <i class="fa-solid fa-pen mr-1 text-cyan-400"></i>
                    Nhập câu trả lời chi tiết của bạn vào khung bên dưới:
                </p>
                <textarea name="answers[<?= $q['id'] ?>][text]" rows="5" required
                          placeholder="Trình bày các bước thực hiện an toàn theo đúng quy chuẩn..."
                          class="w-full p-4 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition leading-relaxed"></textarea>
                <p class="text-[11px] text-slate-500 mt-1 italic">
                    * Câu hỏi tự luận sẽ được Trainer xem xét và chấm điểm thủ công sau khi nộp bài.
                </p>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>

    <!-- Bottom Submit Button -->
    <div class="pt-6 pb-12 text-center">
        <button type="button" onclick="confirmSubmit()" class="touch-tap px-10 py-4 bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white rounded-2xl font-black text-sm tracking-wide shadow-2xl shadow-emerald-950 transition active:scale-95 inline-flex items-center space-x-2">
            <i class="fa-solid fa-check-double text-lg"></i>
            <span>HOÀN TẤT & NỘP BÀI THI</span>
        </button>
    </div>

</form>

<!-- Modal Xác nhận nộp bài -->
<div id="submitConfirmModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl">
        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-clipboard-question"></i>
        </div>
        <h3 class="text-lg font-bold text-white mb-1">Xác nhận nộp bài thi?</h3>
        <p class="text-xs text-slate-400 mb-6">
            Bạn đã kiểm tra kỹ toàn bộ câu trả lời chưa? Sau khi nộp, hệ thống sẽ tự động chấm điểm và cập nhật Skillmap.
        </p>

        <div class="grid grid-cols-2 gap-3 text-xs font-bold">
            <button type="button" onclick="closeConfirmModal()" class="py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl transition">
                Kiểm tra lại
            </button>
            <button type="button" onclick="doFinalSubmit()" class="py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl transition shadow-lg shadow-emerald-950">
                Xác nhận nộp
            </button>
        </div>
    </div>
</div>

<!-- Thư viện JS -->
<script src="assets/js/interactive-canvas.js"></script>
<script src="assets/js/drag-drop.js"></script>

<script>
    // 1. Quản lý đồng hồ đếm ngược (Countdown Timer)
    const durationMinutes = <?= (int)$exam['duration_minutes'] ?>;
    let totalSeconds = durationMinutes * 60;
    const countdownEl = document.getElementById('countdownDisplay');

    const timerInterval = setInterval(() => {
        if (totalSeconds <= 0) {
            clearInterval(timerInterval);
            countdownEl.innerText = 'HẾT GIỜ';
            countdownEl.classList.add('text-red-500');
            alert('Hết giờ làm bài! Hệ thống đang tự động nộp bài thi của bạn.');
            document.getElementById('examForm').submit();
            return;
        }
        totalSeconds--;
        const mins = Math.floor(totalSeconds / 60);
        const secs = totalSeconds % 60;
        countdownEl.innerText = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }, 1000);

    // 2. Khởi tạo các thành phần Tương tác
    const canvasInstances = {};

    <?php foreach ($questions as $q): ?>
        <?php if ($q['question_type'] === 'DRAG_DROP_SEQUENCE'): ?>
            new SequenceSorter('seq_container_<?= $q['id'] ?>', {
                inputId: 'seq_input_<?= $q['id'] ?>'
            });
        <?php elseif ($q['question_type'] === 'HOTSPOT_PINPOINT'): ?>
            canvasInstances[<?= $q['id'] ?>] = new InteractiveCanvas('canvas_<?= $q['id'] ?>', {
                type: 'HOTSPOT_PINPOINT',
                imageUrl: '<?= htmlspecialchars($q['media_url'] ?? '') ?>',
                onUpdate: (data) => {
                    const input = document.getElementById('hotspot_input_<?= $q['id'] ?>');
                    const status = document.getElementById('hotspot_status_<?= $q['id'] ?>');
                    if (data) {
                        input.value = JSON.stringify(data);
                        status.innerText = `Đã ghim vị trí: X=${data.clicked_x_pct}%, Y=${data.clicked_y_pct}%`;
                        status.className = 'mt-2 text-[11px] text-emerald-400 text-center font-mono font-bold';
                    } else {
                        input.value = '';
                        status.innerText = 'Chưa chọn tọa độ.';
                        status.className = 'mt-2 text-[11px] text-slate-400 text-center font-mono';
                    }
                }
            });
        <?php elseif ($q['question_type'] === 'POLYGON_DEFECT'): ?>
            canvasInstances[<?= $q['id'] ?>] = new InteractiveCanvas('canvas_<?= $q['id'] ?>', {
                type: 'POLYGON_DEFECT',
                imageUrl: '<?= htmlspecialchars($q['media_url'] ?? '') ?>',
                onUpdate: (data) => {
                    const input = document.getElementById('polygon_input_<?= $q['id'] ?>');
                    const status = document.getElementById('polygon_status_<?= $q['id'] ?>');
                    if (data && data.drawn_polygon) {
                        input.value = JSON.stringify(data);
                        status.innerText = `Đã vẽ vùng lỗi (${data.drawn_polygon.length} điểm tọa độ).`;
                        status.className = 'mt-2 text-[11px] text-emerald-400 text-center font-mono font-bold';
                    } else {
                        input.value = '';
                        status.innerText = 'Chưa khoanh vùng lỗi.';
                        status.className = 'mt-2 text-[11px] text-slate-400 text-center font-mono';
                    }
                }
            });
        <?php endif; ?>
    <?php endforeach; ?>

    function clearHotspot(qid) {
        if (canvasInstances[qid]) {
            canvasInstances[qid].clear();
        }
    }

    function clearPolygon(qid) {
        if (canvasInstances[qid]) {
            canvasInstances[qid].clear();
        }
    }

    function confirmSubmit() {
        document.getElementById('submitConfirmModal').classList.remove('hidden');
    }

    function closeConfirmModal() {
        document.getElementById('submitConfirmModal').classList.add('hidden');
    }

    function doFinalSubmit() {
        document.getElementById('examForm').submit();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

