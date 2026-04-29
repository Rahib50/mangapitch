<?php
/* ============================================================
   contracts/index.php
   — Studio : sees contracts for their accepted bids only
   — Mangaka: sees contracts on their manga only
   — Admin  : sees all contracts + can update ProductStatus
   ============================================================ */
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Studio', 'Mangaka', 'Admin');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$error  = '';
$success = '';

// ── Admin: handle status update ───────────────────────────────
if ($role === 'Admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractID    = (int)($_POST['contract_id'] ?? 0);
    $newStatus     = $_POST['product_status']    ?? '';
    $validStatuses = ['Pre-Production', 'In-Production', 'Post-Production', 'Released'];

    if ($contractID && in_array($newStatus, $validStatuses, true)) {
        $pdo->prepare("
            UPDATE Contracts SET ProductStatus = ? WHERE ContractID = ?
        ")->execute([$newStatus, $contractID]);
        $success = 'Contract status updated.';
    } else {
        $error = 'Invalid status update.';
    }
}

// ── Build query based on role ─────────────────────────────────
if ($role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT c.ContractID, c.SignedDate, c.ProductStatus,
               b.BidAmount, b.BidID,
               m.Title, m.MangaID,
               s.Name AS StudioName,
               mk.Name AS MangakaName
        FROM Contracts c
        JOIN Bids  b  ON b.BidID     = c.BidID
        JOIN Manga m  ON m.MangaID   = b.MangaID
        JOIN Users s  ON s.UserID    = b.StudioID
        JOIN Users mk ON mk.UserID   = m.MangakaID
        ORDER BY c.SignedDate DESC
    ");
    $stmt->execute();

} elseif ($role === 'Mangaka') {
    $stmt = $pdo->prepare("
        SELECT c.ContractID, c.SignedDate, c.ProductStatus,
               b.BidAmount, b.BidID,
               m.Title, m.MangaID,
               s.Name AS StudioName
        FROM Contracts c
        JOIN Bids  b ON b.BidID   = c.BidID
        JOIN Manga m ON m.MangaID = b.MangaID
        JOIN Users s ON s.UserID  = b.StudioID
        WHERE m.MangakaID = ?
        ORDER BY c.SignedDate DESC
    ");
    $stmt->execute([$userID]);

} else {
    // Studio: only their own accepted bids that have contracts
    $stmt = $pdo->prepare("
        SELECT c.ContractID, c.SignedDate, c.ProductStatus,
               b.BidAmount, b.BidID,
               m.Title, m.MangaID
        FROM Contracts c
        JOIN Bids  b ON b.BidID   = c.BidID
        JOIN Manga m ON m.MangaID = b.MangaID
        WHERE b.StudioID = ?
        ORDER BY c.SignedDate DESC
    ");
    $stmt->execute([$userID]);
}

$contracts = $stmt->fetchAll();

// Status progression order (used for next-step indicator)
$statusOrder = ['Pre-Production', 'In-Production', 'Post-Production', 'Released'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contracts — MangaPitch</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>
        <?php if ($role === 'Admin')   echo 'All Contracts'; ?>
        <?php if ($role === 'Mangaka') echo 'Your Manga Contracts'; ?>
        <?php if ($role === 'Studio')  echo 'Your Contracts'; ?>
    </h2>

    <?php if ($error):   ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert alert-success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <?php if (empty($contracts)): ?>
        <p>No contracts found.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Manga</th>
                <?php if ($role === 'Admin'):   ?><th>Mangaka</th><?php endif; ?>
                <?php if ($role !== 'Studio'):  ?><th>Studio</th><?php endif; ?>
                <?php if ($role !== 'Mangaka'): ?><th>Bid Amount</th><?php endif; ?>
                <th>Signed Date</th>
                <th>Production Status</th>
                <?php if ($role === 'Admin'): ?><th>Update Status</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contracts as $c):
            $currentIdx = array_search($c['ProductStatus'], $statusOrder);
            $nextStatus = $statusOrder[$currentIdx + 1] ?? null;
        ?>
            <tr>
                <td><?= htmlspecialchars($c['Title']) ?></td>

                <?php if ($role === 'Admin'): ?>
                    <td><?= htmlspecialchars($c['MangakaName']) ?></td>
                <?php endif; ?>

                <?php if ($role !== 'Studio'): ?>
                    <td><?= htmlspecialchars($c['StudioName']) ?></td>
                <?php endif; ?>

                <?php if ($role !== 'Mangaka'): ?>
                    <td>$<?= number_format($c['BidAmount'], 2) ?></td>
                <?php endif; ?>

                <td><?= $c['SignedDate'] ? date('M j, Y', strtotime($c['SignedDate'])) : '—' ?></td>

                <!-- Progress tracker -->
                <td>
                    <div class="status-track">
                        <?php foreach ($statusOrder as $i => $stage):
                            $done    = $i <  $currentIdx;
                            $active  = $i === $currentIdx;
                            $pending = $i >  $currentIdx;
                            $cls = $done ? 'done' : ($active ? 'active' : 'pending');
                        ?>
                            <span class="status-step <?= $cls ?>">
                                <?= htmlspecialchars($stage) ?>
                            </span>
                            <?php if ($i < count($statusOrder) - 1): ?>
                                <span class="status-arrow">›</span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </td>

                <!-- Admin-only status update -->
                <?php if ($role === 'Admin'): ?>
                <td>
                    <?php if ($nextStatus): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="contract_id"    value="<?= $c['ContractID'] ?>">
                            <input type="hidden" name="product_status" value="<?= htmlspecialchars($nextStatus) ?>">
                            <button type="submit" class="btn btn-sm">
                                → <?= htmlspecialchars($nextStatus) ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="badge badge-released">Released</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>