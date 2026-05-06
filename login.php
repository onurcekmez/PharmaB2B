<?php
/**
 * LOGIN PAGE
 * 
 * Handles user authentication with:
 * - JavaScript form validation (client-side)
 * - PHP validation (server-side)
 * - SHA-256 password hashing
 * - Session creation
 */
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// -- Handle login form submission (PHP Validation) --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db.php';

    // Server-side validation
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } elseif (strlen($username) < 3) {
        $error = 'Kullanıcı adı en az 3 karakter olmalıdır.';
    } else {
        // Hash the input password with SHA-256
        $password_hash = hash('sha256', $password);

        // Query user with prepared statement
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['password_hash'] === $password_hash) {
            // Login successful - create session
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PharmaB2B - Eczane Tedarik Zinciri Giriş Sayfası">
    <title>Giriş Yap - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="brand">💊</div>
        <h1>PharmaB2B</h1>
        <p class="subtitle">Eczane Tedarik Zinciri Platformu</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Login Form with JavaScript validation -->
        <form method="POST" action="login.php" onsubmit="return validateLoginForm()" id="login-form">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Kullanıcı adınızı girin"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Şifrenizi girin">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content: center; padding: 0.75rem;">
                Giriş Yap
            </button>
        </form>

        <p class="text-center text-muted mt-2" style="font-size:0.8rem;">
            Demo: eczane_ayse / 123456
        </p>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
