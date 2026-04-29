<?php
/* ============================================================
   messages/send.php
   — Handles both: new message (GET shows form) and
     quick-reply POST from thread view
   — Prevents a user messaging themselves
   ============================================================ */
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverID  = (int)($_POST['receiver_id']  ?? 0);
    $messageText = trim($_POST['message_text']  ?? '');
    $redirectTo  = $_POST['redirect_to'] ?? 'index.php';

    if (!$receiverID || !$messageText) {
        $error = 'Recipient and message are required.';
    } elseif ($receiverID === $userID) {
        $error = 'You cannot message yourself.';
    } else {
        // Verify receiver exists
        $rStmt = $pdo->prepare("SELECT UserID FROM Users WHERE UserID = ?");
        $rStmt->execute([$receiverID]);

        if (!$rStmt->fetch()) {
            $error = 'Recipient not found.';
        } else {
            $pdo->prepare("
                INSERT INTO Messages (SenderID, ReceiverID, MessageText)
                VALUES (?, ?, ?)
            ")->execute([$userID, $receiverID, $messageText]);

            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

// ── New message form: fetch eligible recipients ───────────────
// Studios can message Mangakas, Mangakas can message Studios
// Admin can message anyone
if ($role === 'Admin') {
    $rStmt = $pdo->prepare("
        SELECT UserID, Name, Role FROM Users
        WHERE UserID <> ?
        ORDER BY Role, Name
    ");
    $rStmt->execute([$userID]);
} elseif ($role === 'Studio') {
    $rStmt = $pdo->prepare("
        SELECT u.UserID, u.Name, u.Role FROM Users u
        WHERE u.Role = 'Mangaka'
        ORDER BY u.Name
    ");
    $rStmt->execute();
} else {
    // Mangaka can reply to studios who have bid on their manga
    $rStmt = $pdo->prepare("
        SELECT DISTINCT u.UserID, u.Name, u.Role FROM Users u
        JOIN Bids b ON b.StudioID = u.UserID
        JOIN Manga m ON m.MangaID = b.MangaID
        WHERE m.MangakaID = ?
        ORDER BY u.Name
    ");
    $rStmt->execute([$userID]);
}
$recipients = $rStmt->fetchAll();

$prefillID = isset($_GET['to']) ? (int)$_GET['to'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Message — MangaPitch</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>New Message</h2>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="redirect_to" value="index.php">

        <label>To
            <select name="receiver_id" required>
                <option value="">-- Select recipient --</option>
                <?php foreach ($recipients as $r): ?>
                    <option value="<?= $r['UserID'] ?>"
                        <?= $prefillID === $r['UserID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['Name']) ?>
                        (<?= htmlspecialchars($r['Role']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Message
            <textarea name="message_text" rows="5"
                      placeholder="Write your message…" required></textarea>
        </label>

        <button type="submit" class="btn">Send Message</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>