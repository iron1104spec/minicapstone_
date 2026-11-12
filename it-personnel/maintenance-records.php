<?php
// it-personnel/maintenance-records.php
require_once __DIR__ . '/maintenance-records-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Records | PCare</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-maintenance-records.css">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-pc-display me-2"></i>PCare
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="issue-reports.php">Issue Reports</a>
                <a class="nav-link active" href="maintenance-records.php">Maintenance Records</a>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<main class="container-fluid px-lg-4 py-4">

    <!-- Page Heading -->
    <div class="mb-4">
        <p class="text-muted small mb-1">IT Personnel / Maintenance</p>
        <h2 class="page-heading mb-2">Maintenance Records</h2>
        <p class="text-muted mb-0">
            Document inspections, troubleshooting actions, and repair results for reported computer issues.
        </p>
    </div>

    <!-- Success Notification -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Error Notification -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Create Maintenance Record -->
    <section class="content-card p-4 mb-4">
        <div class="mb-4">
            <h5 class="section-title mb-1">
                <i class="bi bi-clipboard-plus me-2 text-success"></i> Create Maintenance Record
            </h5>
            <p class="text-muted small mb-0">Select an issue report and document the maintenance activity.</p>
        </div>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="report_id" class="form-label">Issue Report <span class="text-danger">*</span></label>
                    <select name="report_id" id="report_id" class="form-select" required>
                        <option value="">Select an issue report</option>
                        <?php foreach ($availableReports as $report): ?>
                            <option value="<?= (int) $report['report_id'] ?>">
                                <?= e($report['reference_no']) ?> — <?= e($report['unit_code']) ?> (<?= e($report['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="inspection_date" class="form-label">Inspection Date</label>
                    <input type="datetime-local" name="inspection_date" id="inspection_date" class="form-control">
                </div>

                <div class="col-md-3">
                    <label for="maintenance_date" class="form-label">Maintenance Date</label>
                    <input type="datetime-local" name="maintenance_date" id="maintenance_date" class="form-control">
                </div>

                <div class="col-md-12">
                    <label for="findings" class="form-label">Inspection Findings <span class="text-danger">*</span></label>
                    <textarea name="findings" id="findings" class="form-control" rows="3" placeholder="Describe the problem found during inspection..." required></textarea>
                </div>

                <div class="col-md-12">
                    <label for="action_taken" class="form-label">Actions Taken <span class="text-danger">*</span></label>
                    <textarea name="action_taken" id="action_taken" class="form-control" rows="3" placeholder="Describe the troubleshooting or repair actions performed..." required></textarea>
                </div>

                <div class="col-md-12">
                    <label for="repair_result" class="form-label">Repair Result <span class="text-danger">*</span></label>
                    <textarea name="repair_result" id="repair_result" class="form-control" rows="3" placeholder="Describe the result after maintenance..." required></textarea>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-pcare">
                        <i class="bi bi-save me-2"></i> Save Maintenance Record
                    </button>
                </div>
            </div>
        </form>
    </section>

    <!-- Maintenance Records Table -->
    <section class="content-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="section-title mb-1">Saved Maintenance Records</h5>
                <p class="text-muted small mb-0">Maintenance activities recorded in the database.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border"><?= count($maintenanceRecords) ?> Records</span>
        </div>

        <?php if (count($maintenanceRecords) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Computer Unit</th>
                            <th>Inspection Date</th>
                            <th>Maintenance Date</th>
                            <th>Personnel</th>
                            <th>Issue Status</th>
                            <th>Repair Result</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenanceRecords as $record): ?>
                            <tr>
                                <td class="fw-semibold text-success"><?= e($record['reference_no']) ?></td>
                                <td><?= e($record['unit_code']) ?></td>
                                <td>
                                    <?= $record['inspection_date'] ? e(date('M d, Y h:i A', strtotime($record['inspection_date']))) : '<span class="text-muted">Not set</span>' ?>
                                </td>
                                <td>
                                    <?= $record['maintenance_date'] ? e(date('M d, Y h:i A', strtotime($record['maintenance_date']))) : '<span class="text-muted">Not set</span>' ?>
                                </td>
                                <td><?= e($record['personnel_name']) ?></td>
                                <td>
                                    <span class="status-badge"><?= e($record['status']) ?></span>
                                </td>
                                <td><?= e($record['repair_result']) ?></td>
                                <td>
                                    <a href="maintenance-details.php?id=<?= (int) $record['maintenance_id'] ?>" class="btn-view">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-clipboard2-x d-block mb-3"></i>
                <h6 class="fw-semibold text-dark">No maintenance records yet</h6>
                <p class="small mb-0">
                    Once an IT Personnel member saves a maintenance record, it will appear here.
                </p>
            </div>
        <?php endif; ?>
    </section>

</main>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>