
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();

// ==========================================
// DASHBOARD STATISTICS
// ==========================================

// Total registered users
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE is_active = 1
");
$totalUsers = (int) $stmt->fetchColumn();

// Total computer units
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM computer_units
");
$totalUnits = (int) $stmt->fetchColumn();

// Total issue reports
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM issue_reports
");
$totalReports = (int) $stmt->fetchColumn();

// Issue report statistics
$stmt = $pdo->query("
    SELECT
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status IN ('Under Inspection', 'Under Repair') THEN 1 ELSE 0 END) AS ongoing,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) AS unresolved
    FROM issue_reports
");

$issueStats = $stmt->fetch(PDO::FETCH_ASSOC);

$pendingReports = (int) ($issueStats['pending'] ?? 0);
$ongoingReports = (int) ($issueStats['ongoing'] ?? 0);
$resolvedReports = (int) ($issueStats['resolved'] ?? 0);
$unresolvedReports = (int) ($issueStats['unresolved'] ?? 0);

// Total maintenance records
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM maintenance_records
");
$totalMaintenance = (int) $stmt->fetchColumn();

// ==========================================
// RECENT ISSUE REPORTS
// ==========================================

$stmt = $pdo->query("
    SELECT
        r.report_id,
        r.reference_no,
        r.status,
        r.created_at,
        reporter.full_name AS reporter_name,
        c.unit_code,
        cat.category_name

    FROM issue_reports r

    INNER JOIN users reporter
        ON r.reporter_id = reporter.user_id

    INNER JOIN computer_units c
        ON r.unit_id = c.unit_id

    INNER JOIN issue_categories cat
        ON r.category_id = cat.category_id

    ORDER BY r.created_at DESC, r.report_id DESC
    LIMIT 5
");

$recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status badge styling
function adminStatusClass(string $status): string
{
    switch ($status) {
        case 'Pending':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Under Inspection':
            return 'bg-info-subtle text-info-emphasis';

        case 'Under Repair':
            return 'bg-primary-subtle text-primary';

        case 'Resolved':
            return 'bg-success-subtle text-success';

        case 'Unresolved':
            return 'bg-danger-subtle text-danger';

        default:
            return 'bg-secondary-subtle text-secondary';
    }
}

// Get administrator name
$adminName = $_SESSION['full_name'] ?? 'Administrator';

$stmt = $pdo->prepare("
    SELECT full_name
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'user_id' => $_SESSION['user_id']
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $adminName = $admin['full_name'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrator Dashboard | PCare</title>

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

        .stat-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 14px;
            padding: 22px;
            height: 100%;
            transition: 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(36, 91, 84, 0.07);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .section-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 14px;
            overflow: hidden;
        }

        .table th {
            color: #64736E;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            white-space: nowrap;
        }

        .table td {
            padding: 16px;
            vertical-align: middle;
        }

        .report-link {
            color: #245B54;
            font-weight: 700;
            text-decoration: none;
        }

        .report-link:hover {
            text-decoration: underline;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 12px;
            color: #303C39;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .quick-link:hover {
            border-color: #4E968B;
            background: #F5FAF8;
            transform: translateY(-2px);
        }

        .quick-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #E5F0ED;
            color: #245B54;
            font-size: 19px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 14px;
            }
        }

    </style>

</head>

<body>

