<?php
/* ============================================================
   manga/view.php?id=MANGA_ID
   — Public-facing manga detail page
   — Studios see a "Place Bid" button
   — Mangaka (owner) sees an edit link
   — All roles see analytics
   ============================================================ */
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$mangaID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$mangaID) {
    header('Location: /search/index.php');
    exit;
}

// Fetch manga + analytics
$stmt = $pdo->prepare("
    SELECT m.MangaID, m.Title, m.Synopsis, m.PublishDate, m.MangakaID,
           u.Name AS MangakaName,
           a.TotalViews, a.VolumesSold
    FROM Manga m
    JOIN Users     u ON u.UserID  = m.MangakaID
    LEFT JOIN Analytics a ON a.MangaID = m.MangaID
    WHERE m.MangaID = ?
");
$stmt->execute([$mangaID]);
$manga = $stmt->fetch();

if (!$manga) {
    http_response_code(404);
    die('<h2>Manga not found.</h2>');
}

// Fetch genres
$gStmt = $pdo->prepare("
    SELECT g.GenreName FROM Genre g
    JOIN Manga_Genre_Map mgm ON mgm.GenreID = g.GenreID
    WHERE mgm.MangaID = ?
    ORDER BY g.GenreName
");
$gStmt->execute([$mangaID]);
$genres = $gStmt->fetchAll(PDO::FETCH_COLUMN);

// Check if studio already has a bid on this manga
$existingBid = null;
if ($role === 'Studio') {
    $bStmt = $pdo->prepare("
        SELECT BidID, BidAmount, Status FROM Bids
        WHERE MangaID = ? AND StudioID = ?
    ");
    $bStmt->execute([$mangaID, $userID]);
    $existingBid = $bStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($manga['Title']) ?> — MangaPitch</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">

    <div class="manga-detail">
        <div class="manga-detail-header">
            <h2><?= htmlspecialchars($manga['Title']) ?></h2>
            <p class="manga-author">by <?= htmlspecialchars($manga['MangakaName']) ?></p>

            <div class="genre-tags">
                <?php foreach ($genres as $g): ?>
                    <span class="tag"><?= htmlspecialchars($g) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="manga-detail-body">
            <p class="manga-synopsis">
                <?= nl2br(htmlspecialchars($manga['Synopsis'] ?? 'No synopsis available.')) ?>
            </p>

            <p><strong>Published:</strong>
                <?= $manga['PublishDate'] ? date('F j, Y', strtotime($manga['PublishDate'])) : '—' ?>
            </p>
        </div>

        <!-- Analytics -->
        <div class="analytics-strip">
            <div class="stat">
                <span class="stat-value">
                    <?= number_format($manga['TotalViews'] ?? 0) ?>
                </span>
                <span class="stat-label">Total Views</span>
            </div>
            <div class="stat">
                <span class="stat-value">
                    <?= number_format($manga['VolumesSold'] ?? 0) ?>
                </span>
                <span class="stat-label">Volumes Sold</span>
            </div>
        </div>

        <!-- Role-based actions -->
        <div class="manga-actions">
            <?php if ($role === 'Studio'): ?>
                <?php if ($existingBid): ?>
                    <p>Your current bid:
                        <strong>$<?= number_format($existingBid['BidAmount'], 2) ?></strong>
                        <span class="badge badge-<?= strtolower($existingBid['Status']) ?>">
                            <?= htmlspecialchars($existingBid['Status']) ?>
                        </span>
                    </p>
                    <?php if ($existingBid['Status'] === 'Pending'): ?>
                        <a href="/bids/submit.php?manga_id=<?= $mangaID ?>" class="btn">
                            Raise Bid
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/bids/submit.php?manga_id=<?= $mangaID ?>" class="btn">
                        Place Bid
                    </a>
                <?php endif; ?>
                <a href="/messages/send.php?to=<?= $manga['MangakaID'] ?>" class="btn btn-secondary">
                    Message Mangaka
                </a>
            <?php endif; ?>

            <?php if ($role === 'Mangaka' && $manga['MangakaID'] == $userID): ?>
                <a href="upload.php?edit=<?= $mangaID ?>" class="btn">Edit</a>
                <a href="/bids/index.php" class="btn btn-secondary">View Bids</a>
            <?php endif; ?>
        </div>
    </div>

</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>