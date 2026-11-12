<?php
// it-personnel/profile.php
require_once __DIR__ . '/profile-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | LabSync</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-profile.css">
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
                                    <p class="info-value"><?= e($user['full_name']) ?></p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="info-item">
                                    <p class="info-label">Email Address</p>
                                    <p class="info-value"><?= e($user['email']) ?></p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="info-item">
                                    <p class="info-label">ID Number</p>
                                    <p class="info-value"><?= !empty($user['id_number']) ? e($user['id_number']) : 'Not provided' ?></p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="info-item">
                                    <p class="info-label">Department</p>
                                    <p class="info-value"><?= !empty($user['department']) ? e($user['department']) : 'Not provided' ?></p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="info-item">
                                    <p class="info-label">Contact Number</p>
                                    <p class="info-value"><?= !empty($user['contact_no']) ? e($user['contact_no']) : 'Not provided' ?></p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="info-item">
                                    <p class="info-label">Account Role</p>
                                    <p class="info-value"><?= e(ucfirst($user['role'])) ?></p>
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
                                    <p class="info-value">#<?= (int) $user['user_id'] ?></p>
                                </div>
                                <div class="info-item">
                                    <p class="info-label">Account Created</p>
                                    <p class="info-value"><?= e($createdDate) ?></p>
                                </div>
                                <div class="info-item">
                                    <p class="info-label">Account Status</p>
                                    <p class="info-value"><?= e($accountStatus) ?></p>
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