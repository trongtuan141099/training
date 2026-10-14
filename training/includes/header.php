<?php
// includes/header.php - DX Plastic Group Industrial Management Layout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>DX Plastic Group - Factory Management System</title>
    
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
        body {
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
            width: 5px;
            height: 5px;
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
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="h-full flex flex-col font-sans antialiased text-slate-800 dark:text-slate-100 selection:bg-blue-600 selection:text-white">

<div class="min-h-screen flex flex-row w-full">

    <!-- ============================================================== -->
    <!-- 3. SIDEBAR (THANH MENU BÊN TRÁI): NỀN TRẮNG, ACTIVE ROYAL BLUE  -->
    <!-- ============================================================== -->
    <aside id="factorySidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col transition-all duration-300 ease-in-out -translate-x-full md:translate-x-0 md:static md:inset-auto shrink-0 shadow-lg md:shadow-none">
        
        <!-- Sidebar Brand Logo Top -->
        <div class="h-16 px-5 flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900">
            <a href="index.php" class="flex items-center space-x-2.5">
                <span class="w-9 h-9 rounded-xl bg-blue-900 text-white flex items-center justify-center text-lg font-black shadow-md shadow-blue-900/20">
                    <i class="fa-solid fa-industry"></i>
                </span>
                <div>
                    <span class="font-black text-base tracking-tight text-[#1E3A8A] dark:text-blue-400 block leading-tight">
                        DX PLASTIC GROUP
                    </span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider block">
                        Factory Management
                    </span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button id="closeSidebarBtn" type="button" class="md:hidden text-slate-400 hover:text-slate-600 p-1.5 rounded-lg">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Sidebar Navigation List (Accordion Supported) -->
        <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">
            
            <!-- NHÓM 1: TỔNG QUAN -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Tổng quan hệ thống
                </p>
                <div class="space-y-1">
                    <!-- Mục 'Tổng quan' (Active: Royal Blue) -->
                    <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold transition <?= $currentScript === 'index.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-900' ?>">
                        <i class="fa-solid fa-chart-line w-5 <?= $currentScript === 'index.php' ? 'text-white' : 'text-blue-700 dark:text-blue-400' ?> mr-2.5 text-sm"></i>
                        <span>Tổng quan (Dashboard)</span>
                    </a>

                    <a href="skillmap.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold transition <?= $currentScript === 'skillmap.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-900' ?>">
                        <i class="fa-solid fa-chart-pie w-5 <?= $currentScript === 'skillmap.php' ? 'text-white' : 'text-emerald-600 dark:text-emerald-400' ?> mr-2.5 text-sm"></i>
                        <span>Ma trận Kỹ năng (Skillmap)</span>
                    </a>
                </div>
            </div>

            <!-- NHÓM 2: QUẢN LÝ SẢN XUẤT (CÓ MENU CON ACCORDION) -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Quản lý sản xuất
                </p>
                <div class="space-y-1">
                    <!-- Collapsible Item: Quản lý Chuyền máy -->
                    <div>
                        <button type="button" onclick="toggleSubmenu('sub_production')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
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

                    <!-- Đơn hàng sản xuất -->
                    <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <i class="fa-solid fa-box-open w-5 text-amber-600 dark:text-amber-400 mr-2.5 text-sm"></i>
                        <span>Đơn hàng & Kế hoạch</span>
                    </a>

                    <!-- Thiết bị & IoT -->
                    <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <i class="fa-solid fa-tower-broadcast w-5 text-cyan-600 dark:text-cyan-400 mr-2.5 text-sm"></i>
                        <span>Thiết bị & IoT Giám sát</span>
                    </a>
                </div>
            </div>

            <!-- NHÓM 3: QUẢN LÝ CHẤT LƯỢNG & ĐÀO TẠO (DOJO) -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Chất lượng & Đào tạo
                </p>
                <div class="space-y-1">
                    <!-- Collapsible Item: Đào tạo & Khóa học -->
                    <div>
                        <button type="button" onclick="toggleSubmenu('sub_training')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
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

                    <!-- Quản lý Tài liệu SOP -->
                    <?php if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)): ?>
                    <a href="sop_manager.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold transition <?= $currentScript === 'sop_manager.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i class="fa-solid fa-file-invoice w-5 <?= $currentScript === 'sop_manager.php' ? 'text-white' : 'text-teal-600 dark:text-teal-400' ?> mr-2.5 text-sm"></i>
                        <span>Quản lý Tài liệu SOP</span>
                    </a>
                    <?php endif; ?>

                    <!-- Bàn chấm bài Tự luận -->
                    <?php if ($currentUser && in_array($currentUser['role'], ['TRAINER', 'ADMIN'], true)): ?>
                    <a href="trainer.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-xs font-semibold transition <?= $currentScript === 'trainer.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <div class="flex items-center">
                            <i class="fa-solid fa-pen-nib w-5 <?= $currentScript === 'trainer.php' ? 'text-white' : 'text-amber-600 dark:text-amber-400' ?> mr-2.5 text-sm"></i>
                            <span>Bàn chấm bài Tự luận</span>
                        </div>
                        <?php if ($pendingEssayCount > 0): ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white">
                            <?= $pendingEssayCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>

                    <!-- Lịch sử kiểm toán bài thi -->
                    <a href="history.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold transition <?= $currentScript === 'history.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i class="fa-solid fa-clock-rotate-left w-5 <?= $currentScript === 'history.php' ? 'text-white' : 'text-slate-500 dark:text-slate-400' ?> mr-2.5 text-sm"></i>
                        <span>Lịch sử & Đánh giá</span>
                    </a>
                </div>
            </div>

            <!-- NHÓM 4: NHÂN SỰ & KHO BÃI -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 mb-2">
                    Nhân sự & Vận hành
                </p>
                <div class="space-y-1">
                    <a href="admin.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold transition <?= $currentScript === 'admin.php' ? 'bg-[#1D4ED8] text-white shadow-md shadow-blue-700/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i class="fa-solid fa-users-gear w-5 text-purple-600 dark:text-purple-400 mr-2.5 text-sm"></i>
                        <span>Nhân sự & Ca kíp xưởng</span>
                    </a>
                    <a href="index.php" class="flex items-center px-3.5 py-2.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <i class="fa-solid fa-warehouse w-5 text-rose-600 dark:text-rose-400 mr-2.5 text-sm"></i>
                        <span>Kho vật tư & Feeder</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- Sidebar Bottom Footer Info -->
        <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/70">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Mạng Nội Bộ
                </span>
                <span class="font-mono text-[11px] font-semibold text-blue-900 dark:text-blue-400">v2.0-STD</span>
            </div>
        </div>

    </aside>

    <!-- Overlay Backdrop for Mobile Drawer -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden md:hidden"></div>

    <!-- ============================================================== -->
    <!-- RIGHT MAIN WRAPPER (HEADER + CONTENT + FOOTER)                 -->
    <!-- ============================================================== -->
    <div class="flex-1 flex flex-col min-w-0 bg-[#F4F6F9] dark:bg-[#0B1120]">
        
        <!-- ========================================================== -->
        <!-- 2. HEADER TRÊN CÙNG: LOGO DX PLASTIC GROUP, THEME, PROFILE  -->
        <!-- ========================================================== -->
        <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm flex items-center justify-between px-4 sm:px-6">
            
            <!-- BÊN TRÁI: LOGO 'DX Plastic Group' & NÚT TOGGLE MENU -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <!-- Nút Thu gọn / Mở rộng Sidebar -->
                <button id="toggleSidebarBtn" type="button" class="touch-tap p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-900 hover:bg-slate-100 dark:hover:bg-slate-800 transition focus:outline-none" title="Đóng/Mở Menu">
                    <i class="fa-solid fa-bars-staggered text-lg"></i>
                </button>

                <!-- Logo Brand Name DX Plastic Group -->
                <div class="flex items-center space-x-2">
                    <span class="font-black text-lg tracking-tight text-[#1E3A8A] dark:text-blue-400 whitespace-nowrap">
                        DX Plastic Group
                    </span>
                    <span class="hidden lg:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">
                        Nhà máy Sản xuất
                    </span>
                </div>
            </div>

            <!-- BÊN PHẢI: CHUÔNG THÔNG BÁO, NGÔN NGỮ, DARK/LIGHT MODE, AVATAR -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                
                <!-- 1. ICON THÔNG BÁO (CHUÔNG) -->
                <div class="relative group">
                    <button type="button" class="touch-tap p-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-900 hover:bg-slate-100 dark:hover:bg-slate-800 transition relative" title="Thông báo hệ thống">
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
                                    Có <?= $pendingEssayCount ?> bài thi công nhân đang chờ Trainer chấm.
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

                <!-- 2. CHUYỂN ĐỔI NGÔN NGỮ (ICON CỜ VIỆT NAM & DROPDOWN VI/EN) -->
                <div class="relative group">
                    <button type="button" class="touch-tap flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 text-xs font-bold transition">
                        <span><?= $currentLang === 'vi' ? '🇻🇳' : '🇬🇧' ?></span>
                        <span class="hidden sm:inline text-slate-700 dark:text-slate-200 uppercase"><?= $currentLang ?></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>
                    <!-- Dropdown VI/EN -->
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

                <!-- 3. CHẾ ĐỘ DARK / LIGHT MODE (MẶT TRĂNG / MẶT TRỜI) -->
                <button type="button" onclick="toggleTheme()" class="touch-tap p-2.5 rounded-xl text-slate-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Chuyển chế độ Sáng / Tối">
                    <i class="fa-solid fa-moon text-base dark:hidden"></i>
                    <i class="fa-solid fa-sun text-base hidden dark:inline-block"></i>
                </button>

                <!-- 4. AVATAR & TÊN "Admin User" KÈM MENU THẢ XUỐNG -->
                <div class="relative group pl-1">
                    <button type="button" class="touch-tap flex items-center space-x-2.5 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <!-- Avatar -->
                        <div class="w-9 h-9 rounded-full bg-blue-900 text-white font-bold text-xs flex items-center justify-center border-2 border-white dark:border-slate-800 shadow-sm">
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

                    <!-- Dropdown Quản lý Tài khoản / Đăng xuất -->
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

                        <!-- Danh sách Demo Switcher -->
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

                        <!-- Đăng xuất -->
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
        <!-- 1. PHẦN NỀN (BACKGROUND): NỀN XÁM NHẠT #F4F6F9, KHÔNG GIAN THOÁNG -->
        <!-- ============================================================== -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
