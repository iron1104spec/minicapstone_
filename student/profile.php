
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();

$userId = (int) $_SESSION['user_id'];

$errors = [];
$databaseError = false;
$successMessage = flash_get('success');

$profile = null;

$fullName = '';
$email = '';
$idNumber = '';
$department = '';
$contactNo = '';

try {

    // Retrieve the current student's profile.
    $stmt = $pdo->prepare("
        SELECT
            user_id,
            full_name,
            email,
            id_number,
            department,
            contact_no,
            role
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $profile = $stmt->fetch();

    if (!$profile) {
        throw new RuntimeException('Student account not found.');
    }

    // Load existing profile values.
    $fullName = $profile['full_name'];
    $email = $profile['email'];
    $idNumber = $profile['id_number'];
    $department = $profile['department'];
    $contactNo = $profile['contact_no'];

} catch (Throwable $e) {

    error_log($e->getMessage());
    $databaseError = true;
}


// Process profile update.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid request. Please refresh the page and try again.';

    } else {

        // Retrieve submitted values.
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');

        // Validate full name.
        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullName) > 150) {
            $errors[] = 'Full name must not exceed 150 characters.';
        }

        // Validate email.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors[] = 'Email address is too long.';
        }

        // Validate student ID.
        if ($idNumber === '') {
            $errors[] = 'Student ID number is required.';
        } elseif (
            strlen($idNumber) > 50 ||
            !preg_match('/^[A-Za-z0-9-]+$/', $idNumber)
        ) {
            $errors[] = 'Please enter a valid student ID number.';
        }

        // Validate department.
        if ($department === '') {
            $errors[] = 'Department is required.';
        } elseif (strlen($department) > 100) {
            $errors[] = 'Department must not exceed 100 characters.';
        }

        // Validate contact number.
        if ($contactNo === '') {
            $errors[] = 'Contact number is required.';
        } elseif (
            strlen($contactNo) > 30 ||
            !preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)
        ) {
            $errors[] = 'Please enter a valid contact number.';
        }


        // Check for duplicate email or student ID.
        if (empty($errors)) {

            try {

                $stmt = $pdo->prepare("
                    SELECT user_id
                    FROM users
                    WHERE (email = :email OR id_number = :id_number)
                    AND user_id <> :user_id
                    LIMIT 1
                ");

                $stmt->execute([
                    'email' => $email,
                    'id_number' => $idNumber,
                    'user_id' => $userId
                ]);

                if ($stmt->fetch()) {
                    $errors[] = 'Email address or student ID is already used by another account.';
                }

            } catch (PDOException $e) {

                error_log($e->getMessage());
                $errors[] = 'Unable to validate your account information.';
            }
        }


        // Update the profile if everything is valid.
        if (empty($errors)) {

            try {

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET
                        full_name = :full_name,
                        email = :email,
                        id_number = :id_number,
                        department = :department,
                        contact_no = :contact_no,
                        updated_at = NOW()
                    WHERE user_id = :user_id
                    AND role = 'student'
                ");

                $stmt->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'id_number' => $idNumber,
                    'department' => $department,
                    'contact_no' => $contactNo,
                    'user_id' => $userId
                ]);

                // Update the current session information.
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                flash_set(
                    'success',
                    'Your profile has been updated successfully.'
                );

                redirect('profile.php');

            } catch (PDOException $e) {

                error_log($e->getMessage());
                $errors[] = 'Unable to update your profile. Please try again.';
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

    <title>My Profile | PCare</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <!-- NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">

        <div class="container">

            <a class="navbar-brand fw-bold" href="dashboard.php">
                PCare
            </a>

            <div class="ms-auto d-flex align-items-center">

                <span class="text-white me-3">
                    <?= e($_SESSION['full_name']) ?>
                </span>

                <form action="../logout.php" method="POST">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-outline-light btn-sm"
                    >
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </nav>


    <!-- MAIN CONTENT -->
    <main class="container py-5">

        <!-- PAGE HEADER -->
        <div class="mb-4">

            <a
                href="dashboard.php"
                class="text-decoration-none"
            >
                ← Back to Dashboard
            </a>

            <h2 class="mt-3">
                My Profile
            </h2>

            <p class="text-muted">
                View and manage your personal account information.
            </p>

        </div>


        <!-- DATABASE ERROR -->
        <?php if ($databaseError): ?>

            <div class="alert alert-danger">
                Unable to load your profile information.
                Please try again later.
            </div>

        <?php else: ?>


            <!-- SUCCESS MESSAGE -->
            <?php if ($successMessage): ?>

                <div class="alert alert-success">
                    <?= e($successMessage) ?>
                </div>

            <?php endif; ?>


            <!-- VALIDATION ERRORS -->
            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <strong>Please check the following:</strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- PROFILE FORM -->
            <div class="card border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="mb-4">

                        <h5 class="mb-1">
                            Personal Information
                        </h5>

                        <p class="text-muted small">
                            Update the information associated with your account.
                        </p>

                    </div>


                    <form method="POST" action="">

                        <?= csrf_field() ?>


                        <!-- FULL NAME -->
                        <div class="mb-3">

                            <label
                                for="full_name"
                                class="form-label fw-semibold"
                            >
                                Full Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="full_name"
                                name="full_name"
                                value="<?= e($fullName) ?>"
                                maxlength="150"
                                required
                            >

                        </div>


                        <!-- EMAIL -->
                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label fw-semibold"
                            >
                                Email Address
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= e($email) ?>"
                                maxlength="150"
                                required
                            >

                        </div>


                        <!-- STUDENT ID -->
                        <div class="mb-3">

                            <label
                                for="id_number"
                                class="form-label fw-semibold"
                            >
                                Student ID Number
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="id_number"
                                name="id_number"
                                value="<?= e($idNumber) ?>"
                                maxlength="50"
                                required
                            >

                        </div>


                        <!-- DEPARTMENT -->
                        <div class="mb-3">

                            <label
                                for="department"
                                class="form-label fw-semibold"
                            >
                                Department
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="department"
                                name="department"
                                value="<?= e($department) ?>"
                                maxlength="100"
                                required
                            >

                        </div>


                        <!-- CONTACT NUMBER -->
                        <div class="mb-4">

                            <label
                                for="contact_no"
                                class="form-label fw-semibold"
                            >
                                Contact Number
                            </label>

                            <input
                                type="tel"
                                class="form-control"
                                id="contact_no"
                                name="contact_no"
                                value="<?= e($contactNo) ?>"
                                maxlength="30"
                                required
                            >

                        </div>


                        <!-- ACCOUNT ROLE -->
                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Account Role
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="Student / Staff"
                                disabled
                            >

                            <small class="text-muted">
                                Your account role cannot be changed from this page.
                            </small>

                        </div>


                        <!-- BUTTONS -->
                        <div class="d-flex flex-wrap gap-2">

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Save Changes
                            </button>

                            <a
                                href="dashboard.php"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        <?php endif; ?>

    </main>

</body>
</html>
