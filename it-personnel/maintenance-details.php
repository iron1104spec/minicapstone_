<?php
// it-personnel/maintenance-details.php
require_once __DIR__ . '/maintenance-details-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Details | PCare</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-maintenance-details.css">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-pc-display me-2"></i> PCare
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="text-muted small mb-1">IT Personnel / Maintenance Records / Details</p>
            <h2 class="page-heading mb-1">Maintenance Details</h2>
            <p class="text-muted mb-0">Complete documentation of the recorded maintenance activity.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="maintenance-records.php" class="btn btn-outline-pcare">
                <i class="bi bi-arrow-left me-1"></i> Back to Records
            </a>
            <button onclick="window.print()" class="btn btn-pcare" type="button">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Maintenance Overview -->
    <section class="content-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="detail-label">Maintenance Record</p>
                <h4 class="fw-bold mb-2">
                    MR-<?= str_pad((string) $record['maintenance_id'], 4, '0', STR_PAD_LEFT) ?>
                </h4>
                <p class="text-muted small mb-0">
                    Created <?= e(formatDate($record['created_at'])) ?>
                </p>
            </div>
            <div class="text-md-end">
                <p class="detail-label">Related Issue Status</p>
                <span class="status-badge"><?= e($record['issue_status']) ?></span>
            </div>
        </div>
    </section>

    <!-- Linked Issue Information -->
    <section class="content-card p-4 mb-4">
        <h5 class="section-title mb-4">
            <i class="bi bi-clipboard-data me-2 text-success"></i> Related Issue Report
        </h5>
        <div class="row g-4">
            <div class="col-md-4">
                <p class="detail-label">Reference Number</p>
                <p class="detail-value text-success"><?= e($record['reference_no']) ?></p>
            </div>
            <div class="col-md-4">
                <p class="detail-label">Computer Unit</p>
                <p class="detail-value"><?= e($record['unit_code']) ?></p>
            </div>
            <div class="col-md-4">
                <p class="detail-label">Location</p>
                <p class="detail-value"><?= e($record['location'] ?? 'Not specified') ?></p>
            </div>
            <div class="col-12">
                <p class="detail-label">Reported Issue</p>
                <div class="text-section"><?= e($record['issue_description']) ?></div>
            </div>
        </div>
    </section>

    <!-- Maintenance Schedule -->
    <section class="content-card p-4 mb-4">
        <h5 class="section-title mb-4">
            <i class="bi bi-calendar-check me-2 text-success"></i> Maintenance Schedule
        </h5>
        <div class="row g-4">
            <div class="col-md-6">
                <p class="detail-label">Inspection Date</p>
                <p class="detail-value"><?= e(formatDate($record['inspection_date'])) ?></p>
            </div>
            <div class="col-md-6">
                <p class="detail-label">Maintenance Date</p>
                <p class="detail-value"><?= e(formatDate($record['maintenance_date'])) ?></p>
            </div>
        </div>
    </section>

    <!-- Inspection Findings -->
    <section class="content-card p-4 mb-4">
        <h5 class="section-title mb-3">
            <i class="bi bi-search me-2 text-success"></i> Inspection Findings
        </h5>
        <div class="text-section"><?= e($record['findings']) ?></div>
    </section>

    <!-- Actions Taken -->
    <section class="content-card p-4 mb-4">
        <h5 class="section-title mb-3">
            <i class="bi bi-tools me-2 text-success"></i> Actions Taken
        </h5>
        <div class="text-section"><?= e($record['action_taken']) ?></div>
    </section>

    <!-- Repair Result -->
    <section class="content-card p-4 mb-4">
        <h5 class="section-title mb-3">
            <i class="bi bi-check-circle me-2 text-success"></i> Repair Result
        </h5>
        <div class="text-section"><?= e($record['repair_result']) ?></div>
    </section>

    <!-- Responsible Personnel -->
    <section class="content-card p-4 mb-4">
        <h5 class="section-title mb-4">
            <i class="bi bi-person-badge me-2 text-success"></i> Responsible IT Personnel
        </h5>
        <div class="row g-4">
            <div class="col-md-6">
                <p class="detail-label">Personnel Name</p>
                <p class="detail-value"><?= e($record['personnel_name']) ?></p>
            </div>
            <div class="col-md-6">
                <p class="detail-label">Email Address</p>
                <p class="detail-value"><?= e($record['personnel_email'] ?? 'Not available') ?></p>
            </div>
        </div>
    </section>

</main>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>