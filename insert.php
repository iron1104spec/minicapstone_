
<?php
/*
|--------------------------------------------------------------------------
| LABSYNC REGISTRATION
|--------------------------------------------------------------------------
| Public registration creates Student accounts only.
*/

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

// Automatic redirect removed so the registration page remains accessible.

$successMessage = flash_get('success');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $idNumber = trim((string) ($_POST['id_number'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $contactNo = trim((string) ($_POST['contact_no'] ?? ''));

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 150) {
            $errors['full_name'] = 'Enter your full name (2 to 150 characters).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (!preg_match('/^[A-Za-z0-9\-]{3,50}$/', $idNumber)) {
            $errors['id_number'] = 'Use 3 to 50 letters, numbers, or hyphens.';
        }

        if (mb_strlen($department) < 2 || mb_strlen($department) > 100) {
            $errors['department'] = 'Enter your department (2 to 100 characters).';
        }

        if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)) {
            $errors['contact_no'] = 'Enter a valid contact number.';
        }

        if (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            $errors['password'] = 'Use at least 8 characters with letters and numbers.';
        }

        if ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!$errors) {
            try {
                $pdo = db();

                $check = $pdo->prepare(
                    'SELECT email, id_number
                     FROM users
                     WHERE email = :email OR id_number = :id_number
                     LIMIT 2'
                );

                $check->execute([
                    ':email' => $email,
                    ':id_number' => $idNumber
                ]);

                foreach ($check->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (strtolower((string) $row['email']) === $email) {
                        $errors['email'] = 'This email address is already registered.';
                    }

                    if ((string) $row['id_number'] === $idNumber) {
                        $errors['id_number'] = 'This ID number is already registered.';
                    }
                }

                if (!$errors) {
                    $insert = $pdo->prepare(
                        'INSERT INTO users (
                            full_name,
                            email,
                            password_hash,
                            role,
                            id_number,
                            department,
                            contact_no,
                            is_active
                        ) VALUES (
                            :full_name,
                            :email,
                            :password_hash,
                            :role,
                            :id_number,
                            :department,
                            :contact_no,
                            :is_active
                        )'
                    );

                    $insert->execute([
                        ':full_name' => $fullName,
                        ':email' => $email,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':role' => 'student',
                        ':id_number' => $idNumber,
                        ':department' => $department,
                        ':contact_no' => $contactNo,
                        ':is_active' => 1
                    ]);

                    flash_set(
                        'success',
                        'Account created successfully! You can now sign in.'
                    );

                    redirect('insert.php');
                }

            } catch (PDOException $ex) {
                error_log('LabSync registration error: ' . $ex->getMessage());
                $errors['form'] = 'We could not create your account right now. Please try again.';
            }
        }
    }
}

