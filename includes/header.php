<?php
/* ============================================================
   includes/header.php
   ============================================================ */
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? null;
?>
<header class="site-header">
    <div class="header-inner">
        <a href="<?= BASE ?>/index.php" class="site-logo">MangaPitch</a>
        <nav class="site-nav">
            <?php if ($role === 'Mangaka'): ?>
                <a href="<?= BASE ?>/dashboard/mangaka.php">Dashboard</a>
                <a href="<?= BASE ?>/manga/upload.php">Upload</a>
                <a href="<?= BASE ?>/bids/index.php">Bids</a>
                <a href="<?= BASE ?>/analytics/index.php">Analytics</a>
                <a href="<?= BASE ?>/messages/index.php">Messages</a>
            <?php elseif ($role === 'Studio'): ?>
                <a href="<?= BASE ?>/dashboard/studio.php">Dashboard</a>
                <a href="<?= BASE ?>/search/index.php">Search</a>
                <a href="<?= BASE ?>/bids/index.php">My Bids</a>
                <a href="<?= BASE ?>/contracts/index.php">Contracts</a>
                <a href="<?= BASE ?>/messages/index.php">Messages</a>
            <?php elseif ($role === 'Admin'): ?>
                <a href="<?= BASE ?>/dashboard/admin.php">Dashboard</a>
                <a href="<?= BASE ?>/analytics/index.php">Analytics</a>
                <a href="<?= BASE ?>/bids/index.php">Bids</a>
                <a href="<?= BASE ?>/contracts/index.php">Contracts</a>
                <a href="<?= BASE ?>/messages/index.php">Messages</a>
            <?php endif; ?>
        </nav>
        <div class="header-user">
            <?php if ($name): ?>
                <span class="user-name"><?= htmlspecialchars($name) ?></span>
                <span class="badge badge-<?= strtolower($role) ?>"><?= $role ?></span>
                <a href="<?= BASE ?>/auth/logout.php" class="btn btn-sm btn-secondary">Logout</a>
            <?php else: ?>
                <a href="<?= BASE ?>/auth/login.php" class="btn btn-sm">Login</a>
                <a href="<?= BASE ?>/auth/register.php" class="btn btn-sm btn-secondary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>