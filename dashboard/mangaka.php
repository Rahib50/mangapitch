<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
requireLogin();
$pageTitle = 'Mangaka Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2 style="margin-top:0">Mangaka Dashboard</h2>
  <p>Quick links:</p>
  <ul>
    <li><a href="/manga/upload.php">Upload manga</a></li>
    <li><a href="/messages/index.php">Messages</a></li>
    <li><a href="/bids/index.php">Bids</a></li>
  </ul>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
