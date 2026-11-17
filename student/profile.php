<?php
// student/profile.php

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
        SELECT user_id, full_name, email, id_number, department, contact_no, role
        FROM users WHERE user_id = :user_id LIMIT 1
    ");
<<<<<<< HEAD
    $stmt->execute(['user_id' => $userId]);
=======

    $stmt->execute([
        'user_id' => $userId
    ]);

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
    $profile = $stmt->fetch();

    if (!$profile) { throw new RuntimeException('Student account not found.'); }

    $fullName = $profile['full_name'];
    $email = $profile['email'];
    $idNumber = $profile['id_number'];
    $department = $profile['department'];
    $contactNo = $profile['contact_no'];

} catch (Throwable $e) {

    error_log($e->getMessage());

    $databaseError = true;
}

<<<<<<< HEAD
=======

// ==========================================
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
// PROCESS PROFILE UPDATE
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid request. Please refresh the page and try again.';

    } else {
<<<<<<< HEAD
=======

        // GET FORM VALUES

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');

<<<<<<< HEAD
        if ($fullName === '') $errors[] = 'Full name is required.';
        elseif (strlen($fullName) > 150) $errors[] = 'Full name must not exceed 150 characters.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        elseif (strlen($email) > 150) $errors[] = 'Email address is too long.';

        if ($idNumber === '') $errors[] = 'Student ID number is required.';
        elseif (strlen($idNumber) > 50 || !preg_match('/^[A-Za-z0-9-]+$/', $idNumber)) $errors[] = 'Please enter a valid student ID number.';

        if ($department === '') $errors[] = 'Department is required.';
        elseif (strlen($department) > 100) $errors[] = 'Department must not exceed 100 characters.';
=======

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
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60

        if ($contactNo === '') $errors[] = 'Contact number is required.';
        elseif (strlen($contactNo) > 30 || !preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)) $errors[] = 'Please enter a valid contact number.';

        // ==================================
        // CHECK DUPLICATE EMAIL / STUDENT ID
        // ==================================

        if (empty($errors)) {

            try {

                $stmt = $pdo->prepare("
<<<<<<< HEAD
                    SELECT user_id FROM users 
                    WHERE (email = :email OR id_number = :id_number) 
                    AND user_id <> :user_id LIMIT 1
                ");
                $stmt->execute(['email' => $email, 'id_number' => $idNumber, 'user_id' => $userId]);
                if ($stmt->fetch()) { $errors[] = 'Email address or student ID is already used by another account.'; }
=======
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

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
            } catch (PDOException $e) {

                error_log($e->getMessage());

                $errors[] = 'Unable to validate your account information.';

            }

        }

<<<<<<< HEAD
        // UPDATE PROFILE
=======

        // ==================================
        // UPDATE STUDENT PROFILE
        // ==================================

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
        if (empty($errors)) {

            try {

                $stmt = $pdo->prepare("
                    UPDATE users SET full_name = :full_name, email = :email, id_number = :id_number, 
                    department = :department, contact_no = :contact_no, updated_at = NOW()
                    WHERE user_id = :user_id AND role = 'student'
                ");
                $stmt->execute([
                    'full_name' => $fullName, 'email' => $email, 'id_number' => $idNumber,
                    'department' => $department, 'contact_no' => $contactNo, 'user_id' => $userId
                ]);

                // UPDATE SESSION INFORMATION

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

<<<<<<< HEAD
                flash_set('success', 'Your profile has been updated successfully.');
=======
                // SUCCESS MESSAGE

                flash_set(
                    'success',
                    'Your profile has been updated successfully.'
                );

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
                redirect('profile.php');

            } catch (PDOException $e) {

                error_log($e->getMessage());

                $errors[] = 'Unable to update your profile. Please try again.';

            }

        }

    }

}

<<<<<<< HEAD
// PROFILE INITIALS
=======

// ==========================================
// GENERATE PROFILE INITIALS
// ==========================================

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
$nameParts = preg_split('/\s+/', trim($fullName));

$initials = '';
<<<<<<< HEAD
if (!empty($nameParts[0])) { $initials .= strtoupper(substr($nameParts[0], 0, 1)); }
if (count($nameParts) > 1) { $initials .= strtoupper(substr(end($nameParts), 0, 1)); }
if ($initials === '') { $initials = 'S'; }
=======

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

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>My Profile | LabSync</title>

    <!-- Google Fonts -->
=======

    <meta name="theme-color" content="#16443D">

    <title>My Profile | LabSync</title>


    <!-- GOOGLE FONTS -->

