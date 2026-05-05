<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireRole('Mangaka');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo     = getPDO();
$userID  = $_SESSION['user_id'];
$mangaID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error   = '';
$success = '';

if (!$mangaID) {
    header('Location: ' . BASE . '/dashboard/mangaka.php');
    exit;
}


$manga = $pdo->prepare("SELECT MangaID, Title, CoverImage, PanelImages FROM Manga WHERE MangaID = ? AND MangakaID = ?");
$manga->execute([$mangaID, $userID]);
$manga = $manga->fetch();

if (!$manga) {
    http_response_code(403);
    die('<h2>Access Denied</h2>');
}

$existingPanels = $manga['PanelImages'] ? json_decode($manga['PanelImages'], true) : [];
$existingCover  = $manga['CoverImage'] ?? null;
$allowedTypes   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxFileSize    = 5 * 1024 * 1024;

function uploadPanel(array $file, string $destDir): string|false {
    global $allowedTypes, $maxFileSize;
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxFileSize)     return false;
    if (!in_array($file['type'], $allowedTypes, true)) return false;
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], $destDir . $filename)) return false;
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_cover']) && !empty($_FILES['cover_image']['name'])) {
        $newCover = uploadPanel($_FILES['cover_image'], __DIR__ . '/../uploads/covers/');
        if ($newCover) {
            if ($existingCover) {
                $oldPath = __DIR__ . '/../uploads/covers/' . $existingCover;
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $pdo->prepare("UPDATE Manga SET CoverImage = ? WHERE MangaID = ?")
                ->execute([$newCover, $mangaID]);
            $existingCover = $newCover;
            $success = 'Cover image updated.';
        } else {
            $error = 'Cover upload failed. Use JPG, PNG, WEBP or GIF under 5MB.';
        }

    } elseif (isset($_POST['remove_cover'])) {
        if ($existingCover) {
            $oldPath = __DIR__ . '/../uploads/covers/' . $existingCover;
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $pdo->prepare("UPDATE Manga SET CoverImage = NULL WHERE MangaID = ?")
            ->execute([$mangaID]);
        $existingCover = null;
        $success = 'Cover image removed.';

    } elseif (isset($_POST['delete_panel'])) {
        $delFile = $_POST['delete_panel'];
        $existingPanels = array_values(array_filter($existingPanels, fn($p) => $p !== $delFile));
        $filePath = __DIR__ . '/../uploads/panels/' . $delFile;
        if (file_exists($filePath)) unlink($filePath);
        $pdo->prepare("UPDATE Manga SET PanelImages = ? WHERE MangaID = ?")
            ->execute([!empty($existingPanels) ? json_encode($existingPanels) : null, $mangaID]);
        $success = 'Panel removed.';

    } elseif (!empty($_FILES['new_panels']['name'][0])) {
        $slotsLeft = 5 - count($existingPanels);
        if ($slotsLeft <= 0) {
            $error = 'You already have 5 panels. Remove one before adding more.';
        } else {
            $panels   = $_FILES['new_panels'];
            $count    = min(count($panels['name']), $slotsLeft);
            $newFiles = [];
            for ($i = 0; $i < $count; $i++) {
                if ($panels['error'][$i] !== UPLOAD_ERR_OK) continue;
                $singleFile = [
                    'name'     => $panels['name'][$i],
                    'type'     => $panels['type'][$i],
                    'tmp_name' => $panels['tmp_name'][$i],
                    'error'    => $panels['error'][$i],
                    'size'     => $panels['size'][$i],
                ];
                $filename = uploadPanel($singleFile, __DIR__ . '/../uploads/panels/');
                if ($filename) {
                    $newFiles[] = $filename;
                } else {
                    $error = 'One or more files failed. Use JPG, PNG, WEBP or GIF under 5MB.';
                    break;
                }
            }
            if (empty($error) && !empty($newFiles)) {
                $allPanels = array_merge($existingPanels, $newFiles);
                $pdo->prepare("UPDATE Manga SET PanelImages = ? WHERE MangaID = ?")
                    ->execute([json_encode($allPanels), $mangaID]);
                $existingPanels = $allPanels;
                $success = count($newFiles) . ' panel(s) added successfully.';
            }
        }
    }
}

