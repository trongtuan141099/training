<?php
// sop_manager.php - SOP & Interactive Video Document Hub (Upload & Configuration)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
require_once __DIR__ . '/includes/lang.php';
requireRole(['ADMIN', 'TRAINER']);

$currentUser = getAuthUser();
$db = getDB();

$message = '';
$messageType = 'success';

// XỬ LÝ 1: TẢI LÊN TÀI LIỆU SOP / VIDEO MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_sop') {
    $courseId = (int)$_POST['course_id'];
    $title = trim($_POST['title']);
    $matType = $_POST['material_type']; // 'PDF', 'VIDEO', 'IMAGE_SOP', 'HTML'
    $contentText = trim($_POST['content_text'] ?? '');
    $filePath = '';

    // Xử lý upload file nếu có
    if (!empty($_FILES['sop_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/materials/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $origName = $_FILES['sop_file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'mp4', 'webm', 'svg', 'png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($ext, $allowedExts, true)) {
            $message = 'Định dạng file không được hỗ trợ. Vui lòng chọn PDF, Video (MP4/WebM) hoặc Ảnh (SVG/PNG/JPG).';
            $messageType = 'danger';
        } else {
            $newFileName = 'sop_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['sop_file']['tmp_name'], $uploadDir . $newFileName)) {
                $filePath = 'uploads/materials/' . $newFileName;
            } else {
                $message = 'Không thể lưu file tải lên máy chủ. Vui lòng kiểm tra quyền thư mục.';
                $messageType = 'danger';
            }
        }
    } elseif (!empty($_POST['preset_file'])) {
        $filePath = $_POST['preset_file'];
    }

    if (empty($message)) {
        try {
            $defaultInteractive = null;
            if ($matType === 'VIDEO') {
                $defaultInteractive = json_encode([
                    [
                        'id'            => 'cue_' . time() . '_1',
                        'timestamp_sec' => 3,
                        'question'      => 'Theo SOP an toàn, thao tác bảo hộ bắt buộc nào phải làm trước tiên?',
                        'options'       => [
                            ['id' => 'A', 'text' => 'Đeo vòng tay khử tĩnh điện ESD có dây nối đất'],
                            ['id' => 'B', 'text' => 'Tháo nắp bảo vệ mounter']
                        ],
                        'correct'       => 'A',
                        'explanation'   => 'Đúng! An toàn tĩnh điện ESD luôn là ưu tiên số 1 trong phòng sạch SMT.'
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }

            $stmt = $db->prepare('
                INSERT INTO course_materials (course_id, title, material_type, file_path, content_text, interactive_config, sort_order)
                VALUES (:cid, :title, :mtype, :fpath, :ctxt, :cfg, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM course_materials cm2 WHERE cm2.course_id = :cid2))
            ');
            $stmt->execute([
                'cid'   => $courseId,
                'title' => $title,
                'mtype' => $matType,
                'fpath' => $filePath ?: 'assets/sop_smt_safety.svg',
                'ctxt'  => $contentText,
                'cfg'   => $defaultInteractive,
                'cid2'  => $courseId
            ]);
            $message = 'Đã tải lên và gắn kèm tài liệu SOP/Video thành công!';
            header('Location: sop_manager.php?course_id=' . $courseId . '&msg=' . urlencode($message));
            exit;
        } catch (Exception $e) {
            $message = 'Lỗi lưu tài liệu CSDL: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// XỬ LÝ 2: THÊM ĐIỂM TƯƠNG TÁC POP-UP VÀO VIDEO (TASK 4 REQUIREMENT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_video_cue') {
    $matId = (int)$_POST['material_id'];
    $timeSec = max(0, (int)$_POST['timestamp_sec']);
    $question = trim($_POST['cue_question']);
    $optA = trim($_POST['cue_opt_a']);
    $optB = trim($_POST['cue_opt_b']);
    $optC = trim($_POST['cue_opt_c'] ?? '');
    $correct = $_POST['cue_correct'] ?? 'A';
    $explanation = trim($_POST['cue_explanation'] ?? '');

    try {
        $stmt = $db->prepare('SELECT interactive_config, course_id FROM course_materials WHERE id = :id');
        $stmt->execute(['id' => $matId]);
        $cur = $stmt->fetch();
        $existingCues = !empty($cur['interactive_config']) ? json_decode($cur['interactive_config'], true) : [];
        if (!is_array($existingCues)) $existingCues = [];

        $opts = [
            ['id' => 'A', 'text' => $optA],
            ['id' => 'B', 'text' => $optB]
        ];
        if ($optC !== '') {
            $opts[] = ['id' => 'C', 'text' => $optC];
        }

        $newCue = [
            'id'            => 'cue_' . time() . '_' . rand(10, 99),
            'timestamp_sec' => $timeSec,
            'question'      => $question,
            'options'       => $opts,
            'correct'       => $correct,
            'explanation'   => $explanation
        ];

        $existingCues[] = $newCue;
        usort($existingCues, fn($a, $b) => $a['timestamp_sec'] <=> $b['timestamp_sec']);

        $stmtUp = $db->prepare('UPDATE course_materials SET interactive_config = :cfg WHERE id = :id');
        $stmtUp->execute(['cfg' => json_encode($existingCues, JSON_UNESCAPED_UNICODE), 'id' => $matId]);

        header('Location: sop_manager.php?course_id=' . (int)$cur['course_id'] . '&msg=' . urlencode('Đã thêm điểm dừng tương tác Pop-up mới thành công trên video!'));
        exit;
    } catch (Exception $e) {
        $message = 'Lỗi lưu điểm tương tác: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// XỬ LÝ 3: XÓA ĐIỂM TƯƠNG TÁC POP-UP CỦA VIDEO
if (isset($_GET['delete_cue_id']) && isset($_GET['mat_id'])) {
    $cueId = $_GET['delete_cue_id'];
    $matId = (int)$_GET['mat_id'];

    $stmt = $db->prepare('SELECT interactive_config, course_id FROM course_materials WHERE id = :id');
    $stmt->execute(['id' => $matId]);
    $cur = $stmt->fetch();
    $existingCues = !empty($cur['interactive_config']) ? json_decode($cur['interactive_config'], true) : [];
    if (is_array($existingCues)) {
        $filtered = array_values(array_filter($existingCues, fn($c) => ($c['id'] ?? '') !== $cueId));
        $db->prepare('UPDATE course_materials SET interactive_config = :cfg WHERE id = :id')
           ->execute(['cfg' => json_encode($filtered, JSON_UNESCAPED_UNICODE), 'id' => $matId]);
    }
    header('Location: sop_manager.php?course_id=' . (int)($cur['course_id'] ?? 0) . '&msg=' . urlencode('Đã xóa điểm tương tác video.'));
    exit;
}

// XỬ LÝ 4: XÓA TÀI LIỆU
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $stmtDel = $db->prepare('SELECT file_path, course_id FROM course_materials WHERE id = :id');
    $stmtDel->execute(['id' => $delId]);
    $mat = $stmtDel->fetch();

    if ($mat && !empty($mat['file_path']) && str_starts_with($mat['file_path'], 'uploads/')) {
        $realPath = __DIR__ . '/' . $mat['file_path'];
        if (file_exists($realPath) && !str_ends_with($realPath, 'sample_sop_video.mp4')) {
            @unlink($realPath);
        }
    }
    $db->prepare('DELETE FROM course_materials WHERE id = :id')->execute(['id' => $delId]);
    header('Location: sop_manager.php?course_id=' . (int)($mat['course_id'] ?? 0) . '&msg=' . urlencode('Đã xóa tài liệu SOP thành công.'));
    exit;
}

if (!empty($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Lấy danh sách khóa học
$courses = $db->query('
    SELECT c.*, s.name AS skill_name,
           (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) AS material_count
    FROM courses c
    JOIN skills s ON s.id = c.skill_id
    ORDER BY c.skill_id ASC, c.level ASC
')->fetchAll();

// Lọc theo khóa học được chọn
$selectedCourseId = (int)($_GET['course_id'] ?? 0);
if ($selectedCourseId > 0) {
    $stmtMats = $db->prepare('
        SELECT cm.*, c.title AS course_title, c.level AS course_level, s.name AS skill_name
        FROM course_materials cm
        JOIN courses c ON c.id = cm.course_id
        JOIN skills s ON s.id = c.skill_id
        WHERE cm.course_id = :cid
        ORDER BY cm.sort_order ASC, cm.id ASC
    ');
    $stmtMats->execute(['cid' => $selectedCourseId]);
    $materialsList = $stmtMats->fetchAll();
} else {
    $materialsList = $db->query('
        SELECT cm.*, c.title AS course_title, c.level AS course_level, s.name AS skill_name
        FROM course_materials cm
        JOIN courses c ON c.id = cm.course_id
        JOIN skills s ON s.id = c.skill_id
        ORDER BY cm.id DESC
    ')->fetchAll();
}

$pageTitle = 'Quản lý Tài liệu Hướng dẫn Công việc (SOP Hub)';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Title Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800 uppercase">
                STANDARD OPERATING PROCEDURE (SOP) & VIDEO
            </span>
            <span class="text-slate-400">•</span>
            <span class="text-xs text-slate-500">Kho dữ liệu số hóa nhà máy</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 flex items-center space-x-2">
            <i class="fa-solid fa-file-invoice text-blue-700 dark:text-cyan-400"></i>
            <span>QUẢN LÝ TÀI LIỆU SOP & VIDEO ĐÀO TẠO TƯƠNG TÁC</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Gắn kèm tài liệu PDF quy chuẩn, sơ đồ mạch, video thao tác và nhúng hiệu ứng tương tác pop-up kiểm tra độ tập trung.
        </p>
    </div>

    <!-- Nút mở form Upload -->
    <button onclick="document.getElementById('uploadSopModal').classList.remove('hidden')" class="touch-tap px-4 py-2.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-md shadow-blue-700/20">
        <i class="fa-solid fa-cloud-arrow-up"></i>
        <span>Tải lên Tài liệu SOP Mới</span>
    </button>
</div>

<?php if ($message): ?>
<div class="mb-6 p-4 rounded-xl <?= $messageType === 'danger' ? 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-950/80 dark:border-rose-600 dark:text-rose-300' : 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/80 dark:border-emerald-600 dark:text-emerald-300' ?> border text-xs flex items-center space-x-2 shadow-sm">
    <i class="fa-solid <?= $messageType === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> text-base"></i>
    <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Filter by Course Bar -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 mb-6 shadow-sm flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center space-x-2 text-xs">
        <span class="text-slate-500 font-semibold"><i class="fa-solid fa-filter text-blue-700 dark:text-blue-400 mr-1"></i> Lọc theo Khóa học:</span>
        <select onchange="window.location.href='sop_manager.php?course_id=' + this.value" class="px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-white focus:outline-none">
            <option value="0">-- Tất cả các khóa học --</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $selectedCourseId == $c['id'] ? 'selected' : '' ?>>
                [Cấp <?= $c['level'] ?>] <?= htmlspecialchars($c['title']) ?> (<?= $c['material_count'] ?> file)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <span class="text-xs text-slate-400 font-mono">
        Tìm thấy: <strong class="text-slate-800 dark:text-white"><?= count($materialsList) ?></strong> tài liệu SOP
    </span>
</div>

<!-- Grid Danh sách Tài liệu SOP & Video -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($materialsList)): ?>
    <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500 text-xs shadow-sm">
        <i class="fa-regular fa-folder-open text-4xl text-slate-400 mb-3 block"></i>
        <p>Chưa có tài liệu SOP nào cho khóa học này.</p>
        <button onclick="document.getElementById('uploadSopModal').classList.remove('hidden')" class="mt-3 text-blue-700 dark:text-blue-400 font-bold hover:underline">
            + Bấm vào đây để tải lên tài liệu đầu tiên
        </button>
    </div>
    <?php else: ?>
        <?php foreach ($materialsList as $mat): ?>
        <?php 
            $cues = !empty($mat['interactive_config']) ? json_decode($mat['interactive_config'], true) : [];
            $cueCount = is_array($cues) ? count($cues) : 0;
            $isVideo = ($mat['material_type'] === 'VIDEO');
        ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition">
            
            <div>
                <!-- Top Thumbnail -->
                <div class="h-44 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-200 dark:border-slate-800 relative group">
                    <?php if ($mat['material_type'] === 'IMAGE_SOP' && file_exists(__DIR__ . '/' . $mat['file_path'])): ?>
                        <img src="<?= htmlspecialchars($mat['file_path']) ?>" alt="Preview" class="w-full h-full object-contain p-2 group-hover:scale-105 transition duration-300">
                    <?php elseif ($isVideo): ?>
                        <div class="text-center text-rose-600">
                            <i class="fa-solid fa-circle-play text-5xl mb-2 animate-pulse"></i>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">VIDEO ĐÀO TẠO</p>
                        </div>
                    <?php elseif ($mat['material_type'] === 'PDF'): ?>
                        <div class="text-center text-rose-600">
                            <i class="fa-solid fa-file-pdf text-5xl mb-2"></i>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">TÀI LIỆU PDF CHUẨN</p>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-emerald-600">
                            <i class="fa-solid fa-file-lines text-5xl mb-2"></i>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">HƯỚNG DẪN THAO TÁC</p>
                        </div>
                    <?php endif; ?>

                    <span class="absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] font-bold bg-white/95 dark:bg-slate-900/95 text-blue-800 dark:text-blue-300 border border-slate-200 dark:border-slate-700 uppercase shadow-sm">
                        <?= htmlspecialchars($mat['material_type']) ?>
                    </span>

                    <?php if ($isVideo && $cueCount > 0): ?>
                    <span class="absolute bottom-3 left-3 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-md flex items-center space-x-1">
                        <i class="fa-solid fa-bolt text-xs"></i>
                        <span><?= $cueCount ?> Điểm Tương tác</span>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Card Body -->
                <div class="p-4 sm:p-5">
                    <p class="text-[10px] uppercase font-bold text-slate-400 mb-1 truncate">
                        <?= htmlspecialchars($mat['course_title']) ?> (Cấp <?= $mat['course_level'] ?>)
                    </p>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2 leading-snug">
                        <?= htmlspecialchars($mat['title']) ?>
                    </h3>
                    <?php if (!empty($mat['content_text'])): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                        <?= htmlspecialchars($mat['content_text']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Bottom Actions -->
            <div class="p-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/40 flex items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <a href="<?= htmlspecialchars($mat['file_path']) ?>" target="_blank" class="text-xs font-bold text-blue-700 dark:text-blue-400 hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-eye"></i>
                        <span>Xem file</span>
                    </a>

                    <?php if ($isVideo): ?>
                    <button type="button" onclick="openVideoCueModal(<?= htmlspecialchars(json_encode($mat, JSON_UNESCAPED_UNICODE)) ?>)" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1 ml-2">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Điểm tương tác (<?= $cueCount ?>)</span>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="flex items-center space-x-1 shrink-0">
                    <a href="course.php?id=<?= $mat['course_id'] ?>" class="text-slate-400 hover:text-blue-700 text-xs p-1.5" title="Xem trong khóa học">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    <a href="sop_manager.php?delete_id=<?= $mat['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa tài liệu SOP này?');" class="text-slate-400 hover:text-rose-600 text-xs p-1.5" title="Xóa tài liệu">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL 1: TẢI LÊN TÀI LIỆU SOP MỚI -->
<div id="uploadSopModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-cloud-arrow-up text-blue-400"></i>
                <span>Tải lên Tài liệu Hướng dẫn Công việc (SOP) / Video</span>
            </h3>
            <button onclick="document.getElementById('uploadSopModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="sop_manager.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="upload_sop">

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Gắn kèm vào Khóa đào tạo:</label>
                <select name="course_id" required class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>">[Cấp <?= $c['level'] ?>] <?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Tiêu đề tài liệu SOP / Video:</label>
                <input type="text" name="title" required placeholder="VD: Video Hướng dẫn Khởi động & Xử lý kẹt cuộn máy SMT" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Loại tài liệu:</label>
                    <select name="material_type" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                        <option value="VIDEO">Video thao tác tương tác (MP4/WebM)</option>
                        <option value="IMAGE_SOP">Hình ảnh Sơ đồ / Poster SOP (SVG/PNG)</option>
                        <option value="PDF">Tài liệu Quy chuẩn Kỹ thuật (PDF)</option>
                        <option value="HTML">Văn bản / Check-list hướng dẫn</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tải file từ máy tính:</label>
                    <input type="file" name="sop_file" class="w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Hoặc dùng video / ảnh mẫu sẵn có trong hệ thống:</label>
                <select name="preset_file" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <option value="">-- Tải file mới từ máy tính ở trên --</option>
                    <option value="uploads/materials/sample_sop_video.mp4">Video SOP Thao tác chuẩn & An toàn SMT (uploads/materials/sample_sop_video.mp4)</option>
                    <option value="assets/sop_smt_safety.svg">Poster An toàn SMT (assets/sop_smt_safety.svg)</option>
                    <option value="assets/machine_panel.svg">Bảng điều khiển SMT Mounter (assets/machine_panel.svg)</option>
                    <option value="assets/pcb_defect.svg">Bo mạch & Lỗi chân hàn (assets/pcb_defect.svg)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Tóm tắt các bước chuẩn SOP:</label>
                <textarea name="content_text" rows="3" placeholder="Nhập các bước thực hiện then chốt..." class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('uploadSopModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
                    Hủy bỏ
                </button>
                <button type="submit" class="touch-tap px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-blue-950">
                    <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Lưu & Tải lên
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: QUẢN LÝ ĐIỂM TƯƠNG TÁC POP-UP TRÊN VIDEO (TASK 4)     -->
<!-- ============================================================== -->
<div id="videoCueModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-bolt text-amber-400"></i>
                <span id="cueModalTitle">Quản lý Điểm Tương Tác Video SOP</span>
            </h3>
            <button onclick="document.getElementById('videoCueModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
            
            <!-- Video Preview để lấy giây thực tế -->
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-300"><i class="fa-solid fa-play text-blue-400 mr-1"></i> Trình phát xem trước video:</span>
                    <button type="button" onclick="captureCurrentVideoSecond()" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded-lg font-bold text-[11px] flex items-center gap-1 shadow">
                        <i class="fa-solid fa-stopwatch"></i>
                        <span>Lấy thời điểm giây hiện tại</span>
                    </button>
                </div>
                <video id="modalVideoPlayer" playsinline controls class="w-full max-h-48 bg-black rounded-lg object-contain"></video>
                <p class="text-[10px] text-slate-400 text-right font-mono">
                    Thời điểm đang phát: <strong id="modalVideoCurrentTime" class="text-amber-400">0</strong>s
                </p>
            </div>

            <!-- Form Thêm Điểm Dừng Tương Tác Mới -->
            <form method="POST" action="sop_manager.php" class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                <input type="hidden" name="action" value="add_video_cue">
                <input type="hidden" name="material_id" id="cueFormMatId" value="0">

                <p class="font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-plus"></i>
                    <span>Thêm Câu hỏi Pop-up / Mini-quiz giữa chừng video:</span>
                </p>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-[11px] text-slate-300 block mb-1">Thời điểm bật pop-up (giây):</label>
                        <input type="number" min="0" step="1" name="timestamp_sec" id="cueTimestampInput" value="5" required class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white font-mono font-bold text-amber-400">
                    </div>
                    <div class="col-span-2">
                        <label class="text-[11px] text-slate-300 block mb-1">Nội dung câu hỏi pop-up:</label>
                        <input type="text" name="cue_question" required placeholder="VD: Thao tác an toàn trước khi bấm START là gì?" class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                    </div>
                </div>

                <!-- Phương án A, B, C -->
                <div class="space-y-2">
                    <label class="text-[11px] text-slate-300 block">Các lựa chọn & tích chọn đáp án đúng:</label>
                    <div class="flex items-center space-x-2">
                        <input type="radio" name="cue_correct" value="A" checked class="w-4 h-4 text-amber-500">
                        <span class="w-4 font-bold text-amber-400">A:</span>
                        <input type="text" name="cue_opt_a" required placeholder="Phương án A (VD: Đeo vòng tay ESD)" class="flex-1 p-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="radio" name="cue_correct" value="B" class="w-4 h-4 text-amber-500">
                        <span class="w-4 font-bold text-amber-400">B:</span>
                        <input type="text" name="cue_opt_b" required placeholder="Phương án B (VD: Tháo vòng tay ESD)" class="flex-1 p-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="radio" name="cue_correct" value="C" class="w-4 h-4 text-amber-500">
                        <span class="w-4 font-bold text-amber-400">C:</span>
                        <input type="text" name="cue_opt_c" placeholder="Phương án C (Tùy chọn)" class="flex-1 p-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] text-slate-300 block mb-1">Lời giải thích / Quy chuẩn SOP hiển thị khi trả lời:</label>
                    <input type="text" name="cue_explanation" placeholder="VD: Bắt buộc đeo vòng tay ESD để tránh sốc tĩnh điện vi mạch..." class="w-full p-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="touch-tap px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow">
                        <i class="fa-solid fa-plus"></i>
                        <span>Lưu & Gắn Điểm Tương Tác</span>
                    </button>
                </div>
            </form>

            <!-- Danh sách các điểm tương tác hiện có của video -->
            <div class="space-y-2">
                <p class="font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-list-check text-cyan-400"></i>
                    <span>Các điểm tương tác đã thiết lập:</span>
                </p>
                <div id="modalCueListContainer" class="space-y-2"></div>
            </div>

        </div>

        <div class="pt-3 border-t border-slate-800 flex justify-end shrink-0">
            <button type="button" onclick="document.getElementById('videoCueModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
                Đóng lại
            </button>
        </div>

    </div>
</div>

<script>
    function openVideoCueModal(mat) {
        document.getElementById('cueFormMatId').value = mat.id;
        document.getElementById('cueModalTitle').innerText = 'Điểm Tương Tác: ' + mat.title;

        // Load preview video
        const player = document.getElementById('modalVideoPlayer');
        player.src = mat.file_path;
        player.addEventListener('timeupdate', () => {
            document.getElementById('modalVideoCurrentTime').innerText = Math.floor(player.currentTime);
        });

        // Render existing cues
        const container = document.getElementById('modalCueListContainer');
        const cues = mat.interactive_config ? JSON.parse(mat.interactive_config) : [];

        if (!cues || cues.length === 0) {
            container.innerHTML = '<div class="p-4 rounded-lg bg-slate-950 border border-dashed border-slate-800 text-center text-slate-500 text-xs">Video này chưa có điểm dừng pop-up nào.</div>';
        } else {
            let html = '';
            cues.forEach((c, idx) => {
                const mins = Math.floor(c.timestamp_sec / 60);
                const secs = c.timestamp_sec % 60;
                const timeFmt = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

                html += `
                    <div class="p-3 rounded-lg bg-slate-950 border border-slate-800 flex items-start justify-between gap-3 text-xs">
                        <div class="flex items-start space-x-2.5">
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-400 border border-amber-500/40 font-mono font-bold text-[10px] shrink-0 mt-0.5">
                                ${timeFmt} (${c.timestamp_sec}s)
                            </span>
                            <div>
                                <p class="font-bold text-white">${c.question}</p>
                                <p class="text-[11px] text-emerald-400 mt-0.5">Đáp án đúng: [${c.correct}] - ${c.explanation || ''}</p>
                            </div>
                        </div>
                        <a href="sop_manager.php?delete_cue_id=${c.id}&mat_id=${mat.id}" onclick="return confirm('Bạn có chắc muốn xóa điểm tương tác này?');" class="text-slate-500 hover:text-rose-400 p-1" title="Xóa điểm dừng">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        document.getElementById('videoCueModal').classList.remove('hidden');
    }

    function captureCurrentVideoSecond() {
        const player = document.getElementById('modalVideoPlayer');
        if (player) {
            document.getElementById('cueTimestampInput').value = Math.floor(player.currentTime);
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
