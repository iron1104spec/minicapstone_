
<?php

// ==========================================
// LABSYNC - LOGIN
// ==========================================

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$email = '';

// ==========================================
// PROCESS LOGIN FORM
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token.
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Your session may have expired. Please refresh and try again.';

    } else {

        // Get submitted credentials.
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        // Validate email.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        // Validate password.
        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        // ==========================================
        // AUTHENTICATE USER
        // ==========================================

        if (empty($errors)) {

            try {

                // Use the $pdo connection from config/db.php.
                $stmt = $pdo->prepare("
                    SELECT
                        user_id,
                        full_name,
                        email,
                        password_hash,
                        role,
                        is_active
                    FROM users
                    WHERE email = :email
                    LIMIT 1
                ");

                $stmt->execute([
                    ':email' => $email
                ]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check whether the account exists.
                if (!$user) {

                    $errors[] = 'Invalid email or password.';

                } else {

                    // Verify the submitted password against its stored hash.
                    $passwordIsValid = password_verify(
                        $password,
                        $user['password_hash']
                    );

                    // Check account activation.
                    $accountIsActive = (int) $user['is_active'] === 1;

                    if (!$passwordIsValid || !$accountIsActive) {

                        $errors[] = 'Invalid email or password.';

                    } elseif (
                        !in_array(
                            $user['role'],
                            ['student', 'personnel', 'admin'],
                            true
                        )
                    ) {

                        $errors[] = 'Your account has an invalid role. Please contact the administrator.';

                    } else {

                        // ==========================================
                        // LOGIN SUCCESSFUL
                        // ==========================================

                        // Regenerate session ID for security.
                        session_regenerate_id(true);

                        // Store authenticated user information.
                        $_SESSION['user_id'] = (int) $user['user_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];

                        // Remove registration-only session flag.
                        unset($_SESSION['registration_completed']);

                        // ==========================================
                        // ROLE-BASED REDIRECTION
                        // ==========================================

                        switch ($user['role']) {

                            case 'student':
                                redirect('student/dashboard.php');
                                exit;

                            case 'personnel':
                                redirect('it-personnel/dashboard.php');
                                exit;

                            case 'admin':
                                redirect('admin/dashboard.php');
                                exit;

                            default:
                                $errors[] = 'Unable to identify your account role.';
                                break;
                        }
                    }
                }

            } catch (PDOException $ex) {

                // Record technical error in PHP error log.
                error_log('LabSync login database error: ' . $ex->getMessage());

                $errors[] = 'Unable to sign in right now. Please try again later.';

            } catch (Throwable $ex) {

                error_log('LabSync login error: ' . $ex->getMessage());

                $errors[] = 'An unexpected error occurred. Please try again.';
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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <!-- LabSync Stylesheets -->
    <link rel="stylesheet" href="assets/style.css?v=14">
    <link rel="stylesheet" href="assets/css/auth.css?v=14">

</head>

<body class="auth-screen auth-login">

    <main class="auth-layout">

        <!-- BRAND LOGO -->
        <a href="index.php" class="auth-logo" aria-label="LabSync homepage">

            <span class="auth-logo-icon">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>

            <span class="auth-logo-name">
                LabSync<span>.</span>
            </span>

        </a>

        <!-- LOGIN CARD -->
        <section class="auth-card" aria-labelledby="login-title">

            <div class="auth-card-heading">

                <div class="auth-card-symbol">
                    <i class="bi bi-person-circle"></i>
                </div>

                <span class="auth-kicker">
                    COMPUTER LABORATORY SUPPORT
                </span>

                <h1 id="login-title">
                    Welcome back
                </h1>

                <p>
                    Sign in to continue to your LabSync account.
                </p>

            </div>

            <!-- AUTHENTICATION TABS -->
            <nav class="auth-tabs" aria-label="Account access">

                <a href="login.php"
                    class="auth-tab active"
                    aria-current="page">
                    Sign In
                </a>

                <a href="insert.php" class="auth-tab">
                    Sign Up
                </a>

            </nav>

            <!-- ERROR MESSAGES -->
            <?php if (!empty($errors)): ?>

                <div class="auth-message auth-message-error" role="alert">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <div>

                        <?php foreach ($errors as $error): ?>

                            <p><?= e($error) ?></p>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

            <!-- LOGIN FORM -->
            <form method="POST" action="login.php" class="auth-form">

                <?= csrf_field() ?>

                <!-- EMAIL ADDRESS -->
                <div class="auth-field">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="auth-input-wrap">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($email) ?>"
                            placeholder="name@example.com"
                            autocomplete="username"
                            maxlength="150"
                            required
                        >

                    </div>

                </div>

                <!-- PASSWORD -->
                <div class="auth-field">

                    <label for="password">
                        Password
                    </label>

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

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="auth-submit">

                    <span>Sign In</span>

                    <i class="bi bi-arrow-right"></i>

                </button>

            </form>

            <!-- REGISTRATION LINK -->
            <p class="auth-switch">

                Don't have an account?

                <a href="insert.php">
                    Create an account
                </a>

            </p>

            <!-- FOOTER -->
            <div class="auth-card-footer">

                <span class="auth-footer-line"></span>

                <p>
                    LABSYNC · SLSU LUCENA CAMPUS
                </p>

                <span class="auth-footer-line"></span>

            </div>

        </section>

        <!-- DISCLAIMER -->
        <p class="auth-disclaimer">

            LabSync is an academic mini-capstone project and is not an official SLSU system.

        </p>

    </main>

    <!-- PASSWORD VISIBILITY -->
    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');

            if (passwordInput && toggleButton) {

                toggleButton.addEventListener('click', function () {

                    const isPassword = passwordInput.type === 'password';

                    passwordInput.type = isPassword ? 'text' : 'password';

                    toggleButton.innerHTML = isPassword
                        ? '<i class="bi bi-eye-slash"></i>'
                        : '<i class="bi bi-eye"></i>';

                    toggleButton.setAttribute(
                        'aria-label',
                        isPassword ? 'Hide password' : 'Show password'
                    );

                });

            }

        });

    </script>

</body>

</html>
