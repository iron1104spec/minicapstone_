<?php
// it-personnel/issue-details.php
require_once __DIR__ . '/issue-details-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($report['reference_no']) ?> | PCare</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-issue-details.css">
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
        <div class="mb-4">
            <a href="issue-reports.php" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Issue Reports
            </a>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Issue Details</h2>
                <p class="text-muted mb-0">Review the submitted computer issue report.</p>
            </div>
            <span class="badge rounded-pill fs-6 <?= e($statusClasses[$report['status']] ?? 'bg-secondary') ?>">
                <?= e($report['status']) ?>
            </span>
        </div>

        <!-- NOTIFICATIONS -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= e($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= e($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- REPORT INFORMATION -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Report Information</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted">Reference Number</small>
                                <p class="fw-semibold"><?= e($report['reference_no']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Date Submitted</small>
                                <p class="fw-semibold"><?= e(date('M d, Y h:i A', strtotime($report['created_at']))) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Computer Unit</small>
                                <p class="fw-semibold"><?= e($report['unit_code']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Issue Category</small>
                                <p class="fw-semibold"><?= e($report['category_name']) ?></p>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-bold">Issue Description</h6>
                        <p class="text-muted text-pre-wrap"><?= e($report['description']) ?></p>

                        <?php if (!empty($report['photo_path'])): ?>
                            <hr>
                            <h6 class="fw-bold mb-3">Attached Photo</h6>
                            <img src="../<?= e(ltrim($report['photo_path'], '/\\')) ?>" alt="Issue attachment" class="img-fluid rounded border issue-photo">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STATUS MANAGEMENT -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-2">Manage Issue Status</h5>
                        <p class="text-muted mb-4">Update the progress of this reported computer issue.</p>

                        <form method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label fw-semibold">New Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <?php foreach ($allowedStatuses as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $report['status'] === $status ? 'selected' : '' ?>>
                                            <?= e($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label fw-semibold">
                                    Remarks <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <textarea name="remarks" id="remarks" class="form-control" rows="3" maxlength="1000" placeholder="Enter inspection findings, repair progress, or reason for updating the status..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success">Save Status Update</button>
                        </form>
                    </div>
                </div>

                <!-- STATUS HISTORY -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Status History</h5>
                        
                        <?php if (empty($history)): ?>
                            <p class="text-muted mb-0">No status history available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Previous Status</th>
                                            <th>New Status</th>
                                            <th>Changed By</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $item): ?>
                                            <tr>
                                                <td><?= e($item['old_status'] ?? 'Initial Report') ?></td>
                                                <td class="fw-semibold"><?= e($item['new_status']) ?></td>
                                                <td><?= e($item['changed_by_name'] ?? 'System') ?></td>
                                                <td><?= e(date('M d, Y h:i A', strtotime($item['changed_at']))) ?></td>
                                            </tr>
                                            <?php if (!empty($item['remarks'])): ?>
                                                <tr>
                                                    <td colspan="4">
                                                        <small class="text-muted">Remarks: <?= e($item['remarks']) ?></small>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- REPORTER AND ASSIGNMENT INFORMATION -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Reporter Information</h5>
                        <p class="text-muted mb-1">Full Name</p>
                        <p class="fw-semibold"><?= e($report['reporter_name']) ?></p>
                        <p class="text-muted mb-1">Email Address</p>
                        <p class="fw-semibold"><?= e($report['reporter_email']) ?></p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Assignment Information</h5>
                        <p class="text-muted mb-1">Assigned IT Personnel</p>
                        <p class="fw-semibold"><?= e($report['assigned_name'] ?? 'Not Assigned') ?></p>
                        <hr>
                        <p class="text-muted mb-1">Computer Location</p>
                        <p class="fw-semibold mb-0"><?= e($report['unit_location'] ?? 'Not specified') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>