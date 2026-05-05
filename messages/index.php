<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];

//Delete single message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $deleteID = (int)$_POST['delete_message_id'];
    // Only allow sender to delete
    $pdo->prepare("DELETE FROM Messages WHERE MessageID = ? AND SenderID = ?")
        ->execute([$deleteID, $userID]);
    // Redirect back to same thread
    $withID = (int)($_POST['with_id'] ?? 0);
    header('Location: ' . BASE . '/messages/index.php' . ($withID ? '?with=' . $withID : ''));
    exit;
}

if ($role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT
            LEAST(m.SenderID, m.ReceiverID)    AS UserA,
            GREATEST(m.SenderID, m.ReceiverID) AS UserB,
            MAX(m.Timestamp) AS LastTime,
            (SELECT MessageText FROM Messages
             WHERE (SenderID = LEAST(m.SenderID, m.ReceiverID)
                 OR ReceiverID = LEAST(m.SenderID, m.ReceiverID))
               AND (SenderID = GREATEST(m.SenderID, m.ReceiverID)
                 OR ReceiverID = GREATEST(m.SenderID, m.ReceiverID))
             ORDER BY Timestamp DESC LIMIT 1) AS Preview,
            ua.Name AS NameA,
            ub.Name AS NameB
        FROM Messages m
        JOIN Users ua ON ua.UserID = LEAST(m.SenderID, m.ReceiverID)
        JOIN Users ub ON ub.UserID = GREATEST(m.SenderID, m.ReceiverID)
        GROUP BY UserA, UserB
        ORDER BY LastTime DESC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT
            LEAST(m.SenderID, m.ReceiverID)    AS UserA,
            GREATEST(m.SenderID, m.ReceiverID) AS UserB,
            MAX(m.Timestamp) AS LastTime,
            (SELECT MessageText FROM Messages
             WHERE (SenderID = ? OR ReceiverID = ?)
               AND (SenderID = LEAST(m.SenderID, m.ReceiverID)
                 OR ReceiverID = LEAST(m.SenderID, m.ReceiverID))
               AND (SenderID = GREATEST(m.SenderID, m.ReceiverID)
                 OR ReceiverID = GREATEST(m.SenderID, m.ReceiverID))
             ORDER BY Timestamp DESC LIMIT 1) AS Preview,
            IF(LEAST(m.SenderID, m.ReceiverID) = ?, ub.Name, ua.Name) AS OtherName,
            IF(LEAST(m.SenderID, m.ReceiverID) = ?, ub.UserID, ua.UserID) AS OtherID
        FROM Messages m
        JOIN Users ua ON ua.UserID = LEAST(m.SenderID, m.ReceiverID)
        JOIN Users ub ON ub.UserID = GREATEST(m.SenderID, m.ReceiverID)
        WHERE m.SenderID = ? OR m.ReceiverID = ?
        GROUP BY UserA, UserB
        ORDER BY LastTime DESC
    ");
    $stmt->execute([$userID, $userID, $userID, $userID, $userID, $userID]);
}

$threads = $stmt->fetchAll();

$threadMessages = [];
$otherUser      = null;
$withID         = isset($_GET['with']) ? (int)$_GET['with'] : null;

if ($withID) {
    $uStmt = $pdo->prepare("SELECT UserID, Name, Role FROM Users WHERE UserID = ?");
    $uStmt->execute([$withID]);
    $otherUser = $uStmt->fetch();

    if ($otherUser) {
        $mStmt = $pdo->prepare("
            SELECT m.MessageID, m.MessageText, m.Timestamp,
                   m.SenderID, u.Name AS SenderName
            FROM Messages m
            JOIN Users u ON u.UserID = m.SenderID
            WHERE (m.SenderID = ? AND m.ReceiverID = ?)
               OR (m.SenderID = ? AND m.ReceiverID = ?)
            ORDER BY m.Timestamp ASC
        ");
        $mStmt->execute([$userID, $withID, $withID, $userID]);
        $threadMessages = $mStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Messages</h2>

    <div class="message-layout">
        <aside class="inbox-list">
            <a href="<?= BASE ?>/messages/send.php" class="btn btn-sm" style="margin-bottom:12px">+ New Message</a>

            <?php if (empty($threads)): ?>
                <p class="muted">No conversations yet.</p>
            <?php endif; ?>

            <?php foreach ($threads as $t):
                if ($role === 'Admin') {
                    $label = htmlspecialchars($t['NameA'] . ' & ' . $t['NameB']);
                    $link  = "?with=" . $t['UserA'];
                } else {
                    $label = htmlspecialchars($t['OtherName']);
                    $link  = "?with=" . $t['OtherID'];
                }
                $active = ($withID && (
                    ($role !== 'Admin' && $withID == $t['OtherID']) ||
                    ($role === 'Admin' && $withID == $t['UserA'])
                )) ? 'active' : '';
            ?>
                <a href="<?= $link ?>" class="inbox-item <?= $active ?>">
                    <span class="inbox-name"><?= $label ?></span>
                    <span class="inbox-preview">
                        <?= htmlspecialchars(mb_substr($t['Preview'], 0, 60)) ?>…
                    </span>
                    <span class="inbox-time"><?= date('M j', strtotime($t['LastTime'])) ?></span>
                </a>
            <?php endforeach; ?>
        </aside>

        <section class="thread-panel">
            <?php if (!$withID || !$otherUser): ?>
                <p class="muted thread-placeholder">Select a conversation or start a new one.</p>
            <?php else: ?>
                <h3>
                    <?= htmlspecialchars($otherUser['Name']) ?>
                    <span class="role-badge"><?= htmlspecialchars($otherUser['Role']) ?></span>
                </h3>

                <div class="thread-messages" id="thread">
                    <?php foreach ($threadMessages as $msg):
                        $mine = ($msg['SenderID'] == $userID);
                    ?>
                        <div class="bubble-wrap <?= $mine ? 'mine' : 'theirs' ?>">
                            <div class="bubble">
                                <?= nl2br(htmlspecialchars($msg['MessageText'])) ?>
                            </div>
                            <span class="bubble-time">
                                <?= date('M j, g:i a', strtotime($msg['Timestamp'])) ?>
                            </span>
                            <?php if ($mine): ?>
                                <form method="POST" style="margin:0">
                                    <input type="hidden" name="delete_message_id" value="<?= $msg['MessageID'] ?>">
                                    <input type="hidden" name="with_id" value="<?= $withID ?>">
                                    <button type="submit" class="btn-delete"
                                            onclick="return confirm('Delete this message?')">
                                        🗑
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="<?= BASE ?>/messages/send.php" class="reply-form">
                    <input type="hidden" name="receiver_id" value="<?= $withID ?>">
                    <input type="hidden" name="redirect_to" value="<?= BASE ?>/messages/index.php?with=<?= $withID ?>">
                    <textarea name="message_text" rows="3" placeholder="Write a message…" required></textarea>
                    <button type="submit" class="btn">Send</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
    const thread = document.getElementById('thread');
    if (thread) thread.scrollTop = thread.scrollHeight;
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>