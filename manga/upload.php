<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_guard.php';
requireLogin();
$pageTitle = 'Upload Manga';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Placeholder: insert into DB + handle file uploads.
    header('Location: /manga/index.php');
    exit;
}
?>

<div class="card">
  <h2 style="margin-top:0">Upload Manga</h2>
  <form method="post">
    <label>
      Title
      <input name="title" required />
    </label>
    <div style="height: 12px"></div>
    <label>
      Synopsis
      <textarea name="synopsis" rows="4"></textarea>
    </label>
    <div style="height: 12px"></div>
    <button type="submit">Save</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
