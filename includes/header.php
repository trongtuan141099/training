<?php
// includes/header.php - DX Plastic Group Industrial Management Layout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';

$currentUser = getAuthUser();
$currentScript = basename($_SERVER['PHP_SELF']);
$currentLang = getCurrentLang();

// Kiểm tra số lượng bài thi tự luận chờ chấm để hiển thị badge chuông thông báo
$pendingEssayCount = 0;
if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)) {
    try {
        $dbNotif = getDB();
        $pendingEssayCount = (int)$dbNotif->query('SELECT COUNT(*) FROM exam_attempts WHERE status = "PENDING_EVALUATION"')->fetchColumn();
    } catch (Exception $e) {
        $pendingEssayCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>DX Plastic Group - Hệ thống Đào tạo & Quản lý Sản xuất</title>
    
    <!-- Google Fonts Inter for standard Vietnamese Unicode rendering without font glitches -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN with Custom Color Extensions -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dx: {
                            blue: '#1E3A8A',    // Industrial Dark Blue
                            royal: '#1D4ED8',   // Royal Blue (Active Nav Item)
                            sky: '#0284C7',
                            bg: '#F4F6F9',     // Main Background (Soft Gray)
                            sidebar: '#FFFFFF',// Clean White Sidebar
                            border: '#E2E8F0',  // Thin Border
                            card: '#FFFFFF'     // White Card
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Dark/Light Mode Init Script -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #F4F6F9;
        }
        .dark body {
            background-color: #0B1120;
        }
        button, a, input, select {
            touch-action: manipulation;
        }
        .touch-tap {
            min-height: 44px;
            min-width: 44px;
        }
        /* Custom Slim Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        .dark ::-webkit-scrollbar-track {
            background: #0F172A;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col font-sans antialiased text-slate-800 dark:text-slate-100 bg-[#F4F6F9] dark:bg-[#0B1120] selection:bg-blue-600 selection:text-white">

<!-- Outer Fullscreen Application Shell -->
<div class="h-screen w-screen flex flex-col overflow-hidden">

    <!-- ============================================================== -->
    <!-- 1. TOP HEADER: CỐ ĐỊNH Ở ĐỈNH MÀN HÌNH (FULL WIDTH)            -->
    <!-- ============================================================== -->
    <header class="h-16 shrink-0 z-30 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between px-3 sm:px-6">
        
        <!-- BÊN TRÁI: NÚT TOGGLE MENU & BRAND LOGO 'DX PLASTIC GROUP' -->
        <div class="flex items-center space-x-2 sm:space-x-4">
            <!-- Nút Thu gọn / Mở rộng Sidebar -->
            <button id="toggleSidebarBtn" type="button" class="touch-tap p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-900 hover:bg-slate-100 dark:hover:bg-slate-800 transition focus:outline-none" title="Đóng/Mở Menu">
                <i class="fa-solid fa-bars-staggered text-lg"></i>
            </button>

            <!-- Brand Logo -->
            <a href="index.php" class="flex items-center space-x-2.5">
                <span class="w-9 h-9 rounded-xl bg-blue-900 text-white flex items-center justify-center text-lg font-black shadow-md shadow-blue-900/20 shrink-0">
                    <i class="fa-solid fa-industry"></i>
                </span>
                <div class="hidden xs:block sm:block">
                    <span class="font-black text-sm sm:text-base tracking-tight text-[#1E3A8A] dark:text-blue-400 block leading-tight whitespace-nowrap">
                        DX PLASTIC GROUP
                    </span>
                    <span class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider block">
                        Factory Management
                    </span>
                </div>
            </a>

            <!-- Phân xưởng Badge -->
            <div class="hidden lg:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-800 border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800 text-[11px] font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Line SMT-01 • Panasonic NPM-D3</span>
            </div>
        </div>

        <!-- BÊN PHẢI: CHUÔNG THÔNG BÁO, NGÔN NGỮ, DARK/LIGHT, TÀI KHOẢN -->
        <div class="flex items-center space-x-1.5 sm:space-x-3">
            
            <!-- 1. ICON THÔNG BÁO (CHUÔNG) -->
            <div class="relative group">
                <button type="button" class="touch-tap p-2 sm:p-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-900 hover:bg-slate-100 dark:hover:bg-slate-800 transition relative" title="Thông báo hệ thống">
                    <i class="fa-regular fa-bell text-base"></i>
                    <?php if ($pendingEssayCount > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full"></span>
                    <?php endif; ?>
                </button>
                <!-- Popup Dropdown Thông báo -->
                <div class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl py-2 hidden group-hover:block z-50">
                    <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 dark:text-white">Thông báo</span>
                        <?php if ($pendingEssayCount > 0): ?>
                        <span class="text-[10px] bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">
                            <?= $pendingEssayCount ?> cần xử lý
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="p-2 text-xs">
                        <?php if ($pendingEssayCount > 0): ?>
                        <a href="trainer.php" class="block p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 transition">
                            <p class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                <i class="fa-solid fa-pen-nib"></i> Bài thi tự luận cần duyệt
                            </p>
                            <p class="text-slate-600 dark:text-slate-300 text-[11px] mt-0.5">
                                Có <?= $pendingEssayCount ?> bài thi công nhân đang chờ Trainer chấm điểm.
                            </p>
                        </a>
                        <?php else: ?>
                        <p class="text-center py-4 text-slate-400 text-xs">
                            <i class="fa-regular fa-bell-slash text-base mb-1 block"></i>
                            Không có thông báo mới
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2. CHUYỂN ĐỔI NGÔN NGỮ (VI/EN) -->
            <div class="relative group">
                <button type="button" class="touch-tap flex items-center space-x-1 px-2 sm:px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 text-xs font-bold transition">
                    <span><?= $currentLang === 'vi' ? '🇻🇳' : '🇬🇧' ?></span>
                    <span class="hidden sm:inline text-slate-700 dark:text-slate-200 uppercase"><?= $currentLang ?></span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                </button>
                <div class="absolute right-0 mt-2 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1 hidden group-hover:block z-50 text-xs">
                    <a href="?lang=vi" class="flex items-center space-x-2 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 <?= $currentLang === 'vi' ? 'text-blue-700 font-bold bg-blue-50/60 dark:bg-blue-950/40' : 'text-slate-700 dark:text-slate-300' ?>">
                        <span class="text-base">🇻🇳</span>
                        <span>Tiếng Việt</span>
                    </a>
                    <a href="?lang=en" class="flex items-center space-x-2 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 <?= $currentLang === 'en' ? 'text-blue-700 font-bold bg-blue-50/60 dark:bg-blue-950/40' : 'text-slate-700 dark:text-slate-300' ?>">
                        <span class="text-base">🇬🇧</span>
                        <span>English</span>
                    </a>
                </div>
            </div>

            <!-- 3. CHẾ ĐỘ DARK / LIGHT MODE -->
            <button type="button" onclick="toggleTheme()" class="touch-tap p-2 sm:p-2.5 rounded-xl text-slate-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Chuyển chế độ Sáng / Tối">
                <i class="fa-solid fa-moon text-base dark:hidden"></i>
                <i class="fa-solid fa-sun text-base hidden dark:inline-block"></i>
            </button>

            <!-- 4. AVATAR & TÊN NGƯỜI DÙNG -->
            <div class="relative group pl-1">
                <button type="button" class="touch-tap flex items-center space-x-2 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-blue-900 text-white font-bold text-xs flex items-center justify-center border-2 border-white dark:border-slate-800 shadow-sm shrink-0">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="text-left hidden md:block">
                        <p class="text-xs font-bold text-slate-800 dark:text-white leading-tight">
                            <?= !empty($currentUser['full_name']) ? htmlspecialchars($currentUser['full_name']) : 'Admin User' ?>
                        </p>
                        <p class="text-[10px] text-blue-800 dark:text-blue-400 font-semibold uppercase">
                            <?= !empty($currentUser['role']) ? htmlspecialchars($currentUser['role']) : 'Admin' ?>
                        </p>
                    </div>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 hidden sm:inline-block"></i>
                </button>

                <!-- Dropdown Quản lý Tài khoản & Demo Switcher -->
                <div class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl py-2 hidden group-hover:block z-50 text-xs">
                    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                        <p class="text-[11px] text-slate-400">Tài khoản hiện tại:</p>
                        <p class="text-sm font-bold text-slate-800 dark:text-white truncate">
                            <?= !empty($currentUser['full_name']) ? htmlspecialchars($currentUser['full_name']) : 'Admin User' ?>
                        </p>
                        <p class="text-xs text-blue-800 dark:text-blue-400 font-mono mt-0.5">
                            <?= htmlspecialchars($currentUser['employee_code'] ?? 'ADM001') ?>
                        </p>
                    </div>

                    <div class="px-2 pt-2">
                        <p class="text-[10px] font-bold text-slate-400 px-2 uppercase tracking-wider mb-1">
                            Đổi vai trò kiểm thử nhanh:
                        </p>
                        <a href="login.php?quick_user=1" class="flex items-center px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i class="fa-solid fa-shield text-purple-600 w-5"></i>
                            <span>Admin User (ADM001)</span>
                        </a>
                        <a href="login.php?quick_user=2" class="flex items-center px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i class="fa-solid fa-chalkboard-user text-amber-600 w-5"></i>
                            <span>Trainer Giảng viên (TRN001)</span>
                        </a>
                        <a href="login.php?quick_user=3" class="flex items-center px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i class="fa-solid fa-user-gear text-emerald-600 w-5"></i>
                            <span>Worker 1: Level 0 (WRK001)</span>
                        </a>
                        <a href="login.php?quick_user=4" class="flex items-center px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i class="fa-solid fa-user-check text-blue-600 w-5"></i>
                            <span>Worker 2: Đạt Level 1 (WRK002)</span>
                        </a>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-800 mt-2 pt-1 px-2">
                        <a href="logout.php" class="flex items-center px-3 py-2 rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                            <i class="fa-solid fa-arrow-right-from-bracket w-5"></i>
                            <span>Đăng xuất hệ thống</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </header>

    <!-- ============================================================== -->
    <!-- 2. MIDDLE WORKSPACE: KÉO DÀI TỪ HEADER XUỐNG SÁT FOOTER        -->
    <!-- ============================================================== -->
    <div class="flex-1 flex flex-row overflow-hidden min-h-0 relative">

        <!-- ========================================================== -->
        <!-- SIDEBAR: CỐ ĐỊNH BÊN TRÁI KÉO DÀI TỪ HEADER ĐẾN FOOTER     -->
        <!-- ========================================================== -->
        <aside id="factorySidebar" class="w-64 shrink-0 h-full bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col transition-all duration-300 ease-in-out md:static fixed top-16 md:top-auto bottom-11 md:bottom-auto left-0 z-40 -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none">
            
            <!-- Mobile Top Header of Drawer -->
            <div class="md:hidden px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    <i class="fa-solid fa-compass mr-1.5 text-blue-600"></i> Menu Điều Hướng
                </span>
                <button id="closeSidebarBtn" type="button" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Sidebar Navigation Items (Thanh cuộn riêng bên trong Sidebar) -->
            <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-5">
                
                <!-- NHÓM 1: TỔNG QUAN -->
                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-1.5">
                        Tổng quan hệ thống
                    </p>
                    <div class="space-y-1">
                        <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'index.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-900' ?>">
                            <i class="fa-solid fa-chart-line w-5 <?= $currentScript === 'index.php' ? 'text-white' : 'text-blue-700 dark:text-blue-400' ?> mr-2.5 text-sm"></i>
                            <span>Tổng quan (Dashboard)</span>
                        </a>

                        <a href="skillmap.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'skillmap.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-900' ?>">
                            <i class="fa-solid fa-chart-pie w-5 <?= $currentScript === 'skillmap.php' ? 'text-white' : 'text-emerald-600 dark:text-emerald-400' ?> mr-2.5 text-sm"></i>
                            <span>Ma trận Kỹ năng (Skillmap)</span>
                        </a>
                    </div>
                </div>

                <!-- NHÓM 2: QUẢN LÝ SẢN XUẤT -->
                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-1.5">
                        Quản lý sản xuất
                    </p>
                    <div class="space-y-1">
                        <div>
                            <button type="button" onclick="toggleSubmenu('sub_production')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <span class="flex items-center">
                                    <i class="fa-solid fa-microchip w-5 text-indigo-600 dark:text-indigo-400 mr-2.5 text-sm"></i>
                                    <span>Chuyền & Máy sản xuất</span>
                                </span>
                                <i id="sub_production_arrow" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform"></i>
                            </button>
                            <div id="sub_production" class="hidden pl-8 pr-2 py-1 space-y-1 text-xs">
                                <a href="index.php" class="block py-1.5 text-slate-500 hover:text-blue-700 dark:text-slate-400">Line SMT Panasonic D3</a>
                                <a href="index.php" class="block py-1.5 text-slate-500 hover:text-blue-700 dark:text-slate-400">Máy ép nhựa Plastic #04</a>
                                <a href="index.php" class="block py-1.5 text-slate-500 hover:text-blue-700 dark:text-slate-400">Dây chuyền Lắp ráp Assy</a>
                            </div>
                        </div>

                        <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i class="fa-solid fa-box-open w-5 text-amber-600 dark:text-amber-400 mr-2.5 text-sm"></i>
                            <span>Đơn hàng & Kế hoạch</span>
                        </a>

                        <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i class="fa-solid fa-tower-broadcast w-5 text-cyan-600 dark:text-cyan-400 mr-2.5 text-sm"></i>
                            <span>Thiết bị & IoT Giám sát</span>
                        </a>
                    </div>
                </div>

                <!-- NHÓM 3: CHẤT LƯỢNG & ĐÀO TẠO (DOJO) -->
                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-1.5">
                        Chất lượng & Đào tạo
                    </p>
                    <div class="space-y-1">
                        <div>
                            <button type="button" onclick="toggleSubmenu('sub_training')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <span class="flex items-center">
                                    <i class="fa-solid fa-graduation-cap w-5 text-blue-700 dark:text-blue-400 mr-2.5 text-sm"></i>
                                    <span>Chương trình Đào tạo</span>
                                </span>
                                <i id="sub_training_arrow" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform"></i>
                            </button>
                            <div id="sub_training" class="hidden pl-8 pr-2 py-1 space-y-1 text-xs">
                                <a href="index.php" class="block py-1.5 <?= $currentScript === 'index.php' ? 'text-blue-700 font-bold' : 'text-slate-500 hover:text-blue-700 dark:text-slate-400' ?>">Lộ trình theo Cấp độ</a>
                                <a href="course.php?id=1" class="block py-1.5 text-slate-500 hover:text-blue-700 dark:text-slate-400">Khóa SOP Mẫu chuẩn</a>
                                <?php if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)): ?>
                                <a href="exam_builder.php" class="block py-1.5 <?= $currentScript === 'exam_builder.php' ? 'text-blue-700 font-bold' : 'text-slate-500 hover:text-blue-700 dark:text-slate-400' ?>">Thiết kế bài thi mới</a>
                                <a href="sop_manager.php" class="block py-1.5 <?= $currentScript === 'sop_manager.php' ? 'text-blue-700 font-bold' : 'text-slate-500 hover:text-blue-700 dark:text-slate-400' ?>">Quản lý kho SOP</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Thiết kế bài thi (Exam Builder) -->
                        <?php if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)): ?>
                        <a href="exam_builder.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'exam_builder.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                            <i class="fa-solid fa-wand-magic-sparkles w-5 <?= $currentScript === 'exam_builder.php' ? 'text-white' : 'text-amber-500 dark:text-amber-400' ?> mr-2.5 text-sm"></i>
                            <span>Tạo & Bốc Đề Thi</span>
                        </a>
                        <?php endif; ?>

                        <!-- Quản lý Tài liệu SOP & Video -->
                        <?php if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)): ?>
                        <a href="sop_manager.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'sop_manager.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                            <i class="fa-solid fa-file-invoice w-5 <?= $currentScript === 'sop_manager.php' ? 'text-white' : 'text-teal-600 dark:text-teal-400' ?> mr-2.5 text-sm"></i>
                            <span>Quản lý SOP & Video</span>
                        </a>
                        <?php endif; ?>

                        <!-- Bàn chấm bài Tự luận -->
                        <?php if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)): ?>
                        <a href="trainer.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'trainer.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                            <div class="flex items-center">
                                <i class="fa-solid fa-pen-nib w-5 <?= $currentScript === 'trainer.php' ? 'text-white' : 'text-amber-600 dark:text-amber-400' ?> mr-2.5 text-sm"></i>
                                <span>Bàn chấm Tự luận</span>
                            </div>
                            <?php if ($pendingEssayCount > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white">
                                <?= $pendingEssayCount ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>

                        <!-- Lịch sử kiểm toán bài thi -->
                        <a href="history.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'history.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                            <i class="fa-solid fa-clock-rotate-left w-5 <?= $currentScript === 'history.php' ? 'text-white' : 'text-slate-500 dark:text-slate-400' ?> mr-2.5 text-sm"></i>
                            <span>Lịch sử & Đánh giá</span>
                        </a>
                    </div>
                </div>

                <!-- NHÓM 4: NHÂN SỰ & QUẢN TRỊ -->
                <div>
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-1.5">
                        Nhân sự & Quản trị
                    </p>
                    <div class="space-y-1">
                        <a href="admin.php" class="flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript === 'admin.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                            <i class="fa-solid fa-users-gear w-5 text-purple-600 dark:text-purple-400 mr-2.5 text-sm"></i>
                            <span>Quản trị Xưởng & KPI</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Sidebar Bottom Status Footer -->
            <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/70">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5 font-medium text-[11px]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span> Mạng Nội Bộ (LAN)
                    </span>
                    <span class="font-mono text-[10px] font-bold text-blue-900 dark:text-blue-400 bg-blue-50 dark:bg-blue-950 px-1.5 py-0.5 rounded">v2.1-STD</span>
                </div>
            </div>

        </aside>

        <!-- Overlay Backdrop for Mobile Drawer -->
        <div id="sidebarBackdrop" class="fixed inset-0 top-16 bottom-11 bg-slate-900/60 backdrop-blur-sm z-30 hidden md:hidden"></div>

        <!-- ============================================================== -->
        <!-- 3. MAIN CONTENT: THANH CUỘN RIÊNG CHO PHẦN NỘI DUNG CHÍNH      -->
        <!-- ============================================================== -->
        <main id="mainContent" class="flex-1 h-full overflow-y-auto min-w-0 bg-[#F4F6F9] dark:bg-[#0B1120] p-4 sm:p-6 lg:p-8 scroll-smooth focus:outline-none">
            <div class="max-w-7xl mx-auto w-full">
