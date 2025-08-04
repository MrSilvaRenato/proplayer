<?php
require_once __DIR__ . '/../config.php'; // Correct path to config.php (one level up)
?>
<div id="toastContainer" class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999;">

<link rel="stylesheet" href="<?= BASE_URL ?>css/toast.css">
<script src="<?= BASE_URL ?>js/toast.js"></script>

<?php if (isset($_SESSION['toast'])): ?>
  <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] === 'success' ? 'success' : 'danger' ?> border-0 show" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        <?= htmlspecialchars($_SESSION['toast']['message']) ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      showToast();
    });
  </script>
<?php unset($_SESSION['toast']); endif; ?>
