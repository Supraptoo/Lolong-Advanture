<footer class="footer mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">Lelong Adventure &copy; <?= date('Y') ?></span>
            </div>
            <div class="col-md-6 text-end">
                <span class="text-muted">Version 1.0.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript Libraries -->
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin.js"></script>

<!-- Custom Script for this page -->
<script>
    $(document).ready(function() {
        // Sidebar toggle
        $('#sidebarToggle').click(function() {
            $('#sidebar').toggleClass('active');
            $('.main-content').toggleClass('active');
        });

        // Delete confirmation
        $('.delete-btn').click(function() {
            const id = $(this).data('id');
            if (confirm('Apakah Anda yakin ingin menghapus pemesanan ini?')) {
                window.location.href = `delete.php?id=${id}`;
            }
        });
    });
</script>
</body>

</html>