
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare("
                SELECT user_id, full_name, email, password_hash, role, is_active
                FROM users
                WHERE email = :email
                LIMIT 1
            ");

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$user ||
                !password_verify($password, $user['password_hash']) ||
                (int) $user['is_active'] !== 1
            ) {
                $errors[] = 'Invalid email or password.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                switch ($user['role']) {
                    case 'student':
                        redirect('student/dashboard.php');

                    case 'personnel':
                        redirect('it-personnel/dashboard.php');

                    case 'admin':
                        redirect('admin/dashboard.php');

                    default:
                        $_SESSION = [];
                        session_destroy();
                        $errors[] = 'Your account has an invalid role. Please contact the administrator.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Sign in to LabSync, the Computer Laboratory Issue Reporting and Maintenance Tracking System.">

    <title>Sign In | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css?v=8">
    <link rel="stylesheet" href="assets/css/auth.css?v=8">
</head>

<body class="page-auth page-login">

<div class="auth-page">

    <header class="auth-topbar">
        <a href="index.php" class="auth-brand">
            <span class="auth-brand-icon">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>
            <span>LabSync</span>
        </a>

        <div class="auth-campus-label">
            SLSU LUCENA CAMPUS
        </div>
    </header>

    <main class="auth-stage">

        <section class="auth-shell">

            <!-- LEFT VISUAL PANEL -->
            <aside class="auth-visual">

                <div class="auth-visual-image"></div>
                <div class="auth-visual-shade"></div>

                <div class="auth-visual-content">

                    <span class="auth-eyebrow">
                        <span class="auth-eyebrow-dot"></span>
                        COMPUTER LABORATORY SUPPORT
                    </span>

                    <div class="auth-welcome">
                        <span>Welcome to</span>

                        <h1>LabSync<span>.</span></h1>

                        <p>
                            Report problems.<br>
                            Follow the fix.
                        </p>
                    </div>

                    <div class="auth-visual-bottom">
                        <div class="auth-visual-line"></div>

                        <p>
                            A centralized platform for computer issue
                            reporting and maintenance tracking.
                        </p>
                    </div>

                </div>
            </aside>

            <!-- RIGHT SIGN IN PANEL -->
            <section class="auth-form-panel">

                <div class="auth-tabs">
                    <a href="login.php"
                       class="auth-tab active"
                       aria-current="page">
                        Sign In
                    </a>

                    <a href="insert.php" class="auth-tab">
                        Sign Up
                    </a>
                </div>

                <div class="auth-form-heading">
                    <span class="auth-kicker">ACCOUNT ACCESS</span>

                    <h2>Welcome back.</h2>

                    <p>
                        Enter your account details to access your LabSync dashboard.
                    </p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="auth-alert auth-alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>

                        <div>
                            <?php foreach ($errors as $error): ?>
                                <p><?= e($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">

                    <?= csrf_field() ?>

                    <div class="auth-fields-grid">

                        <div class="auth-field auth-field-full">
                            <label for="email">Email Address</label>

                            <div class="auth-input-wrap">
                                <i class="bi bi-envelope"></i>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="Enter your email address"
                                    value="<?= e($email) ?>"
                                    autocomplete="username"
                                    maxlength="150"
                                    required
                                >
                            </div>
                        </div>

                        <div class="auth-field auth-field-full">
                            <label for="password">Password</label>

                            <div class="auth-input-wrap">
                                <i class="bi bi-lock"></i>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="auth-password-toggle"
                                    id="togglePassword"
                                    aria-label="Show password"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="auth-submit">
                        <span>Sign In</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </form>

                <p class="auth-switch">
                    Don't have an account?
                    <a href="insert.php">Create an account</a>
                </p>

                <p class="auth-disclaimer">
                    LabSync is an academic mini-capstone project and is not an official SLSU system.
                </p>

            </section>

        </section>

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    if (passwordInput && toggleButton) {
        toggleButton.addEventListener('click', function () {

            const showing = passwordInput.type === 'password';

            passwordInput.type = showing ? 'text' : 'password';

            toggleButton.innerHTML = showing
                ? '<i class="bi bi-eye-slash"></i>'
                : '<i class="bi bi-eye"></i>';

            toggleButton.setAttribute(
                'aria-label',
                showing ? 'Hide password' : 'Show password'
            );
        });
    }

});
</script>

</body>
</html>
