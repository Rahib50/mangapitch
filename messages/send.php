<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
$pageTitle = 'Send Message';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Placeholder: insert into `Messages` (SenderID from session)
    header('Location: /messages/index.php');
    exit;
}
?>

<div class="card">
  <h2 style="margin-top:0">Send Message</h2>
  <form method="post">
    <label>
      Receiver user id
      <input name="receiver_id" type="number" min="1" required />
    </label>
    <div style="height: 12px"></div>
    <label>
      Message
      <textarea name="message" rows="4" required></textarea>
    </label>
    <div style="height: 12px"></div>
    <button type="submit">Send</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
