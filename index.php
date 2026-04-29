<?php
declare(strict_types=1);
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <h1 style="margin-top:0">MangaPitch</h1>
  <p>Connect Mangaka and Studios to pitch manga, place bids, sign contracts, and collaborate.</p>
  <p>
    <?php if (!empty($_SESSION['user'])): ?>
      Signed in as <b><?= htmlspecialchars((string)($_SESSION['user']['name'] ?? 'User')) ?></b>.
    <?php else: ?>
      You are not signed in. Try <a href="/auth/login.php">Login</a>.
    <?php endif; ?>
  </p>
</div>

<div class="split">
  <div class="card">
    <h3 style="margin-top:0">Core Modules</h3>
    <p class="muted">Your CSE370 project has all major workflows connected from one place.</p>
    <ul>
      <li>Manga upload and discovery</li>
      <li>Bid and contract tracking</li>
      <li>Role-based dashboard flow</li>
      <li>Built-in direct messaging</li>
    </ul>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Quick Access</h3>
    <p><a href="/manga/index.php" class="btn">Browse Manga</a></p>
    <p><a href="/messages/index.php" class="btn btn-secondary">Open Messages</a></p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
