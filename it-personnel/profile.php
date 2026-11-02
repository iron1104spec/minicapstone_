
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();

$userId = (int) $_SESSION['user_id'];

// Retrieve the logged-in personnel's information
$stmt = $pdo->prepare("
    SELECT
        user_id,
        full_name,
        email,
        role,
        id_number,
        department,
        contact_no,
        is_active,
        created_at
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'user_id' => $userId
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('User account not found.');
}

$accountStatus = (int) $user['is_active'] === 1
    ? 'Active'
    : 'Inactive';

$initial = strtoupper(substr(trim($user['full_name']), 0, 1));

$createdDate = !empty($user['created_at'])
    ? date('F d, Y', strtotime($user['created_at']))
    : 'Not available';

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
            --muted: #7A8983;
            --border: #E8EEEB;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .topbar {
            min-height: 72px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
        }

        .topbar-inner {
            width: 100%;
            max-width: 1400px;
            margin: auto;
            padding: 0 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            color: var(--deep-green);
            text-decoration: none;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.7px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .topbar-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
        }

        .main-content {
            max-width: 1400px;
            margin: auto;
            padding: 42px 36px;
        }

        .breadcrumb-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .page-title {
            color: var(--deep-green);
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .page-description {
            color: var(--muted);
            margin: 0;
            line-height: 1.7;
        }

        .back-button {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--deep-green);
            padding: 11px 17px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .back-button:hover {
            background: var(--soft-mint);
            color: var(--deep-green);
            border-color: #C9DED7;
        }

        .profile-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(36, 91, 84, 0.035);
        }

        .profile-header {
            background: var(--deep-green);
            color: var(--white);
            padding: 34px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 50%;
            right: -55px;
            top: -115px;
        }

        .profile-header::before {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 50%;
            right: 55px;
            bottom: -105px;
        }

        .profile-header-content {
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 76px;
            height: 76px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--soft-mint);
            color: var(--deep-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 29px;
            font-weight: 800;
            border: 4px solid rgba(255,255,255,0.18);
        }

        .profile-name {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }

        .profile-role {
            color: rgba(255,255,255,0.75);
            font-size: 13px;
            margin-bottom: 12px;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.13);
            border: 1px solid rgba(255,255,255,0.18);
            color: var(--white);
            border-radius: 30px;
            padding: 6px 11px;
            font-size: 11px;
            font-weight: 600;
        }

        .profile-body {
            padding: 32px;
        }

        .section-heading {
            color: var(--deep-green);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .section-description {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 20px;
        }

        .info-item {
            min-height: 91px;
            padding: 17px 0;
            border-bottom: 1px solid #EEF2EF;
        }

        .info-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 9px;
        }

        .info-value {
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-active {
            background: #E3F3E9;
            color: #277347;
        }

        .status-inactive {
            background: #F8E8E8;
            color: #A33C3C;
        }

        .account-section {
            border-top: 1px solid var(--border);
            padding-top: 26px;
            margin-top: 28px;
        }

        .account-info {
            background: #FAFCFB;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 5px 18px;
        }

        .account-info .info-item:last-child {
            border-bottom: none;
        }

        .notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #F2F7F5;
            border: 1px solid #E0EBE6;
            border-radius: 11px;
            padding: 16px;
            margin-top: 25px;
        }

        .notice-icon {
            color: var(--primary);
            font-size: 17px;
        }

        .notice p {
            color: #61716A;
            font-size: 12px;
            line-height: 1.7;
            margin: 0;
        }

        .page-footer {
            color: #9AA59F;
            font-size: 11px;
            text-align: center;
            padding: 25px 0 5px;
        }

        @media (max-width: 768px) {

            .topbar-inner {
                padding: 0 18px;
            }

            .topbar-label {
                display: none;
            }

            .main-content {
                padding: 30px 16px;
            }

            .page-title {
                font-size: 23px;
            }

            .profile-header {
                padding: 25px 21px;
            }

            .profile-body {
                padding: 23px 20px;
            }

            .profile-avatar {
                width: 65px;
                height: 65px;
                font-size: 25px;
            }

            .profile-name {
                font-size: 18px;
            }

        }

    </style>

</head>

