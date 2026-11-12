<?php
// it-personnel/dashboard.php
require_once __DIR__ . '/dashboard-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Personnel Dashboard | PCare</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Optional, kung gusto mo lagyan ng icons later) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-dashboard.css">
</head>

<body class="bg-light">

    <!-- MAIN NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-brand-green">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                PCare | IT Personnel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#personnelNavbar" aria-controls="personnelNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="personnelNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="issue-reports.php">Issue Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="maintenance-records.php">Maintenance Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="container py-5">
        <!-- WELCOME SECTION -->
        <div class="mb-4">
            <h2>Welcome, <?= e($fullName) ?>!</h2>
            <p class="text-muted">
                Welcome to your PCare IT Personnel Dashboard.
                Monitor and manage computer issue reports from the B204 Computer Laboratory.
            </p>
        </div>

        <!-- DATABASE ERROR MESSAGE -->
        <?php if ($databaseError): ?>
            <div class="alert alert-danger">
                Unable to load dashboard information. Please check the database connection and try again.
            </div>
        <?php else: ?>

            <!-- ISSUE REPORT STATISTICS -->
            <h4 class="mb-3">Issue Report Overview</h4>
            <div class="row g-3 mb-5">
                <!-- TOTAL REPORTS -->
                <div class="col-md-4 col-lg">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Total Reports</p>
                            <h2 class="fw-bold"><?= $totalReports ?></h2>
                        </div>
                    </div>
                </div>

                <!-- PENDING REPORTS -->
                <div class="col-md-4 col-lg">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Pending</p>
                            <h2 class="fw-bold text-warning"><?= $pendingReports ?></h2>
                        </div>
                    </div>
                </div>

                <!-- ONGOING REPORTS -->
                <div class="col-md-4 col-lg">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Ongoing</p>
                            <h2 class="fw-bold text-primary"><?= $ongoingReports ?></h2>
                        </div>
                    </div>
                </div>

                <!-- RESOLVED REPORTS -->
                <div class="col-md-4 col-lg">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Resolved</p>
                            <h2 class="fw-bold text-success"><?= $resolvedReports ?></h2>
                        </div>
                    </div>
                </div>

                <!-- UNRESOLVED REPORTS -->
                <div class="col-md-4 col-lg">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Unresolved</p>
                            <h2 class="fw-bold text-danger"><?= $unresolvedReports ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT ISSUE REPORTS -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Recent Issue Reports</h4>
                    <p class="text-muted mb-0">Latest computer problems submitted by users.</p>
                </div>
                <a href="issue-reports.php" class="btn btn-outline-success btn-sm">View All Reports</a>
            </div>

            <!-- REPORTS TABLE -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($recentReports)): ?>
                        <div class="text-center py-5">
                            <h5>No Issue Reports Yet</h5>
                            <p class="text-muted mb-0">There are currently no computer issue reports recorded in the system.</p>
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
                                            <td class="fw-semibold"><?= e($report['reference_no']) ?></td>
                                            <td><?= e($report['reporter_name']) ?></td>
                                            <td><?= e($report['unit_code']) ?></td>
                                            <td><?= e($report['category_name']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= e($statusClasses[$report['status']] ?? 'bg-secondary') ?>">
                                                    <?= e($report['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= e($report['assigned_name'] ?? 'Not Assigned') ?></td>
                                            <td><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>