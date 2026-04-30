<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Mangaka');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$error   = '';
$success = '';

$genres = $pdo->query("SELECT GenreID, GenreName FROM Genre ORDER BY GenreName")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']        ?? '');
    $synopsis    = trim($_POST['synopsis']     ?? '');
    $publishDate = trim($_POST['publish_date'] ?? '');
    $genreIDs    = $_POST['genres']            ?? [];

    if (!$title) {
        $error = 'Title is required.';
    } elseif (!$publishDate) {
        $error = 'Publish date is required.';
    } elseif (empty($genreIDs)) {
        $error = 'Select at least one genre.';
    } else {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("
                INSERT INTO Manga (MangakaID, Title, Synopsis, PublishDate)
                VALUES (?, ?, ?, ?)
            ")->execute([$userID, $title, $synopsis ?: null, $publishDate]);

            $mangaID = $pdo->lastInsertId();

            $gStmt = $pdo->prepare("INSERT INTO Manga_Genre_Map (MangaID, GenreID) VALUES (?, ?)");
            foreach ($genreIDs as $gid) {
                $gStmt->execute([$mangaID, (int)$gid]);
            }

            $pdo->prepare("
                INSERT INTO Analytics (MangaID, TotalViews, VolumesSold) VALUES (?, 0, 0)
            ")->execute([$mangaID]);

            $pdo->commit();
            $success = 'Manga "' . htmlspecialchars($title) . '" uploaded successfully.';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Upload failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Manga — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Upload Manga</h2>

    <?php if ($error):   ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert alert-success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <form method="POST">
        <label>Title
            <input type="text" name="title" required maxlength="200">
        </label>

        <label>Synopsis
            <textarea name="synopsis" rows="5"
                      placeholder="Brief description of your manga…"></textarea>
        </label>

        <label>Publish Date
            <input type="date" name="publish_date" required>
        </label>

        <fieldset>
            <legend>Genres (select all that apply)</legend>
            <div class="genre-grid">
                <?php foreach ($genres as $g): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="genres[]" value="<?= $g['GenreID'] ?>">
                        <?= htmlspecialchars($g['GenreName']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="submit" class="btn">Upload Manga</button>
        <a href="<?= BASE ?>/dashboard/mangaka.php" class="btn btn-secondary">Cancel</a>
    </form>
</main>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>