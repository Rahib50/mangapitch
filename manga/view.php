<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$mangaID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$mangaID) {
    header('Location: ' . BASE . '/search/index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.MangaID, m.Title, m.Synopsis, m.PublishDate, m.MangakaID,
           m.CoverImage, m.PanelImages,
           u.Name AS MangakaName,
           a.TotalViews, a.VolumesSold
    FROM Manga m
    JOIN Users u ON u.UserID = m.MangakaID
    LEFT JOIN Analytics a ON a.MangaID = m.MangaID
    WHERE m.MangaID = ?
");
$stmt->execute([$mangaID]);
$manga = $stmt->fetch();

if (!$manga) {
    http_response_code(404);
    die('<h2>Manga not found.</h2>');
}

$gStmt = $pdo->prepare("
    SELECT g.GenreName FROM Genre g
    JOIN Manga_Genre_Map mgm ON mgm.GenreID = g.GenreID
    WHERE mgm.MangaID = ?
    ORDER BY g.GenreName
");
$gStmt->execute([$mangaID]);
$genres = $gStmt->fetchAll(PDO::FETCH_COLUMN);

$panels = $manga['PanelImages'] ? json_decode($manga['PanelImages'], true) : [];

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
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <div class="manga-detail">
        <div class="manga-hero">
            <?php if ($manga['CoverImage']): ?>
                <div class="manga-cover">
                    <img src="<?= BASE ?>/uploads/covers/<?= htmlspecialchars($manga['CoverImage']) ?>"
                         alt="Cover — <?= htmlspecialchars($manga['Title']) ?>">
                </div>
            <?php endif; ?>
            <div class="manga-info">
                <h2><?= htmlspecialchars($manga['Title']) ?></h2>
                <p class="manga-author">by <?= htmlspecialchars($manga['MangakaName']) ?></p>

                <div class="genre-tags">
                    <?php foreach ($genres as $g): ?>
                        <span class="tag"><?= htmlspecialchars($g) ?></span>
                    <?php endforeach; ?>
                </div>

                //scrollable synopsis
                <div class="manga-synopsis-wrap">
                    <p class="manga-synopsis">
                        <?= nl2br(htmlspecialchars($manga['Synopsis'] ?? 'No synopsis available.')) ?>
                    </p>
                </div>

                <p class="manga-publish">
                     Published: <?= $manga['PublishDate'] ? date('F j, Y', strtotime($manga['PublishDate'])) : '—' ?>
                </p>
            </div>
        </div>

        <div class="analytics-strip">
            <div class="stat">
                <span class="stat-value"><?= number_format($manga['TotalViews'] ?? 0) ?></span>
                <span class="stat-label">Total Views</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= number_format($manga['VolumesSold'] ?? 0) ?></span>
                <span class="stat-label">Volumes Sold</span>
            </div>
        </div>

        <?php if (!empty($panels)): ?>
            <div class="manga-panels">
                <h3>Panels</h3>
                <div class="panel-grid">
                    <?php foreach ($panels as $panel): ?>
                        <div class="panel-item" onclick="openLightbox('<?= BASE ?>/uploads/panels/<?= htmlspecialchars($panel) ?>')">
                            <img src="<?= BASE ?>/uploads/panels/<?= htmlspecialchars($panel) ?>"
                                 alt="Panel">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div id="lightbox" onclick="closeLightbox()">
            <span id="lightbox-close">✕</span>
            <img id="lightbox-img" src="" alt="Panel fullview">
        </div>

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
                        <a href="<?= BASE ?>/bids/submit.php?manga_id=<?= $mangaID ?>" class="btn">Raise Bid</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= BASE ?>/bids/submit.php?manga_id=<?= $mangaID ?>" class="btn">Place Bid</a>
                <?php endif; ?>
                <a href="<?= BASE ?>/messages/send.php?to=<?= $manga['MangakaID'] ?>" class="btn btn-secondary">
                    Message Mangaka
                </a>
            <?php endif; ?>

            <?php if ($role === 'Mangaka' && $manga['MangakaID'] == $userID): ?>
                <a href="<?= BASE ?>/manga/panels.php?id=<?= $mangaID ?>" class="btn btn-secondary">Manage Panels</a>
                <a href="<?= BASE ?>/bids/index.php" class="btn btn-secondary">View Bids</a>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
<script>
    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').classList.add('active');
    }
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
        document.getElementById('lightbox-img').src = '';
    }
    // Close on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeLightbox();
    });
    // Prevent closing when clicking the image itself
    document.getElementById('lightbox-img').addEventListener('click', e => e.stopPropagation());
</script>
</body>
</html>