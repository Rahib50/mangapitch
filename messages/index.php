<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
requireLogin();
$pageTitle = 'Messages';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2 style="margin-top:0">Messages</h2>
  <p>Placeholder inbox. Next step: query `Messages` table for the logged-in user.</p>
  <p><a href="/messages/send.php">Send a message</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
