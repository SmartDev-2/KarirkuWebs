<?php
// logout-modal.php
?>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt text-warning me-2"></i>
                    Konfirmasi Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-question-circle fa-3x text-warning"></i>
                </div>
                <h6 class="fw-bold">Apakah Anda yakin ingin logout?</h6>
                <p class="text-muted mb-0">Anda akan keluar dari akun KaririKu dan perlu login kembali untuk mengakses fitur.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Ya, Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Function untuk menampilkan modal logout
function confirmLogout() {
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
    return false; // Mencegah default action
}
</script>