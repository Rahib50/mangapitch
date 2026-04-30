<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Manga';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <h2 style="margin-top:0">Manga</h2>
  <p>This is a placeholder listing page. Next step: fetch from DB (`Manga` table).</p>
  <p><a href="<?= BASE ?>/manga/upload.php">Upload</a></p>
  <p><a href="<?= BASE ?>/manga/view.php?id=1">View sample manga</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>