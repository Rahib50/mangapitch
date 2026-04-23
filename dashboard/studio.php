<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
requireLogin();
$pageTitle = 'Studio Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2 style="margin-top:0">Studio Dashboard</h2>
  <p>Quick links:</p>
  <ul>
    <li><a href="/search/index.php">Find manga</a></li>
    <li><a href="/bids/index.php">View/submit bids</a></li>
    <li><a href="/contracts/index.php">Contracts</a></li>
    <li><a href="/messages/index.php">Messages</a></li>
  </ul>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