function field_error(array $errors, string $key): void
{
    if (isset($errors[$key])) {
        echo '<p class="field-error" id="err-' . e($key) . '">'
            . '<i class="bi bi-exclamation-circle"></i> '
            . e($errors[$key])
            . '</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Create your LabSync account to report and track computer issues at SLSU Lucena Campus.">

    <title>Create Account | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css?v=4">
    <link rel="stylesheet" href="assets/css/auth.css?v=4">
</head>

<body class="page-auth">

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

            <section class="auth-form-panel">

                <div class="auth-tabs">
                    <a href="login.php" class="auth-tab">
                        Sign In
                    </a>

                    <a href="insert.php" class="auth-tab active" aria-current="page">
                        Sign Up
                    </a>
                </div>

                <div class="auth-form-heading">
                    <span class="auth-kicker">GET STARTED WITH LABSYNC</span>
                    <h2>Create your account</h2>
                    <p>
                        Join LabSync to report and monitor computer issues.
                    </p>
                </div>

                <?php if ($successMessage): ?>
                    <div class="auth-alert auth-alert-success" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?= e($successMessage) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors['form'])): ?>
                    <div class="auth-alert auth-alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?= e($errors['form']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="insert.php" class="auth-register-form" data-once>

                    <?= csrf_field() ?>

                    <div class="auth-fields-grid">

                        <div class="auth-field auth-field-full">
                            <label for="full_name">Full Name</label>
                            <div class="auth-input-wrap<?= isset($errors['full_name']) ? ' has-error' : '' ?>">
                                <i class="bi bi-person"></i>
                                <input type="text" id="full_name" name="full_name"
                                       value="<?= old('full_name') ?>"
                                       maxlength="150" autocomplete="name"
                                       placeholder="Enter your full name" required>
                            </div>
                            <?php field_error($errors, 'full_name'); ?>
                        </div>

                        <div class="auth-field auth-field-full">
                            <label for="email">Email Address</label>
                            <div class="auth-input-wrap<?= isset($errors['email']) ? ' has-error' : '' ?>">
                                <i class="bi bi-envelope"></i>
                                <input type="email" id="email" name="email"
                                       value="<?= old('email') ?>"
                                       maxlength="150" autocomplete="email"
                                       placeholder="name@example.com" required>
                            </div>
                            <?php field_error($errors, 'email'); ?>
                        </div>

                        <div class="auth-field">
                            <label for="id_number">ID Number</label>
                            <div class="auth-input-wrap<?= isset($errors['id_number']) ? ' has-error' : '' ?>">
                                <i class="bi bi-person-vcard"></i>
                                <input type="text" id="id_number" name="id_number"
                                       value="<?= old('id_number') ?>"
                                       maxlength="50" placeholder="Your ID number" required>
                            </div>
                            <?php field_error($errors, 'id_number'); ?>
                        </div>

                        <div class="auth-field">
                            <label for="contact_no">Contact Number</label>
                            <div class="auth-input-wrap<?= isset($errors['contact_no']) ? ' has-error' : '' ?>">
                                <i class="bi bi-telephone"></i>
                                <input type="tel" id="contact_no" name="contact_no"
                                       value="<?= old('contact_no') ?>"
                                       maxlength="30" autocomplete="tel"
                                       placeholder="09XX XXX XXXX" required>
                            </div>
                            <?php field_error($errors, 'contact_no'); ?>
                        </div>

                        <div class="auth-field auth-field-full">
                            <label for="department">Department</label>
                            <div class="auth-input-wrap<?= isset($errors['department']) ? ' has-error' : '' ?>">
                                <i class="bi bi-building"></i>
                                <input type="text" id="department" name="department"
                                       value="<?= old('department') ?>"
                                       maxlength="100" placeholder="Enter your department" required>
                            </div>
                            <?php field_error($errors, 'department'); ?>
                        </div>

                        <div class="auth-field">
                            <label for="password">Password</label>
                            <div class="auth-input-wrap<?= isset($errors['password']) ? ' has-error' : '' ?>">
                                <i class="bi bi-lock"></i>
                                <input type="password" id="password" name="password"
                                       autocomplete="new-password"
                                       placeholder="Create password" required>
                                <button type="button" class="auth-password-toggle"
                                        data-toggle-password="password"
                                        aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php field_error($errors, 'password'); ?>
                        </div>

                        <div class="auth-field">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="auth-input-wrap<?= isset($errors['confirm_password']) ? ' has-error' : '' ?>">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" id="confirm_password"
                                       name="confirm_password" autocomplete="new-password"
                                       placeholder="Repeat password" required>
                                <button type="button" class="auth-password-toggle"
                                        data-toggle-password="confirm_password"
                                        aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php field_error($errors, 'confirm_password'); ?>
                        </div>

                    </div>

                    <button type="submit" class="auth-submit">
                        <span>Create Account</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </form>

                <div class="auth-switch">
                    Already have an account?
                    <a href="login.php">Sign In</a>
                </div>

                <p class="auth-disclaimer">
                    LabSync is an academic mini-capstone project and is not an official SLSU system.
                </p>

            </section>

        </section>

    </main>

</div>

<script src="script.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(
                button.getAttribute('data-toggle-password')
            );

            const icon = button.querySelector('i');

            if (!input) return;

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';

            if (icon) {
                icon.classList.toggle('bi-eye', showing);
                icon.classList.toggle('bi-eye-slash', !showing);
            }

            button.setAttribute(
                'aria-label',
                showing ? 'Show password' : 'Hide password'
            );
        });
    });

});
</script>

</body>
</html>
