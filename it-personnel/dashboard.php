
<?php

// Load authentication and CSRF protection.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Only active IT Personnel accounts can access this page.
require_role('personnel');

// Get the current personnel information.
$fullName = $_SESSION['full_name'] ?? 'IT Personnel';

// Connect to the database.
$pdo = db();

// Initialize dashboard values.
$totalReports = 0;
$pendingReports = 0;
$ongoingReports = 0;
$resolvedReports = 0;
$unresolvedReports = 0;

$recentReports = [];
$databaseError = false;

try {

    // 1. Get the overall issue report statistics.
    // Unlike the Student Dashboard, this includes ALL users' reports.

    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_reports,

            SUM(
                CASE WHEN status = 'Pending'
                THEN 1 ELSE 0 END
            ) AS pending_reports,

            SUM(
                CASE WHEN status IN (
                    'Under Inspection',
                    'Under Repair'
                )
                THEN 1 ELSE 0 END
            ) AS ongoing_reports,

            SUM(
                CASE WHEN status = 'Resolved'
                THEN 1 ELSE 0 END
            ) AS resolved_reports,

            SUM(
                CASE WHEN status = 'Unresolved'
                THEN 1 ELSE 0 END
            ) AS unresolved_reports

        FROM issue_reports
    ");

    $statistics = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalReports = (int) $statistics['total_reports'];
    $pendingReports = (int) $statistics['pending_reports'];
    $ongoingReports = (int) $statistics['ongoing_reports'];
    $resolvedReports = (int) $statistics['resolved_reports'];
    $unresolvedReports = (int) $statistics['unresolved_reports'];


    // 2. Get the five most recent issue reports.
    // Include the reporter, computer unit, category, and assigned personnel.

    $stmt = $pdo->query("
        SELECT
            r.report_id,
            r.reference_no,
            r.description,
            r.status,
            r.created_at,

            reporter.full_name AS reporter_name,

            c.unit_code,

            cat.category_name,

            assigned.full_name AS assigned_name

        FROM issue_reports AS r

        INNER JOIN users AS reporter
            ON r.reporter_id = reporter.user_id

        INNER JOIN computer_units AS c
            ON r.unit_id = c.unit_id

        INNER JOIN issue_categories AS cat
            ON r.category_id = cat.category_id

        LEFT JOIN users AS assigned
            ON r.assigned_to = assigned.user_id

        ORDER BY r.created_at DESC, r.report_id DESC

        LIMIT 5
    ");

    $recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    // Record the actual error in the PHP error log.
    error_log($e->getMessage());

    // Display a friendly error message.
    $databaseError = true;
}


// Bootstrap status badge colors.
$statusClasses = [
    'Pending' => 'bg-warning text-dark',
    'Under Inspection' => 'bg-info text-dark',
    'Under Repair' => 'bg-primary',
    'Resolved' => 'bg-success',
    'Unresolved' => 'bg-danger'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>IT Personnel Dashboard | PCare</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <!-- MAIN NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark"
         style="background-color: #245B54;">

        <div class="container">

            <a class="navbar-brand fw-bold" href="dashboard.php">
                PCare | IT Personnel
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#personnelNavbar"
                aria-controls="personnelNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="personnelNavbar">

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="issue-reports.php">
                            Issue Reports
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="maintenance-records.php">
                            Maintenance Records
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            Logout
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>


    <!-- MAIN DASHBOARD CONTENT -->
    <main class="container py-5">

        <!-- WELCOME SECTION -->
        <div class="mb-4">

            <h2>
                Welcome, <?= e($fullName) ?>!
            </h2>

            <p class="text-muted">
                Welcome to your PCare IT Personnel Dashboard.
                Monitor and manage computer issue reports
                from the B204 Computer Laboratory.
            </p>

        </div>


        <!-- DATABASE ERROR MESSAGE -->
        <?php if ($databaseError): ?>

            <div class="alert alert-danger">

                Unable to load dashboard information.
                Please check the database connection and try again.

            </div>

        <?php else: ?>


            <!-- ISSUE REPORT STATISTICS -->
            <h4 class="mb-3">
                Issue Report Overview
            </h4>

            <div class="row g-3 mb-5">


                <!-- TOTAL REPORTS -->
                <div class="col-md-4 col-lg">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Total Reports
                            </p>

                            <h2 class="fw-bold">
                                <?= $totalReports ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <!-- PENDING REPORTS -->
                <div class="col-md-4 col-lg">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Pending
                            </p>

                            <h2 class="fw-bold text-warning">
                                <?= $pendingReports ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <!-- ONGOING REPORTS -->
                <div class="col-md-4 col-lg">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Ongoing
                            </p>

                            <h2 class="fw-bold text-primary">
                                <?= $ongoingReports ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <!-- RESOLVED REPORTS -->
                <div class="col-md-4 col-lg">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Resolved
                            </p>

                            <h2 class="fw-bold text-success">
                                <?= $resolvedReports ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <!-- UNRESOLVED REPORTS -->
                <div class="col-md-4 col-lg">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Unresolved
                            </p>

                            <h2 class="fw-bold text-danger">
                                <?= $unresolvedReports ?>
                            </h2>

                        </div>

                    </div>

                </div>

            </div>


            <!-- RECENT ISSUE REPORTS -->
            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h4 class="mb-1">
                        Recent Issue Reports
                    </h4>

                    <p class="text-muted mb-0">
                        Latest computer problems submitted by users.
                    </p>

                </div>

                <a
                    href="issue-reports.php"
                    class="btn btn-outline-success btn-sm"
                >
                    View All Reports
                </a>

            </div>


            <!-- REPORTS TABLE -->
            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <?php if (empty($recentReports)): ?>

                        <div class="text-center py-5">

                            <h5>No Issue Reports Yet</h5>

                            <p class="text-muted mb-0">
                                There are currently no computer issue
                                reports recorded in the system.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle">

                                <thead class="table-light">

                                    <tr>

                                        <th>Reference No.</th>
                                        <th>Reported By</th>
                                        <th>Computer Unit</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Assigned Personnel</th>
                                        <th>Date Submitted</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($recentReports as $report): ?>

                                        <tr>

                                            <td class="fw-semibold">

                                                <?= e($report['reference_no']) ?>

                                            </td>

                                            <td>

                                                <?= e($report['reporter_name']) ?>

                                            </td>

                                            <td>

                                                <?= e($report['unit_code']) ?>

                                            </td>

                                            <td>

                                                <?= e($report['category_name']) ?>

                                            </td>

                                            <td>

                                                <span class="badge rounded-pill <?= e(
                                                    $statusClasses[$report['status']] ?? 'bg-secondary'
                                                ) ?>">

                                                    <?= e($report['status']) ?>

                                                </span>

                                            </td>

                                            <td>

                                                <?= e(
                                                    $report['assigned_name'] ?? 'Not Assigned'
                                                ) ?>

                                            </td>

                                            <td>

                                                <?= e(
                                                    date(
                                                        'M d, Y',
                                                        strtotime($report['created_at'])
                                                    )
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </main>


    <!-- Bootstrap JavaScript -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

</body>
</html>
