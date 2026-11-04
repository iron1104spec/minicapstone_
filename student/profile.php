
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

    $stmt->execute(['user_id' => $userId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        throw new RuntimeException('Student account not found.');
    }

    $fullName = $profile['full_name'];
    $email = $profile['email'];
    $idNumber = $profile['id_number'];
    $department = $profile['department'];
    $contactNo = $profile['contact_no'];

} catch (Throwable $e) {
    error_log($e->getMessage());
    $databaseError = true;
}


// PROCESS PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    } else {

        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullName) > 150) {
            $errors[] = 'Full name must not exceed 150 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors[] = 'Email address is too long.';
        }

        if ($idNumber === '') {
            $errors[] = 'Student ID number is required.';
        } elseif (
            strlen($idNumber) > 50 ||
            !preg_match('/^[A-Za-z0-9-]+$/', $idNumber)
        ) {
            $errors[] = 'Please enter a valid student ID number.';
        }

        if ($department === '') {
            $errors[] = 'Department is required.';
        } elseif (strlen($department) > 100) {
            $errors[] = 'Department must not exceed 100 characters.';
        }

        if ($contactNo === '') {
            $errors[] = 'Contact number is required.';
        } elseif (
            strlen($contactNo) > 30 ||
            !preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)
        ) {
            $errors[] = 'Please enter a valid contact number.';
        }


        // CHECK DUPLICATE EMAIL OR ID
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


        // UPDATE PROFILE
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

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                flash_set('success', 'Your profile has been updated successfully.');

                redirect('profile.php');

            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to update your profile. Please try again.';
            }
        }
    }
}


// PROFILE INITIALS
$nameParts = preg_split('/\s+/', trim($fullName));
$initials = '';

if (!empty($nameParts[0])) {
    $initials .= strtoupper(substr($nameParts[0], 0, 1));
}

if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

