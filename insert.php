
<?php

/*
|--------------------------------------------------------------------------
| PCARE REGISTRATION
|--------------------------------------------------------------------------
| Public registration creates Student/Staff accounts only.
*/

// Required files
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

// Redirect authenticated users
if (is_logged_in()) {
    redirect('index.php');
}

// Flash message
$successMessage = flash_get('success');

// Form errors
$errors = [];

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors['form'] = 'Your session expired. Please try again.';

    } else {

        // Get submitted values
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $idNumber = trim((string) ($_POST['id_number'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $contactNo = trim((string) ($_POST['contact_no'] ?? ''));

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        // =====================================
        // SERVER-SIDE VALIDATION
        // =====================================

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

        // =====================================
        // CHECK DUPLICATES AND INSERT ACCOUNT
        // =====================================

        if (!$errors) {

            try {

                $pdo = db();

                // Check existing email or ID number
                $check = $pdo->prepare(
                    'SELECT email, id_number
                     FROM users
                     WHERE email = :email
                        OR id_number = :id_number
                     LIMIT 2'
                );

                $check->execute([
                    ':email' => $email,
                    ':id_number' => $idNumber
                ]);

                foreach ($check->fetchAll() as $row) {

                    if (strtolower((string) $row['email']) === $email) {
                        $errors['email'] = 'This email address is already registered.';
                    }

                    if ((string) $row['id_number'] === $idNumber) {
                        $errors['id_number'] = 'This ID number is already registered.';
                    }
                }

                // Insert if no duplicate
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
                        ':password_hash' => password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        ),
                        ':role' => 'student',
                        ':id_number' => $idNumber,
                        ':department' => $department,
                        ':contact_no' => $contactNo,
                        ':is_active' => 1
                    ]);

                    // Save success notification
                    flash_set(
                        'success',
                        'Account created successfully! You can now sign in.'
                    );

                    // Redirect back to registration page
                    // Login page will be connected separately.
                    redirect('insert.php');
                }

            } catch (PDOException $ex) {

                error_log(
                    'PCare registration error: ' . $ex->getMessage()
                );

                $errors['form'] =
                    'We could not create your account right now. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FIELD ERROR HELPER
|--------------------------------------------------------------------------
*/

function field_error(array $errors, string $key): void
{
    if (isset($errors[$key])) {

        echo '<p class="field-error" id="err-' .
            e($key) .
            '"><i class="bi bi-exclamation-circle"></i> ' .
            e($errors[$key]) .
            '</p>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Create a PCare Student/Staff account to report and track computer issues.">

    <title>Create Account | PCare</title>

    <!-- GOOGLE FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- PCARE MAIN CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>


<body class="page-auth">

<div class="auth-container">

    <!-- =================================
         LEFT INFORMATION PANEL
    ================================== -->

    <aside class="auth-aside">

        <a href="index.php" class="brand brand-light">

            <span class="brand-mark">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>

            <span class="brand-text">PCare</span>

        </a>


        <div class="auth-aside-body">

            <h1>
                Report problems.
                Follow the fix.
            </h1>

            <p>
                Create a Student/Staff account to report
                computer issues in the B204 Computer Laboratory
                and track each one until it is resolved.
            </p>


            <ul class="auth-points">

                <li>
                    <i class="bi bi-check2-circle"></i>
                    Report any of the ten computer units
                </li>

                <li>
                    <i class="bi bi-check2-circle"></i>
                    See the status of your reports
                </li>

                <li>
                    <i class="bi bi-check2-circle"></i>
                    Review the full history of each issue
                </li>

            </ul>

        </div>


        <p class="auth-aside-foot">
            Academic mini-capstone project.
            Not an official SLSU system.
        </p>

    </aside>


    <!-- =================================
         RIGHT REGISTRATION PANEL
    ================================== -->

    <main class="auth-main">

        <div class="auth-card auth-card-wide">

            <a href="index.php" class="auth-back">
                <i class="bi bi-arrow-left"></i>
                Back to Homepage
            </a>


            <h2>Create Account</h2>

            <p class="auth-sub">
                Fill in your details. Accounts created here
                are Student/Staff accounts.
            </p>


            <!-- SUCCESS MESSAGE -->

            <?php if ($successMessage): ?>

                <div class="pc-alert pc-alert-success"
                     role="alert"
                     data-dismissible>

                    <i class="bi bi-check-circle-fill"></i>

                    <div>
                        <?= e($successMessage) ?>
                    </div>

                    <button type="button"
                            class="pc-alert-close"
                            data-dismiss-alert
                            aria-label="Dismiss message">

                        <i class="bi bi-x-lg"></i>

                    </button>

                </div>

            <?php endif; ?>


            <!-- GENERAL ERROR -->

            <?php if (isset($errors['form'])): ?>

                <div class="pc-alert pc-alert-danger"
                     role="alert"
                     data-dismissible>

                    <i class="bi bi-exclamation-octagon-fill"></i>

                    <div>
                        <?= e($errors['form']) ?>
                    </div>

                    <button type="button"
                            class="pc-alert-close"
                            data-dismiss-alert
                            aria-label="Dismiss message">

                        <i class="bi bi-x-lg"></i>

                    </button>

                </div>

            <?php endif; ?>


            <!-- REGISTRATION FORM -->

            <form method="POST"
                  action="insert.php"
                  class="auth-form"
                  data-once>

                <?= csrf_field() ?>


                <div class="form-grid">


                    <!-- FULL NAME -->

                    <div class="form-field form-field-full">

                        <label for="full_name">
                            Full Name
                        </label>

                        <div class="input-wrap<?= isset($errors['full_name']) ? ' has-error' : '' ?>">

                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= old('full_name') ?>"
                                maxlength="150"
                                autocomplete="name"
                                placeholder="Juan Dela Cruz"
                                required
                            >

                        </div>

                        <?php field_error($errors, 'full_name'); ?>

                    </div>


                    <!-- EMAIL -->

                    <div class="form-field form-field-full">

                        <label for="email">
                            Email Address
                        </label>

                        <div class="input-wrap<?= isset($errors['email']) ? ' has-error' : '' ?>">

                            <i class="bi bi-envelope"></i>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= old('email') ?>"
                                maxlength="150"
                                autocomplete="email"
                                placeholder="name@example.com"
                                required
                            >

                        </div>

                        <?php field_error($errors, 'email'); ?>

                    </div>


                    <!-- ID NUMBER -->

                    <div class="form-field">

                        <label for="id_number">
                            ID Number
                        </label>

                        <div class="input-wrap<?= isset($errors['id_number']) ? ' has-error' : '' ?>">

                            <i class="bi bi-person-vcard"></i>

                            <input
                                type="text"
                                id="id_number"
                                name="id_number"
                                value="<?= old('id_number') ?>"
                                maxlength="50"
                                placeholder="Student or staff ID"
                                required
                            >

                        </div>

                        <?php field_error($errors, 'id_number'); ?>

                    </div>


                    <!-- CONTACT NUMBER -->

                    <div class="form-field">

                        <label for="contact_no">
                            Contact Number
                        </label>

                        <div class="input-wrap<?= isset($errors['contact_no']) ? ' has-error' : '' ?>">

                            <i class="bi bi-telephone"></i>

                            <input
                                type="tel"
                                id="contact_no"
                                name="contact_no"
                                value="<?= old('contact_no') ?>"
                                maxlength="30"
                                autocomplete="tel"
                                placeholder="09XX XXX XXXX"
                                required
                            >

                        </div>

                        <?php field_error($errors, 'contact_no'); ?>

                    </div>


                    <!-- DEPARTMENT -->

                    <div class="form-field form-field-full">

                        <label for="department">
                            Department
                        </label>

                        <div class="input-wrap<?= isset($errors['department']) ? ' has-error' : '' ?>">

                            <i class="bi bi-building"></i>

                            <input
                                type="text"
                                id="department"
                                name="department"
                                value="<?= old('department') ?>"
                                maxlength="100"
                                placeholder="e.g. Computer Technology"
                                required
                            >

                        </div>

                        <?php field_error($errors, 'department'); ?>

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-field">

                        <label for="password">
                            Password
                        </label>

                        <div class="input-wrap<?= isset($errors['password']) ? ' has-error' : '' ?>">

                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="At least 8 characters"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                data-toggle-password="password"
                                aria-label="Show password">

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                        <?php field_error($errors, 'password'); ?>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="form-field">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <div class="input-wrap<?= isset($errors['confirm_password']) ? ' has-error' : '' ?>">

                            <i class="bi bi-lock-fill"></i>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                                placeholder="Repeat your password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                data-toggle-password="confirm_password"
                                aria-label="Show password">

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                        <?php field_error($errors, 'confirm_password'); ?>

                    </div>

                </div>


                <!-- SUBMIT BUTTON -->

                <button type="submit"
                        class="btn-pcare btn-pcare-primary btn-block">

                    Create Account

                    <i class="bi bi-arrow-right ms-2"></i>

                </button>

            </form>


            <!-- LOGIN LINK -->

            <p class="auth-switch">

                Already have an account?

                <a href="login.php">Sign In</a>

            </p>

        </div>

    </main>

</div>


<!-- JAVASCRIPT -->
<script src="script.js" defer></script>


<!-- PASSWORD VISIBILITY AND ALERT DISMISSAL -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Show / hide password
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {

        button.addEventListener('click', function () {

            const inputId = button.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
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


    // Dismiss notifications
    document.querySelectorAll('[data-dismiss-alert]').forEach(function (button) {

        button.addEventListener('click', function () {

            const alert = button.closest('.pc-alert');

            if (alert) {
                alert.remove();
            }

        });

    });

});
</script>

</body>
</html>
