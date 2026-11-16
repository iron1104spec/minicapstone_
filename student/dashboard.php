<?php
// student/dashboard.php
require_once __DIR__ . '/dashboard-logic.php';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
</head>

<body>

    <!-- REUSABLE STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>

    <main class="dashboard-content">

        <!-- WELCOME SECTION -->
        <section class="welcome-section d-flex justify-content-between align-items-center mb-5">
            <div>
                <div class="welcome-label">Student / Staff Portal</div>
                <h1 class="welcome-title">Welcome back, <?= e($fullName) ?>!</h1>
                <p class="welcome-description">
                    Here's an overview of your computer issue reports and their current maintenance progress.
                </p>
            </div>
            <a href="report-issue.php" class="report-button">
                <i class="bi bi-plus-lg"></i> Report an Issue
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
                    <h2 class="section-heading mb-1">Report Overview</h2>
                    <p class="section-description mb-0">A summary of your submitted computer issue reports.</p>
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
                            <div class="stat-number"><?= $totalReports ?></div>
                            <div class="stat-label mt-2">Total Reports</div>
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
                            <div class="stat-number"><?= $pendingReports ?></div>
                            <div class="stat-label mt-2">Pending</div>
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
                            <div class="stat-number"><?= $ongoingReports ?></div>
                            <div class="stat-label mt-2">Ongoing</div>
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
                            <div class="stat-number"><?= $resolvedReports ?></div>
                            <div class="stat-label mt-2">Resolved</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RECENT REPORTS -->
            <section>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="section-heading mb-1">My Recent Reports</h2>
                        <p class="section-description mb-0">Your five most recently submitted reports.</p>
                    </div>
                    <a href="my-reports.php" class="view-all">
                        View All <i class="bi bi-arrow-right ms-1"></i>
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
                                You haven't submitted any computer issue reports yet.
                                Once you report a problem, you can monitor its progress here.
                            </p>
                            <a href="report-issue.php" class="report-button">
                                <i class="bi bi-plus-lg"></i> Submit Your First Report
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
                                                <span class="reference-number"><?= e($report['reference_no']) ?></span>
                                            </td>
                                            <td><?= e($report['unit_code']) ?></td>
                                            <td><?= e($report['category_name']) ?></td>
                                            <td>
                                                <span class="status-badge <?= e(student_status_class($report['status'])) ?>">
                                                    <?= e($report['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
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