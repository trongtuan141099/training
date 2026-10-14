<?php
// includes/footer.php - Thin Bottom Bar & Interactive Scripts
?>
        </main>

        <!-- ============================================================== -->
        <!-- 4. FOOTER: THANH MỎNG (BOTTOM BAR) PHÂN TÁCH BỞI ĐƯỜNG KẺ MỎNG  -->
        <!-- ============================================================== -->
        <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 py-3.5 px-6 text-xs mt-auto">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2.5">
                <!-- Nội dung bên trái -->
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">DX Plastic Group — Factory Management System</span>
                </div>
                <!-- Nội dung bên phải -->
                <div class="text-slate-400 dark:text-slate-500 text-[11px] font-medium">
                    Phiên bản 2.0 • Tiêu chuẩn công nghiệp
                </div>
            </div>
        </footer>

    </div><!-- /right main wrapper -->

</div><!-- /min-h-screen wrapper -->

<!-- Interactive Scripts: Sidebar Collapsible & Accordion Submenus & Theme Toggle -->
<script>
    // 1. Sidebar Drawer Toggle (Mobile & Desktop)
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const sidebar = document.getElementById('factorySidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function toggleSidebar() {
        if (!sidebar) return;
        // On mobile: toggle translate
        if (window.innerWidth < 768) {
            sidebar.classList.toggle('-translate-x-full');
            if (backdrop) backdrop.classList.toggle('hidden');
        } else {
            // On desktop: collapse/expand width
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
