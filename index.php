<?php
declare(strict_types=1);
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <h1 style="margin-top:0">MangaPitch</h1>
  <p>A starter structure for your CSE370 project (XAMPP + phpMyAdmin).</p>
  <p>
    <?php if (!empty($_SESSION['user_id'])): ?>
      Signed in as <b><?= htmlspecialchars((string)($_SESSION['name'] ?? 'User')) ?></b>.
    <?php else: ?>
      You are not signed in. Try <a href="/auth/login.php">Login</a>.
    <?php endif; ?>
  </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
