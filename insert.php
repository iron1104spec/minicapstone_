
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

// PROCESS REGISTRATION
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

        // INPUT VALIDATION

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

        // Only these roles are recognized by the registration form.
        $allowedRoles = ['student', 'personnel', 'admin'];

        if (!in_array($role, $allowedRoles, true)) {
            $errors['role'] = 'Please select a valid account role.';
        }

        // Public registration cannot create administrator accounts.
        if ($role === 'admin') {
            $errors['role'] = 'Administrator accounts must be created through a controlled setup process.';
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

        // DATABASE REGISTRATION

        if (empty($errors)) {

            try {

                $pdo = db();

                // Check duplicate email and ID number.
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

                // Create account if no duplicate exists.
                if (empty($errors)) {

                    // Students are active immediately.
                    // IT Personnel accounts require administrator approval.
                    $isActive = ($role === 'student') ? 1 : 0;

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
                        ':role' => $role,
                        ':id_number' => $idNumber,
                        ':department' => $department,
                        ':contact_no' => $contactNo,
                        ':is_active' => $isActive
                    ]);

                    // Get the newly created account ID.
                    $newUserId = (int) $pdo->lastInsertId();

                    if ($role === 'student') {

                        // Automatically log in the active student.
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = 'student';

                        unset($_SESSION['registration_completed']);

                        redirect('student/dashboard.php');

                    } elseif ($role === 'personnel') {

                        // IT Personnel must wait for administrator approval.
                        $successMessage = 'Your IT Personnel account has been submitted successfully. Please wait for administrator approval before signing in.';

                        // Clear submitted form values after successful registration.
                        $fullName = '';
                        $email = '';
                        $idNumber = '';
                        $department = '';
                        $contactNo = '';
                        $role = '';
                    }
                }

            } catch (PDOException $ex) {

                error_log('PCare registration error: ' . $ex->getMessage());

                $errors['form'] = 'We could not create your account right now. Please try again.';
            }
        }
    }
}

// FIELD ERROR DISPLAY
function field_error(array $errors, string $key): void
{
    if (isset($errors[$key])) {
        echo '<p class="auth-field-error">'
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
        content="Create your account to report and track computer issues at SLSU Lucena Campus.">

    <title>Create Account | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="assets/style.css?v=13">
    <link rel="stylesheet" href="assets/css/auth.css?v=14">

</head>

<body class="auth-screen auth-register">

    <main class="auth-layout">

        <a href="index.php" class="auth-logo" aria-label="LabSync homepage">

            <span class="auth-logo-icon">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>

            <span class="auth-logo-name">LabSync<span>.</span></span>

        </a>

        <section class="auth-card auth-register-card" aria-labelledby="register-title">

            <div class="auth-card-heading">

                <div class="auth-card-symbol">
                    <i class="bi bi-person-plus"></i>
                </div>

                <span class="auth-kicker">GET STARTED WITH LABSYNC</span>

                <h1 id="register-title">Create your account</h1>

                <p>Register to report and monitor computer issues.</p>

            </div>

            <nav class="auth-tabs" aria-label="Account access">

                <a href="login.php" class="auth-tab">
                    Sign In
                </a>

                <a href="insert.php"
                    class="auth-tab active"
                    aria-current="page">
                    Sign Up
                </a>

            </nav>

            <?php if (isset($errors['form'])): ?>

                <div class="auth-message auth-message-error" role="alert">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <div>
                        <p><?= e($errors['form']) ?></p>
                    </div>

                </div>

            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>

                <div class="auth-message auth-message-success" role="status">

                    <i class="bi bi-check-circle-fill"></i>

                    <div>
                        <p><?= e($successMessage) ?></p>
                        <p><a href="login.php">Proceed to Sign In</a></p>
                    </div>

                </div>

            <?php endif; ?>

            <form method="POST"
                action="insert.php"
                class="auth-form auth-register-form"
                data-once>

                <?= csrf_field() ?>

                <div class="auth-fields-grid">

                    <!-- FULL NAME -->
                    <div class="auth-field auth-field-full">

                        <label for="full_name">Full Name</label>

                        <div class="auth-input-wrap">
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

                    <!-- EMAIL -->
                    <div class="auth-field auth-field-full">

                        <label for="email">Email Address</label>

                        <div class="auth-input-wrap">
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

                    <!-- ACCOUNT ROLE -->
                    <div class="auth-field auth-field-full">

                        <label for="role">Account Role</label>

                        <div class="auth-input-wrap">
                            <i class="bi bi-person-badge"></i>

                            <select
                                id="role"
                                name="role"
                                required
                            >
                                <option value="">Select your role</option>

                                <option value="student"
                                    <?= $role === 'student' ? 'selected' : '' ?>>
                                    Student / Staff
                                </option>

                                <option value="personnel"
                                    <?= $role === 'personnel' ? 'selected' : '' ?>>
                                    IT Personnel
                                </option>

                                <option value="admin"
                                    <?= $role === 'admin' ? 'selected' : '' ?>>
                                    Administrator
                                </option>

                            </select>
                        </div>

                        <small class="auth-field-hint">
                            IT Personnel accounts require administrator approval.
                            Administrator accounts are created through a controlled setup process.
                        </small>

                        <?php field_error($errors, 'role'); ?>

                    </div>

                    <!-- ID NUMBER -->
                    <div class="auth-field">

                        <label for="id_number">ID Number</label>

                        <div class="auth-input-wrap">
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

                    <!-- CONTACT NUMBER -->
                    <div class="auth-field">

                        <label for="contact_no">Contact Number</label>

                        <div class="auth-input-wrap">
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

                    <!-- DEPARTMENT -->
                    <div class="auth-field auth-field-full">

                        <label for="department">Department</label>

                        <div class="auth-input-wrap">
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

                    <!-- PASSWORD -->
                    <div class="auth-field">

                        <label for="password">Password</label>

                        <div class="auth-input-wrap">
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
                                class="auth-password-toggle"
                                data-toggle-password="password"
                                aria-label="Show password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <?php field_error($errors, 'password'); ?>

                    </div>

                    <!-- CONFIRM PASSWORD -->
                    <div class="auth-field">

                        <label for="confirm_password">Confirm Password</label>

                        <div class="auth-input-wrap">
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
                                class="auth-password-toggle"
                                data-toggle-password="confirm_password"
                                aria-label="Show password"
                            >
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

            <p class="auth-switch">
                Already have an account?
                <a href="login.php">Sign In</a>
            </p>

            <div class="auth-card-footer">

                <span class="auth-footer-line"></span>

                <p>LABSYNC · SLSU LUCENA CAMPUS</p>

                <span class="auth-footer-line"></span>

            </div>

        </section>

        <p class="auth-disclaimer">
            LabSync is an academic mini-capstone project and is not an official SLSU system.
        </p>

    </main>

    <script src="assets/js/main.js" defer></script>

</body>
</html>
