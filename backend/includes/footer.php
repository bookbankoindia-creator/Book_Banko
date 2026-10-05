<?php
/**
 * Common Footer Include
 * Book Banko Admin Panel
 */
?>
        </main>
        
        <!-- Bottom Footer -->
        <footer class="text-center py-3 border-top bg-white text-secondary" style="font-size: 13px;">
            &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> Admin Portal &bull; Developed for Educational Excellence
        </footer>
    </div>
</div>

<!-- jQuery (Required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Responsive Extensions -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($('.data-table').length > 0) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records..."
            }
        });
    }

    // Sidebar Mobile Toggle
    $('#sidebarToggle').on('click', function() {
        $('#appSidebar').toggleClass('show');
    });

    // Auto dismiss flash alerts after 5 seconds
    setTimeout(function() {
        $('.custom-alert').fadeOut('slow');
    }, 5000);
});
</script>
</body>
</html>
