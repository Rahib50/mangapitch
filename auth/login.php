<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: /dashboard/' . strtolower($_SESSION['role']) . '.php');
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

            // Role-based redirect
            $redirect = match($user['Role']) {
                'Mangaka' => '/dashboard/mangaka.php',
                'Studio'  => '/dashboard/studio.php',
                'Admin'   => '/dashboard/admin.php',
                default   => '/index.php',
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
    <link rel="stylesheet" href="/assets/css/style.css">
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
        <button type="submit">Login</button>
    </form>
    <p>No account? <a href="register.php">Register</a></p>
</div>
</body>
</html>
