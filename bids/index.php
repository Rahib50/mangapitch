<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Studio', 'Mangaka', 'Admin');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];

//Mangaka: handle accept/reject
if ($role === 'Mangaka' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $bidID  = (int)($_POST['bid_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($bidID && in_array($action, ['accept', 'reject'], true)) {
        // Verify the bid is on a manga owned by this Mangaka
        $verify = $pdo->prepare("
            SELECT b.BidID, b.MangaID FROM Bids b
            JOIN Manga m ON m.MangaID = b.MangaID
            WHERE b.BidID = ? AND m.MangakaID = ? AND b.Status = 'Pending'
        ");
        $verify->execute([$bidID, $userID]);
        $bid = $verify->fetch();

        if ($bid) {
            if ($action === 'accept') {
                $pdo->beginTransaction();
                try {
                    // Accept this bid
                    $pdo->prepare("UPDATE Bids SET Status = 'Accepted' WHERE BidID = ?")
                        ->execute([$bidID]);
                    // Reject all other pending bids 
                    $pdo->prepare("
                        UPDATE Bids SET Status = 'Rejected'
                        WHERE MangaID = ? AND BidID <> ? AND Status = 'Pending'
                    ")->execute([$bid['MangaID'], $bidID]);
                    // Create contract
                    $pdo->prepare("
                        INSERT INTO Contracts (BidID, SignedDate, ProductStatus)
                        VALUES (?, CURDATE(), 'Pre-Production')
                    ")->execute([$bidID]);
                    $pdo->commit();
                    $success = 'Bid accepted and contract created.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Failed to accept bid. Please try again.';
                }
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE Bids SET Status = 'Rejected' WHERE BidID = ?")
                    ->execute([$bidID]);
                $success = 'Bid rejected.';
            }
        }
    }
}

if ($role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
               m.Title, m.MangaID,
               u.Name AS StudioName,
               b.StudioID
        FROM Bids b
        JOIN Manga m ON m.MangaID = b.MangaID
        JOIN Users u ON u.UserID  = b.StudioID
        ORDER BY b.CreatedAt DESC
    ");
    $stmt->execute();

} elseif ($role === 'Mangaka') {
    $stmt = $pdo->prepare("
        SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
               m.Title, m.MangaID,
               u.Name AS StudioName,
               b.StudioID
        FROM Bids b
        JOIN Manga m ON m.MangaID = b.MangaID
        JOIN Users u ON u.UserID  = b.StudioID
        WHERE m.MangakaID = ?
        ORDER BY m.MangaID, b.BidAmount DESC
    ");
    $stmt->execute([$userID]);

} else {
    $stmt = $pdo->prepare("
        SELECT b.BidID, b.BidAmount, b.Status, b.CreatedAt,
               m.Title, m.MangaID,
               (SELECT COUNT(*) FROM Bids b2
                WHERE b2.MangaID = b.MangaID AND b2.StudioID <> ?) AS CompetitorCount
        FROM Bids b
        JOIN Manga m ON m.MangaID = b.MangaID
        WHERE b.StudioID = ?
        ORDER BY b.CreatedAt DESC
    ");
    $stmt->execute([$userID, $userID]);
}

$bids = $stmt->fetchAll();
$error   = $error   ?? '';
$success = $success ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bids — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>
        <?php if ($role === 'Admin')   echo 'All Bids'; ?>
        <?php if ($role === 'Mangaka') echo 'Bids on Your Manga'; ?>
        <?php if ($role === 'Studio')  echo 'Your Bids'; ?>
    </h2>

    <?php if ($error):   ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert alert-success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <?php if (empty($bids)): ?>
        <p>No bids found.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Manga</th>
                    <?php if ($role !== 'Studio'): ?><th>Studio</th><?php endif; ?>
                    <th>Amount</th>
                    <th>Status</th>
                    <?php if ($role === 'Studio'): ?>
                        <th>Competition</th>
                        <th>Action</th>
                    <?php endif; ?>
                    <?php if ($role === 'Mangaka'): ?><th>Action</th><?php endif; ?>
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

                    <td>$<?= number_format($bid['BidAmount'], 2) ?></td>

                    <td>
                        <span class="badge badge-<?= strtolower($bid['Status']) ?>">
                            <?= htmlspecialchars($bid['Status']) ?>
                        </span>
                    </td>

                    <?php if ($role === 'Studio'): ?>
                        <td>
                            <?php if ($bid['CompetitorCount'] > 0): ?>
                                <?= (int)$bid['CompetitorCount'] ?> other bid<?= $bid['CompetitorCount'] > 1 ? 's' : '' ?> placed
                            <?php else: ?>
                                No other bids yet
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bid['Status'] === 'Pending'): ?>
                                <a href="<?= BASE ?>/bids/submit.php?manga_id=<?= $bid['MangaID'] ?>" class="btn btn-sm">
                                    Raise Bid
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <?php if ($role === 'Mangaka' && $bid['Status'] === 'Pending'): ?>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="bid_id" value="<?= $bid['BidID'] ?>">
                                <button name="action" value="accept" class="btn btn-sm">Accept</button>
                                <button name="action" value="reject" class="btn btn-sm btn-secondary">Reject</button>
                            </form>
                        </td>
                    <?php elseif ($role === 'Mangaka'): ?>
                        <td>—</td>
                    <?php endif; ?>

                    <td><?= date('M j, Y', strtotime($bid['CreatedAt'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($role === 'Studio'): ?>
        <a href="<?= BASE ?>/bids/submit.php" class="btn">Place New Bid</a>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>