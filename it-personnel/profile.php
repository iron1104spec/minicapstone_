
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

// Account status
$accountStatus = (int) $user['is_active'] === 1
    ? 'Active'
    : 'Inactive';

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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #F7F8F6;
            color: #303C39;
            font-family: Arial, sans-serif;
        }

        .main-content {
            padding: 35px;
        }

        .page-title {
            color: #245B54;
            font-weight: 700;
        }

        .profile-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 16px;
            overflow: hidden;
        }

        .profile-header {
            background: #245B54;
            color: #FFFFFF;
            padding: 30px;
        }

        .profile-avatar {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: #E5F0ED;
            color: #245B54;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 700;
        }

        .profile-body {
            padding: 30px;
        }

        .info-label {
            color: #718079;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 7px;
        }

        .info-value {
            font-weight: 600;
            color: #303C39;
            margin-bottom: 0;
            overflow-wrap: anywhere;
        }

        .info-item {
            padding: 17px 0;
            border-bottom: 1px solid #EEF1EF;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .status-active {
            background: #DDF2E5;
            color: #207044;
        }

        .status-inactive {
            background: #F1E5E5;
            color: #9A3636;
        }

        @media (max-width: 768px) {

            .main-content {
                padding: 20px 14px;
            }

            .profile-body {
                padding: 20px;
            }

        }

    </style>

</head>

<body>

<div class="container-fluid main-content">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <p class="text-secondary small mb-1">
                IT PERSONNEL / ACCOUNT
            </p>

            <h2 class="page-title mb-1">
                My Profile
            </h2>

            <p class="text-secondary mb-0">
                View your PCare account information.
            </p>

        </div>

        <a href="dashboard.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Dashboard
        </a>

    </div>

    <!-- Profile Card -->
    <div class="row">

        <div class="col-12 col-xl-8">

            <div class="profile-card">

                <!-- Profile Header -->
                <div class="profile-header">

                    <div class="d-flex flex-wrap align-items-center gap-3">

                        <div class="profile-avatar">

                            <?= e(strtoupper(substr($user['full_name'], 0, 1))) ?>

                        </div>

                        <div>

                            <h4 class="fw-bold mb-1">
                                <?= e($user['full_name']) ?>
                            </h4>

                            <p class="mb-2 opacity-75">
                                IT Personnel
                            </p>

                            <span class="badge rounded-pill bg-light text-success">
                                <i class="bi bi-shield-check me-1"></i>
                                <?= e(ucfirst($user['role'])) ?> Account
                            </span>

                        </div>

                    </div>

                </div>

                <!-- Profile Information -->
                <div class="profile-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="fw-bold mb-0">
                            Personal Information
                        </h5>

                        <span class="badge rounded-pill <?= (int) $user['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>">
                            <?= e($accountStatus) ?> Account
                        </span>

                    </div>

                    <div class="row">

                        <div class="col-12 col-md-6">

                            <div class="info-item">

                                <p class="info-label">
                                    Full Name
                                </p>

                                <p class="info-value">
                                    <?= e($user['full_name']) ?>
                                </p>

                            </div>

                        </div>

                        <div class="col-12 col-md-6">

                            <div class="info-item">

                                <p class="info-label">
                                    Email Address
                                </p>

                                <p class="info-value">
                                    <?= e($user['email']) ?>
                                </p>

                            </div>

                        </div>

                        <div class="col-12 col-md-6">

                            <div class="info-item">

                                <p class="info-label">
                                    ID Number
                                </p>

                                <p class="info-value">
                                    <?= !empty($user['id_number']) ? e($user['id_number']) : 'Not provided' ?>
                                </p>

                            </div>

                        </div>

                        <div class="col-12 col-md-6">

                            <div class="info-item">

                                <p class="info-label">
                                    Department
                                </p>

                                <p class="info-value">
                                    <?= !empty($user['department']) ? e($user['department']) : 'Not provided' ?>
                                </p>

                            </div>

                        </div>

                        <div class="col-12 col-md-6">

                            <div class="info-item">

                                <p class="info-label">
                                    Contact Number
                                </p>

                                <p class="info-value">
                                    <?= !empty($user['contact_no']) ? e($user['contact_no']) : 'Not provided' ?>
                                </p>

                            </div>

                        </div>

                        <div class="col-12 col-md-6">

                            <div class="info-item">

                                <p class="info-label">
                                    Account Role
                                </p>

                                <p class="info-value">
                                    <?= e(ucfirst($user['role'])) ?>
                                </p>

                            </div>

                        </div>

                    </div>

                    <!-- Account Details -->
                    <div class="border-top pt-4 mt-4">

                        <h5 class="fw-bold mb-3">
                            Account Details
                        </h5>

                        <div class="info-item">

                            <p class="info-label">
                                User ID
                            </p>

                            <p class="info-value">
                                #<?= (int) $user['user_id'] ?>
                            </p>

                        </div>

                        <div class="info-item">

                            <p class="info-label">
                                Account Created
                            </p>

                            <p class="info-value">

                                <?= $user['created_at']
                                    ? e(date('F d, Y', strtotime($user['created_at'])))
                                    : 'Not available'
                                ?>

                            </p>

                        </div>

                        <div class="info-item">

                            <p class="info-label">
                                Account Status
                            </p>

                            <p class="info-value">
                                <?= e($accountStatus) ?>
                            </p>

                        </div>

                    </div>

                    <div class="alert alert-light border mt-4 mb-0">

                        <i class="bi bi-info-circle me-2 text-success"></i>

                        Your account information is currently view-only.
                        Contact the system administrator if your information needs correction.

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="text-center mt-4">

        <small class="text-secondary">
            PCare · B204 Computer Laboratory
        </small>

    </div>

</div>

</body>
</html>
