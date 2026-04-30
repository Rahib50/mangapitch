
<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Admin');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getPDO();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM Users WHERE Role = 'Mangaka') AS Mangakas,
        (SELECT COUNT(*) FROM Users WHERE Role = 'Studio')  AS Studios,
        (SELECT COUNT(*) FROM Manga)                        AS Mangas,
        (SELECT COUNT(*) FROM Bids)                         AS Bids,
        (SELECT COUNT(*) FROM Bids WHERE Status = 'Pending') AS PendingBids,
        (SELECT COUNT(*) FROM Contracts)                    AS Contracts,
        (SELECT SUM(TotalViews)  FROM Analytics)            AS TotalViews,
        (SELECT SUM(VolumesSold) FROM Analytics)            AS TotalVolumes
")->fetch();

$users = $pdo->query("
    SELECT UserID, Name, Email, Role, CreatedAt
    FROM Users ORDER BY CreatedAt DESC LIMIT 8
")->fetchAll();

$pendingBids = $pdo->query("
    SELECT b.BidID, b.BidAmount, b.CreatedAt,
           m.Title, s.Name AS StudioName, mk.Name AS MangakaName
    FROM Bids b
    JOIN Manga m  ON m.MangaID  = b.MangaID
    JOIN Users s  ON s.UserID   = b.StudioID
    JOIN Users mk ON mk.UserID  = m.MangakaID
    WHERE b.Status = 'Pending'
    ORDER BY b.CreatedAt DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Admin Dashboard</h2>

    <div class="analytics-summary">
        <div class="stat"><span class="stat-value"><?= $stats['Mangakas'] ?></span><span class="stat-label">Mangakas</span></div>
        <div class="stat"><span class="stat-value"><?= $stats['Studios'] ?></span><span class="stat-label">Studios</span></div>
        <div class="stat"><span class="stat-value"><?= $stats['Mangas'] ?></span><span class="stat-label">Manga Titles</span></div>
        <div class="stat"><span class="stat-value"><?= $stats['Bids'] ?></span><span class="stat-label">Total Bids</span></div>
        <div class="stat"><span class="stat-value"><?= $stats['PendingBids'] ?></span><span class="stat-label">Pending Bids</span></div>
        <div class="stat"><span class="stat-value"><?= $stats['Contracts'] ?></span><span class="stat-label">Contracts</span></div>
        <div class="stat"><span class="stat-value"><?= number_format($stats['TotalViews']) ?></span><span class="stat-label">Total Views</span></div>
        <div class="stat"><span class="stat-value"><?= number_format($stats['TotalVolumes']) ?></span><span class="stat-label">Volumes Sold</span></div>
    </div>

    <div class="admin-links">
        <a href="<?= BASE ?>/analytics/index.php" class="btn">Platform Analytics</a>
        <a href="<?= BASE ?>/contracts/index.php" class="btn">Manage Contracts</a>
        <a href="<?= BASE ?>/bids/index.php"      class="btn">All Bids</a>
        <a href="<?= BASE ?>/messages/index.php"  class="btn">Messages</a>
    </div>

    <h3>Recent Users</h3>
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['Name']) ?></td>
                <td><?= htmlspecialchars($u['Email']) ?></td>
                <td><span class="badge badge-<?= strtolower($u['Role']) ?>"><?= $u['Role'] ?></span></td>
                <td><?= date('M j, Y', strtotime($u['CreatedAt'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Pending Bids</h3>
    <?php if (empty($pendingBids)): ?>
        <p class="muted">No pending bids.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>Manga</th><th>Mangaka</th><th>Studio</th><th>Amount</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($pendingBids as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['Title']) ?></td>
                    <td><?= htmlspecialchars($b['MangakaName']) ?></td>
                    <td><?= htmlspecialchars($b['StudioName']) ?></td>
                    <td>$<?= number_format($b['BidAmount'], 2) ?></td>
                    <td><?= date('M j, Y', strtotime($b['CreatedAt'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>