if ($initials === '') {
    $initials = 'S';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary: #4E968B;
            --deep-green: #245B54;
            --soft-mint: #E5F0ED;
            --background: #F7F8F6;
            --text: #303C39;
            --muted: #78827F;
            --white: #FFFFFF;
            --border: #E8ECE9;
        }

        body {
            background: var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .dashboard-content {
            max-width: 1450px;
            margin: auto;
            padding: 42px 30px 60px;
        }

        .welcome-label {
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .welcome-title {
            color: var(--deep-green);
            font-size: clamp(25px, 4vw, 32px);
            font-weight: 800;
            letter-spacing: -1.2px;
            margin-bottom: 9px;
        }

        .welcome-description {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
            margin-bottom: 0;
        }

        .section-heading {
            color: var(--deep-green);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .section-description {
            color: var(--muted);
            font-size: 12px;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 290px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .profile-card,
        .information-card {
            height: 100%;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
        }

        .profile-card {
            padding: 28px 22px;
            text-align: center;
        }

        .profile-avatar {
            width: 78px;
            height: 78px;
            display: grid;
            place-items: center;
            margin: 0 auto 17px;
            border-radius: 22px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 25px;
            font-weight: 800;
        }

        .profile-name {
            color: var(--deep-green);
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 6px;
            overflow-wrap: anywhere;
        }

        .profile-email {
            color: var(--muted);
            font-size: 11px;
            overflow-wrap: anywhere;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            padding: 7px 12px;
            border-radius: 20px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 10px;
            font-weight: 700;
        }

        .profile-divider {
            height: 1px;
            background: var(--border);
            margin: 23px 0;
        }

        .profile-note {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.8;
            text-align: left;
        }

        .information-card {
            padding: 25px;
        }

        .information-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
        }

        .information-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 18px;
        }

        .form-label {
            color: var(--text);
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .form-control {
            min-height: 45px;
            padding: 11px 13px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #FCFDFC;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 12px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78, 150, 139, .12);
        }

        .form-control:disabled {
            background: #F1F4F2;
            color: var(--muted);
        }

        .form-hint {
            color: var(--muted);
            font-size: 10px;
            margin-top: 7px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 22px;
            margin-top: 22px;
            border-top: 1px solid var(--border);
        }

        .save-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border: none;
            border-radius: 9px;
            background: var(--deep-green);
            color: var(--white);
            font-size: 11px;
            font-weight: 700;
            transition: .2s ease;
        }

        .save-button:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .cancel-button {
            display: inline-flex;
            align-items: center;
            padding: 11px 18px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--white);
            color: var(--text);
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .cancel-button:hover {
            background: var(--background);
            color: var(--deep-green);
        }

        .profile-alert {
            padding: 14px 17px;
            border-radius: 11px;
            font-size: 12px;
        }

        @media (max-width: 767px) {

            .dashboard-content {
                padding: 28px 17px 45px;
            }

            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-card {
                padding: 25px 20px;
            }

            .information-card {
                padding: 21px 17px;
            }

        }

    </style>

</head>

<body>

    <!-- SAME NAVIGATION AS STUDENT DASHBOARD -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>


    <main class="dashboard-content">

        <!-- PAGE HEADER -->
        <section class="mb-4">

            <div class="welcome-label">
                Student / Staff Portal
            </div>

            <h1 class="welcome-title">
                My Profile
            </h1>

            <p class="welcome-description">
                Manage your personal information and account details.
            </p>

        </section>


        <?php if ($databaseError): ?>

            <div class="alert alert-danger profile-alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                Unable to load your profile information. Please try again later.
            </div>

        <?php else: ?>


            <!-- SUCCESS MESSAGE -->
            <?php if ($successMessage): ?>

                <div class="alert alert-success profile-alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= e($successMessage) ?>
                </div>

            <?php endif; ?>


            <!-- VALIDATION ERRORS -->
            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger profile-alert">

                    <strong>Please check the following:</strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <div class="profile-layout">

                <!-- PROFILE OVERVIEW -->
                <aside class="profile-card">

                    <div class="profile-avatar">
                        <?= e($initials) ?>
                    </div>

                    <h2 class="profile-name">
                        <?= e($fullName) ?>
                    </h2>

                    <p class="profile-email mb-0">
                        <?= e($email) ?>
                    </p>

                    <span class="profile-role">
                        <i class="bi bi-person-check-fill"></i>
                        Student / Staff
                    </span>

                    <div class="profile-divider"></div>

                    <div class="profile-note">

                        <strong class="d-block mb-2" style="color: var(--deep-green);">
                            Account Information
                        </strong>

                        Keep your personal details updated to ensure
                        your LabSync account information remains accurate.

                    </div>

                </aside>


                <!-- EDIT PROFILE FORM -->
                <section class="information-card">

                    <div class="information-header">

                        <div>
                            <h2 class="section-heading mb-1">
                                Personal Information
                            </h2>

                            <p class="section-description mb-0">
                                Update the information associated with your account.
                            </p>
                        </div>

                        <div class="information-icon">
                            <i class="bi bi-person-vcard"></i>
                        </div>

                    </div>


                    <form method="POST" action="">

                        <?= csrf_field() ?>

                        <div class="row g-3">

                            <div class="col-12">

                                <label for="full_name" class="form-label">
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


                            <div class="col-md-6">

                                <label for="email" class="form-label">
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


                            <div class="col-md-6">

                                <label for="id_number" class="form-label">
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


                            <div class="col-md-6">

                                <label for="department" class="form-label">
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


                            <div class="col-md-6">

                                <label for="contact_no" class="form-label">
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


                            <div class="col-12">

                                <label class="form-label">
                                    Account Role
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Student"
                                    disabled
                                >

                                <p class="form-hint">
                                    Your account role cannot be changed from this page.
                                </p>

                            </div>

                        </div>


                        <div class="form-actions">

                            <button type="submit" class="save-button">
                                <i class="bi bi-check2-circle"></i>
                                Save Changes
                            </button>

                            <a href="dashboard.php" class="cancel-button">
                                Cancel
                            </a>

                        </div>

                    </form>

                </section>

            </div>

        <?php endif; ?>

    </main>

</body>
</html>
