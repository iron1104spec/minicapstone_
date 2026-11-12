<?php
// it-personnel/computer-units.php
require_once __DIR__ . '/computer-units-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Computer Units | PCare</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-units.css">
</head>

<body>

<div class="container-fluid main-content">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <p class="text-secondary small mb-1">
                IT PERSONNEL / COMPUTER UNITS
            </p>
            <h2 class="page-title mb-1">
                Computer Units
            </h2>
            <p class="text-secondary mb-0">
                Monitor the registered computers in B204 Computer Laboratory.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Dashboard
        </a>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Total Units</p>
                        <h3 class="fw-bold mb-0"><?= $totalUnits ?></h3>
                    </div>
                    <div class="stat-icon" style="background:#E5F0ED;color:#245B54;">
                        <i class="bi bi-pc-display"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Active</p>
                        <h3 class="fw-bold mb-0 text-success"><?= $activeUnits ?></h3>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Under Maintenance</p>
                        <h3 class="fw-bold mb-0 text-warning"><?= $maintenanceUnits ?></h3>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Inactive</p>
                        <h3 class="fw-bold mb-0 text-secondary"><?= $inactiveUnits ?></h3>
                    </div>
                    <div class="stat-icon bg-secondary-subtle text-secondary">
                        <i class="bi bi-pc-display-horizontal"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Computer Unit Cards -->
    <div class="mb-4">
        <div class="mb-3">
            <h5 class="fw-bold mb-1">Unit Overview</h5>
            <p class="text-secondary small mb-0">
                Current condition of registered computer units.
            </p>
        </div>

        <div class="row g-3">
            <?php if (empty($units)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No computer units are currently registered.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($units as $unit): ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="unit-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="unit-icon">
                                    <i class="bi bi-pc-display"></i>
                                </div>
                                <span class="badge rounded-pill <?= unitStatusClass($unit['unit_status']) ?>">
                                    <?= e($unit['unit_status']) ?>
                                </span>
                            </div>

                            <h5 class="unit-code mb-1"><?= e($unit['unit_code']) ?></h5>

                            <p class="text-secondary small mb-3">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?= e($unit['location']) ?>
                            </p>

                            <div class="border-top pt-3">
                                <p class="small text-secondary mb-1">Remarks</p>
                                <p class="small mb-0">
                                    <?= !empty($unit['remarks']) ? e($unit['remarks']) : 'No remarks available.' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Computer Units Table -->
    <div class="section-card">
        <div class="p-3 p-md-4 border-bottom">
            <h5 class="fw-bold mb-1">Registered Computer Units</h5>
            <p class="text-secondary small mb-0">
                Complete list of computers assigned to B204 Computer Laboratory.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Unit Code</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($units)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                No computer unit records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($units as $unit): ?>
                            <tr>
                                <td class="unit-code"><?= e($unit['unit_code']) ?></td>
                                <td><?= e($unit['location']) ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= unitStatusClass($unit['unit_status']) ?>">
                                        <?= e($unit['unit_status']) ?>
                                    </span>
                                </td>
                                <td><?= !empty($unit['remarks']) ? e($unit['remarks']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            <small class="text-secondary">
                Showing <?= $totalUnits ?> registered computer unit(s).
            </small>
        </div>
    </div>

    <div class="text-center mt-4">
        <small class="text-secondary">
            PCare · B204 Computer Laboratory
        </small>
    </div>

</div>

</body>
</html>