<?php
/* ============================================================
   index.php  — Landing page
   ============================================================ */
require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header('Location: ' . BASE . '/dashboard/' . strtolower($role) . '.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MangaPitch — Connect Manga Creators with Animation Studios</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body class="landing">
<?php require_once 'includes/header.php'; ?>
<main>
    <section class="hero">
        <div class="hero-content">
            <h1>Where Manga Meets Animation</h1>
            <p>MangaPitch connects manga creators with top animation studios.
               Pitch your work, place bids, and bring stories to life.</p>
            <div class="hero-actions">
                <a href="<?= BASE ?>/auth/register.php" class="btn btn-lg">Get Started</a>
                <a href="<?= BASE ?>/auth/login.php"    class="btn btn-lg btn-secondary">Login</a>
            </div>
        </div>
    </section>

    <section class="features container">
        <div class="feature-card">
            <h3>📖 Manga Portfolio</h3>
            <p>Upload your titles, synopses, and publish dates to showcase your work.</p>
        </div>
        <div class="feature-card">
            <h3>💰 Blind Bidding</h3>
            <p>Studios compete with confidential bids — creators see all offers, studios only see their own.</p>
        </div>
        <div class="feature-card">
            <h3>📊 Analytics</h3>
            <p>Track total views and volumes sold to demonstrate your manga's market value.</p>
        </div>
        <div class="feature-card">
            <h3>📝 Contracts</h3>
            <p>Monitor your production pipeline from Pre-Production all the way to Release.</p>
        </div>
        <div class="feature-card">
            <h3>💬 Direct Messaging</h3>
            <p>Negotiate privately with studios or creators through the built-in messaging system.</p>
        </div>
        <div class="feature-card">
            <h3>🔍 Search & Filter</h3>
            <p>Studios can find manga by genre, popularity, and publication date instantly.</p>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>