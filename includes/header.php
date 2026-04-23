<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'MangaPitch';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars((string)$pageTitle) ?></title>
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
  <div class="container">
    <div class="nav">
      <a href="/index.php">Home</a>
      <a href="/manga/index.php">Manga</a>
      <a href="/search/index.php">Search</a>
      <a href="/analytics/index.php">Analytics</a>
      <a href="/bids/index.php">Bids</a>
      <a href="/contracts/index.php">Contracts</a>
      <a href="/messages/index.php">Messages</a>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <?php
          $role = strtolower((string)($_SESSION['role'] ?? 'mangaka')); // DB stores Mangaka/Studio/Admin
          $dashboard = in_array($role, ['mangaka', 'studio', 'admin'], true) ? $role : 'mangaka';
        ?>
        <a href="/dashboard/<?= htmlspecialchars($dashboard) ?>.php">Dashboard</a>
        <a href="/auth/logout.php">Logout</a>
      <?php else: ?>
        <a href="/auth/login.php">Login</a>
        <a href="/auth/register.php">Register</a>
      <?php endif; ?>
    </div>
