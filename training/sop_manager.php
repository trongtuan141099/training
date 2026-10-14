<?php
// sop_manager.php - SOP & Training Document Hub (Upload & Configuration)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/skillmap.php';
require_once __DIR__ . '/includes/lang.php';
requireRole(['ADMIN', 'TRAINER']);

$currentUser = getAuthUser();
$db = getDB();

$message = '';
$messageType = 'success';

// XỬ LÝ 1: TẢI LÊN TÀI LIỆU SOP MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_sop') {
    $courseId = (int)$_POST['course_id'];
    $title = trim($_POST['title']);
    $matType = $_POST['material_type']; // 'PDF', 'VIDEO', 'IMAGE_SOP', 'HTML'
    $contentText = trim($_POST['content_text'] ?? '');
    $filePath = '';

    // Xử lý upload file nếu có
    if (!empty($_FILES['sop_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/materials/';
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
            $stmt = $db->prepare('
                INSERT INTO course_materials (course_id, title, material_type, file_path, content_text, sort_order)
                VALUES (:cid, :title, :mtype, :fpath, :ctxt, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM course_materials cm2 WHERE cm2.course_id = :cid2))
            ');
            $stmt->execute([
                'cid'   => $courseId,
                'title' => $title,
                'mtype' => $matType,
                'fpath' => $filePath ?: 'assets/sop_smt_safety.svg',
                'ctxt'  => $contentText,
                'cid2'  => $courseId
            ]);
            $message = 'Đã tải lên và gắn kèm tài liệu SOP thành công!';
            header('Location: sop_manager.php?course_id=' . $courseId . '&msg=' . urlencode($message));
            exit;
        } catch (Exception $e) {
            $message = 'Lỗi lưu tài liệu CSDL: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// XỬ LÝ 2: XÓA TÀI LIỆU
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $stmtDel = $db->prepare('SELECT file_path FROM course_materials WHERE id = :id');
    $stmtDel->execute(['id' => $delId]);
    $mat = $stmtDel->fetch();

    if ($mat && !empty($mat['file_path']) && str_starts_with($mat['file_path'], 'uploads/')) {
        $realPath = __DIR__ . '/' . $mat['file_path'];
        if (file_exists($realPath)) {
            @unlink($realPath);
        }
    }
    $db->prepare('DELETE FROM course_materials WHERE id = :id')->execute(['id' => $delId]);
    header('Location: sop_manager.php?msg=' . urlencode('Đã xóa tài liệu SOP thành công.'));
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
                STANDARD OPERATING PROCEDURE (SOP)
            </span>
            <span class="text-slate-400">•</span>
            <span class="text-xs text-slate-500">Kho dữ liệu số hóa nhà máy</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 flex items-center space-x-2">
            <i class="fa-solid fa-file-invoice text-blue-700 dark:text-cyan-400"></i>
            <span>QUẢN LÝ & THIẾT LẬP TÀI LIỆU SOP NHÀ MÁY</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Gắn kèm tài liệu PDF quy chuẩn, sơ đồ mạch, video thao tác và ảnh SOP trực quan vào các bài học và kỳ thi.
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
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 mb-6 shadow-sm flex items-center justify-between flex-wrap gap-3">
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
        Tìm thấy: <strong class="text-slate-800 dark:text-white"><?= count($materialsList) ?></strong> tài liệu
    </span>
</div>

<!-- Grid Danh sách Tài liệu SOP -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($materialsList)): ?>
    <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-12 text-center text-slate-500 text-xs shadow-sm">
        <i class="fa-regular fa-folder-open text-4xl text-slate-400 mb-3 block"></i>
        <p>Chưa có tài liệu SOP nào cho khóa học này.</p>
        <button onclick="document.getElementById('uploadSopModal').classList.remove('hidden')" class="mt-3 text-blue-700 dark:text-blue-400 font-bold hover:underline">
            + Bấm vào đây để tải lên tài liệu đầu tiên
        </button>
    </div>
    <?php else: ?>
        <?php foreach ($materialsList as $mat): ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition">
            
            <!-- Top Preview or Thumbnail -->
            <div>
                <div class="h-44 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-200 dark:border-slate-800 relative group">
                    <?php if ($mat['material_type'] === 'IMAGE_SOP' && file_exists(__DIR__ . '/' . $mat['file_path'])): ?>
                        <img src="<?= htmlspecialchars($mat['file_path']) ?>" alt="Preview" class="w-full h-full object-contain p-2 group-hover:scale-105 transition duration-300">
                    <?php elseif ($mat['material_type'] === 'PDF'): ?>
                        <div class="text-center text-rose-600">
                            <i class="fa-solid fa-file-pdf text-5xl mb-2"></i>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">TÀI LIỆU PDF CHUẨN</p>
                        </div>
                    <?php elseif ($mat['material_type'] === 'VIDEO'): ?>
                        <div class="text-center text-blue-700">
                            <i class="fa-solid fa-circle-play text-5xl mb-2 animate-pulse"></i>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">VIDEO QUY TRÌNH</p>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-emerald-600">
                            <i class="fa-solid fa-file-lines text-5xl mb-2"></i>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">HƯỚNG DẪN THAO TÁC</p>
                        </div>
                    <?php endif; ?>

                    <span class="absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] font-bold bg-white/90 dark:bg-slate-900/90 text-blue-800 dark:text-blue-300 border border-slate-200 dark:border-slate-700 uppercase shadow-sm">
                        <?= htmlspecialchars($mat['material_type']) ?>
                    </span>
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
            <div class="p-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/40 flex items-center justify-between">
                <a href="<?= htmlspecialchars($mat['file_path']) ?>" target="_blank" class="text-xs font-bold text-blue-700 dark:text-blue-400 hover:underline flex items-center gap-1.5">
                    <i class="fa-solid fa-eye"></i>
                    <span>Xem file gốc</span>
                </a>

                <div class="flex items-center space-x-2">
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

<!-- MODAL: TẢI LÊN TÀI LIỆU SOP MỚI -->
<div id="uploadSopModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-cloud-arrow-up text-blue-400"></i>
                <span>Tải lên Tài liệu Hướng dẫn Công việc (SOP)</span>
            </h3>
            <button onclick="document.getElementById('uploadSopModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="sop_manager.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="upload_sop">

            <!-- Chọn khóa học gắn kèm -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Gắn kèm vào Khóa đào tạo:</label>
                <select name="course_id" required class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>">[Cấp <?= $c['level'] ?>] <?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tiêu đề SOP -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Tiêu đề tài liệu SOP:</label>
                <input type="text" name="title" required placeholder="VD: SOP-SMT-003: Quy chuẩn Thay cuộn linh kiện Feeder" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
            </div>

            <!-- Định dạng tài liệu -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Loại tài liệu:</label>
                    <select name="material_type" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                        <option value="IMAGE_SOP">Hình ảnh Sơ đồ / Poster SOP (SVG/PNG)</option>
                        <option value="PDF">Tài liệu Quy chuẩn Kỹ thuật (PDF)</option>
                        <option value="VIDEO">Video thao tác chuẩn (MP4/WebM)</option>
                        <option value="HTML">Văn bản / Check-list hướng dẫn</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tải file từ máy tính:</label>
                    <input type="file" name="sop_file" class="w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs">
                </div>
            </div>

            <!-- Dùng file mẫu sẵn có -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Hoặc dùng ảnh mẫu có sẵn trong hệ thống:</label>
                <select name="preset_file" class="w-full p-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white">
                    <option value="">-- Tải file mới ở trên --</option>
                    <option value="assets/sop_smt_safety.svg">Poster An toàn SMT (assets/sop_smt_safety.svg)</option>
                    <option value="assets/machine_panel.svg">Bảng điều khiển SMT Mounter (assets/machine_panel.svg)</option>
                    <option value="assets/pcb_defect.svg">Bo mạch & Lỗi chân hàn (assets/pcb_defect.svg)</option>
                </select>
            </div>

            <!-- Nội dung tóm tắt -->
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>

