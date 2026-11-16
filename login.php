
<?php

// ==========================================
// LABSYNC - LOGIN
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$email = '';

// ==========================================
// PROCESS LOGIN FORM
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Your session may have expired. Please refresh and try again.';

    } else {

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        // ==========================================
        // AUTHENTICATE USER
        // ==========================================

        if (empty($errors)) {

            try {

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

                if (!$user) {

                    $errors[] = 'Invalid email or password.';

                } else {

                    $passwordIsValid = password_verify(
                        $password,
                        $user['password_hash']
                    );

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

                        // LOGIN SUCCESSFUL

                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int) $user['user_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];

                        unset($_SESSION['registration_completed']);

                        // ROLE-BASED REDIRECTION

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <!-- STYLESHEETS -->
    <link rel="stylesheet" href="assets/css/style.css?v=20">
    <link rel="stylesheet" href="assets/css/login.css?v=2">

</head>

<body class="labsync-login">

    <main class="login-page">

        <!-- LEFT BRANDING -->
        <section class="login-visual">

            <a href="index.php" class="login-brand">

                <span class="login-brand-icon">
                    <i class="bi bi-pc-display-horizontal"></i>
                </span>

                <span>
                    LabSync<span class="login-brand-dot">.</span>
                </span>

            </a>

            <div class="login-intro">

                <span class="login-eyebrow">
                    <span></span>
                    B204 COMPUTER LABORATORY
                </span>

                <h1>
                    Smarter Lab Management
                    <span>Starts Here.</span>
                </h1>

                <p>
                    A centralized platform for reporting computer issues,
                    tracking maintenance progress, and keeping laboratory
                    records organized.
                </p>

            </div>

            <div class="login-visual-info">

                <div class="login-info-item">

                    <i class="bi bi-pc-display"></i>

                    <div>
                        <strong>10 Computer Units</strong>
                        <small>PC-01 to PC-10</small>
                    </div>

                </div>

                <div class="login-info-item">

                    <i class="bi bi-tools"></i>

                    <div>
                        <strong>Maintenance Tracking</strong>
                        <small>Documented issue progress</small>
                    </div>

                </div>

            </div>

            <div class="login-visual-footer">
                <span>LABSYNC</span>
                <span>SLSU LUCENA CAMPUS</span>
            </div>

        </section>

        <!-- RIGHT LOGIN PANEL -->
        <section class="login-panel">

            <div class="login-panel-top">

                <span>LABSYNC ACCOUNT</span>

                <a href="insert.php">
                    Create an account
                </a>

            </div>

            <div class="login-form-container">

                <div class="login-heading">

                    <div class="login-heading-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>

                    <span class="login-kicker">
                        COMPUTER LABORATORY SUPPORT
                    </span>

                    <h2>Welcome back</h2>

                    <p>
                        Sign in to continue to your LabSync account.
                    </p>

                </div>

                <!-- ERROR MESSAGES -->
                <?php if (!empty($errors)): ?>

                    <div class="login-alert" role="alert">

                        <i class="bi bi-exclamation-circle-fill"></i>

                        <div>

                            <?php foreach ($errors as $error): ?>

                                <p><?= e($error) ?></p>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endif; ?>

                <!-- LOGIN FORM -->
                <form method="POST" action="login.php" class="login-form">

                    <?= csrf_field() ?>

                    <!-- EMAIL -->
                    <div class="login-field">

                        <label for="email">
                            Email Address
                        </label>

                        <div class="login-input-wrap">

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
                    <div class="login-field">

                        <label for="password">
                            Password
                        </label>

                        <div class="login-input-wrap">

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
                                class="login-password-toggle"
                                id="togglePassword"
                                aria-label="Show password"
                            >

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                    </div>

                    <!-- SUBMIT -->
                    <button type="submit" class="login-submit">

                        <span>Sign In</span>

                        <i class="bi bi-arrow-right"></i>

                    </button>

                </form>

                <!-- REGISTRATION -->
                <p class="login-register-link">

                    Don't have an account?

                    <a href="insert.php">
                        Create an account
                    </a>

                </p>

                <!-- FOOTER -->
                <div class="login-divider">

                    <span></span>

                    <small>
                        LABSYNC · SLSU LUCENA CAMPUS
                    </small>

                    <span></span>

                </div>

                <a href="index.php" class="login-back">
                    <i class="bi bi-arrow-left"></i>
                    Back to Homepage
                </a>

            </div>

            <p class="login-disclaimer">
                LabSync is an academic mini-capstone project and is not an official SLSU system.
            </p>

        </section>

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
