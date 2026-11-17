<?php
// student/my-reports.php
require_once __DIR__ . '/my-reports-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports | LabSync</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/student-my-reports.css">
</head>

<body>

    <!-- SHARED STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>

    <main class="dashboard-content">
        <!-- PAGE HEADER -->
        <div class="page-heading">
            <a href="dashboard.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1>My Reports</h1>
                    <p>View and monitor the computer issues you have submitted.</p>
                </div>
                <a href="report-issue.php" class="primary-button">
                    <i class="bi bi-plus-lg"></i> Report an Issue
                </a>
            </div>
        </div>

        <!-- DATABASE ERROR -->
        <?php if ($databaseError): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Unable to retrieve your reports. Please try again later.
            </div>
        <?php else: ?>

            <!-- REPORT COUNT -->
            <div class="summary-card">
                <div>
                    <div class="summary-label">Total Submitted Reports</div>
                    <div class="summary-number"><?= count($reports) ?></div>
                </div>
                <div class="summary-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
            </div>

            <!-- REPORT HISTORY -->
            <section class="reports-card">
                <div class="reports-header">
                    <div>
                        <h2>Report History</h2>
                        <p>A record of your submitted computer issue reports.</p>
                    </div>
                    
                    <?php if (!empty($reports)): ?>
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="search" id="reportSearch" placeholder="Search your reports..." aria-label="Search your reports">
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($reports)): ?>
                    <!-- EMPTY STATE -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-clipboard-x"></i>
                        </div>
                        <h3>No Reports Yet</h3>
                        <p>
                            You haven't submitted any computer issue reports.
                            Your submitted reports will appear here.
                        </p>
                        <a href="report-issue.php" class="primary-button">
                            <i class="bi bi-plus-lg"></i> Submit Your First Report
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Reference No.</th>
                                        <th>Computer Unit</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Date Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="reportsTableBody">
                                    <?php foreach ($reports as $report): ?>
                                        <tr class="report-row">
                                            <td>
                                                <span class="reference-number"><?= e($report['reference_no']) ?></span>
                                            </td>
                                            <td>
                                                <span class="unit-label"><?= e($report['unit_code']) ?></span>
                                            </td>
                                            <td><?= e($report['category_name']) ?></td>
                                            <td class="description-cell"><?= e($report['description']) ?></td>
                                            <td>
                                                <span class="status-badge <?= e(getStatusClass($report['status'])) ?>">
                                                    <?= e($report['status']) ?>
                                                </span>
                                            </td>
                                            <td class="date-label"><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
                                            <td>
                                                <a href="report-details.php?id=<?= (int) $report['report_id'] ?>" class="view-button">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="no-results" id="noResults">
                            <i class="bi bi-search me-1"></i>
                            No reports match your search.
                        </div>
                    </div>
                <?php endif; ?>
            </section>

        <?php endif; ?>
    </main>

    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- External JS -->
    <script src="../assets/js/student-my-reports.js"></script>

</body>
</html>