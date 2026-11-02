
<?php

// Load authentication, database, and CSRF protection.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Only logged-in student accounts can access this page.
require_role('student');

// Get the current student's information.
$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Student';

// Connect to the database.
$pdo = db();

// Initialize dashboard values.
$totalReports = 0;
$pendingReports = 0;
$ongoingReports = 0;
$resolvedReports = 0;
$recentReports = [];
$databaseError = false;

try {

    // 1. Count all reports submitted by this student.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
    ");

    $stmt->execute(['user_id' => $userId]);
    $totalReports = (int) $stmt->fetchColumn();


    // 2. Count pending reports.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
        AND status = 'Pending'
    ");

    $stmt->execute(['user_id' => $userId]);
    $pendingReports = (int) $stmt->fetchColumn();


    // 3. Count ongoing reports.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
        AND status IN ('Under Inspection', 'Under Repair')
    ");

    $stmt->execute(['user_id' => $userId]);
    $ongoingReports = (int) $stmt->fetchColumn();


    // 4. Count resolved reports.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
        AND status = 'Resolved'
    ");

    $stmt->execute(['user_id' => $userId]);
    $resolvedReports = (int) $stmt->fetchColumn();


    // 5. Get the student's five most recent reports.
    $stmt = $pdo->prepare("
        SELECT
            r.report_id,
            r.reference_no,
            r.description,
            r.status,
            r.created_at,
            c.unit_code,
            cat.category_name
        FROM issue_reports AS r

        INNER JOIN computer_units AS c
            ON r.unit_id = c.unit_id

        INNER JOIN issue_categories AS cat
            ON r.category_id = cat.category_id

        WHERE r.reporter_id = :user_id

        ORDER BY r.created_at DESC, r.report_id DESC
        LIMIT 5
    ");

    $stmt->execute(['user_id' => $userId]);
    $recentReports = $stmt->fetchAll();

} catch (PDOException $e) {

    // Record the actual error in the PHP error log.
    error_log($e->getMessage());

    // Display a friendly error message on the page.
    $databaseError = true;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | PCare</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <!-- REUSABLE STUDENT NAVIGATION -->
    <?php
    require_once __DIR__ . '/../includes/student-nav.php';
    ?>


    <!-- MAIN DASHBOARD CONTENT -->
    <main class="container py-5">

        <!-- WELCOME SECTION -->
        <div class="mb-4">

            <h2>
                Welcome, <?= e($fullName) ?>!
            </h2>

            <p class="text-muted">
                Welcome to your PCare Student Dashboard.
                Monitor your computer issue reports here.
            </p>

        </div>


        <!-- DATABASE ERROR MESSAGE -->
        <?php if ($databaseError): ?>

            <div class="alert alert-danger">
                Unable to load dashboard information.
                Please try again later.
            </div>

        <?php else: ?>


            <!-- REPORT STATISTICS -->
            <h4 class="mb-3">
                Report Overview
            </h4>

            <div class="row g-3 mb-5">

                <!-- TOTAL REPORTS -->
                <div class="col-md-3">

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
                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Pending
                            </p>

                            <h2 class="fw-bold">
                                <?= $pendingReports ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <!-- ONGOING REPORTS -->
                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Ongoing
                            </p>

                            <h2 class="fw-bold">
                                <?= $ongoingReports ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <!-- RESOLVED REPORTS -->
                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <p class="text-muted mb-1">
                                Resolved
                            </p>

                            <h2 class="fw-bold">
                                <?= $resolvedReports ?>
                            </h2>

                        </div>

                    </div>

                </div>

            </div>


            <!-- RECENT REPORTS -->
            <div class="d-flex justify-content-between align-items-center mb-3">

                <h4 class="mb-0">
                    My Recent Reports
                </h4>

                <a
                    href="my-reports.php"
                    class="btn btn-outline-success btn-sm"
                >
                    View All Reports
                </a>

            </div>


            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <?php if (empty($recentReports)): ?>

                        <div class="text-center py-4">

                            <h5>No Reports Yet</h5>

                            <p class="text-muted mb-3">
                                You have not submitted any computer
                                issue reports yet.
                            </p>

                            <a
                                href="report-issue.php"
                                class="btn btn-success"
                            >
                                Report an Issue
                            </a>

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-striped align-middle">

                                <thead>

                                    <tr>
                                        <th>Reference No.</th>
                                        <th>Computer Unit</th>
                                        <th>Category</th>
                                        <th>Status</th>
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
                                                <?= e($report['unit_code']) ?>
                                            </td>

                                            <td>
                                                <?= e($report['category_name']) ?>
                                            </td>

                                            <td>
                                                <?= e($report['status']) ?>
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

</body>
</html>
