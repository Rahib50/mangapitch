<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo    = getPDO();
$userID = $_SESSION['user_id'];
$role   = $_SESSION['role'];

$query   = trim($_GET['q']      ?? '');
$genreID = (int)($_GET['genre'] ?? 0);
$sortBy  = $_GET['sort']        ?? 'views';
$validSort = ['views' => 'a.TotalViews', 'volumes' => 'a.VolumesSold', 'date' => 'm.PublishDate'];
$orderCol  = $validSort[$sortBy] ?? 'a.TotalViews';

$genres = $pdo->query("SELECT GenreID, GenreName FROM Genre ORDER BY GenreName")->fetchAll();

$params = [];
$sql = "
    SELECT DISTINCT m.MangaID, m.Title, m.Synopsis, m.PublishDate,
           u.Name AS MangakaName,
           a.TotalViews, a.VolumesSold,
           GROUP_CONCAT(g.GenreName ORDER BY g.GenreName SEPARATOR ', ') AS Genres
    FROM Manga m
    JOIN Users u ON u.UserID = m.MangakaID
    LEFT JOIN Analytics       a   ON a.MangaID  = m.MangaID
    LEFT JOIN Manga_Genre_Map mgm ON mgm.MangaID = m.MangaID
    LEFT JOIN Genre           g   ON g.GenreID   = mgm.GenreID
    WHERE 1=1
";

if ($query) {
    $sql .= " AND (m.Title LIKE ? OR m.Synopsis LIKE ? OR u.Name LIKE ?)";
    $like = "%$query%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($genreID) {
    $sql .= " AND m.MangaID IN (SELECT MangaID FROM Manga_Genre_Map WHERE GenreID = ?)";
    $params[] = $genreID;
}

$sql .= " GROUP BY m.MangaID ORDER BY $orderCol DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

//AJAX: return only results HTML and exit
if (isset($_GET['ajax'])) {
    $count = count($results);
    $countText = $count . ' result' . ($count !== 1 ? 's' : '') . ' found';

    if (empty($results)) {
        echo json_encode([
            'count' => $countText,
            'html'  => '<p class="muted">No manga found matching your search.</p>'
        ]);
    } else {
        $html = '<div class="manga-grid">';
        foreach ($results as $m) {
            $bid = $role === 'Studio'
                ? '<a href="' . BASE . '/bids/submit.php?manga_id=' . $m['MangaID'] . '" class="btn btn-sm">Bid</a>'
                : '';
            $html .= '<div class="manga-card">
                <div class="manga-card-body">
                    <h3><a href="' . BASE . '/manga/view.php?id=' . $m['MangaID'] . '">'
                        . htmlspecialchars($m['Title']) . '</a></h3>
                    <p class="manga-author">by ' . htmlspecialchars($m['MangakaName']) . '</p>
                    <p class="manga-genres">' . htmlspecialchars($m['Genres'] ?? '—') . '</p>
                    <p class="manga-synopsis-short">'
                        . htmlspecialchars(mb_substr($m['Synopsis'] ?? '', 0, 120)) . '…</p>
                </div>
                <div class="manga-card-footer">
                    <span>👁 ' . number_format($m['TotalViews'] ?? 0) . '</span>
                    <span>📦 ' . number_format($m['VolumesSold'] ?? 0) . '</span>
                    ' . $bid . '
                </div>
            </div>';
        }
        $html .= '</div>';
        echo json_encode(['count' => $countText, 'html' => $html]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>
<main class="container">
    <h2>Search Manga</h2>

    <form method="GET" class="search-bar">
        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>"
               placeholder="Search by title, synopsis, or mangaka…">

        <select name="genre">
            <option value="">All Genres</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= $g['GenreID'] ?>"
                    <?= $genreID == $g['GenreID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['GenreName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort">
            <option value="views"   <?= $sortBy === 'views'   ? 'selected' : '' ?>>Most Viewed</option>
            <option value="volumes" <?= $sortBy === 'volumes' ? 'selected' : '' ?>>Most Volumes Sold</option>
            <option value="date"    <?= $sortBy === 'date'    ? 'selected' : '' ?>>Latest</option>
        </select>

        <button type="submit" class="btn">Search</button>
    </form>

    <p class="result-count" id="result-count">
        <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> found
    </p>

    <div id="search-results">
        <?php if (empty($results)): ?>
            <p class="muted">No manga found matching your search.</p>
        <?php else: ?>
            <div class="manga-grid">
                <?php foreach ($results as $m): ?>
                    <div class="manga-card">
                        <div class="manga-card-body">
                            <h3>
                                <a href="<?= BASE ?>/manga/view.php?id=<?= $m['MangaID'] ?>">
                                    <?= htmlspecialchars($m['Title']) ?>
                                </a>
                            </h3>
                            <p class="manga-author">by <?= htmlspecialchars($m['MangakaName']) ?></p>
                            <p class="manga-genres"><?= htmlspecialchars($m['Genres'] ?? '—') ?></p>
                            <p class="manga-synopsis-short">
                                <?= htmlspecialchars(mb_substr($m['Synopsis'] ?? '', 0, 120)) ?>…
                            </p>
                        </div>
                        <div class="manga-card-footer">
                            <span>👁 <?= number_format($m['TotalViews'] ?? 0) ?></span>
                            <span>📦 <?= number_format($m['VolumesSold'] ?? 0) ?></span>
                            <?php if ($role === 'Studio'): ?>
                                <a href="<?= BASE ?>/bids/submit.php?manga_id=<?= $m['MangaID'] ?>"
                                   class="btn btn-sm">Bid</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
<script>
    const searchInput = document.querySelector('input[name="q"]');
    const genreSelect = document.querySelector('select[name="genre"]');
    const sortSelect  = document.querySelector('select[name="sort"]');
    const resultsDiv  = document.getElementById('search-results');

    let debounceTimer;

    function fetchResults() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const q     = searchInput.value;
            const genre = genreSelect.value;
            const sort  = sortSelect.value;
            const url   = `?q=${encodeURIComponent(q)}&genre=${genre}&sort=${sort}&ajax=1`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = data.html;
                    document.getElementById('result-count').textContent = data.count;
                    searchInput.focus();
                });
        }, 300);
    }

    searchInput.addEventListener('input', fetchResults);
    genreSelect.addEventListener('change', fetchResults);
    sortSelect.addEventListener('change', fetchResults);
</script>
</body>
</html>