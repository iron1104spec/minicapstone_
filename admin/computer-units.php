<?php
// admin/computer-units.php
require_once __DIR__ . '/computer-units-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Units | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/admin-units.css">
</head>
<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
            <span>
                <span class="brand-name">LabSync</span>
                <span class="brand-subtitle">LABORATORY MANAGEMENT</span>
            </span>
        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="users.php" class="sidebar-link"><i class="bi bi-people"></i> User Management</a>
            <a href="computer-units.php" class="sidebar-link active"><i class="bi bi-pc-display"></i> Computer Units</a>
            <a href="categories.php" class="sidebar-link"><i class="bi bi-tags"></i> Issue Categories</a>
            <a href="issue-reports.php" class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link"><i class="bi bi-tools"></i> Maintenance Records</a>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">AD</div>
                <div>
                    <div class="admin-name">Administrator</div>
                    <div class="admin-role">System Administrator</div>
                </div>
            </div>
            <a href="../logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="mobile-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="breadcrumb-text">
                    Administrator <span class="mx-2">/</span> <strong>Computer Units</strong>
                </div>
            </div>
            <div class="topbar-right">
                <span><span class="topbar-dot"></span> Administrator Portal</span>
                <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?></span>
            </div>
        </header>

        <main class="main-content">
            <!-- PAGE HEADING -->
            <div class="mb-4">
                <p class="page-eyebrow">LABORATORY ASSET MONITORING</p>
                <h1 class="page-title">Computer Units</h1>
                <p class="page-description">
                    Monitor registered computer units and their current operational status.
                </p>
            </div>

            <!-- STATISTICS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span class="stat-label">Total Units</span>
                            <span class="stat-icon"><i class="bi bi-pc-display"></i></span>
                        </div>
                        <h3 class="stat-value"><?= (int) $stats['total'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span class="stat-label">Active Units</span>
                            <span class="stat-icon"><i class="bi bi-check-circle"></i></span>
                        </div>
                        <h3 class="stat-value"><?= (int) $stats['active'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span class="stat-label">Under Maintenance</span>
                            <span class="stat-icon"><i class="bi bi-tools"></i></span>
                        </div>
                        <h3 class="stat-value"><?= (int) $stats['maintenance'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span class="stat-label">Inactive Units</span>
                            <span class="stat-icon"><i class="bi bi-pc-display"></i></span>
                        </div>
                        <h3 class="stat-value"><?= (int) $stats['inactive'] ?></h3>
                    </div>
                </div>
            </div>

            <!-- UNIT OVERVIEW -->
            <section class="content-card mb-4">
                <div class="card-heading">
                    <h5 class="card-title"><i class="bi bi-grid me-2"></i> Computer Unit Overview</h5>
                    <span class="card-subtitle">Visual overview of computer units registered in the database.</span>
                </div>

                <div class="unit-grid">
                    <?php if (empty($units)): ?>
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <i class="bi bi-pc-display"></i>
                            <strong class="d-block text-dark">No computer units found</strong>
                            <span>There are currently no registered computer units.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($units as $unit): ?>
                            <?php $unitStatus = (string) $unit['unit_status']; ?>
                            <div class="unit-card">
                                <div class="unit-icon">
                                    <i class="bi bi-pc-display-horizontal"></i>
                                </div>
                                <div class="unit-code"><?= e($unit['unit_code']) ?></div>
                                <div class="unit-location">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= e($unit['location'] ?: 'Location not specified') ?>
                                </div>
                                <span class="unit-status <?= e(unitStatusClass($unitStatus)) ?>">
                                    <i class="bi <?= e(unitStatusIcon($unitStatus)) ?>"></i>
                                    <?= e($unitStatus) ?>
                                </span>
                                <?php if (!empty($unit['remarks'])): ?>
                                    <div class="unit-remarks"><?= e($unit['remarks']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- UNIT DETAILS TABLE -->
            <section class="content-card">
                <div class="card-heading">
                    <h5 class="card-title">Registered Computer Units</h5>
                    <span class="card-subtitle">Detailed information retrieved from the computer_units table.</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Unit Code</th>
                                <th>Location</th>
                                <th>Operational Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($units)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="bi bi-pc-display"></i>
                                    <strong class="d-block text-dark">No computer units available</strong>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($units as $unit): ?>
                                <?php $unitStatus = (string) $unit['unit_status']; ?>
                                <tr>
                                    <td><span class="table-unit-code"><?= e($unit['unit_code']) ?></span></td>
                                    <td><?= e($unit['location'] ?: '—') ?></td>
                                    <td>
                                        <span class="table-status <?= e(unitStatusClass($unitStatus)) ?>">
                                            <span class="table-dot"></span>
                                            <?= e($unitStatus) ?>
                                        </span>
                                    </td>
                                    <td><?= e($unit['remarks'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <i class="bi bi-info-circle me-1"></i>
                    Showing <?= count($units) ?> registered computer unit(s). This page currently provides view-only access.
                </div>
            </section>

            <footer class="page-footer">
                LabSync · Administrator Portal · SLSU Lucena Campus
            </footer>
        </main>
    </div>

    <!-- External JS -->
    <script src="../assets/js/admin-units.js"></script>

</body>
</html>