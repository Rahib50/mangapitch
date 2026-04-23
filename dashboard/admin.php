<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
requireLogin();
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2 style="margin-top:0">Admin Dashboard</h2>
  <p>Admin overview (placeholders):</p>
  <ul>
    <li><b>User management</b>: view/delete users</li>
    <li><b>Bids & contracts oversight</b>: monitor all bids/contracts</li>
    <li><b>Platform analytics</b>: platform-wide usage and performance</li>
  </ul>

  <div style="height: 8px"></div>
  <p>Quick links:</p>
  <ul>
    <li><a href="/analytics/index.php">Analytics</a></li>
    <li><a href="/bids/index.php">Bids</a></li>
    <li><a href="/contracts/index.php">Contracts</a></li>
    <li><a href="/messages/index.php">Messages</a></li>
  </ul>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

