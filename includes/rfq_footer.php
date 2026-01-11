    </div><!-- /.main-content -->

    <!-- Footer -->
    <footer class="text-center py-3 text-muted" style="margin-left: 250px; border-top: 1px solid #eee; background: #fff; font-size: 0.85rem;">
        <span>&copy; <?php echo date('Y'); ?> Inava Steel Ltd. All rights reserved.</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/aiforms/assets/js/app.js"></script>
    <script>
        // Sidebar toggle for mobile and desktop collapse
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            // Mobile toggle
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            sidebar.classList.remove('show');
                        }
                    }
                });
            }

            // Desktop sidebar collapse
            const footer = document.querySelector('footer');
            if (sidebarCollapseBtn && sidebar && mainContent) {
                // Restore state from localStorage
                const isCollapsed = localStorage.getItem('rfq_sidebar_collapsed') === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                    if (footer) footer.classList.add('expanded');
                }

                sidebarCollapseBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    if (footer) footer.classList.toggle('expanded');

                    // Save state to localStorage
                    const nowCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('rfq_sidebar_collapsed', nowCollapsed);
                });
            }
        });
    </script>
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
