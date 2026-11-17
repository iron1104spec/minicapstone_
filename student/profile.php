
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


// ==========================================
// GET STUDENT PROFILE
// ==========================================

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

    $stmt->execute([
        'user_id' => $userId
    ]);

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


// ==========================================
// PROCESS PROFILE UPDATE
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid request. Please refresh the page and try again.';

    } else {

        // GET FORM VALUES

        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');


        // ==================================
        // VALIDATE FULL NAME
        // ==================================

        if ($fullName === '') {

            $errors[] = 'Full name is required.';

        } elseif (strlen($fullName) > 150) {

            $errors[] = 'Full name must not exceed 150 characters.';

        }


        // ==================================
        // VALIDATE EMAIL
        // ==================================

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $errors[] = 'Please enter a valid email address.';

        } elseif (strlen($email) > 150) {

            $errors[] = 'Email address is too long.';

        }


        // ==================================
        // VALIDATE STUDENT ID
        // ==================================

        if ($idNumber === '') {

            $errors[] = 'Student ID number is required.';

        } elseif (
            strlen($idNumber) > 50 ||
            !preg_match('/^[A-Za-z0-9-]+$/', $idNumber)
        ) {

            $errors[] = 'Please enter a valid student ID number.';

        }


        // ==================================
        // VALIDATE DEPARTMENT
        // ==================================

        if ($department === '') {

            $errors[] = 'Department is required.';

        } elseif (strlen($department) > 100) {

            $errors[] = 'Department must not exceed 100 characters.';

        }


        // ==================================
        // VALIDATE CONTACT NUMBER
        // ==================================

        if ($contactNo === '') {

            $errors[] = 'Contact number is required.';

        } elseif (
            strlen($contactNo) > 30 ||
            !preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)
        ) {

            $errors[] = 'Please enter a valid contact number.';

        }


        // ==================================
        // CHECK DUPLICATE EMAIL / STUDENT ID
        // ==================================

        if (empty($errors)) {

            try {

                $stmt = $pdo->prepare("
                    SELECT user_id
                    FROM users
                    WHERE (
                        email = :email
                        OR id_number = :id_number
                    )
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


        // ==================================
        // UPDATE STUDENT PROFILE
        // ==================================

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

                // UPDATE SESSION INFORMATION

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                // SUCCESS MESSAGE

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


// ==========================================
// GENERATE PROFILE INITIALS
// ==========================================

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


// ACCOUNT ROLE DISPLAY

$roleLabel = 'Student';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#16443D">

    <title>My Profile | LabSync</title>


    <!-- GOOGLE FONTS -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICONS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- PROFILE CUSTOM CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/student-profile.css?v=2"
    >

</head>


<body>


    <!-- ==================================
         STUDENT NAVIGATION
    =================================== -->

    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>


    <!-- ==================================
         MAIN CONTENT
    =================================== -->

    <main class="dashboard-content">


        <!-- ==================================
             PAGE HEADER
        =================================== -->

        <section class="mb-4">

            <div class="welcome-label">
                Student / Staff Portal
            </div>

            <h1 class="welcome-title">
                My Profile
            </h1>

            <p class="welcome-description">
                Manage your personal information and account details.
                Keep your LabSync account updated and accurate.
            </p>

        </section>



        <!-- ==================================
             DATABASE ERROR
        =================================== -->

        <?php if ($databaseError): ?>

            <div class="alert alert-danger profile-alert">

                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <strong>Unable to load your profile.</strong>

                Please try again later.

            </div>


        <?php else: ?>


            <!-- ==================================
                 SUCCESS MESSAGE
            =================================== -->

            <?php if ($successMessage): ?>

                <div class="alert alert-success profile-alert">

                    <i class="bi bi-check-circle-fill me-2"></i>

                    <strong>Profile Updated!</strong>

                    <?= e($successMessage) ?>

                </div>

            <?php endif; ?>



            <!-- ==================================
                 VALIDATION ERRORS
            =================================== -->

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger profile-alert">

                    <strong>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Please check the following:
                    </strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>



            <!-- ==================================
                 PROFILE LAYOUT
            =================================== -->

            <div class="profile-layout">


                <!-- ==================================
                     LEFT: PROFILE OVERVIEW
                =================================== -->

                <aside class="profile-card">


                    <!-- PROFILE AVATAR -->

                    <div class="profile-avatar">

                        <?= e($initials) ?>

                    </div>



                    <!-- STUDENT NAME -->

                    <h2 class="profile-name">

                        <?= e($fullName) ?>

                    </h2>



                    <!-- STUDENT EMAIL -->

                    <p class="profile-email mb-0">

                        <?= e($email) ?>

                    </p>



                    <!-- ACCOUNT ROLE -->

                    <span class="profile-role">

                        <i class="bi bi-person-check-fill"></i>

                        <?= e($roleLabel) ?>

                    </span>



                    <!-- DIVIDER -->

                    <div class="profile-divider"></div>



                    <!-- ACCOUNT INFORMATION NOTE -->

                    <div class="profile-note">

                        <strong>
                            Account Information
                        </strong>

                        Keep your personal details updated to ensure
                        your LabSync account information remains accurate.

                    </div>


                </aside>



                <!-- ==================================
                     RIGHT: PERSONAL INFORMATION
                =================================== -->

                <section class="information-card">


                    <!-- CARD HEADER -->

                    <div class="information-header">


                        <div>

                            <h2 class="section-heading">

                                Personal Information

                            </h2>

                            <p class="section-description mb-0">

                                Update the information associated with your account.

                            </p>

                        </div>



                        <!-- HEADER ICON -->

                        <div class="information-icon">

                            <i class="bi bi-person-vcard"></i>

                        </div>


                    </div>



                    <!-- ==================================
                         EDIT PROFILE FORM
                    =================================== -->

                    <form method="POST" action="" autocomplete="on">

                        <?= csrf_field() ?>


                        <div class="row g-3">


                            <!-- FULL NAME -->

                            <div class="col-12">

                                <label
                                    for="full_name"
                                    class="form-label"
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
                                    autocomplete="name"
                                    placeholder="Enter your full name"
                                    required
                                >

                            </div>



                            <!-- EMAIL ADDRESS -->

                            <div class="col-md-6">

                                <label
                                    for="email"
                                    class="form-label"
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
                                    autocomplete="email"
                                    placeholder="Enter your email address"
                                    required
                                >

                            </div>



                            <!-- STUDENT ID -->

                            <div class="col-md-6">

                                <label
                                    for="id_number"
                                    class="form-label"
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
                                    placeholder="Enter your student ID"
                                    required
                                >

                            </div>



                            <!-- DEPARTMENT -->

                            <div class="col-md-6">

                                <label
                                    for="department"
                                    class="form-label"
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
                                    placeholder="Enter your department"
                                    required
                                >

                            </div>



                            <!-- CONTACT NUMBER -->

                            <div class="col-md-6">

                                <label
                                    for="contact_no"
                                    class="form-label"
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
                                    autocomplete="tel"
                                    placeholder="Enter your contact number"
                                    required
                                >

                            </div>



                            <!-- ACCOUNT ROLE -->

                            <div class="col-12">

                                <label
                                    for="account_role"
                                    class="form-label"
                                >

                                    Account Role

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="account_role"
                                    value="<?= e(ucfirst($profile['role'])) ?>"
                                    disabled
                                >

                                <p class="form-hint">

                                    Your account role cannot be changed from this page.

                                </p>

                            </div>


                        </div>



                        <!-- ==================================
                             FORM ACTION BUTTONS
                        =================================== -->

                        <div class="form-actions">


                            <!-- SAVE CHANGES -->

                            <button
                                type="submit"
                                class="save-button"
                            >

                                <i class="bi bi-check2-circle"></i>

                                Save Changes

                            </button>



                            <!-- CANCEL -->

                            <a
                                href="dashboard.php"
                                class="cancel-button"
                            >

                                <i class="bi bi-arrow-left"></i>

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
