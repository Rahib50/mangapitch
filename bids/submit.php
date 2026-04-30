<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Studio');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$error   = '';
$success = '';

$prefillMangaID = isset($_GET['manga_id']) ? (int)$_GET['manga_id'] : null;

$mangaStmt = $pdo->prepare("
    SELECT m.MangaID, m.Title, u.Name AS MangakaName
    FROM Manga m
    JOIN Users u ON u.UserID = m.MangakaID
    WHERE m.MangaID NOT IN (
        SELECT MangaID FROM Bids WHERE StudioID = ? AND Status = 'Accepted'
    )
    ORDER BY m.Title
");
$mangaStmt->execute([$userID]);
$availableManga = $mangaStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mangaID   = (int)($_POST['manga_id']    ?? 0);
    $bidAmount = (float)($_POST['bid_amount'] ?? 0);

    if (!$mangaID || $bidAmount <= 0) {
        $error = 'Please select a manga and enter a valid bid amount.';
    } else {
        $existing = $pdo->prepare("
            SELECT BidID, BidAmount, Status FROM Bids
            WHERE MangaID = ? AND StudioID = ?
        ");
        $existing->execute([$mangaID, $userID]);
        $currentBid = $existing->fetch();

        if ($currentBid) {
            if ($currentBid['Status'] !== 'Pending') {
                $error = 'You can only raise a bid that is still pending.';
            } elseif ($bidAmount <= $currentBid['BidAmount']) {
                $error = sprintf(
                    'Your new bid must exceed your current bid of $%s.',
                    number_format($currentBid['BidAmount'], 2)
                );
            } else {
                $pdo->prepare("UPDATE Bids SET BidAmount = ? WHERE BidID = ?")
                    ->execute([$bidAmount, $currentBid['BidID']]);
                $success = sprintf('Bid raised to $%s successfully.', number_format($bidAmount, 2));
            }
        } else {
            $pdo->prepare("
                INSERT INTO Bids (MangaID, StudioID, BidAmount, Status) VALUES (?, ?, ?, 'Pending')
            ")->execute([$mangaID, $userID, $bidAmount]);
            $success = sprintf('Bid of $%s placed successfully.', number_format($bidAmount, 2));
        }
    }
}

$currentBidAmount = null;
if ($prefillMangaID) {
    $check = $pdo->prepare("
        SELECT BidAmount FROM Bids WHERE MangaID = ? AND StudioID = ? AND Status = 'Pending'
    ");
    $check->execute([$prefillMangaID, $userID]);
    $row = $check->fetch();
    if ($row) $currentBidAmount = $row['BidAmount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Place Bid — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2><?= $currentBidAmount ? 'Raise Your Bid' : 'Place a Bid' ?></h2>

    <?php if ($error):   ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert alert-success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <form method="POST">
        <label>Manga
            <select name="manga_id" required>
                <option value="">-- Select a title --</option>
                <?php foreach ($availableManga as $m): ?>
                    <option value="<?= $m['MangaID'] ?>"
                        <?= $prefillMangaID === $m['MangaID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['Title']) ?>
                        (by <?= htmlspecialchars($m['MangakaName']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if ($currentBidAmount): ?>
            <p class="hint">
                Your current bid: <strong>$<?= number_format($currentBidAmount, 2) ?></strong>.
                Your new bid must exceed this amount.
            </p>
        <?php endif; ?>

        <label>Bid Amount (USD)
            <input type="number" name="bid_amount" min="1" step="0.01"
                   placeholder="<?= $currentBidAmount ? 'Must exceed $' . number_format($currentBidAmount, 2) : 'Enter amount' ?>"
                   required>
        </label>

        <p class="hint">Your bid is confidential. Other studios cannot see your offer.</p>

        <button type="submit"><?= $currentBidAmount ? 'Raise Bid' : 'Submit Bid' ?></button>
        <a href="<?= BASE ?>/bids/index.php" class="btn btn-secondary">Cancel</a>
    </form>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>