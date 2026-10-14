<?php
// login.php
require_once __DIR__ . '/includes/auth.php';

// Xử lý Quick Switch đăng nhập 1 chạm cho Demo
if (isset($_GET['quick_user'])) {
    $uid = (int)$_GET['quick_user'];
    if (quickLoginById($uid)) {
        header('Location: index.php');
        exit;
    }
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['employee_code'] ?? '';
    $pass = $_POST['password'] ?? '';

    $result = loginUser($code, $pass);
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $errorMessage = $result['message'];
    }
}

$pageTitle = 'Đăng nhập';
?>
<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Đăng nhập - Hệ thống Skillmap Dojo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-950 via-[#0b1120] to-blue-950">

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-8">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-950 border border-blue-700 text-blue-400 mb-3 shadow-inner">
                <i class="fa-solid fa-microchip text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-wide">SKILLMAP <span class="text-blue-400">4.0</span></h1>
            <p class="text-xs text-slate-400 mt-1">Hệ thống Đào tạo & Đánh giá Năng lực Công nhân</p>
        </div>

        <?php if ($errorMessage): ?>
        <div class="mb-5 p-3 rounded-xl bg-red-950/80 border border-red-800 text-red-300 text-xs flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
            <span><?= htmlspecialchars($errorMessage) ?></span>
        </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Mã số nhân viên (Employee Code)
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    <input type="text" name="employee_code" required autofocus placeholder="VD: WRK001 hoặc ADM001"
                           class="w-full pl-10 pr-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Mật khẩu (Password)
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" required placeholder="Nhập mật khẩu"
                           class="w-full pl-10 pr-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-600 hover:to-blue-500 text-white rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-blue-950 transition active:scale-[0.99] touch-manipulation">
                <i class="fa-solid fa-right-to-bracket mr-2"></i> ĐĂNG NHẬP HỆ THỐNG
            </button>
        </form>

        <!-- Demo 1-Click Fast Switch -->
        <div class="mt-8 pt-6 border-t border-slate-800">
            <p class="text-xs font-bold text-slate-400 text-center uppercase tracking-wider mb-3">
                <i class="fa-solid fa-bolt text-amber-400 mr-1"></i> Đăng nhập Demo nhanh (1 Chạm)
            </p>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <a href="login.php?quick_user=3" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-emerald-950/60 border border-slate-700 hover:border-emerald-600 transition flex flex-col items-center text-center">
                    <span class="font-bold text-emerald-400"><i class="fa-solid fa-user-helmet-safety mr-1"></i>Worker 1</span>
                    <span class="text-[10px] text-slate-400">WRK001 (Level 0)</span>
                </a>
                <a href="login.php?quick_user=4" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-cyan-950/60 border border-slate-700 hover:border-cyan-600 transition flex flex-col items-center text-center">
                    <span class="font-bold text-cyan-400"><i class="fa-solid fa-user-check mr-1"></i>Worker 2</span>
                    <span class="text-[10px] text-slate-400">WRK002 (Đạt Level 1)</span>
                </a>
                <a href="login.php?quick_user=2" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-amber-950/60 border border-slate-700 hover:border-amber-600 transition flex flex-col items-center text-center">
                    <span class="font-bold text-amber-400"><i class="fa-solid fa-chalkboard-user mr-1"></i>Trainer</span>
                    <span class="text-[10px] text-slate-400">TRN001 (Chấm điểm)</span>
                </a>
                <a href="login.php?quick_user=1" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-purple-950/60 border border-slate-700 hover:border-purple-600 transition flex flex-col items-center text-center">
                    <span class="font-bold text-purple-400"><i class="fa-solid fa-shield mr-1"></i>Admin</span>
                    <span class="text-[10px] text-slate-400">ADM001 (Quản trị)</span>
                </a>
            </div>
            <p class="text-[11px] text-slate-500 text-center mt-3">Mật khẩu mẫu chung: <code>admin123</code> / <code>trainer123</code> / <code>worker123</code></p>
        </div>

    </div>

</body>
</html>

