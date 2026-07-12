    <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    </main>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        }
    </script>
</body>
</html>
