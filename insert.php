
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$successMessage = '';

$fullName = '';
$email = '';
$idNumber = '';
$department = '';
$contactNo = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session may have expired. Please refresh and try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $idNumber = trim((string) ($_POST['id_number'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $contactNo = trim((string) ($_POST['contact_no'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        // Input validation
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

        $allowedRoles = ['student', 'personnel'];

        if (!in_array($role, $allowedRoles, true)) {
            $errors['role'] = 'Please select a valid account role.';
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

        // Database registration
        if (empty($errors)) {
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

                $existingUsers = $check->fetchAll(PDO::FETCH_ASSOC);

                foreach ($existingUsers as $row) {
                    if (strtolower((string) $row['email']) === $email) {
                        $errors['email'] = 'This email address is already registered.';
                    }

                    if ((string) $row['id_number'] === $idNumber) {
                        $errors['id_number'] = 'This ID number is already registered.';
                    }
                }

                if (empty($errors)) {
                    // Student accounts are active immediately.
                    // IT Personnel accounts require administrator approval.
                    $isActive = ($role === 'student') ? 1 : 0;

                    $insert = $pdo->prepare(
                        'INSERT INTO users (
                            full_name, email, password_hash, role,
                            id_number, department, contact_no, is_active
                        ) VALUES (
                            :full_name, :email, :password_hash, :role,
                            :id_number, :department, :contact_no, :is_active
                        )'
                    );

                    $insert->execute([
                        ':full_name' => $fullName,
                        ':email' => $email,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':role' => $role,
                        ':id_number' => $idNumber,
                        ':department' => $department,
                        ':contact_no' => $contactNo,
                        ':is_active' => $isActive
                    ]);

                    $newUserId = (int) $pdo->lastInsertId();

                    if ($role === 'student') {
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = 'student';

                        unset($_SESSION['registration_completed']);

                        redirect('student/dashboard.php');

                    } elseif ($role === 'personnel') {
                        $successMessage = 'Your IT Personnel account has been submitted successfully. Please wait for administrator approval before signing in.';

                        $fullName = '';
                        $email = '';
                        $idNumber = '';
                        $department = '';
                        $contactNo = '';
                        $role = '';
                    }
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
        echo '<p class="register-error">'
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

    <meta name="description" content="Create your LabSync account to report and monitor computer issues at SLSU Lucena Campus.">

    <title>Create Account | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="/minicapstone/assets/style.css?v=18">
    <link rel="stylesheet" href="/minicapstone/assets/css/auth.css?v=18">
    <link rel="stylesheet" href="/minicapstone/assets/css/register.css?v=1">
</head>

<body class="register-screen">

<div class="register-layout">

    <!-- LEFT: CAMPUS BRANDING PANEL -->
    <aside class="register-visual">

        <div class="register-visual-overlay"></div>

        <div class="register-visual-content">

            <a href="index.php" class="register-brand">
                <span class="register-brand-icon">
                    <i class="bi bi-pc-display-horizontal"></i>
                </span>
                <span>LabSync<span class="brand-dot">.</span></span>
            </a>

            <div class="register-intro">
                <span class="register-eyebrow">
                    <span class="eyebrow-dot"></span>
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

            <div class="register-visual-info">

                <div class="visual-info-item">
                    <span class="visual-info-icon">
                        <i class="bi bi-pc-display"></i>
                    </span>
                    <div>
                        <strong>10 Computer Units</strong>
                        <small>PC-01 to PC-10</small>
                    </div>
                </div>

                <div class="visual-info-item">
                    <span class="visual-info-icon">
                        <i class="bi bi-tools"></i>
                    </span>
                    <div>
                        <strong>Maintenance Tracking</strong>
                        <small>Documented issue progress</small>
                    </div>
                </div>

            </div>

            <div class="register-visual-footer">
                <span>LABSYNC</span>
                <span>SLSU LUCENA CAMPUS</span>
            </div>

        </div>
    </aside>

    <!-- RIGHT: REGISTRATION FORM -->
    <main class="register-main">

        <div class="register-top-link">
            <span>Already have an account?</span>
            <a href="login.php">Sign In <i class="bi bi-arrow-up-right"></i></a>
        </div>

        <section class="register-form-container">

            <div class="register-heading">
                <div class="register-heading-icon">
                    <i class="bi bi-person-plus"></i>
                </div>

                <span class="register-kicker">GET STARTED WITH LABSYNC</span>
                <h2>Create your account</h2>
                <p>Enter your details to get started with LabSync.</p>
            </div>

            <?php if (isset($errors['form'])): ?>
                <div class="register-alert register-alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= e($errors['form']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="register-alert register-alert-success" role="status">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>Registration submitted</strong>
                        <p><?= e($successMessage) ?></p>
                        <a href="login.php">Proceed to Sign In</a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="insert.php" class="register-form" data-once>
                <?= csrf_field() ?>

                <div class="register-fields">

                    <div class="register-field field-full">
                        <label for="full_name">Full Name</label>
                        <div class="register-input">
                            <i class="bi bi-person"></i>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= e($fullName) ?>"
                                maxlength="150"
                                autocomplete="name"
                                placeholder="Enter your full name"
                                required
                            >
                        </div>
                        <?php field_error($errors, 'full_name'); ?>
                    </div>

                    <div class="register-field field-full">
                        <label for="email">Email Address</label>
                        <div class="register-input">
                            <i class="bi bi-envelope"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= e($email) ?>"
                                maxlength="150"
                                autocomplete="email"
                                placeholder="name@example.com"
                                required
                            >
                        </div>
                        <?php field_error($errors, 'email'); ?>
                    </div>

                    <div class="register-field field-full">
                        <label for="role">Account Role</label>
                        <div class="register-input">
                            <i class="bi bi-person-badge"></i>
                            <select id="role" name="role" required>
                                <option value="">Select your role</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>
                                    Student / Staff
                                </option>
                                <option value="personnel" <?= $role === 'personnel' ? 'selected' : '' ?>>
                                    IT Personnel
                                </option>
                            </select>
                            <i class="bi bi-chevron-down select-chevron"></i>
                        </div>
                        <small class="register-hint">
                            IT Personnel accounts require administrator approval.
                        </small>
                        <?php field_error($errors, 'role'); ?>
                    </div>

                    <div class="register-field">
                        <label for="id_number">ID Number</label>
                        <div class="register-input">
                            <i class="bi bi-person-vcard"></i>
                            <input
                                type="text"
                                id="id_number"
                                name="id_number"
                                value="<?= e($idNumber) ?>"
                                maxlength="50"
                                placeholder="Your ID number"
                                required
                            >
                        </div>
                        <?php field_error($errors, 'id_number'); ?>
                    </div>

                    <div class="register-field">
                        <label for="contact_no">Contact Number</label>
                        <div class="register-input">
                            <i class="bi bi-telephone"></i>
                            <input
                                type="tel"
                                id="contact_no"
                                name="contact_no"
                                value="<?= e($contactNo) ?>"
                                maxlength="30"
                                autocomplete="tel"
                                placeholder="09XX XXX XXXX"
                                required
                            >
                        </div>
                        <?php field_error($errors, 'contact_no'); ?>
                    </div>

                    <div class="register-field field-full">
                        <label for="department">Department</label>
                        <div class="register-input">
                            <i class="bi bi-building"></i>
                            <input
                                type="text"
                                id="department"
                                name="department"
                                value="<?= e($department) ?>"
                                maxlength="100"
                                placeholder="Enter your department"
                                required
                            >
                        </div>
                        <?php field_error($errors, 'department'); ?>
                    </div>

                    <div class="register-field">
                        <label for="password">Password</label>
                        <div class="register-input">
                            <i class="bi bi-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="Create password"
                                required
                            >
                            <button
                                type="button"
                                class="register-password-toggle"
                                data-toggle-password="password"
                                aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php field_error($errors, 'password'); ?>
                    </div>

                    <div class="register-field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="register-input">
                            <i class="bi bi-lock-fill"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                                placeholder="Repeat password"
                                required
                            >
                            <button
                                type="button"
                                class="register-password-toggle"
                                data-toggle-password="confirm_password"
                                aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php field_error($errors, 'confirm_password'); ?>
                    </div>

                </div>

                <button type="submit" class="register-submit">
                    <span>Create Account</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            <p class="register-bottom-text">
                By creating an account, you agree to use LabSync responsibly
                for B204 laboratory-related concerns.
            </p>

            <div class="register-divider">
                <span></span>
                <small>LABSYNC · SLSU LUCENA CAMPUS</small>
                <span></span>
            </div>

            <a href="index.php" class="register-back">
                <i class="bi bi-arrow-left"></i> Back to Homepage
            </a>

        </section>

        <p class="register-disclaimer">
            LabSync is an academic mini-capstone project and is not an official SLSU system.
        </p>

    </main>

</div>

<script src="/minicapstone/assets/js/main.js" defer></script>

</body>
</html>
