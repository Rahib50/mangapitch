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

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxFileSize  = 5 * 1024 * 1024; // 5MB per file

function uploadFile(array $file, string $destDir): string|false {
    global $allowedTypes, $maxFileSize;
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxFileSize)     return false;
    if (!in_array($file['type'], $allowedTypes, true)) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '.' . strtolower($ext);
    $destPath = $destDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) return false;
    return $filename;
}

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
        $coverFilename = null;
        if (!empty($_FILES['cover_image']['name'])) {
            $coverFilename = uploadFile(
                $_FILES['cover_image'],
                __DIR__ . '/../uploads/covers/'
            );
            if ($coverFilename === false) {
                $error = 'Cover image upload failed. Use JPG, PNG, WEBP or GIF under 5MB.';
            }
        }

        $panelFilenames = [];
        if (empty($error) && !empty($_FILES['panel_images']['name'][0])) {
            $panels = $_FILES['panel_images'];
            $count  = min(count($panels['name']), 5);

            for ($i = 0; $i < $count; $i++) {
                if ($panels['error'][$i] !== UPLOAD_ERR_OK) continue;

                // handles for multiple file uploads
                $singleFile = [
                    'name'     => $panels['name'][$i],
                    'type'     => $panels['type'][$i],
                    'tmp_name' => $panels['tmp_name'][$i],
                    'error'    => $panels['error'][$i],
                    'size'     => $panels['size'][$i],
                ];

                $filename = uploadFile($singleFile, __DIR__ . '/../uploads/panels/');
                if ($filename) {
                    $panelFilenames[] = $filename;
                } else {
                    $error = 'One or more panel images failed. Use JPG, PNG, WEBP or GIF under 5MB each.';
                    break;
                }
            }
        }

        if (empty($error)) {
            try {
                $pdo->beginTransaction();

                $pdo->prepare("
                    INSERT INTO Manga (MangakaID, Title, Synopsis, PublishDate, CoverImage, PanelImages)
                    VALUES (?, ?, ?, ?, ?, ?)
                ")->execute([
                    $userID,
                    $title,
                    $synopsis ?: null,
                    $publishDate,
                    $coverFilename,
                    !empty($panelFilenames) ? json_encode($panelFilenames) : null
                ]);

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

            } 
            // ACID concept: Atomicity, Consistency, Isolation, Durability
            catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Upload failed. Please try again.';
            }
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

    <form method="POST" enctype="multipart/form-data">
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

        <label>Cover Image <span class="hint" style="display:inline">(optional, max 5MB)</span>
            <label class="file-label" for="cover_image">Choose Cover</label>
            <span class="file-name" id="cover_name">No file chosen</span>
            <input type="file" name="cover_image" id="cover_image" accept="image/*">
        </label>

        <label>Panel Images <span class="hint" style="display:inline">(optional, up to 5 panels, max 5MB each)</span>
            <label class="file-label" for="panel_images">Choose Panels</label>
            <span class="file-name" id="panel_name">No file chosen</span>
            <input type="file" name="panel_images[]" id="panel_images" accept="image/*" multiple>
        </label>
        <p class="hint">Hold Ctrl (Windows) or Cmd (Mac) to select multiple panel images at once.</p>

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
<script>
    document.getElementById('cover_image').addEventListener('change', function() {
        document.getElementById('cover_name').textContent =
            this.files[0] ? this.files[0].name : 'No file chosen';
    });
    document.getElementById('panel_images').addEventListener('change', function() {
        document.getElementById('panel_name').textContent =
            this.files.length > 0 ? this.files.length + ' file(s) selected' : 'No file chosen';
    });
</script>
</body>
</html>