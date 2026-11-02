
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

    // Count all reports submitted by this student.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
    ");

    $stmt->execute(['user_id' => $userId]);
    $totalReports = (int) $stmt->fetchColumn();

    // Count pending reports.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
        AND status = 'Pending'
    ");

    $stmt->execute(['user_id' => $userId]);
    $pendingReports = (int) $stmt->fetchColumn();

    // Count ongoing reports.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
        AND status IN ('Under Inspection', 'Under Repair')
    ");

    $stmt->execute(['user_id' => $userId]);
    $ongoingReports = (int) $stmt->fetchColumn();

    // Count resolved reports.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM issue_reports
        WHERE reporter_id = :user_id
        AND status = 'Resolved'
    ");

    $stmt->execute(['user_id' => $userId]);
    $resolvedReports = (int) $stmt->fetchColumn();

    // Get the student's five most recent reports.
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

    error_log($e->getMessage());
    $databaseError = true;
}

// Get a suitable visual style for each report status.
function student_status_class(string $status): string
{
    return match ($status) {
        'Pending' => 'status-pending',
        'Under Inspection' => 'status-inspection',
        'Under Repair' => 'status-repair',
        'Resolved' => 'status-resolved',
        'Unresolved' => 'status-unresolved',
        default => 'status-default'
    };
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="View your LabSync computer issue reports and monitor their maintenance progress.">

    <title>Student Dashboard | LabSync</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
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

        .report-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            background: var(--deep-green);
            color: var(--white);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }

        .report-button:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
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

        .stat-card {
            height: 100%;
            padding: 22px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(36, 91, 84, .07);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 19px;
        }

        .stat-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 19px;
        }

        .icon-total {
            background: var(--soft-mint);
            color: var(--deep-green);
        }

        .icon-pending {
            background: #FFF4DF;
            color: #A36A12;
        }

        .icon-ongoing {
            background: #EAF0FF;
            color: #4767AE;
        }

        .icon-resolved {
            background: #E5F4EB;
            color: #267448;
        }

        .stat-number {
            color: var(--deep-green);
            font-size: 31px;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1;
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .reports-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .reports-panel-header {
            padding: 21px 23px;
            border-bottom: 1px solid var(--border);
        }

        .view-all {
            color: var(--deep-green);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .view-all:hover {
            color: var(--primary);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            padding: 15px 20px;
            background: #FAFBFA;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 17px 20px;
            color: var(--text);
            border-color: #F0F2F0;
            font-size: 12px;
            vertical-align: middle;
        }

        .reference-number {
            color: var(--deep-green);
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pending {
            background: #FFF4DF;
            color: #95620E;
        }

        .status-inspection {
            background: #EAF0FF;
            color: #4767AE;
        }

        .status-repair {
            background: #F0EAFE;
            color: #7653A8;
        }

        .status-resolved {
            background: #E5F4EB;
            color: #267448;
        }

        .status-unresolved {
            background: #FDECEC;
            color: #B13F3F;
        }

        .status-default {
            background: #EEF0EF;
            color: #59635F;
        }

        .empty-state {
            padding: 55px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 65px;
            height: 65px;
            display: grid;
            place-items: center;
            margin: 0 auto 17px;
            border-radius: 20px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 27px;
        }

        .empty-state h5 {
            color: var(--deep-green);
            font-size: 16px;
            font-weight: 800;
        }

        .empty-state p {
            max-width: 380px;
            margin: 9px auto 20px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .error-panel {
            padding: 17px 20px;
            background: #FEF3F2;
            border: 1px solid #FECACA;
            border-radius: 12px;
            color: #B42318;
            font-size: 13px;
        }

        @media (max-width: 767px) {

            .dashboard-content {
                padding: 28px 17px 45px;
            }

            .welcome-section {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 20px;
            }

            .report-button {
                width: 100%;
            }

            .stat-card {
                padding: 18px;
            }

            .reports-panel-header {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

    <!-- REUSABLE STUDENT NAVIGATION -->
    <?php
    require_once __DIR__ . '/../includes/student-nav.php';
    ?>

    <main class="dashboard-content">

        <!-- WELCOME SECTION -->

        <section class="welcome-section d-flex justify-content-between align-items-center mb-5">

            <div>

                <div class="welcome-label">
                    Student / Staff Portal
                </div>

                <h1 class="welcome-title">
                    Welcome back, <?= e($fullName) ?>!
                </h1>

                <p class="welcome-description">
                    Here's an overview of your computer issue reports
                    and their current maintenance progress.
                </p>

            </div>

            <a href="report-issue.php" class="report-button">
                <i class="bi bi-plus-lg"></i>
                Report an Issue
            </a>

        </section>


        <?php if ($databaseError): ?>

            <div class="error-panel mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                Unable to load dashboard information. Please try again later.
            </div>

        <?php else: ?>


            <!-- REPORT STATISTICS -->

            <section class="mb-5">

                <div class="mb-3">

                    <h2 class="section-heading mb-1">
                        Report Overview
                    </h2>

                    <p class="section-description mb-0">
                        A summary of your submitted computer issue reports.
                    </p>

                </div>

                <div class="row g-3">

                    <!-- TOTAL REPORTS -->

                    <div class="col-6 col-lg-3">

                        <div class="stat-card">

                            <div class="stat-top">

                                <div class="stat-icon icon-total">
                                    <i class="bi bi-clipboard-data"></i>
                                </div>

                            </div>

                            <div class="stat-number">
                                <?= $totalReports ?>
                            </div>

                            <div class="stat-label mt-2">
                                Total Reports
                            </div>

                        </div>

                    </div>


                    <!-- PENDING REPORTS -->

                    <div class="col-6 col-lg-3">

                        <div class="stat-card">

                            <div class="stat-top">

                                <div class="stat-icon icon-pending">
                                    <i class="bi bi-clock-history"></i>
                                </div>

                            </div>

                            <div class="stat-number">
                                <?= $pendingReports ?>
                            </div>

                            <div class="stat-label mt-2">
                                Pending
                            </div>

                        </div>

                    </div>


                    <!-- ONGOING REPORTS -->

                    <div class="col-6 col-lg-3">

                        <div class="stat-card">

                            <div class="stat-top">

                                <div class="stat-icon icon-ongoing">
                                    <i class="bi bi-tools"></i>
                                </div>

                            </div>

                            <div class="stat-number">
                                <?= $ongoingReports ?>
                            </div>

                            <div class="stat-label mt-2">
                                Ongoing
                            </div>

                        </div>

                    </div>


                    <!-- RESOLVED REPORTS -->

                    <div class="col-6 col-lg-3">

                        <div class="stat-card">

                            <div class="stat-top">

                                <div class="stat-icon icon-resolved">
                                    <i class="bi bi-check-circle"></i>
                                </div>

                            </div>

                            <div class="stat-number">
                                <?= $resolvedReports ?>
                            </div>

                            <div class="stat-label mt-2">
                                Resolved
                            </div>

                        </div>

                    </div>

                </div>

            </section>


            <!-- RECENT REPORTS -->

            <section>

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h2 class="section-heading mb-1">
                            My Recent Reports
                        </h2>

                        <p class="section-description mb-0">
                            Your five most recently submitted reports.
                        </p>

                    </div>

                    <a href="my-reports.php" class="view-all">
                        View All
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                </div>


                <div class="reports-panel">

                    <?php if (empty($recentReports)): ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                <i class="bi bi-clipboard2-plus"></i>
                            </div>

                            <h5>No Reports Yet</h5>

                            <p>
                                You haven't submitted any computer issue
                                reports yet. Once you report a problem,
                                you can monitor its progress here.
                            </p>

                            <a href="report-issue.php" class="report-button">
                                <i class="bi bi-plus-lg"></i>
                                Submit Your First Report
                            </a>

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table align-middle">

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

                                            <td>
                                                <span class="reference-number">
                                                    <?= e($report['reference_no']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= e($report['unit_code']) ?>
                                            </td>

                                            <td>
                                                <?= e($report['category_name']) ?>
                                            </td>

                                            <td>

                                                <span class="status-badge <?= e(student_status_class($report['status'])) ?>">
                                                    <?= e($report['status']) ?>
                                                </span>

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

            </section>

        <?php endif; ?>

    </main>

</body>
</html>
