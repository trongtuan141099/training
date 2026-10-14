<?php
// exam_builder.php - Industrial Exam Designer & Interactive Question Builder
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
require_once __DIR__ . '/includes/lang.php';
requireRole(['ADMIN', 'TRAINER']);

$currentUser = getAuthUser();
$db = getDB();

$message = '';
$messageType = 'success'; // 'success' | 'danger'

// XỬ LÝ 1: TẠO BÀI THI / KHÓA HỌC MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_exam') {
    $skillId = (int)$_POST['skill_id'];
    $title = trim($_POST['title']);
    $level = (int)$_POST['level'];
    $prereqId = !empty($_POST['prerequisite_course_id']) ? (int)$_POST['prerequisite_course_id'] : null;
    $minPassScore = (float)($_POST['min_pass_score'] ?? 80.0);
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 20);
    $maxAttempts = (int)($_POST['max_attempts'] ?? 5);

    try {
        $db->beginTransaction();

        // Tạo khóa học (Course)
        $stmtC = $db->prepare('
            INSERT INTO courses (skill_id, title, level, prerequisite_course_id, min_pass_score, description, is_published)
            VALUES (:sid, :title, :lvl, :prereq, :min_score, :desc, 1)
        ');
        $stmtC->execute([
            'sid'       => $skillId,
            'title'     => $title,
            'lvl'       => $level,
            'prereq'    => $prereqId,
            'min_score' => $minPassScore,
            'desc'      => $description
        ]);
        $newCourseId = (int)$db->lastInsertId();

        // Tạo bài kiểm tra (Exam) tương ứng
        $stmtE = $db->prepare('
            INSERT INTO exams (course_id, title, duration_minutes, total_score, max_attempts, is_active)
            VALUES (:cid, :etitle, :dur, 100.00, :att, 1)
        ');
        $stmtE->execute([
            'cid'    => $newCourseId,
            'etitle' => 'Bài thi đánh giá: ' . $title,
            'dur'    => $duration,
            'att'    => $maxAttempts
        ]);
        $newExamId = (int)$db->lastInsertId();

        $db->commit();
        $message = 'Đã tạo thành công khóa học và bài thi mới (ID: #' . $newExamId . ')!';
        header('Location: exam_builder.php?exam_id=' . $newExamId . '&msg=' . urlencode($message));
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Lỗi tạo bài thi: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// XỬ LÝ 2: THÊM CÂU HỎI VÀO BÀI THI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $targetExamId = (int)$_POST['exam_id'];
    $qType = $_POST['question_type'];
    $prompt = trim($_POST['prompt']);
    $score = (float)($_POST['score'] ?? 20.0);
    $mediaUrl = null;

    // Xử lý upload ảnh cho câu hỏi tương tác (nếu có)
    if (!empty($_FILES['media_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/questions/';
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
            $newFileName = 'q_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $uploadDir . $newFileName)) {
                $mediaUrl = 'uploads/questions/' . $newFileName;
            }
        }
    } elseif (!empty($_POST['preset_media'])) {
        $mediaUrl = $_POST['preset_media'];
    }

    // Xây dựng JSON cấu hình đáp án theo từng loại
    $answerConfig = [];

    if ($qType === 'SINGLE_CHOICE' || $qType === 'TRUE_FALSE') {
        $opts = [];
        $rawOpts = $_POST['choice_text'] ?? [];
        $correct = $_POST['choice_correct'] ?? '';
        foreach ($rawOpts as $key => $txt) {
            if (trim($txt) !== '') {
                $opts[] = ['id' => $key, 'text' => trim($txt)];
            }
        }
        $answerConfig = [
            'options' => $opts,
            'correct' => [$correct]
        ];
    } elseif ($qType === 'MULTI_CHOICE') {
        $opts = [];
        $rawOpts = $_POST['multi_text'] ?? [];
        $correctArr = $_POST['multi_correct'] ?? [];
        foreach ($rawOpts as $key => $txt) {
            if (trim($txt) !== '') {
                $opts[] = ['id' => $key, 'text' => trim($txt)];
            }
        }
        $answerConfig = [
            'options' => $opts,
            'correct' => $correctArr
        ];
    } elseif ($qType === 'HOTSPOT_PINPOINT') {
        $answerConfig = [
            'target_x_pct'         => (float)($_POST['hotspot_x'] ?? 50.0),
            'target_y_pct'         => (float)($_POST['hotspot_y'] ?? 50.0),
            'tolerance_radius_pct' => (float)($_POST['hotspot_tolerance'] ?? 7.5),
            'hint'                 => trim($_POST['hotspot_hint'] ?? '')
        ];
    } elseif ($qType === 'POLYGON_DEFECT') {
        $polyPoints = !empty($_POST['polygon_json']) ? json_decode($_POST['polygon_json'], true) : [];
        $answerConfig = [
            'center_x_pct'         => (float)($_POST['poly_center_x'] ?? 50.0),
            'center_y_pct'         => (float)($_POST['poly_center_y'] ?? 44.0),
            'tolerance_radius_pct' => (float)($_POST['poly_tolerance'] ?? 10.0),
            'polygon_points'       => $polyPoints ?: []
        ];
    } elseif ($qType === 'DRAG_DROP_SEQUENCE') {
        $seqSteps = $_POST['seq_steps'] ?? [];
        $items = [];
        $correctOrder = [];
        foreach ($seqSteps as $sIdx => $stext) {
            if (trim($stext) !== '') {
                $sid = 'step_' . ($sIdx + 1);
                $items[] = ['id' => $sid, 'text' => trim($stext)];
                $correctOrder[] = $sid;
            }
        }
        $answerConfig = [
            'items'         => $items,
            'correct_order' => $correctOrder
        ];
    } elseif ($qType === 'ESSAY') {
        $answerConfig = [
            'rubric'    => trim($_POST['essay_rubric'] ?? ''),
            'min_words' => (int)($_POST['essay_min_words'] ?? 15)
        ];
    }

    try {
        $stmtQ = $db->prepare('
            INSERT INTO questions (exam_id, question_type, prompt, media_url, answer_config, score, sort_order)
            VALUES (:eid, :qtype, :prompt, :media, :config, :score, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM questions q2 WHERE q2.exam_id = :eid2))
        ');
        $stmtQ->execute([
            'eid'    => $targetExamId,
            'qtype'  => $qType,
            'prompt' => $prompt,
            'media'  => $mediaUrl,
            'config' => json_encode($answerConfig, JSON_UNESCAPED_UNICODE),
            'score'  => $score,
            'eid2'   => $targetExamId
        ]);
        $message = 'Đã thêm câu hỏi mới thành công vào bài thi!';
        header('Location: exam_builder.php?exam_id=' . $targetExamId . '&msg=' . urlencode($message));
        exit;
    } catch (Exception $e) {
        $message = 'Lỗi thêm câu hỏi: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// XỬ LÝ 3: XÓA CÂU HỎI
if (isset($_GET['delete_q'])) {
    $delQId = (int)$_GET['delete_q'];
    $curExam = (int)($_GET['exam_id'] ?? 0);
    $db->prepare('DELETE FROM questions WHERE id = :id')->execute(['id' => $delQId]);
    header('Location: exam_builder.php?exam_id=' . $curExam . '&msg=' . urlencode('Đã xóa câu hỏi.'));
    exit;
}

if (!empty($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Lấy danh sách kỹ năng, khóa học & bài thi hiện có
$skills = $db->query('SELECT * FROM skills ORDER BY id ASC')->fetchAll();
$allCourses = $db->query('SELECT id, title, level, skill_id FROM courses ORDER BY skill_id ASC, level ASC')->fetchAll();
$allExams = $db->query('
    SELECT e.*, c.title AS course_title, c.level AS course_level, s.name AS skill_name,
           (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) AS question_count
    FROM exams e
    JOIN courses c ON c.id = e.course_id
    JOIN skills s ON s.id = c.skill_id
    ORDER BY e.id DESC
')->fetchAll();

// Bài thi đang được chọn để thêm câu hỏi
$selectedExamId = (int)($_GET['exam_id'] ?? ($allExams[0]['id'] ?? 0));
$currentExam = null;
$currentQuestions = [];

if ($selectedExamId > 0) {
    $stmtCur = $db->prepare('
        SELECT e.*, c.title AS course_title, c.level AS course_level, c.min_pass_score, c.prerequisite_course_id, s.name AS skill_name
        FROM exams e
        JOIN courses c ON c.id = e.course_id
        JOIN skills s ON s.id = c.skill_id
        WHERE e.id = :id
        LIMIT 1
    ');
    $stmtCur->execute(['id' => $selectedExamId]);
    $currentExam = $stmtCur->fetch();

    if ($currentExam) {
        $stmtQs = $db->prepare('SELECT * FROM questions WHERE exam_id = :eid ORDER BY sort_order ASC, id ASC');
        $stmtQs->execute(['eid' => $selectedExamId]);
        $currentQuestions = $stmtQs->fetchAll();
    }
}

$pageTitle = 'Thiết kế & Tạo Bài thi (Exam Builder)';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Title & Introduction Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800 uppercase">
                DOJO CURRICULUM ARCHITECT
            </span>
            <span class="text-slate-400">•</span>
            <span class="text-xs text-slate-500">Chặn cấp độ & Đa dạng hình thức tương tác</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 flex items-center space-x-2">
            <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
            <span>THIẾT KẾ & TẠO BÀI THI NĂNG LỰC (EXAM BUILDER)</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Xây dựng nội dung đánh giá theo chuẩn Lean Dojo: Cấp 1 (Cơ bản) &rarr; Cấp 2 (Nâng cao) &rarr; Cấp 3 (Chuyên gia).
        </p>
    </div>

    <!-- Nút mở Modal Tạo Bài Thi Mới -->
    <button onclick="document.getElementById('createExamModal').classList.remove('hidden')" class="touch-tap px-4 py-2.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-md shadow-blue-700/20">
        <i class="fa-solid fa-plus"></i>
        <span>Tạo Khóa học / Bài thi Mới</span>
    </button>
</div>

<?php if ($message): ?>
<div class="mb-6 p-4 rounded-xl <?= $messageType === 'danger' ? 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-950/80 dark:border-rose-600 dark:text-rose-300' : 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/80 dark:border-emerald-600 dark:text-emerald-300' ?> border text-xs flex items-center space-x-2 shadow-sm">
    <i class="fa-solid <?= $messageType === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> text-base"></i>
    <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Main 2-Column Grid: Left (List Exams & Select) / Right (Manage Questions) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Cột trái (1/3): Danh sách các bài thi hiện có -->
    <div class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    <i class="fa-solid fa-list-check text-blue-700 dark:text-blue-400 mr-1.5"></i> Danh sách Bài thi (<?= count($allExams) ?>)
                </h2>
                <span class="text-[10px] text-slate-400 font-mono">Chọn để sửa</span>
            </div>

            <div class="space-y-2 max-h-[600px] overflow-y-auto pr-1">
                <?php foreach ($allExams as $ex): ?>
                <?php $isAct = ($ex['id'] == $selectedExamId); ?>
                <a href="exam_builder.php?exam_id=<?= $ex['id'] ?>" class="block p-3.5 rounded-xl border transition <?= $isAct ? 'bg-blue-50/70 border-blue-500 shadow-sm dark:bg-blue-950/80 dark:border-blue-500' : 'bg-slate-50/60 dark:bg-slate-950/70 border-slate-200 dark:border-slate-800 hover:border-slate-300' ?>">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase
                            <?= $ex['course_level'] == 1 ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : ($ex['course_level'] == 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300') ?>">
                            Cấp độ <?= $ex['course_level'] ?>
                        </span>
                        <span class="text-[11px] font-mono text-blue-700 dark:text-cyan-400 font-bold"><?= $ex['question_count'] ?> câu hỏi</span>
                    </div>
                    <p class="text-xs font-bold text-slate-900 dark:text-white leading-snug"><?= htmlspecialchars($ex['title']) ?></p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1"><?= htmlspecialchars($ex['skill_name']) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải (2/3): Quản lý Câu hỏi của Bài thi đã chọn -->
    <div class="lg:col-span-2 space-y-6">
        <?php if ($currentExam): ?>
        
        <!-- Header thông tin bài thi đang mở -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-blue-700 text-white">
                        ĐANG BIÊN SOẠN: BÀI THI #<?= $currentExam['id'] ?>
                    </span>
                    <h2 class="text-base font-black text-slate-900 dark:text-white mt-1"><?= htmlspecialchars($currentExam['title']) ?></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Chuyên môn: <strong class="text-slate-800 dark:text-slate-300"><?= htmlspecialchars($currentExam['skill_name']) ?></strong> • Cấp độ: <strong class="text-amber-600 dark:text-amber-400"><?= $currentExam['course_level'] ?></strong>
                    </p>
                </div>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-emerald-700 dark:text-emerald-400 font-bold">
                        Đạt: &ge; <?= (float)$currentExam['min_pass_score'] ?>%
                    </span>
                    <span class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-amber-700 dark:text-amber-400 font-bold">
                        <?= $currentExam['duration_minutes'] ?> phút
                    </span>
                </div>
            </div>

            <!-- Prerequisite Note -->
            <div class="mt-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 text-xs flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-400">
                    <i class="fa-solid fa-lock text-amber-500 mr-1.5"></i>
                    <strong>Quy tắc khóa cấp độ:</strong> 
                    <?= !empty($currentExam['prerequisite_course_id']) ? 'Bắt buộc đạt điểm tối thiểu ' . (float)$currentExam['min_pass_score'] . '% ở cấp trước để mở khóa.' : 'Cấp 1 cơ bản - Mở khóa tự do cho mọi công nhân.' ?>
                </span>
                <a href="exam.php?exam_id=<?= $currentExam['id'] ?>" target="_blank" class="text-blue-700 dark:text-blue-400 hover:underline font-bold text-[11px] whitespace-nowrap ml-2">
                    <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Xem giao diện thi thực tế
                </a>
            </div>
        </div>

        <!-- Danh sách các câu hỏi hiện có trong bài thi -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">
                    <i class="fa-solid fa-list-ol text-cyan-400 mr-1"></i> Các câu hỏi hiện có (<?= count($currentQuestions) ?>)
                </h3>
                <button onclick="document.getElementById('addQuestionDrawer').classList.remove('hidden')" class="touch-tap px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-md">
                    <i class="fa-solid fa-plus"></i>
                    <span>Thêm câu hỏi mới</span>
                </button>
            </div>

            <?php if (empty($currentQuestions)): ?>
                <div class="p-8 text-center text-slate-500 text-xs bg-slate-950/50 rounded-xl border border-slate-800/80">
                    <p>Bài thi này chưa có câu hỏi nào. Bấm nút "Thêm câu hỏi mới" để bắt đầu thiết kế.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($currentQuestions as $qIdx => $cq): ?>
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-start justify-between gap-3 hover:border-slate-700 transition">
                        <div class="flex items-start space-x-3">
                            <span class="w-6 h-6 rounded-md bg-blue-600/30 text-blue-300 font-bold text-xs flex items-center justify-center border border-blue-500/40 shrink-0 mt-0.5">
                                <?= $qIdx + 1 ?>
                            </span>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-white"><?= htmlspecialchars($cq['prompt']) ?></p>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap text-[11px]">
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-cyan-400 font-mono">
                                        <?= htmlspecialchars($cq['question_type']) ?>
                                    </span>
                                    <span class="text-amber-400 font-bold"><?= (float)$cq['score'] ?> Điểm</span>
                                    <?php if ($cq['media_url']): ?>
                                    <span class="text-slate-400"><i class="fa-solid fa-image text-blue-400 mr-1"></i> Có ảnh tương tác</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <a href="exam_builder.php?exam_id=<?= $selectedExamId ?>&delete_q=<?= $cq['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');" class="text-slate-500 hover:text-rose-400 p-2 text-xs transition" title="Xóa câu hỏi">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Khung Thêm Câu Hỏi Mới (Accordion / Drawer Form) -->
        <div id="addQuestionDrawer" class="bg-slate-900 border border-slate-700 rounded-2xl p-6 shadow-2xl space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-black text-white flex items-center space-x-2">
                    <i class="fa-solid fa-circle-plus text-blue-400"></i>
                    <span>THÊM CÂU HỎI MỚI VÀO BÀI THI</span>
                </h3>
                <button type="button" onclick="document.getElementById('addQuestionDrawer').classList.add('hidden')" class="text-slate-400 hover:text-white text-xs">
                    <i class="fa-solid fa-xmark mr-1"></i> Đóng lại
                </button>
            </div>

            <form method="POST" action="exam_builder.php" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="exam_id" value="<?= $currentExam['id'] ?>">

                <!-- Chọn Loại câu hỏi -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Hình thức câu hỏi (Question Type):
                    </label>
                    <select id="questionTypeSelect" name="question_type" onchange="switchQuestionType(this.value)" class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="SINGLE_CHOICE"><?= __('q_type_single') ?></option>
                        <option value="MULTI_CHOICE"><?= __('q_type_multi') ?></option>
                        <option value="TRUE_FALSE"><?= __('q_type_tf') ?></option>
                        <option value="HOTSPOT_PINPOINT"><?= __('q_type_hotspot') ?></option>
                        <option value="POLYGON_DEFECT"><?= __('q_type_polygon') ?></option>
                        <option value="DRAG_DROP_SEQUENCE"><?= __('q_type_sequence') ?></option>
                        <option value="ESSAY"><?= __('q_type_essay') ?></option>
                    </select>
                </div>

                <!-- Đề bài câu hỏi -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Nội dung Đề bài / Yêu cầu thao tác:
                    </label>
                    <textarea name="prompt" rows="3" required placeholder="Nhập câu hỏi hoặc hướng dẫn thao tác cho công nhân..." class="w-full p-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <!-- Điểm số của câu -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Điểm số của câu này (Score):
                    </label>
                    <input type="number" step="1" name="score" value="20" required class="w-36 px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-amber-400 font-bold focus:outline-none focus:border-blue-500">
                </div>

                <!-- Sub-form 1: Trắc nghiệm 1 đáp án (SINGLE_CHOICE) -->
                <div id="subform_SINGLE_CHOICE" class="type-subform space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-blue-400">Cấu hình các lựa chọn & Đáp án đúng:</p>
                    <div class="space-y-2 text-xs">
                        <?php foreach (['A', 'B', 'C', 'D'] as $optK): ?>
                        <div class="flex items-center space-x-2">
                            <input type="radio" name="choice_correct" value="<?= $optK ?>" <?= $optK === 'A' ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                            <span class="w-6 font-bold text-cyan-400"><?= $optK ?>:</span>
                            <input type="text" name="choice_text[<?= $optK ?>]" placeholder="Nội dung phương án <?= $optK ?>" class="flex-1 p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sub-form 2: Trắc nghiệm nhiều lựa chọn (MULTI_CHOICE) -->
                <div id="subform_MULTI_CHOICE" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-blue-400">Cấu hình các lựa chọn & Tích chọn các đáp án đúng:</p>
                    <div class="space-y-2 text-xs">
                        <?php foreach (['A', 'B', 'C', 'D'] as $optK): ?>
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="multi_correct[]" value="<?= $optK ?>" class="w-4 h-4 text-blue-600 rounded">
                            <span class="w-6 font-bold text-cyan-400"><?= $optK ?>:</span>
                            <input type="text" name="multi_text[<?= $optK ?>]" placeholder="Nội dung phương án <?= $optK ?>" class="flex-1 p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sub-form 3: Tương tác Chạm điểm (HOTSPOT_PINPOINT) -->
                <div id="subform_HOTSPOT_PINPOINT" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-rose-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-fingerprint"></i> Cấu hình Tọa độ Điểm chạm Linh kiện (Hotspot):
                    </p>
                    <div class="space-y-2 text-xs">
                        <label class="block text-slate-300">Chọn hình ảnh nền linh kiện / bảng máy:</label>
                        <select name="preset_media" class="w-full p-2.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs mb-2">
                            <option value="assets/machine_panel.svg">Bảng điều khiển máy SMT (assets/machine_panel.svg)</option>
                            <option value="assets/pcb_defect.svg">Bo mạch PCB & Linh kiện (assets/pcb_defect.svg)</option>
                        </select>
                        <p class="text-[11px] text-slate-400">Hoặc tải lên hình ảnh máy thực tế mới:</p>
                        <input type="file" name="media_file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs">
                        
                        <div class="grid grid-cols-3 gap-3 pt-2">
                            <div>
                                <label class="text-[11px] text-slate-400 block">Tọa độ X (%) chuẩn:</label>
                                <input type="number" step="0.1" name="hotspot_x" value="74.5" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-400 block">Tọa độ Y (%) chuẩn:</label>
                                <input type="number" step="0.1" name="hotspot_y" value="32.0" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-400 block">Dung sai bán kính (%):</label>
                                <input type="number" step="0.5" name="hotspot_tolerance" value="8.0" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sub-form 4: Tương tác Khoanh vùng (POLYGON_DEFECT) -->
                <div id="subform_POLYGON_DEFECT" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-draw-polygon"></i> Cấu hình Vùng khoanh lỗi Bo mạch (Polygon):
                    </p>
                    <div class="space-y-2 text-xs">
                        <label class="block text-slate-300">Chọn hình ảnh bo mạch:</label>
                        <select name="preset_media" class="w-full p-2.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs mb-2">
                            <option value="assets/pcb_defect.svg">Bo mạch SMT lỗi chân hàn IC (assets/pcb_defect.svg)</option>
                            <option value="assets/machine_panel.svg">Bảng điều khiển máy SMT (assets/machine_panel.svg)</option>
                        </select>
                        <div class="grid grid-cols-3 gap-3 pt-2">
                            <div>
                                <label class="text-[11px] text-slate-400 block">Tâm lỗi X (%):</label>
                                <input type="number" step="0.1" name="poly_center_x" value="50.0" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-400 block">Tâm lỗi Y (%):</label>
                                <input type="number" step="0.1" name="poly_center_y" value="44.0" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-400 block">Bán kính chấp nhận (%):</label>
                                <input type="number" step="0.5" name="poly_tolerance" value="10.0" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sub-form 5: Kéo thả Quy trình (DRAG_DROP_SEQUENCE) -->
                <div id="subform_DRAG_DROP_SEQUENCE" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-cyan-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-arrows-up-down"></i> Nhập các bước quy trình theo đúng thứ tự chuẩn SOP:
                    </p>
                    <div class="space-y-2 text-xs">
                        <input type="text" name="seq_steps[]" placeholder="Bước 1 chuẩn SOP (VD: Bật van khí nén)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        <input type="text" name="seq_steps[]" placeholder="Bước 2 chuẩn SOP (VD: Bật nguồn điện tổng)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        <input type="text" name="seq_steps[]" placeholder="Bước 3 chuẩn SOP (VD: Nạp chương trình Model)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        <input type="text" name="seq_steps[]" placeholder="Bước 4 chuẩn SOP (VD: Chạy thử không tải)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                    </div>
                </div>

                <!-- Sub-form 6: Tự luận (ESSAY) -->
                <div id="subform_ESSAY" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-nib"></i> Tiêu chí chấm điểm chuẩn (Rubric) cho Giảng viên:
                    </p>
                    <textarea name="essay_rubric" rows="2" placeholder="Ghi chú hướng dẫn cho Trainer khi chấm điểm bài làm của công nhân..." class="w-full p-2.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-white"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('addQuestionDrawer').classList.add('hidden')" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                        <?= __('btn_cancel') ?>
                    </button>
                    <button type="submit" class="touch-tap px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-blue-950">
                        <i class="fa-solid fa-plus mr-1"></i> Lưu & Thêm vào bài thi
                    </button>
                </div>

            </form>
        </div>

        <?php endif; ?>
    </div>

</div>

<!-- MODAL: TẠO BÀI THI & KHÓA HỌC MỚI (Cơ bản -> Nâng cao -> Chuyên gia) -->
<div id="createExamModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-folder-plus text-blue-400"></i>
                <span>Tạo Khóa học & Bài thi Năng lực Mới</span>
            </h3>
            <button onclick="document.getElementById('createExamModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="exam_builder.php" class="space-y-4">
            <input type="hidden" name="action" value="create_exam">

            <!-- Kỹ năng chuyên môn -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Chuyên môn / Kỹ năng:</label>
                <select name="skill_id" required class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <?php foreach ($skills as $sk): ?>
                    <option value="<?= $sk['id'] ?>"><?= htmlspecialchars($sk['name']) ?> (<?= htmlspecialchars($sk['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tên khóa học -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Tên bài học / Chuyên đề đào tạo:</label>
                <input type="text" name="title" required placeholder="VD: SMT-202: Cài đặt Nozzle & Camera nhận dạng" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
            </div>

            <!-- Cấp độ & Điều kiện tiên quyết (Prerequisite Logic) -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Phân cấp cấp độ:</label>
                    <select name="level" id="modalLevelSelect" onchange="updatePrerequisiteDropdown(this.value)" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                        <option value="1">Cấp 1: Cơ bản</option>
                        <option value="2">Cấp 2: Nâng cao</option>
                        <option value="3">Cấp 3: Chuyên gia</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Điểm tối thiểu đạt (%):</label>
                    <input type="number" step="1" name="min_pass_score" value="80" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white font-bold text-emerald-400">
                </div>
            </div>

            <!-- Khóa học tiền đề bắt buộc phải vượt qua -->
            <div id="prereqContainer">
                <label class="block text-xs font-bold text-slate-300 mb-1">
                    <i class="fa-solid fa-lock text-amber-400 mr-1"></i> Khóa học cấp trước (Điều kiện bắt buộc):
                </label>
                <select name="prerequisite_course_id" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <option value="">-- Không có (Khóa nhập môn cơ bản) --</option>
                    <?php foreach ($allCourses as $ac): ?>
                    <option value="<?= $ac['id'] ?>">[Cấp <?= $ac['level'] ?>] <?= htmlspecialchars($ac['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-400 mt-1 italic">
                    * Công nhân chưa đạt điểm tối thiểu ở khóa học tiền đề này sẽ bị hệ thống khóa bài thi tự động.
                </p>
            </div>

            <!-- Thời gian & Mô tả -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Thời gian thi (phút):</label>
                    <input type="number" name="duration_minutes" value="20" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Số lần thi tối đa:</label>
                    <input type="number" name="max_attempts" value="5" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Mô tả tóm tắt:</label>
                <textarea name="description" rows="2" placeholder="Tóm tắt mục tiêu đào tạo của bài..." class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('createExamModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
                    Hủy bỏ
                </button>
                <button type="submit" class="touch-tap px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-blue-950">
                    <i class="fa-solid fa-check mr-1"></i> Khởi tạo Bài thi
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    function switchQuestionType(type) {
        document.querySelectorAll('.type-subform').forEach(el => el.classList.add('hidden'));
        const target = document.getElementById('subform_' + type);
        if (target) {
            target.classList.remove('hidden');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

