<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
$pageTitle = 'Submit Bid';
require_once __DIR__ . '/../includes/header.php';

$mangaId = isset($_GET['manga_id']) ? (int)$_GET['manga_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Placeholder: insert into `Bids`
    header('Location: /bids/index.php');
    exit;
}
?>

<div class="card">
  <h2 style="margin-top:0">Submit Bid</h2>
  <p>Manga ID: <b><?= htmlspecialchars((string)$mangaId) ?></b></p>
  <form method="post">
    <label>
      Bid amount
      <input name="amount" type="number" min="0" step="0.01" required />
    </label>
    <div style="height: 12px"></div>
    <button type="submit">Submit</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