>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<<<<<<< HEAD
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL & BACKGROUND PATTERN --- */
        body {
            background-color: #f4f7f6;
            background-image: radial-gradient(#d1dbd6 1px, transparent 1px);
            background-size: 20px 20px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        /* --- TOP HEADER --- */
        .custom-topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #ffffff; display: flex; justify-content: space-between;
            align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 9999;
        }
        .brand-area { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-icon { font-size: 24px; color: #0d2f24; }
        .brand-name { display: block; font-weight: 800; font-size: 18px; color: #0d2f24; line-height: 1; }
        .brand-caption { display: block; font-size: 9px; letter-spacing: 1px; color: #666; font-weight: 600; }

        /* --- FLOATING DOCK --- */
        .floating-dock {
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%);
            display: flex; flex-direction: row; gap: 8px; background-color: #0d2f24;
            padding: 12px 24px; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1); z-index: 9999;
            max-width: 95%; overflow-x: auto;
        }
        .floating-dock::-webkit-scrollbar { display: none; }
        .dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #a0b8b0; text-decoration: none; padding: 10px 20px;
            border-radius: 16px; transition: all 0.3s ease; white-space: nowrap;
        }
        .dock-item i { font-size: 20px; margin-bottom: 4px; }
        .dock-item span { font-size: 11px; font-weight: 500; }
        .dock-item:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; transform: translateY(-4px); }
        .dock-item.active { background-color: #1a4a3a; color: #ffffff; }

        /* --- MAIN CONTENT SPACING --- */
        .main-wrapper {
            padding-top: 90px; 
            padding-bottom: 120px; 
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding-left: 30px;
            padding-right: 30px;
        }

        /* --- HERO BANNER --- */
        .welcome-section {
            background: linear-gradient(135deg, #0f3d2e 0%, #1a5c47 100%);
            border-radius: 20px;
            padding: 40px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(13, 47, 36, 0.15);
            margin-bottom: 2rem;
        }
        .welcome-section::before {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 300px; height: 300px; border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
        .welcome-label {
            display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
            background: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 20px;
            margin-bottom: 15px; text-transform: uppercase;
        }
        .welcome-title { font-size: 32px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px; }
        .welcome-description { font-size: 14px; color: rgba(255, 255, 255, 0.8); max-width: 600px; line-height: 1.6; margin: 0; }

        /* --- LAYOUT GRID --- */
        .profile-layout {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        /* --- CARDS (Gaya sa Admin) --- */
        .content-card {
            background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-top: 4px solid #1a4a3a;
            overflow: hidden; padding: 30px;
        }

        /* Profile Sidebar Specific */
        .profile-card { text-align: center; }
        .profile-avatar {
            width: 80px; height: 80px; display: grid; place-items: center;
            margin: 0 auto 17px; border-radius: 20px; background: #e6f4ea; 
            color: #0f3d2e; font-size: 28px; font-weight: 800;
        }
        .profile-name { color: #0f3d2e; font-size: 18px; font-weight: 800; margin-bottom: 6px; overflow-wrap: anywhere; }
        .profile-email { color: #64748b; font-size: 13px; overflow-wrap: anywhere; margin-bottom: 15px;}
        .profile-role {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
            border-radius: 20px; background: #e6f4ea; color: #0f3d2e; font-size: 11px; font-weight: 700;
        }
        .profile-divider { height: 1px; background: #e2e8f0; margin: 24px 0; }
        .profile-note { color: #64748b; font-size: 12px; line-height: 1.8; text-align: left; }

        /* Info Form Specific */
        .information-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .section-heading { color: #0d2f24; font-size: 18px; font-weight: 700; margin: 0 0 5px 0; }
        .section-description { color: #64748b; font-size: 13px; margin: 0; }
        .information-icon {
            width: 45px; height: 45px; display: grid; place-items: center;
            border-radius: 12px; background: #e6f4ea; color: #0f3d2e; font-size: 20px;
        }

        /* Form Controls */
        .form-label { color: #1e293b; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-control {
            min-height: 45px; padding: 12px 15px; border: 1px solid #cbd5e1; 
            border-radius: 8px; background: #f8fafc; color: #1e293b; font-size: 14px;
        }
        .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25); background: #ffffff;}
        .form-control:disabled { background: #e2e8f0; color: #64748b; }
        .form-hint { color: #64748b; font-size: 11px; margin-top: 7px; }

        /* Actions */
        .form-actions { display: flex; flex-wrap: wrap; gap: 12px; padding-top: 24px; margin-top: 24px; border-top: 1px solid #e2e8f0; }
        .btn-save { 
            background: #0f3d2e; color: white; border: none; font-weight: 600; font-size: 13px;
            padding: 12px 24px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
        }
        .btn-save:hover { background: #1a5c47; transform: translateY(-2px); color: white; }
        .btn-cancel { 
            background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600; font-size: 13px;
            padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center;
        }
        .btn-cancel:hover { background: #e2e8f0; color: #1e293b; }

        /* Alerts */
        .custom-alert { padding: 15px 20px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; }

        @media (max-width: 991px) {
            .profile-layout { grid-template-columns: 1fr; }
            .main-wrapper { padding: 90px 20px 120px; }
        }
    </style>
</head>
<body>

<!-- TOP HEADER -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">STUDENT PORTAL</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px; font-weight: 500;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                <?= e($initials) ?>
=======
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
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2; color: #1a1a1a;"><?= e($fullName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;">Student</div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    
    <!-- HERO BANNER -->
    <section class="welcome-section">
        <div style="position: relative; z-index: 2;">
            <div class="welcome-label">STUDENT / STAFF PORTAL</div>
            <h1 class="welcome-title">My Profile</h1>
            <p class="welcome-description">
<<<<<<< HEAD
                Manage your personal information and account details. Keep your information updated for accurate records.
=======
                Manage your personal information and account details.
                Keep your LabSync account updated and accurate.
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
            </p>
        </div>
    </section>

    <!-- NOTIFICATIONS -->
    <?php if ($databaseError): ?>
        <div class="alert alert-danger custom-alert">
            <i class="bi bi-exclamation-circle me-2"></i> Unable to load your profile information. Please try again later.
        </div>
    <?php else: ?>

<<<<<<< HEAD
        <?php if ($successMessage): ?>
            <div class="alert alert-success custom-alert">
                <i class="bi bi-check-circle me-2"></i> <?= e($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger custom-alert">
                <strong><i class="bi bi-exclamation-triangle me-2"></i> Please check the following:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- PROFILE GRID LAYOUT -->
        <div class="profile-layout">
            
            <!-- LEFT: PROFILE OVERVIEW -->
            <aside class="content-card profile-card">
                <div class="profile-avatar">
                    <?= e($initials) ?>
                </div>
                <h2 class="profile-name"><?= e($fullName) ?></h2>
                <p class="profile-email"><?= e($email) ?></p>
                
                <span class="profile-role">
                    <i class="bi bi-person-check-fill"></i> Student / Staff
                </span>

                <div class="profile-divider"></div>

                <div class="profile-note">
                    <strong class="d-block mb-2" style="color: #0f3d2e;">Account Information</strong>
                    Keep your personal details updated to ensure your LabSync account information remains accurate.
                </div>
            </aside>

            <!-- RIGHT: EDIT FORM -->
            <section class="content-card">
                <div class="information-header">
                    <div>
                        <h2 class="section-heading">Personal Information</h2>
                        <p class="section-description">Update the information associated with your account.</p>
                    </div>
                    <div class="information-icon">
                        <i class="bi bi-person-vcard"></i>
                    </div>
                </div>

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($fullName) ?>" maxlength="150" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" maxlength="150" required>
                        </div>

                        <div class="col-md-6">
                            <label for="id_number" class="form-label">Student ID Number</label>
                            <input type="text" class="form-control" id="id_number" name="id_number" value="<?= e($idNumber) ?>" maxlength="50" required>
                        </div>

                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?= e($department) ?>" maxlength="100" required>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_no" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="contact_no" name="contact_no" value="<?= e($contactNo) ?>" maxlength="30" required>
                        </div>

                        <div class="col-12 mt-4">
                            <label class="form-label">Account Role</label>
                            <input type="text" class="form-control" value="Student" disabled>
                            <p class="form-hint">Your account role cannot be changed from this page.</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-check2-circle"></i> Save Changes
                        </button>
                        <a href="dashboard.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </section>
        </div>

    <?php endif; ?>
</div>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="report-issue.php" class="dock-item">
        <i class="bi bi-plus-circle"></i><span>Report an Issue</span>
    </a>
    <a href="my-reports.php" class="dock-item">
        <i class="bi bi-file-earmark-text"></i><span>My Reports</span>
    </a>
    <a href="profile.php" class="dock-item active">
        <i class="bi bi-person-circle"></i><span>My Profile</span>
    </a>
</nav>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
=======


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
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60


</body>
<<<<<<< HEAD
</html>
=======

</html>
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
