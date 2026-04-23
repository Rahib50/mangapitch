<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $role     =      $_POST['role']     ?? '';

    $validRoles = ['Mangaka', 'Studio'];

    if (!$name || !$email || !$password || !in_array($role, $validRoles, true)) {
        $error = 'All fields are required and role must be Mangaka or Studio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $pdo  = getPDO();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $pdo->beginTransaction();

            // Insert into Users
            $stmt = $pdo->prepare(
                "INSERT INTO Users (Name, Email, Password, Role) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $hash, $role]);
            $userId = $pdo->lastInsertId();

            // Insert into subtype table
            if ($role === 'Mangaka') {
                $portfolio = trim($_POST['portfolio_link'] ?? '');
                $pdo->prepare("INSERT INTO Mangaka (UserID, PortfolioLink) VALUES (?, ?)")
                    ->execute([$userId, $portfolio ?: null]);
            } elseif ($role === 'Studio') {
                $regNum = trim($_POST['registration_number'] ?? '');
                $pdo->prepare("INSERT INTO Studio (UserID, RegistrationNumber) VALUES (?, ?)")
                    ->execute([$userId, $regNum ?: null]);
            }

            $pdo->commit();

            // Auto-login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['name']    = $name;
            $_SESSION['role']    = $role;

            header('Location: /dashboard/' . strtolower($role) . '.php');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = str_contains($e->getMessage(), 'Duplicate')
                ? 'Email already registered.'
                : 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register — MangaPitch</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <h2>Create Account</h2>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Name<input type="text" name="name" required></label>
        <label>Email<input type="email" name="email" required></label>
        <label>Password<input type="password" name="password" required minlength="8"></label>

        <label>Role
            <select name="role" id="roleSelect" required>
                <option value="">-- Select --</option>
                <option value="Mangaka">Mangaka</option>
                <option value="Studio">Studio</option>
            </select>
        </label>

        <!-- Mangaka-only field -->
        <div id="mangakaFields" style="display:none">
            <label>Portfolio Link<input type="url" name="portfolio_link"></label>
        </div>

        <!-- Studio-only field -->
        <div id="studioFields" style="display:none">
            <label>Registration Number<input type="text" name="registration_number"></label>
        </div>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
    const roleSelect = document.getElementById('roleSelect');
    roleSelect.addEventListener('change', function () {
        document.getElementById('mangakaFields').style.display =
            this.value === 'Mangaka' ? 'block' : 'none';
        document.getElementById('studioFields').style.display =
            this.value === 'Studio' ? 'block' : 'none';
    });
</script>
</body>
</html>