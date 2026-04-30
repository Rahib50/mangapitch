<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE . '/dashboard/' . strtolower($_SESSION['role']) . '.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Both fields are required.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT UserID, Name, Password, Role FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['name']    = $user['Name'];
            $_SESSION['role']    = $user['Role'];

            $redirect = match($user['Role']) {
                'Mangaka' => BASE . '/dashboard/mangaka.php',
                'Studio'  => BASE . '/dashboard/studio.php',
                'Admin'   => BASE . '/dashboard/admin.php',
                default   => BASE . '/index.php',
            };
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — MangaPitch</title>
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <h2>Login</h2>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email<input type="email" name="email" required></label>
        <label>Password<input type="password" name="password" required></label>
        <button type="submit" class="btn">Login</button>
    </form>
    <p>No account? <a href="<?= BASE ?>/auth/register.php">Register</a></p>
</div>
</body>
</html>