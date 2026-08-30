<?php
/**
 * AVASTRA Admin — Footer Include Component
 */
?>
        <footer class="admin-footer text-center">
            <div class="container-fluid d-flex align-items-center justify-content-center gap-2">
                <img src="<?= APP_URL; ?>/assets/images/PHP%20LOGO/transparent-logo.svg" alt="AVASTRA Logo" height="20" style="object-fit:contain;">
                <span>&copy; <?= date('Y'); ?> <strong>AVASTRA</strong>. All rights reserved.</span>
            </div>
        </footer>
    </div> <!-- End #admin-main -->
</div> <!-- End #admin-wrapper -->

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom Admin JS -->
<script src="<?= APP_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
