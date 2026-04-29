<?php
/* ============================================================
   dashboard/mangaka.php
   ============================================================ */
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Mangaka');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];

// Manga list with bid counts
$manga = $pdo->prepare("
    SELECT m.MangaID, m.Title, m.PublishDate,
           a.TotalViews, a.VolumesSold,
           COUNT(b.BidID) AS BidCount
    FROM Manga m
    LEFT JOIN Analytics a ON a.MangaID = m.MangaID
    LEFT JOIN Bids      b ON b.MangaID = m.MangaID
    WHERE m.MangakaID = ?
    GROUP BY m.MangaID
    ORDER BY m.CreatedAt DESC
");
$manga->execute([$userID]);
$mangas = $manga->fetchAll();

// Recent bids on their manga
$bids = $pdo->prepare("
    SELECT b.BidID, b.Status, b.CreatedAt,
           m.Title, s.Name AS StudioName
    FROM Bids b
    JOIN Manga m ON m.MangaID  = b.MangaID
    JOIN Users s ON s.UserID   = b.StudioID
    WHERE m.MangakaID = ?
    ORDER BY b.CreatedAt DESC
    LIMIT 5
");
$bids->execute([$userID]);
$recentBids = $bids->fetchAll();

// Unread message count
$msgs = $pdo->prepare("
    SELECT COUNT(*) FROM Messages WHERE ReceiverID = ?
");
$msgs->execute([$userID]);
$msgCount = $msgs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mangaka Dashboard — MangaPitch</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>

    <!-- Summary strip -->
    <div class="analytics-summary">
        <div class="stat">
            <span class="stat-value"><?= count($mangas) ?></span>
            <span class="stat-label">Manga Published</span>
        </div>
        <div class="stat">
            <span class="stat-value">
                <?= array_sum(array_column($mangas, 'BidCount')) ?>
            </span>
            <span class="stat-label">Total Bids Received</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= number_format(array_sum(array_column($mangas, 'TotalViews'))) ?></span>
            <span class="stat-label">Total Views</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= $msgCount ?></span>
            <span class="stat-label">Messages</span>
        </div>
    </div>

    <!-- Manga list -->
    <div class="section-header">
        <h3>Your Manga</h3>
        <a href="/manga/upload.php" class="btn btn-sm">+ Upload New</a>
    </div>

    <?php if (empty($mangas)): ?>
        <p class="muted">No manga uploaded yet. <a href="/manga/upload.php">Upload your first one.</a></p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Publish Date</th>
                    <th>Views</th>
                    <th>Volumes Sold</th>
                    <th>Bids</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($mangas as $m): ?>
                <tr>
                    <td><a href="/manga/view.php?id=<?= $m['MangaID'] ?>"><?= htmlspecialchars($m['Title']) ?></a></td>
                    <td><?= $m['PublishDate'] ? date('M j, Y', strtotime($m['PublishDate'])) : '—' ?></td>
                    <td><?= number_format($m['TotalViews'] ?? 0) ?></td>
                    <td><?= number_format($m['VolumesSold'] ?? 0) ?></td>
                    <td><?= $m['BidCount'] ?></td>
                    <td>
                        <a href="/bids/index.php" class="btn btn-sm">View Bids</a>
                        <a href="/messages/index.php" class="btn btn-sm btn-secondary">Messages</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Recent bids -->
    <h3>Recent Bids</h3>
    <?php if (empty($recentBids)): ?>
        <p class="muted">No bids yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>Manga</th><th>Studio</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentBids as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['Title']) ?></td>
                    <td><?= htmlspecialchars($b['StudioName']) ?></td>
                    <td><span class="badge badge-<?= strtolower($b['Status']) ?>"><?= $b['Status'] ?></span></td>
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
