<?php
declare(strict_types=1);
$pageTitle = 'Search';
require_once __DIR__ . '/../includes/header.php';

$q = trim((string)($_GET['q'] ?? ''));
?>

<div class="card">
  <h2 style="margin-top:0">Search Manga</h2>
  <form method="get">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by title..." />
    <button type="submit">Search</button>
  </form>
  <?php if ($q !== ''): ?>
    <div style="height:12px"></div>
    <p>Placeholder results for: <b><?= htmlspecialchars($q) ?></b></p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
