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

// AJAX ENDPOINT: BỐC THỬ ĐỀ NGẪU NHIÊN (LIVE PREVIEW)
if (isset($_GET['ajax_preview_random'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $examId = (int)$_GET['exam_id'];
    $count = max(1, (int)($_GET['count'] ?? 5));
    $mode = $_GET['mode'] ?? 'EXAM_POOL';

    try {
        if ($mode === 'SKILL_CATEGORY') {
            $stmt = $db->prepare('
                SELECT q.id, q.prompt, q.question_type, q.score, q.media_url, e.title as exam_title
                FROM questions q
                JOIN exams e ON e.id = q.exam_id
                JOIN courses c ON c.id = e.course_id
                WHERE c.skill_id = (SELECT c2.skill_id FROM exams e2 JOIN courses c2 ON c2.id = e2.course_id WHERE e2.id = :eid)
                ORDER BY RAND()
                LIMIT :lim
            ');
            $stmt->bindValue(':eid', $examId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $count, PDO::PARAM_INT);
            $stmt->execute();
            $sample = $stmt->fetchAll();
        } else {
            $stmt = $db->prepare('
                SELECT id, prompt, question_type, score, media_url
                FROM questions
                WHERE exam_id = :eid
                ORDER BY RAND()
                LIMIT :lim
            ');
            $stmt->bindValue(':eid', $examId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $count, PDO::PARAM_INT);
            $stmt->execute();
            $sample = $stmt->fetchAll();
        }
        echo json_encode(['success' => true, 'count' => count($sample), 'questions' => $sample], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

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

        $stmtE = $db->prepare('
            INSERT INTO exams (course_id, title, duration_minutes, total_score, max_attempts, is_randomized, random_question_count, question_bank_mode, is_active)
            VALUES (:cid, :etitle, :dur, 100.00, :att, 0, 5, "EXAM_POOL", 1)
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

// XỬ LÝ 2: CẬP NHẬT CẤU HÌNH ĐỀ THI & BỐC ĐỀ NGẪU NHIÊN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_exam_settings') {
    $targetExamId = (int)$_POST['exam_id'];
    $isRand = isset($_POST['is_randomized']) ? 1 : 0;
    $randCount = max(1, (int)($_POST['random_question_count'] ?? 5));
    $bankMode = in_array($_POST['question_bank_mode'] ?? '', ['EXAM_POOL', 'SKILL_CATEGORY'], true) ? $_POST['question_bank_mode'] : 'EXAM_POOL';
    $duration = max(1, (int)($_POST['duration_minutes'] ?? 20));
    $minScore = (float)($_POST['min_pass_score'] ?? 80.0);

    try {
        $stmtUp = $db->prepare('
            UPDATE exams 
            SET is_randomized = :rand, random_question_count = :rcount, question_bank_mode = :bmode, duration_minutes = :dur
            WHERE id = :eid
        ');
        $stmtUp->execute([
            'rand'   => $isRand,
            'rcount' => $randCount,
            'bmode'  => $bankMode,
            'dur'    => $duration,
            'eid'    => $targetExamId
        ]);

        $stmtUpC = $db->prepare('UPDATE courses SET min_pass_score = :mscore WHERE id = (SELECT course_id FROM exams WHERE id = :eid2)');
        $stmtUpC->execute(['mscore' => $minScore, 'eid2' => $targetExamId]);

        $message = 'Đã cập nhật thành công cấu hình đề thi & chế độ bốc ngẫu nhiên!';
        header('Location: exam_builder.php?exam_id=' . $targetExamId . '&msg=' . urlencode($message));
        exit;
    } catch (Exception $e) {
        $message = 'Lỗi cập nhật cấu hình: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// XỬ LÝ 3: THÊM CÂU HỎI VÀO BÀI THI (HỖ TRỢ ĐÍNH KÈM ẢNH & HOTSPOT / ROI EDITOR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $targetExamId = (int)$_POST['exam_id'];
    $qType = $_POST['question_type'];
    $prompt = trim($_POST['prompt']);
    $score = (float)($_POST['score'] ?? 20.0);
    $mediaUrl = null;

    // 1. Xử lý ảnh đính kèm (áp dụng cho TẤT CẢ các dạng câu hỏi)
    if (!empty($_FILES['media_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/questions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
            $newFileName = 'q_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $uploadDir . $newFileName)) {
                $mediaUrl = 'uploads/questions/' . $newFileName;
            }
        }
    } elseif (!empty($_POST['preset_media'])) {
        $mediaUrl = $_POST['preset_media'];
    } elseif (!empty($_POST['custom_media_url'])) {
        $mediaUrl = trim($_POST['custom_media_url']);
    }

    // 2. Xây dựng JSON cấu hình đáp án
    $answerConfig = [];

    // Nhận diện dữ liệu từ ROI Editor nếu có
    $rois = !empty($_POST['rois_json']) ? json_decode($_POST['rois_json'], true) : [];
    if (!empty($rois) && is_array($rois)) {
        $answerConfig['rois'] = $rois;
        // Điền trước các giá trị tương thích ngược
        if (isset($rois[0])) {
            $first = $rois[0];
            if ($first['type'] === 'hotspot') {
                $answerConfig['target_x_pct'] = (float)$first['x'];
                $answerConfig['target_y_pct'] = (float)$first['y'];
                $answerConfig['tolerance_radius_pct'] = (float)($first['r'] ?? 7.5);
            } else {
                $answerConfig['center_x_pct'] = (float)($first['x'] + ($first['w'] ?? 0) / 2);
                $answerConfig['center_y_pct'] = (float)($first['y'] + ($first['h'] ?? 0) / 2);
                $answerConfig['tolerance_radius_pct'] = (float)(max($first['w'] ?? 10, $first['h'] ?? 10) / 2);
            }
        }
    }

    if ($qType === 'SINGLE_CHOICE' || $qType === 'TRUE_FALSE') {
        $opts = [];
        $rawOpts = $_POST['choice_text'] ?? [];
        $correct = $_POST['choice_correct'] ?? '';
        foreach ($rawOpts as $key => $txt) {
            if (trim($txt) !== '') {
                $opts[] = ['id' => $key, 'text' => trim($txt)];
            }
        }
        $answerConfig['options'] = $opts;
        $answerConfig['correct'] = [$correct];

    } elseif ($qType === 'MULTI_CHOICE') {
        $opts = [];
        $rawOpts = $_POST['multi_text'] ?? [];
        $correctArr = $_POST['multi_correct'] ?? [];
        foreach ($rawOpts as $key => $txt) {
            if (trim($txt) !== '') {
                $opts[] = ['id' => $key, 'text' => trim($txt)];
            }
        }
        $answerConfig['options'] = $opts;
        $answerConfig['correct'] = $correctArr;

    } elseif ($qType === 'HOTSPOT_PINPOINT') {
        if (empty($answerConfig['rois'])) {
            $answerConfig['target_x_pct']         = (float)($_POST['hotspot_x'] ?? 50.0);
            $answerConfig['target_y_pct']         = (float)($_POST['hotspot_y'] ?? 50.0);
            $answerConfig['tolerance_radius_pct'] = (float)($_POST['hotspot_tolerance'] ?? 7.5);
        }
        $answerConfig['hint'] = trim($_POST['hotspot_hint'] ?? '');

    } elseif ($qType === 'POLYGON_DEFECT') {
        if (empty($answerConfig['rois'])) {
            $polyPoints = !empty($_POST['polygon_json']) ? json_decode($_POST['polygon_json'], true) : [];
            $answerConfig['center_x_pct']         = (float)($_POST['poly_center_x'] ?? 50.0);
            $answerConfig['center_y_pct']         = (float)($_POST['poly_center_y'] ?? 44.0);
            $answerConfig['tolerance_radius_pct'] = (float)($_POST['poly_tolerance'] ?? 10.0);
            $answerConfig['polygon_points']       = $polyPoints ?: [];
        }

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
        $answerConfig['items'] = $items;
        $answerConfig['correct_order'] = $correctOrder;

    } elseif ($qType === 'ESSAY') {
        $answerConfig['rubric']    = trim($_POST['essay_rubric'] ?? '');
        $answerConfig['min_words'] = (int)($_POST['essay_min_words'] ?? 15);
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

// XỬ LÝ 4: XÓA CÂU HỎI
if (isset($_GET['delete_q'])) {
    $delQId = (int)$_GET['delete_q'];
    $curExam = (int)($_GET['exam_id'] ?? 0);
    $db->prepare('DELETE FROM questions WHERE id = :id')->execute(['id' => $delQId]);
    header('Location: exam_builder.php?exam_id=' . $curExam . '&msg=' . urlencode('Đã xóa câu hỏi khỏi bài thi.'));
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

// Bài thi đang được chọn để quản lý
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
            <span class="text-xs text-slate-500">Khóa cấp độ & Tương tác Hotspot / ROI Editor</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 flex items-center space-x-2">
            <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
            <span>THIẾT KẾ BÀI THI & NGÂN HÀNG CÂU HỎI TƯƠNG TÁC</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Chèn hình ảnh trực tiếp, khoanh vùng lỗi trên bo mạch (Hotspot & ROI Editor) và tự động bốc đề thi ngẫu nhiên.
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

<!-- Main 2-Column Grid: Left (List Exams) / Right (Exam Settings & Question Builder) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Cột trái (1/3): Danh sách các bài thi hiện có -->
    <div class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    <i class="fa-solid fa-list-check text-blue-700 dark:text-blue-400 mr-1.5"></i> Danh sách Bài thi (<?= count($allExams) ?>)
                </h2>
                <span class="text-[10px] text-slate-400 font-mono">Chọn để quản lý</span>
            </div>

            <div class="space-y-2 max-h-[600px] overflow-y-auto pr-1">
                <?php foreach ($allExams as $ex): ?>
                <?php $isAct = ($ex['id'] == $selectedExamId); ?>
                <a href="exam_builder.php?exam_id=<?= $ex['id'] ?>" class="block p-3.5 rounded-xl border transition <?= $isAct ? 'bg-blue-50/80 border-blue-500 shadow-sm dark:bg-blue-950/80 dark:border-blue-500' : 'bg-slate-50/60 dark:bg-slate-950/70 border-slate-200 dark:border-slate-800 hover:border-slate-300' ?>">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase
                            <?= $ex['course_level'] == 1 ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : ($ex['course_level'] == 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300') ?>">
                            Cấp độ <?= $ex['course_level'] ?>
                        </span>
                        <div class="flex items-center gap-1.5">
                            <?php if (!empty($ex['is_randomized'])): ?>
                            <span class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 text-[9px] font-bold">
                                <i class="fa-solid fa-shuffle"></i> Ngẫu nhiên
                            </span>
                            <?php endif; ?>
                            <span class="text-[11px] font-mono text-blue-700 dark:text-cyan-400 font-bold"><?= $ex['question_count'] ?> câu</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-900 dark:text-white leading-snug"><?= htmlspecialchars($ex['title']) ?></p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1"><?= htmlspecialchars($ex['skill_name']) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải (2/3): Quản lý Câu hỏi & Cấu hình Đề thi đã chọn -->
    <div class="lg:col-span-2 space-y-6">
        <?php if ($currentExam): ?>
        
        <!-- Header thông tin bài thi đang mở -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
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
            <div class="mt-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 text-xs flex items-center justify-between flex-wrap gap-2">
                <span class="text-slate-600 dark:text-slate-400">
                    <i class="fa-solid fa-lock text-amber-500 mr-1.5"></i>
                    <strong>Quy tắc khóa cấp độ:</strong> 
                    <?= !empty($currentExam['prerequisite_course_id']) ? 'Bắt buộc đạt điểm tối thiểu ' . (float)$currentExam['min_pass_score'] . '% ở cấp trước để mở khóa.' : 'Cấp 1 cơ bản - Mở khóa tự do cho mọi công nhân.' ?>
                </span>
                <a href="exam.php?exam_id=<?= $currentExam['id'] ?>" target="_blank" class="text-blue-700 dark:text-blue-400 hover:underline font-bold text-[11px] whitespace-nowrap">
                    <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Xem phòng thi thực tế
                </a>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- PANEL: CẤU HÌNH BỐC ĐỀ THI NGẪU NHIÊN (TASK 3 REQUIREMENT)     -->
        <!-- ============================================================== -->
        <div class="bg-gradient-to-r from-purple-950/40 via-slate-900 to-slate-900 border border-purple-500/30 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-purple-600/30 text-purple-400 flex items-center justify-center font-bold text-sm border border-purple-500/40">
                        <i class="fa-solid fa-shuffle"></i>
                    </span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider">
                            Cấu hình Bốc Đề Thi Ngẫu Nhiên (Randomized Question Drawer)
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            Tự động bốc ngẫu nhiên số lượng câu hỏi từ ngân hàng để tránh tình trạng nhìn bài.
                        </p>
                    </div>
                </div>
                <!-- Nút Bốc thử 1 đề mẫu -->
                <button type="button" onclick="previewRandomDraw(<?= $currentExam['id'] ?>)" class="touch-tap px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-md">
                    <i class="fa-solid fa-dice text-sm"></i>
                    <span>Bốc thử 1 đề mẫu (Live Preview)</span>
                </button>
            </div>

            <form method="POST" action="exam_builder.php" class="space-y-4">
                <input type="hidden" name="action" value="update_exam_settings">
                <input type="hidden" name="exam_id" value="<?= $currentExam['id'] ?>">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <!-- Toggle Bật/Tắt -->
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-white block">Chế độ Bốc Ngẫu Nhiên:</span>
                            <span class="text-[10px] text-slate-400">Đổi đề cho mỗi lần thi</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_randomized" value="1" <?= !empty($currentExam['is_randomized']) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>

                    <!-- Số lượng câu hỏi bốc -->
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <label class="block text-slate-300 font-bold mb-1">Số câu hỏi cần bốc (khi thi):</label>
                        <input type="number" min="1" max="50" name="random_question_count" value="<?= (int)($currentExam['random_question_count'] ?: 5) ?>" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white font-mono font-bold text-purple-400">
                    </div>

                    <!-- Phạm vi ngân hàng -->
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <label class="block text-slate-300 font-bold mb-1">Nguồn ngân hàng câu hỏi:</label>
                        <select name="question_bank_mode" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            <option value="EXAM_POOL" <?= ($currentExam['question_bank_mode'] ?? '') === 'EXAM_POOL' ? 'selected' : '' ?>>Kho câu hỏi của bài thi này (<?= count($currentQuestions) ?> câu)</option>
                            <option value="SKILL_CATEGORY" <?= ($currentExam['question_bank_mode'] ?? '') === 'SKILL_CATEGORY' ? 'selected' : '' ?>>Toàn bộ Ngân hàng Chuyên môn (<?= htmlspecialchars($currentExam['skill_name']) ?>)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="text-[11px] text-purple-300">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Hệ thống sẽ tự động chấm điểm và quy đổi thang điểm 100% theo đúng số lượng câu hỏi được bốc ngẫu nhiên.
                    </span>
                    <button type="submit" class="touch-tap px-4 py-2 bg-purple-700 hover:bg-purple-600 text-white rounded-xl text-xs font-bold transition shadow-md">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Lưu Cấu Hình Đề Thi
                    </button>
                </div>
            </form>
        </div>

        <!-- Danh sách các câu hỏi hiện có trong bài thi -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">
                    <i class="fa-solid fa-list-ol text-cyan-400 mr-1"></i> Ngân hàng Câu hỏi Hiện có (<?= count($currentQuestions) ?> câu)
                </h3>
                <button onclick="document.getElementById('addQuestionDrawer').classList.remove('hidden')" class="touch-tap px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-md">
                    <i class="fa-solid fa-plus"></i>
                    <span>Thêm câu hỏi mới</span>
                </button>
            </div>

            <?php if (empty($currentQuestions)): ?>
                <div class="p-8 text-center text-slate-500 text-xs bg-slate-950/50 rounded-xl border border-slate-800/80">
                    <p>Bài thi này chưa có câu hỏi nào trong kho. Bấm nút "Thêm câu hỏi mới" để bắt đầu thiết kế.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($currentQuestions as $qIdx => $cq): ?>
                    <?php 
                        $cfg = json_decode($cq['answer_config'], true) ?? [];
                        $roiCount = count($cfg['rois'] ?? []);
                    ?>
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
                                    <span class="text-slate-300 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                                        <i class="fa-solid fa-image text-blue-400 mr-1"></i> Có ảnh minh họa
                                    </span>
                                    <?php endif; ?>

                                    <?php if ($roiCount > 0): ?>
                                    <span class="text-emerald-300 bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-800 font-bold">
                                        <i class="fa-solid fa-bullseye text-emerald-400 mr-1"></i> <?= $roiCount ?> Vùng lỗi ROI
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <a href="exam_builder.php?exam_id=<?= $selectedExamId ?>&delete_q=<?= $cq['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này khỏi đề thi?');" class="text-slate-500 hover:text-rose-400 p-2 text-xs transition" title="Xóa câu hỏi">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================== -->
        <!-- KHUNG THÊM CÂU HỎI MỚI (CHÈN ẢNH & HOTSPOT / ROI EDITOR)       -->
        <!-- ============================================================== -->
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
                        <option value="HOTSPOT_PINPOINT"><?= __('q_type_hotspot') ?> (Chỉ điểm trên ảnh)</option>
                        <option value="POLYGON_DEFECT"><?= __('q_type_polygon') ?> (Khoanh vùng lỗi trên ảnh)</option>
                        <option value="DRAG_DROP_SEQUENCE"><?= __('q_type_sequence') ?></option>
                        <option value="ESSAY"><?= __('q_type_essay') ?></option>
                    </select>
                </div>

                <!-- Đề bài câu hỏi -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Nội dung Đề bài / Yêu cầu thao tác cho Công nhân:
                    </label>
                    <textarea name="prompt" rows="3" required placeholder="Nhập câu hỏi hoặc hướng dẫn thao tác (VD: Quan sát hình bo mạch sau và chạm vào linh kiện bị hàn nguội)..." class="w-full p-3.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <!-- Điểm số của câu -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Điểm số của câu này (Score):
                    </label>
                    <input type="number" step="1" name="score" value="20" required class="w-36 px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-amber-400 font-bold focus:outline-none focus:border-blue-500">
                </div>

                <!-- ========================================================== -->
                <!-- MODULE CHÈN HÌNH ẢNH TRỰC TIẾP (TASK 3 REQUIREMENT)        -->
                <!-- ========================================================== -->
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-cyan-400 uppercase tracking-wider flex items-center space-x-1.5">
                            <i class="fa-solid fa-image"></i>
                            <span>Chèn Hình Ảnh Trực Tiếp vào Câu Hỏi (Tùy chọn mọi dạng câu hỏi)</span>
                        </label>
                        <span class="text-[10px] text-slate-400 font-mono">JPG, PNG, SVG, WebP</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <!-- Chọn ảnh mẫu có sẵn trong hệ thống -->
                        <div>
                            <label class="text-[11px] text-slate-300 block mb-1">Chọn ảnh mẫu có sẵn nhà máy:</label>
                            <select id="presetMediaSelect" name="preset_media" onchange="onPresetImageChange(this.value)" class="w-full p-2.5 bg-slate-900 border border-slate-700 rounded-lg text-white">
                                <option value="">-- Tùy chọn: Chọn ảnh có sẵn --</option>
                                <option value="assets/machine_panel.svg">Bảng điều khiển máy SMT (assets/machine_panel.svg)</option>
                                <option value="assets/pcb_defect.svg">Bo mạch SMT lỗi chân hàn IC (assets/pcb_defect.svg)</option>
                                <option value="assets/sop_smt_safety.svg">Áp phích An toàn SMT (assets/sop_smt_safety.svg)</option>
                            </select>
                        </div>

                        <!-- Hoặc tải lên file ảnh từ máy -->
                        <div>
                            <label class="text-[11px] text-slate-300 block mb-1">Hoặc Tải ảnh thực tế từ máy tính:</label>
                            <input type="file" id="mediaFileInput" name="media_file" accept="image/*" onchange="onLocalImageUpload(this)" class="w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs file:font-bold hover:file:bg-blue-500 cursor-pointer">
                        </div>
                    </div>

                    <!-- Khung Preview Ảnh trực quan -->
                    <div id="imagePreviewContainer" class="hidden pt-2 border-t border-slate-800/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-slate-300"><i class="fa-solid fa-eye text-blue-400 mr-1"></i> Xem trước hình ảnh đã chọn:</span>
                            <button type="button" onclick="clearSelectedImage()" class="text-rose-400 hover:text-rose-300 text-[11px] font-bold">
                                <i class="fa-solid fa-xmark mr-0.5"></i> Bỏ chọn ảnh
                            </button>
                        </div>
                        <div class="rounded-xl overflow-hidden border border-slate-700 bg-slate-900 max-h-48 flex items-center justify-center p-2">
                            <img id="imagePreviewEl" src="" alt="Preview" class="max-h-44 object-contain mx-auto rounded">
                        </div>
                    </div>
                </div>

                <!-- Sub-form 1: Trắc nghiệm 1 đáp án (SINGLE_CHOICE) -->
                <div id="subform_SINGLE_CHOICE" class="type-subform space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-blue-400">Cấu hình các lựa chọn & Tích chọn 1 Đáp án đúng:</p>
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
                    <p class="text-xs font-bold text-blue-400">Cấu hình các lựa chọn & Tích chọn tất cả các đáp án đúng:</p>
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

                <!-- Sub-form 3: Đúng / Sai (TRUE_FALSE) -->
                <div id="subform_TRUE_FALSE" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-blue-400">Chọn đáp án Đúng hoặc Sai:</p>
                    <div class="space-y-2 text-xs">
                        <label class="flex items-center space-x-2 p-2 bg-slate-900 rounded-lg border border-slate-800 cursor-pointer">
                            <input type="radio" name="choice_correct" value="TRUE" class="w-4 h-4 text-blue-600">
                            <span class="font-bold text-emerald-400">ĐÚNG</span>
                            <input type="hidden" name="choice_text[TRUE]" value="Đúng">
                        </label>
                        <label class="flex items-center space-x-2 p-2 bg-slate-900 rounded-lg border border-slate-800 cursor-pointer">
                            <input type="radio" name="choice_correct" value="FALSE" checked class="w-4 h-4 text-blue-600">
                            <span class="font-bold text-rose-400">SAI</span>
                            <input type="hidden" name="choice_text[FALSE]" value="Sai">
                        </label>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- MODULE KHOANH VÙNG LỖI / CHỈ VỊ TRÍ LỖI (HOTSPOT & ROI EDITOR) -->
                <!-- ========================================================== -->
                <div id="subform_HOTSPOT_ROI" class="type-subform hidden space-y-4 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-800">
                        <div>
                            <p class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-crosshairs text-rose-500"></i>
                                <span>TRÌNH BIÊN TẬP VÙNG LỖI TRỰC QUAN (HOTSPOT & ROI EDITOR)</span>
                            </p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Dùng chuột kéo/khoanh trực tiếp trên ảnh để thiết lập nhiều vùng đáp án chính xác.
                            </p>
                        </div>

                        <!-- Chế độ vẽ -->
                        <div class="flex items-center space-x-2 text-xs">
                            <button type="button" id="btnModeRect" onclick="setRoiMode('rect')" class="px-2.5 py-1.5 rounded-lg bg-red-600 text-white font-bold transition flex items-center space-x-1 shadow">
                                <i class="fa-solid fa-vector-square"></i>
                                <span>Khoanh Vùng (Kéo Bounding Box)</span>
                            </button>
                            <button type="button" id="btnModeHotspot" onclick="setRoiMode('hotspot')" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold transition flex items-center space-x-1">
                                <i class="fa-solid fa-fingerprint"></i>
                                <span>Chỉ Điểm (Hotspot Click)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tên nhãn vùng lỗi chuẩn bị vẽ -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="sm:col-span-2">
                            <label class="text-[11px] text-slate-300 block mb-1">Tên nhãn / Mô tả vùng lỗi (gắn vào vùng sắp vẽ):</label>
                            <input type="text" id="roiLabelInput" oninput="if(roiEditor) roiEditor.setLabel(this.value)" value="Lỗi dính chân IC 14-16" placeholder="VD: Lỗi hàn dính chân vi mạch, Nút dừng E-STOP..." class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-300 block mb-1">Dung sai Hotspot bán kính (%):</label>
                            <input type="number" id="roiToleranceInput" oninput="if(roiEditor) roiEditor.setTolerance(this.value)" step="0.5" value="7.5" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white font-bold text-cyan-400">
                        </div>
                    </div>

                    <!-- Canvas Editor Workspace -->
                    <div class="rounded-xl overflow-hidden border border-slate-700 bg-slate-950 relative shadow-inner">
                        <canvas id="builderRoiCanvas" class="w-full cursor-crosshair block"></canvas>
                        
                        <!-- Overlay Hướng dẫn thao tác nhanh -->
                        <div class="absolute top-2 right-2 bg-slate-900/80 backdrop-blur-sm px-2.5 py-1 rounded-lg border border-slate-700 text-[10px] text-slate-300 pointer-events-none">
                            <i class="fa-solid fa-mouse-pointer text-amber-400 mr-1"></i>
                            <span id="roiInstructionText">Nhấp giữ & kéo chuột để khoanh vùng hình chữ nhật</span>
                        </div>
                    </div>

                    <!-- Bảng Danh sách các Vùng Lỗi / ROI đã thiết lập -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-300 uppercase tracking-wider">
                                <i class="fa-solid fa-list-check text-emerald-400 mr-1"></i> Các vùng đáp án chính xác đã khoanh (<span id="roiCountDisplay">0</span> vùng)
                            </span>
                            <button type="button" onclick="if(roiEditor) roiEditor.clearAll()" class="text-rose-400 hover:text-rose-300 text-[11px] font-bold">
                                <i class="fa-solid fa-trash-can mr-1"></i> Xóa tất cả vùng
                            </button>
                        </div>

                        <div id="roiEmptyNotice" class="p-4 rounded-lg bg-slate-900/80 border border-dashed border-slate-800 text-center text-slate-500 text-xs">
                            Chưa có vùng lỗi nào được khoanh. Hãy dùng chuột kéo trên hình ảnh phía trên để tạo vùng đáp án.
                        </div>

                        <div id="roiItemsList" class="space-y-2 max-h-40 overflow-y-auto"></div>
                    </div>

                    <!-- Hidden input lưu trữ JSON toàn bộ các ROI -->
                    <input type="hidden" name="rois_json" id="rois_json" value="[]">
                    <input type="hidden" name="hotspot_x" id="hotspot_x" value="50.0">
                    <input type="hidden" name="hotspot_y" id="hotspot_y" value="50.0">
                    <input type="hidden" name="hotspot_tolerance" id="hotspot_tolerance" value="7.5">
                    <input type="hidden" name="poly_center_x" id="poly_center_x" value="50.0">
                    <input type="hidden" name="poly_center_y" id="poly_center_y" value="44.0">
                    <input type="hidden" name="poly_tolerance" id="poly_tolerance" value="10.0">
                </div>

                <!-- Sub-form 5: Kéo thả Quy trình (DRAG_DROP_SEQUENCE) -->
                <div id="subform_DRAG_DROP_SEQUENCE" class="type-subform hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <p class="text-xs font-bold text-cyan-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-arrows-up-down"></i> Nhập các bước quy trình theo đúng thứ tự chuẩn SOP:
                    </p>
                    <div class="space-y-2 text-xs">
                        <input type="text" name="seq_steps[]" placeholder="Bước 1 chuẩn SOP (VD: Bật van khí nén chính)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        <input type="text" name="seq_steps[]" placeholder="Bước 2 chuẩn SOP (VD: Bật Aptomat nguồn tổng)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        <input type="text" name="seq_steps[]" placeholder="Bước 3 chuẩn SOP (VD: Nạp chương trình Model)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                        <input type="text" name="seq_steps[]" placeholder="Bước 4 chuẩn SOP (VD: Chạy thử không tải Dry Run)" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
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

<!-- MODAL 1: TẠO BÀI THI & KHÓA HỌC MỚI -->
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

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Chuyên môn / Kỹ năng:</label>
                <select name="skill_id" required class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <?php foreach ($skills as $sk): ?>
                    <option value="<?= $sk['id'] ?>"><?= htmlspecialchars($sk['name']) ?> (<?= htmlspecialchars($sk['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Tên bài học / Chuyên đề đào tạo:</label>
                <input type="text" name="title" required placeholder="VD: SMT-202: Cài đặt Nozzle & Camera nhận dạng" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Phân cấp cấp độ:</label>
                    <select name="level" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
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

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">
                    <i class="fa-solid fa-lock text-amber-400 mr-1"></i> Khóa học cấp trước (Điều kiện bắt buộc):
                </label>
                <select name="prerequisite_course_id" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <option value="">-- Không có (Khóa nhập môn cơ bản) --</option>
                    <?php foreach ($allCourses as $ac): ?>
                    <option value="<?= $ac['id'] ?>">[Cấp <?= $ac['level'] ?>] <?= htmlspecialchars($ac['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

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

<!-- MODAL 2: LIVE PREVIEW BỐC ĐỀ THI NGẪU NHIÊN -->
<div id="randomPreviewModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-dice text-purple-400 text-base"></i>
                <span>KẾT QUẢ BỐC THỬ ĐỀ THI NGẪU NHIÊN</span>
            </h3>
            <button onclick="document.getElementById('randomPreviewModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="randomPreviewContent" class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs">
            <div class="text-center py-8 text-slate-400">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-purple-400 block"></i>
                Đang bốc ngẫu nhiên câu hỏi từ ngân hàng...
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800 flex items-center justify-between shrink-0">
            <button type="button" onclick="previewRandomDraw(<?= $currentExam['id'] ?? 0 ?>)" class="px-4 py-2 bg-purple-700 hover:bg-purple-600 text-white rounded-xl text-xs font-bold">
                <i class="fa-solid fa-rotate mr-1"></i> Bốc lại lần nữa
            </button>
            <button type="button" onclick="document.getElementById('randomPreviewModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
                Đóng lại
            </button>
        </div>
    </div>
</div>

<!-- Thư viện Canvas & ROI Editor -->
<script src="assets/js/interactive-canvas.js"></script>

<script>
    let roiEditor = null;
    let currentActiveImageUrl = 'assets/pcb_defect.svg';

    function switchQuestionType(type) {
        document.querySelectorAll('.type-subform').forEach(el => el.classList.add('hidden'));
        
        if (type === 'HOTSPOT_PINPOINT' || type === 'POLYGON_DEFECT') {
            const target = document.getElementById('subform_HOTSPOT_ROI');
            if (target) {
                target.classList.remove('hidden');
                initOrUpdateRoiEditor(type === 'HOTSPOT_PINPOINT' ? 'hotspot' : 'rect');
            }
        } else {
            const target = document.getElementById('subform_' + type);
            if (target) {
                target.classList.remove('hidden');
            }
        }
    }

    function setRoiMode(mode) {
        if (!roiEditor) return;
        roiEditor.setMode(mode);
        const btnRect = document.getElementById('btnModeRect');
        const btnHotspot = document.getElementById('btnModeHotspot');
        const txt = document.getElementById('roiInstructionText');

        if (mode === 'rect') {
            btnRect.className = 'px-2.5 py-1.5 rounded-lg bg-red-600 text-white font-bold transition flex items-center space-x-1 shadow';
            btnHotspot.className = 'px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold transition flex items-center space-x-1';
            txt.innerText = 'Nhấp giữ & kéo chuột để khoanh vùng hình chữ nhật';
        } else {
            btnRect.className = 'px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-red-400 font-bold transition flex items-center space-x-1';
            btnHotspot.className = 'px-2.5 py-1.5 rounded-lg bg-cyan-600 text-white font-bold transition flex items-center space-x-1 shadow';
            txt.innerText = 'Bấm chuột vào đúng điểm trên ảnh để ghim vị trí Hotspot';
        }
    }

    function initOrUpdateRoiEditor(defaultMode) {
        const canvas = document.getElementById('builderRoiCanvas');
        if (!canvas) return;

        if (!roiEditor) {
            roiEditor = new RoiEditor('builderRoiCanvas', {
                imageUrl: currentActiveImageUrl,
                mode: defaultMode || 'rect',
                label: document.getElementById('roiLabelInput').value || 'Vùng lỗi #1',
                tolerance: parseFloat(document.getElementById('roiToleranceInput').value) || 7.5,
                onUpdate: updateRoiUI
            });
        } else {
            if (defaultMode) setRoiMode(defaultMode);
            roiEditor.loadImage(currentActiveImageUrl);
        }
    }

    function updateRoiUI(regions) {
        const countDisplay = document.getElementById('roiCountDisplay');
        const emptyNotice = document.getElementById('roiEmptyNotice');
        const listContainer = document.getElementById('roiItemsList');
        const jsonInput = document.getElementById('rois_json');

        if (countDisplay) countDisplay.innerText = regions.length;
        if (jsonInput) jsonInput.value = JSON.stringify(regions);

        if (regions.length === 0) {
            if (emptyNotice) emptyNotice.classList.remove('hidden');
            if (listContainer) listContainer.innerHTML = '';
            return;
        }

        if (emptyNotice) emptyNotice.classList.add('hidden');
        if (listContainer) {
            listContainer.innerHTML = '';
            regions.forEach((r, idx) => {
                const isHotspot = (r.type === 'hotspot');
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-xs';
                
                const coordsText = isHotspot 
                    ? `Hotspot Tâm: (${r.x}%, ${r.y}%) • R=${r.r}%`
                    : `Vùng Rect: X=${r.x}%, Y=${r.y}% [${r.w}% x ${r.h}%]`;

                row.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded flex items-center justify-center font-bold text-[10px] ${isHotspot ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/40' : 'bg-red-500/20 text-red-400 border border-red-500/40'}">
                            ${idx + 1}
                        </span>
                        <div>
                            <span class="font-bold text-white">${r.label || 'Vùng lỗi #' + (idx + 1)}</span>
                            <span class="text-[10px] text-slate-400 block font-mono">${coordsText}</span>
                        </div>
                    </div>
                    <button type="button" onclick="roiEditor.removeRegion('${r.id}')" class="text-slate-500 hover:text-rose-400 p-1" title="Xóa vùng này">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                `;
                listContainer.appendChild(row);
            });
        }
    }

    // Xử lý đổi ảnh từ Preset dropdown
    function onPresetImageChange(url) {
        if (!url) return;
        currentActiveImageUrl = url;
        showPreviewImage(url);
        if (roiEditor) {
            roiEditor.loadImage(url);
        }
    }

    // Xử lý tải ảnh lên từ máy tính (Client-side instant preview)
    function onLocalImageUpload(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const objectUrl = URL.createObjectURL(file);
            currentActiveImageUrl = objectUrl;
            showPreviewImage(objectUrl);
            if (roiEditor) {
                roiEditor.loadImage(objectUrl);
            }
        }
    }

    function showPreviewImage(url) {
        const previewContainer = document.getElementById('imagePreviewContainer');
        const previewEl = document.getElementById('imagePreviewEl');
        if (previewContainer && previewEl) {
            previewEl.src = url;
            previewContainer.classList.remove('hidden');
        }
    }

    function clearSelectedImage() {
        document.getElementById('presetMediaSelect').value = '';
        document.getElementById('mediaFileInput').value = '';
        document.getElementById('imagePreviewContainer').classList.add('hidden');
        currentActiveImageUrl = 'assets/pcb_defect.svg';
        if (roiEditor) {
            roiEditor.loadImage(currentActiveImageUrl);
        }
    }

    // Live preview bốc đề ngẫu nhiên
    function previewRandomDraw(examId) {
        const modal = document.getElementById('randomPreviewModal');
        const container = document.getElementById('randomPreviewContent');
        modal.classList.remove('hidden');
        container.innerHTML = `
            <div class="text-center py-8 text-slate-400">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-purple-400 block"></i>
                Đang bốc ngẫu nhiên câu hỏi từ ngân hàng...
            </div>
        `;

        const randCount = document.querySelector('input[name="random_question_count"]')?.value || 5;
        const bankMode = document.querySelector('select[name="question_bank_mode"]')?.value || 'EXAM_POOL';

        fetch(`exam_builder.php?ajax_preview_random=1&exam_id=${examId}&count=${randCount}&mode=${bankMode}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.questions || data.questions.length === 0) {
                    container.innerHTML = `
                        <div class="p-6 text-center text-amber-400 bg-amber-950/30 rounded-xl border border-amber-800">
                            <i class="fa-solid fa-triangle-exclamation text-2xl mb-1 block"></i>
                            Không có đủ câu hỏi trong ngân hàng để bốc ngẫu nhiên. Vui lòng thêm thêm câu hỏi.
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="p-3 bg-purple-950/50 rounded-xl border border-purple-800 flex items-center justify-between">
                        <span class="font-bold text-purple-200">Đã bốc thành công ${data.questions.length} câu hỏi ngẫu nhiên</span>
                        <span class="text-[10px] bg-purple-900 text-purple-300 px-2 py-0.5 rounded-full font-mono">Chế độ: ${bankMode}</span>
                    </div>
                `;

                data.questions.forEach((q, i) => {
                    html += `
                        <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold text-blue-400">Câu ${i + 1} • ${q.question_type}</span>
                                <span class="text-amber-400 font-bold">${q.score} Điểm</span>
                            </div>
                            <p class="text-xs font-semibold text-white">${q.prompt}</p>
                            ${q.media_url ? `<span class="text-[10px] text-slate-400"><i class="fa-solid fa-image text-cyan-400 mr-1"></i> Có ảnh đính kèm: ${q.media_url}</span>` : ''}
                        </div>
                    `;
                });

                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = `<div class="p-4 text-center text-rose-400">Lỗi bốc đề: ${err.message}</div>`;
            });
    }

    // Tự động khởi tạo canvas nếu mở tab Hotspot/Polygon
    document.addEventListener('DOMContentLoaded', () => {
        const qTypeSelect = document.getElementById('questionTypeSelect');
        if (qTypeSelect && (qTypeSelect.value === 'HOTSPOT_PINPOINT' || qTypeSelect.value === 'POLYGON_DEFECT')) {
            initOrUpdateRoiEditor(qTypeSelect.value === 'HOTSPOT_PINPOINT' ? 'hotspot' : 'rect');
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
