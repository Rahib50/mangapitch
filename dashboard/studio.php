<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Studio');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];

$bids = $pdo->prepare("
    SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
           m.Title, m.MangaID,
           (SELECT COUNT(*) FROM Bids b2
            WHERE b2.MangaID = b.MangaID AND b2.StudioID <> ?) AS Competitors
    FROM Bids b
    JOIN Manga m ON m.MangaID = b.MangaID
    WHERE b.StudioID = ?
    ORDER BY b.CreatedAt DESC
    LIMIT 5
");
$bids->execute([$userID, $userID]);
$recentBids = $bids->fetchAll();

$contracts = $pdo->prepare("
    SELECT c.ContractID, c.ProductStatus, c.SignedDate,
           m.Title
    FROM Contracts c
    JOIN Bids  b ON b.BidID   = c.BidID
    JOIN Manga m ON m.MangaID = b.MangaID
    WHERE b.StudioID = ?
    ORDER BY c.SignedDate DESC
");
$contracts->execute([$userID]);
$activeContracts = $contracts->fetchAll();

$msgs = $pdo->prepare("SELECT COUNT(*) FROM Messages WHERE ReceiverID = ?");
$msgs->execute([$userID]);
$msgCount = $msgs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Studio Dashboard — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>

    <div class="analytics-summary">
        <div class="stat">
            <span class="stat-value"><?= count($recentBids) ?></span>
            <span class="stat-label">Active Bids</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= count($activeContracts) ?></span>
            <span class="stat-label">Contracts</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= $msgCount ?></span>
            <span class="stat-label">Messages</span>
        </div>
    </div>

    <div class="section-header">
        <h3>Your Bids</h3>
        <a href="<?= BASE ?>/search/index.php" class="btn btn-sm">Browse Manga</a>
    </div>

    <?php if (empty($recentBids)): ?>
        <p class="muted">No bids placed yet. <a href="<?= BASE ?>/search/index.php">Browse manga to bid on.</a></p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>Manga</th><th>Your Bid</th><th>Status</th><th>Competition</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentBids as $b): ?>
                <tr>
                    <td><a href="<?= BASE ?>/manga/view.php?id=<?= $b['MangaID'] ?>"><?= htmlspecialchars($b['Title']) ?></a></td>
                    <td>$<?= number_format($b['BidAmount'], 2) ?></td>
                    <td><span class="badge badge-<?= strtolower($b['Status']) ?>"><?= $b['Status'] ?></span></td>
                    <td><?= $b['Competitors'] > 0 ? $b['Competitors'] . ' other bid(s)' : 'No competition' ?></td>
                    <td><?= date('M j, Y', strtotime($b['CreatedAt'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Your Contracts</h3>
    <?php if (empty($activeContracts)): ?>
        <p class="muted">No contracts yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>Manga</th><th>Signed</th><th>Production Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($activeContracts as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['Title']) ?></td>
                    <td><?= $c['SignedDate'] ? date('M j, Y', strtotime($c['SignedDate'])) : '—' ?></td>
                    <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $c['ProductStatus'])) ?>"><?= htmlspecialchars($c['ProductStatus']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>