$slotsLeft = 5 - count($existingPanels);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Panels — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
    <style>
        .panel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .panel-item { position: relative; border: 2px solid var(--charcoal); border-radius: var(--radius); overflow: hidden; }
        .panel-item img { width: 100%; height: 160px; object-fit: cover; display: block; }
        .panel-item form { position: absolute; top: .4rem; right: .4rem; }
        .panel-delete { background: var(--accent); color: #fff; border: none; border-radius: 2px; padding: .2rem .5rem; cursor: pointer; font-size: .75rem; font-weight: 700; }
        .slots-left { font-size: .85rem; color: var(--text-muted); margin-bottom: 1rem; font-weight: 600; }
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Manage Panels — <?= htmlspecialchars($manga['Title']) ?></h2>

    <?php if ($error):   ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert alert-success"><?= htmlspecialchars($success) ?></p><?php endif; ?>


    <h3>Cover Image</h3>
    <div style="display:flex; gap:1.5rem; align-items:flex-start; margin-bottom:2rem; flex-wrap:wrap;">
        <?php if ($existingCover): ?>
            <div class="manga-cover" style="margin:0">
                <img src="<?= BASE ?>/uploads/covers/<?= htmlspecialchars($existingCover) ?>"
                     alt="Current Cover">
            </div>
            <div style="display:flex; flex-direction:column; gap:.8rem;">
                <p class="hint">Current cover image. Upload a new one to replace it.</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_cover" value="1">
                    <label>Replace Cover (max 5MB)
                        <label class="file-label" for="cover_replace">Choose File</label>
                        <span class="file-name" id="cover_replace_name">No file chosen</span>
                        <input type="file" name="cover_image" id="cover_replace" accept="image/*" required>
                    </label>
                    <button type="submit" class="btn btn-sm" style="margin-top:.5rem">Replace Cover</button>
                </form>
                <form method="POST" onsubmit="return confirm('Remove cover image?')">
                    <input type="hidden" name="remove_cover" value="1">
                    <button type="submit" class="btn btn-sm btn-secondary">Remove Cover</button>
                </form>
            </div>
        <?php else: ?>
            <div>
                <p class="muted" style="margin-bottom:.8rem">No cover image uploaded.</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_cover" value="1">
                    <label>Upload Cover (max 5MB)
                        <label class="file-label" for="cover_upload">Choose File</label>
                        <span class="file-name" id="cover_upload_name">No file chosen</span>
                        <input type="file" name="cover_image" id="cover_upload" accept="image/*" required>
                    </label>
                    <button type="submit" class="btn btn-sm" style="margin-top:.5rem">Upload Cover</button>
                </form>
            </div>
        <?php endif; ?>
    </div>


    <h3>Current Panels (<?= count($existingPanels) ?>/5)</h3>
    <?php if (empty($existingPanels)): ?>
        <p class="muted">No panels uploaded yet.</p>
    <?php else: ?>
        <div class="panel-grid">
            <?php foreach ($existingPanels as $panel): ?>
                <div class="panel-item">
                    <img src="<?= BASE ?>/uploads/panels/<?= htmlspecialchars($panel) ?>"
                         alt="Panel">
                    <form method="POST">
                        <input type="hidden" name="delete_panel" value="<?= htmlspecialchars($panel) ?>">
                        <button type="submit" class="panel-delete"
                                onclick="return confirm('Remove this panel?')">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

  
    <?php if ($slotsLeft > 0): ?>
        <h3>Add Panels</h3>
        <p class="slots-left"><?= $slotsLeft ?> slot(s) remaining</p>
        <form method="POST" enctype="multipart/form-data">
            <label>Select Panel Images (up to <?= $slotsLeft ?>, max 5MB each)
                <label class="file-label" for="new_panels">Choose Files</label>
                <span class="file-name" id="panels_name">No file chosen</span>
                <input type="file" name="new_panels[]" id="new_panels" accept="image/*" multiple>
            </label>
            <p class="hint">Hold Ctrl (Windows) or Cmd (Mac) to select multiple images.</p>
            <button type="submit" class="btn">Upload Panels</button>
        </form>
    <?php else: ?>
        <p class="alert alert-error">Maximum of 5 panels reached. Remove a panel to add a new one.</p>
    <?php endif; ?>

    <div style="margin-top:1.5rem">
        <a href="<?= BASE ?>/manga/view.php?id=<?= $mangaID ?>" class="btn btn-secondary">Back to Manga</a>
    </div>
</main>

<script>
    const inputs = [
        { id: 'cover_replace', nameId: 'cover_replace_name' },
        { id: 'cover_upload',  nameId: 'cover_upload_name'  },
        { id: 'new_panels',    nameId: 'panels_name'        },
    ];
    inputs.forEach(({ id, nameId }) => {
        const el = document.getElementById(id);
        const nm = document.getElementById(nameId);
        if (el && nm) {
            el.addEventListener('change', function() {
                nm.textContent = this.files.length > 1
                    ? this.files.length + ' files selected'
                    : this.files[0] ? this.files[0].name : 'No file chosen';
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>