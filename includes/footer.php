<?php
// includes/footer.php - Fixed Bottom Bar & Responsive Interactive Layout Scripts
?>
            </div><!-- /max-w-7xl mx-auto -->
        </main>

    </div><!-- /middle workspace flex container -->

    <!-- ============================================================== -->
    <!-- 4. FOOTER: CỐ ĐỊNH Ở CUỐI TRANG (BOTTOM BAR)                   -->
    <!-- ============================================================== -->
    <footer class="h-11 shrink-0 z-30 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 px-4 sm:px-6 text-xs flex items-center justify-between shadow-sm">
        <div class="max-w-7xl w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-1 text-[11px]">
            <!-- Nội dung bên trái -->
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                <span class="font-bold text-slate-700 dark:text-slate-300">DX Plastic Group</span>
                <span class="text-slate-400">— Hệ thống Quản trị Đào tạo & Năng lực Nhà máy</span>
            </div>
            <!-- Nội dung bên phải -->
            <div class="text-slate-400 dark:text-slate-500 font-medium flex items-center space-x-3">
                <span class="hidden sm:inline"><i class="fa-solid fa-microchip text-blue-600 mr-1"></i> Tiêu chuẩn Lean Dojo 4.0</span>
                <span>•</span>
                <span>Unicode UTF-8 Chuẩn</span>
                <span>•</span>
                <span class="font-mono text-blue-700 dark:text-blue-400 font-bold">2026-v2.1</span>
            </div>
        </div>
    </footer>

</div><!-- /h-screen w-screen outer container -->

<!-- Interactive Scripts: Sidebar Collapsible, Accordion Submenus & Theme Toggle -->
<script>
    // 1. Sidebar Drawer Toggle (Mobile & Desktop)
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const sidebar = document.getElementById('factorySidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function toggleSidebar() {
        if (!sidebar) return;
        if (window.innerWidth < 768) {
            // Mobile: toggle translate
            sidebar.classList.toggle('-translate-x-full');
            if (backdrop) backdrop.classList.toggle('hidden');
        } else {
            // Desktop: toggle collapse/expand
            sidebar.classList.toggle('md:hidden');
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', () => {
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
    });
    if (backdrop) backdrop.addEventListener('click', () => {
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
    });

    // 2. Accordion Collapsible Submenus for Sidebar
    function toggleSubmenu(id) {
        const sub = document.getElementById(id);
        const arrow = document.getElementById(id + '_arrow');
        if (sub) {
            sub.classList.toggle('hidden');
            if (arrow) {
                arrow.classList.toggle('rotate-180');
            }
        }
    }

    // 3. Theme Toggle (Dark / Light Mode)
    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
        } else {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
        }
    }
</script>

</body>
</html>
