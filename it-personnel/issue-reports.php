<?php
// it-personnel/issue-reports.php
require_once __DIR__ . '/issue-reports-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Reports | PCare IT Personnel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-issue-reports.css">
</head>

<body class="bg-light">

    <!-- NAVIGATION -->
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="issue-reports.php">Issue Reports</a>
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

    <!-- MAIN CONTENT -->
    <main class="container py-5">
        
        <!-- PAGE HEADER -->
        <div class="mb-4">
            <h2 class="fw-bold">Issue Reports</h2>
            <p class="text-muted">
                View and monitor all computer issue reports submitted to the B204 Computer Laboratory.
            </p>
        </div>

        <?php if ($databaseError): ?>
            <div class="alert alert-danger">
                Unable to retrieve issue reports. Please try again later.
            </div>
        <?php else: ?>

            <!-- SEARCH AND FILTER -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="issue-reports.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search Reports</label>
                                <input type="text" name="search" class="form-control" placeholder="Reference number, reporter, or PC unit" value="<?= e($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filter by Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($allowedStatuses as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                            <?= e($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- REPORTS TABLE -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">All Submitted Reports</h5>
                        <span class="badge bg-secondary"><?= count($reports) ?> Reports</span>
                    </div>

                    <?php if (empty($reports)): ?>
                        <div class="text-center py-5">
                            <h5>No Reports Found</h5>
                            <p class="text-muted mb-0">No issue reports match your search or filter.</p>
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
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <!-- CLICKABLE REFERENCE NUMBER -->
                                            <td class="fw-semibold">
                                                <a href="issue-details.php?id=<?= (int) $report['report_id'] ?>" class="text-success text-decoration-none fw-bold">
                                                    <?= e($report['reference_no']) ?>
                                                </a>
                                            </td>
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