<body>

    <!-- Top Navigation -->
    <header class="topbar">

        <div class="topbar-inner">

            <a href="dashboard.php" class="brand">

                <span class="brand-icon">
                    <i class="bi bi-pc-display-horizontal"></i>
                </span>

                LabSync

            </a>

            <span class="topbar-label">
                IT PERSONNEL PORTAL
            </span>

        </div>

    </header>


    <main class="main-content">

        <!-- Page Heading -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>

                <p class="breadcrumb-label">
                    IT Personnel / Account
                </p>

                <h1 class="page-title">
                    My Profile
                </h1>

                <p class="page-description">
                    View your personal information and LabSync account details.
                </p>

            </div>

            <a href="dashboard.php" class="back-button">

                <i class="bi bi-arrow-left me-2"></i>
                Back to Dashboard

            </a>

        </div>


        <!-- Profile Card -->
        <div class="row">

            <div class="col-12 col-xl-9">

                <section class="profile-card">

                    <!-- Profile Header -->
                    <div class="profile-header">

                        <div class="profile-header-content d-flex flex-wrap align-items-center gap-3">

                            <div class="profile-avatar">
                                <?= e($initial) ?>
                            </div>

                            <div>

                                <h2 class="profile-name">
                                    <?= e($user['full_name']) ?>
                                </h2>

                                <p class="profile-role">
                                    IT Personnel
                                </p>

                                <span class="role-badge">

                                    <i class="bi bi-shield-check"></i>

                                    <?= e(ucfirst($user['role'])) ?> Account

                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Profile Body -->
                    <div class="profile-body">

                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">

                            <div>

                                <h3 class="section-heading">
                                    Personal Information
                                </h3>

                                <p class="section-description">
                                    Your registered account information.
                                </p>

                            </div>

                            <span class="status-badge <?= (int) $user['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>">

                                <i class="bi bi-circle-fill" style="font-size: 7px;"></i>

                                <?= e($accountStatus) ?> Account

                            </span>

                        </div>


                        <div class="row">

                            <div class="col-12 col-md-6">

                                <div class="info-item">

                                    <p class="info-label">Full Name</p>

                                    <p class="info-value">
                                        <?= e($user['full_name']) ?>
                                    </p>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="info-item">

                                    <p class="info-label">Email Address</p>

                                    <p class="info-value">
                                        <?= e($user['email']) ?>
                                    </p>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="info-item">

                                    <p class="info-label">ID Number</p>

                                    <p class="info-value">
                                        <?= !empty($user['id_number']) ? e($user['id_number']) : 'Not provided' ?>
                                    </p>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="info-item">

                                    <p class="info-label">Department</p>

                                    <p class="info-value">
                                        <?= !empty($user['department']) ? e($user['department']) : 'Not provided' ?>
                                    </p>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="info-item">

                                    <p class="info-label">Contact Number</p>

                                    <p class="info-value">
                                        <?= !empty($user['contact_no']) ? e($user['contact_no']) : 'Not provided' ?>
                                    </p>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="info-item">

                                    <p class="info-label">Account Role</p>

                                    <p class="info-value">
                                        <?= e(ucfirst($user['role'])) ?>
                                    </p>

                                </div>

                            </div>

                        </div>


                        <!-- Account Details -->
                        <div class="account-section">

                            <h3 class="section-heading">
                                Account Details
                            </h3>

                            <p class="section-description">
                                Additional information about your account.
                            </p>


                            <div class="account-info">

                                <div class="info-item">

                                    <p class="info-label">User ID</p>

                                    <p class="info-value">
                                        #<?= (int) $user['user_id'] ?>
                                    </p>

                                </div>


                                <div class="info-item">

                                    <p class="info-label">Account Created</p>

                                    <p class="info-value">
                                        <?= e($createdDate) ?>
                                    </p>

                                </div>


                                <div class="info-item">

                                    <p class="info-label">Account Status</p>

                                    <p class="info-value">
                                        <?= e($accountStatus) ?>
                                    </p>

                                </div>

                            </div>

                        </div>


                        <!-- Information Notice -->
                        <div class="notice">

                            <i class="bi bi-info-circle notice-icon"></i>

                            <p>
                                Your account information is currently view-only.
                                If any information needs correction, please contact
                                the system administrator.
                            </p>

                        </div>

                    </div>

                </section>

            </div>

        </div>


        <!-- Footer -->
        <footer class="page-footer">

            LabSync · Computer Laboratories · SLSU Lucena Campus

        </footer>

    </main>

</body>
</html>
