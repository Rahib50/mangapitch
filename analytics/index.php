<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];

if ($role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT m.MangaID, m.Title, m.PublishDate,
               u.Name AS MangakaName,
               a.TotalViews, a.VolumesSold,
               GROUP_CONCAT(g.GenreName SEPARATOR ', ') AS Genres
        FROM Analytics a
        JOIN Manga m ON m.MangaID = a.MangaID
        JOIN Users u ON u.UserID  = m.MangakaID
        LEFT JOIN Manga_Genre_Map mgm ON mgm.MangaID = m.MangaID
        LEFT JOIN Genre g ON g.GenreID = mgm.GenreID
        GROUP BY a.AnalyticsID
        ORDER BY a.TotalViews DESC
    ");
    $stmt->execute();

} elseif ($role === 'Mangaka') {
    $stmt = $pdo->prepare("
        SELECT m.MangaID, m.Title, m.PublishDate,
               a.TotalViews, a.VolumesSold,
               GROUP_CONCAT(g.GenreName SEPARATOR ', ') AS Genres
        FROM Analytics a
        JOIN Manga m ON m.MangaID = a.MangaID
        LEFT JOIN Manga_Genre_Map mgm ON mgm.MangaID = m.MangaID
        LEFT JOIN Genre g ON g.GenreID = mgm.GenreID
        WHERE m.MangakaID = ?
        GROUP BY a.AnalyticsID
        ORDER BY a.TotalViews DESC
    ");
    $stmt->execute([$userID]);

} else {
    $stmt = $pdo->prepare("
        SELECT m.MangaID, m.Title, m.PublishDate,
               u.Name AS MangakaName,
               a.TotalViews, a.VolumesSold,
               GROUP_CONCAT(g.GenreName SEPARATOR ', ') AS Genres
        FROM Analytics a
        JOIN Manga m ON m.MangaID = a.MangaID
        JOIN Users u ON u.UserID  = m.MangakaID
        LEFT JOIN Manga_Genre_Map mgm ON mgm.MangaID = m.MangaID
        LEFT JOIN Genre g ON g.GenreID = mgm.GenreID
        WHERE m.MangaID IN (SELECT MangaID FROM Bids WHERE StudioID = ?)
        GROUP BY a.AnalyticsID
        ORDER BY a.TotalViews DESC
    ");
    $stmt->execute([$userID]);
}

$rows = $stmt->fetchAll();

$totals = null;
if ($role === 'Admin') {
    $totals = $pdo->query("
        SELECT SUM(TotalViews) AS TotalViews, SUM(VolumesSold) AS TotalVolumes
        FROM Analytics
    ")->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Analytics</h2>

    <?php if ($role === 'Admin' && $totals): ?>
        <div class="analytics-summary">
            <div class="stat">
                <span class="stat-value"><?= number_format($totals['TotalViews']) ?></span>
                <span class="stat-label">Platform Total Views</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= number_format($totals['TotalVolumes']) ?></span>
                <span class="stat-label">Platform Total Volumes Sold</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <p class="muted">No analytics data available.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Manga</th>
                    <?php if ($role !== 'Mangaka'): ?><th>Mangaka</th><?php endif; ?>
                    <th>Genres</th>
                    <th>Publish Date</th>
                    <th>Total Views</th>
                    <th>Volumes Sold</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <a href="<?= BASE ?>/manga/view.php?id=<?= $r['MangaID'] ?>">
                            <?= htmlspecialchars($r['Title']) ?>
                        </a>
                    </td>
                    <?php if ($role !== 'Mangaka'): ?>
                        <td><?= htmlspecialchars($r['MangakaName']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($r['Genres'] ?? '—') ?></td>
                    <td><?= $r['PublishDate'] ? date('M j, Y', strtotime($r['PublishDate'])) : '—' ?></td>
                    <td><?= number_format($r['TotalViews']) ?></td>
                    <td><?= number_format($r['VolumesSold']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>