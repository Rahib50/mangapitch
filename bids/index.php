
<?php
/* ============================================================
   bids/index.php
   — Studio : sees only their own bids + competitor count
   — Mangaka: sees all bids on their manga (amounts visible)
   — Admin  : sees all bids platform-wide
   ============================================================ */
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Studio', 'Mangaka', 'Admin');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];

// ── Build query based on role ─────────────────────────────────
if ($role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
               m.Title, m.MangaID,
               u.Name  AS StudioName,
               b.StudioID
        FROM Bids b
        JOIN Manga   m ON m.MangaID  = b.MangaID
        JOIN Users   u ON u.UserID   = b.StudioID
        ORDER BY b.CreatedAt DESC
    ");
    $stmt->execute();

} elseif ($role === 'Mangaka') {
    // Mangaka sees all bids on manga they own
    $stmt = $pdo->prepare("
        SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
               m.Title, m.MangaID,
               u.Name  AS StudioName,
               b.StudioID
        FROM Bids b
        JOIN Manga   m ON m.MangaID  = b.MangaID
        JOIN Users   u ON u.UserID   = b.StudioID
        WHERE m.MangakaID = ?
        ORDER BY m.MangaID, b.BidAmount DESC
    ");
    $stmt->execute([$userID]);

} else {
    // Studio sees only their own bids
    $stmt = $pdo->prepare("
        SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
               m.Title, m.MangaID,
               (
                   SELECT COUNT(*)
                   FROM Bids b2
                   WHERE b2.MangaID = b.MangaID
                     AND b2.StudioID <> ?
               ) AS CompetitorCount
        FROM Bids b
        JOIN Manga m ON m.MangaID = b.MangaID
        WHERE b.StudioID = ?
        ORDER BY b.CreatedAt DESC
    ");
    $stmt->execute([$userID, $userID]);
}

$bids = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bids — MangaPitch</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>
        <?php if ($role === 'Admin')   echo 'All Bids'; ?>
        <?php if ($role === 'Mangaka') echo 'Bids on Your Manga'; ?>
        <?php if ($role === 'Studio')  echo 'Your Bids'; ?>
    </h2>

    <?php if (empty($bids)): ?>
        <p>No bids found.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Manga</th>
                <?php if ($role !== 'Studio'): ?>
                    <th>Studio</th>
                <?php endif; ?>
                <th>Your Offer</th>
                <th>Status</th>
                <?php if ($role === 'Studio'): ?>
                    <th>Competition</th>
                    <th>Action</th>
                <?php endif; ?>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bids as $bid): ?>
            <tr>
                <td><?= htmlspecialchars($bid['Title']) ?></td>

                <?php if ($role !== 'Studio'): ?>
                    <td><?= htmlspecialchars($bid['StudioName']) ?></td>
                <?php endif; ?>

                <!-- Amount: Studio always sees their own, others see all -->
                <td>$<?= number_format($bid['BidAmount'], 2) ?></td>

                <td>
                    <span class="badge badge-<?= strtolower($bid['Status']) ?>">
                        <?= htmlspecialchars($bid['Status']) ?>
                    </span>
                </td>

                <?php if ($role === 'Studio'): ?>
                    <!-- Competitor count — no amounts revealed -->
                    <td>
                        <?php if ($bid['CompetitorCount'] > 0): ?>
                            <?= (int)$bid['CompetitorCount'] ?> other bid<?= $bid['CompetitorCount'] > 1 ? 's' : '' ?> placed
                        <?php else: ?>
                            No other bids yet
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($bid['Status'] === 'Pending'): ?>
                            <a href="submit.php?manga_id=<?= $bid['MangaID'] ?>" class="btn btn-sm">
                                Raise Bid
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                <?php endif; ?>

                <td><?= date('M j, Y', strtotime($bid['CreatedAt'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($role === 'Studio'): ?>
        <a href="submit.php" class="btn">Place New Bid</a>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>