<div class="container-fluid main-content">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <p class="text-secondary small mb-1">
                PCARE / ADMINISTRATOR
            </p>

            <h2 class="page-title mb-1">
                Administrator Dashboard
            </h2>

            <p class="text-secondary mb-0">
                Welcome back, <?= e($adminName) ?>.
                Here's an overview of the PCare system.
            </p>

        </div>

        <a href="../logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right me-1"></i>
            Logout
        </a>

    </div>

    <!-- Main Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <p class="text-secondary small mb-1">Active Users</p>
                        <h3 class="fw-bold mb-0"><?= $totalUsers ?></h3>
                    </div>

                    <div class="stat-icon" style="background:#E5F0ED;color:#245B54;">
                        <i class="bi bi-people"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <p class="text-secondary small mb-1">Computer Units</p>
                        <h3 class="fw-bold mb-0"><?= $totalUnits ?></h3>
                    </div>

                    <div class="stat-icon bg-info-subtle text-info-emphasis">
                        <i class="bi bi-pc-display"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <p class="text-secondary small mb-1">Issue Reports</p>
                        <h3 class="fw-bold mb-0"><?= $totalReports ?></h3>
                    </div>

                    <div class="stat-icon bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-clipboard-data"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <p class="text-secondary small mb-1">Maintenance Records</p>
                        <h3 class="fw-bold mb-0"><?= $totalMaintenance ?></h3>
                    </div>

                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-tools"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Issue Status Overview -->
    <div class="mb-4">

        <h5 class="fw-bold mb-3">
            Issue Status Overview
        </h5>

        <div class="row g-3">

            <div class="col-6 col-lg-3">

                <div class="stat-card">

                    <p class="text-secondary small mb-1">
                        Pending
                    </p>

                    <h3 class="fw-bold text-warning mb-0">
                        <?= $pendingReports ?>
                    </h3>

                </div>

            </div>

            <div class="col-6 col-lg-3">

                <div class="stat-card">

                    <p class="text-secondary small mb-1">
                        Ongoing
                    </p>

                    <h3 class="fw-bold text-primary mb-0">
                        <?= $ongoingReports ?>
                    </h3>

                </div>

            </div>

            <div class="col-6 col-lg-3">

                <div class="stat-card">

                    <p class="text-secondary small mb-1">
                        Resolved
                    </p>

                    <h3 class="fw-bold text-success mb-0">
                        <?= $resolvedReports ?>
                    </h3>

                </div>

            </div>

            <div class="col-6 col-lg-3">

                <div class="stat-card">

                    <p class="text-secondary small mb-1">
                        Unresolved
                    </p>

                    <h3 class="fw-bold text-danger mb-0">
                        <?= $unresolvedReports ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <!-- Quick Access -->
    <div class="mb-4">

        <h5 class="fw-bold mb-3">
            Quick Access
        </h5>

        <div class="row g-3">

            <div class="col-12 col-sm-6 col-lg-3">

                <a href="users.php" class="quick-link">

                    <div class="quick-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">User Management</div>
                        <small class="text-secondary">Manage accounts</small>
                    </div>

                </a>

            </div>

            <div class="col-12 col-sm-6 col-lg-3">

                <a href="computer-units.php" class="quick-link">

                    <div class="quick-icon">
                        <i class="bi bi-pc-display"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">Computer Units</div>
                        <small class="text-secondary">Manage units</small>
                    </div>

                </a>

            </div>

            <div class="col-12 col-sm-6 col-lg-3">

                <a href="categories.php" class="quick-link">

                    <div class="quick-icon">
                        <i class="bi bi-grid"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">Issue Categories</div>
                        <small class="text-secondary">Manage categories</small>
                    </div>

                </a>

            </div>

            <div class="col-12 col-sm-6 col-lg-3">

                <a href="analytics.php" class="quick-link">

                    <div class="quick-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">Reports & Analytics</div>
                        <small class="text-secondary">View system data</small>
                    </div>

                </a>

            </div>

        </div>

    </div>

    <!-- Recent Issue Reports -->
    <div class="section-card">

        <div class="p-3 p-md-4 border-bottom">

            <h5 class="fw-bold mb-1">
                Recent Issue Reports
            </h5>

            <p class="text-secondary small mb-0">
                The five most recently submitted computer issue reports.
            </p>

        </div>

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">

                    <tr>
                        <th>Reference No.</th>
                        <th>Reporter</th>
                        <th>Computer Unit</th>
                        <th>Category</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($recentReports)): ?>

                        <tr>
                            <td colspan="6" class="text-center py-5">

                                <i class="bi bi-inbox fs-1 text-secondary"></i>

                                <p class="text-secondary mt-2 mb-0">
                                    No issue reports have been submitted yet.
                                </p>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentReports as $report): ?>

                            <tr>

                                <td>
                                    <a
                                        href="issue-reports.php"
                                        class="report-link"
                                    >
                                        <?= e($report['reference_no']) ?>
                                    </a>
                                </td>

                                <td>
                                    <?= e($report['reporter_name']) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= e($report['unit_code']) ?>
                                </td>

                                <td>
                                    <?= e($report['category_name']) ?>
                                </td>

                                <td class="text-nowrap">
                                    <?= e(date('M d, Y h:i A', strtotime($report['created_at']))) ?>
                                </td>

                                <td>
                                    <span class="badge rounded-pill <?= adminStatusClass($report['status']) ?>">
                                        <?= e($report['status']) ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="p-3 border-top">

            <a href="issue-reports.php" class="text-success text-decoration-none fw-semibold">
                View All Issue Reports
                <i class="bi bi-arrow-right ms-1"></i>
            </a>

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
