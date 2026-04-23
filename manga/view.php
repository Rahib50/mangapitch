<?php
declare(strict_types=1);
$pageTitle = 'View Manga';
require_once __DIR__ . '/../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<div class="card">
  <h2 style="margin-top:0">Manga #<?= htmlspecialchars((string)$id) ?></h2>
  <p>Placeholder view page. Next step: query by `MangaID` from DB.</p>
  <p><a href="/bids/submit.php?manga_id=<?= urlencode((string)$id) ?>">Submit a bid</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
