<?php
/* ============================================================
   messages/index.php
   — Shows inbox: all conversations the logged-in user is part of
   — Clicking a conversation opens the thread view
   — Admin can see all conversations
   ============================================================ */
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];

// ── Fetch conversation threads ────────────────────────────────
// Group by the other participant, show latest message preview
if ($role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT
            LEAST(m.SenderID, m.ReceiverID)    AS UserA,
            GREATEST(m.SenderID, m.ReceiverID) AS UserB,
            MAX(m.Timestamp)  AS LastTime,
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

// ── Thread view: load full conversation with one user ─────────
$threadMessages = [];
$otherUser      = null;
$withID         = isset($_GET['with']) ? (int)$_GET['with'] : null;

if ($withID) {
    // Verify the other user exists
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
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Messages</h2>

    <div class="message-layout">

        <!-- ── Inbox sidebar ── -->
        <aside class="inbox-list">
            <a href="send.php" class="btn btn-sm" style="margin-bottom:12px">+ New Message</a>

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
                    <span class="inbox-time">
                        <?= date('M j', strtotime($t['LastTime'])) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- ── Thread panel ── -->
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
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick reply form -->
                <form method="POST" action="send.php" class="reply-form">
                    <input type="hidden" name="receiver_id" value="<?= $withID ?>">
                    <input type="hidden" name="redirect_to" value="index.php?with=<?= $withID ?>">
                    <textarea name="message_text" rows="3"
                              placeholder="Write a message…" required></textarea>
                    <button type="submit" class="btn">Send</button>
                </form>
            <?php endif; ?>
        </section>

    </div>
</main>

<script>
    // Auto-scroll thread to bottom
    const thread = document.getElementById('thread');
    if (thread) thread.scrollTop = thread.scrollHeight;
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>


