<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

$pageTitle = 'Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Placeholder auth: replace with DB lookup + password_verify()
    $_SESSION['user'] = [
        'id' => 1,
        'name' => trim((string)($_POST['name'] ?? 'User')),
        'role' => ($_POST['role'] ?? 'mangaka') === 'studio' ? 'studio' : 'mangaka',
    ];
    header('Location: /index.php');
    exit;
}
?>

<div class="card">
  <h2>Login</h2>
  <form method="post">
    <label>
      Name
      <input name="name" required />
    </label>
    <div style="height: 12px"></div>
    <label>
      Role
      <select name="role">
        <option value="mangaka">Mangaka</option>
        <option value="studio">Studio</option>
      </select>
    </label>
    <div style="height: 12px"></div>
    <button type="submit">Sign in</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
