<?php
// admin/maintenance-records.php
require_once __DIR__ . '/maintenance-records-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Records | PCare</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/admin-maintenance.css">
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
            <span>
                <span class="brand-name">PCare</span>
                <span class="brand-subtitle">LABORATORY MANAGEMENT</span>
            </span>
        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="users.php" class="sidebar-link"><i class="bi bi-people"></i> User Management</a>
            <a href="computer-units.php" class="sidebar-link"><i class="bi bi-pc-display"></i> Computer Units</a>
            <a href="categories.php" class="sidebar-link"><i class="bi bi-tags"></i> Issue Categories</a>
            <a href="issue-reports.php" class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link active"><i class="bi bi-tools"></i> Maintenance Records</a>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?= e(pcare_maintenance_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
                </div>
                <div>
                    <div class="admin-name"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                    <div class="admin-role">System Administrator</div>
                </div>
            </div>
            <a href="../logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="mobile-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="breadcrumb-text">
                    Administrator <span class="mx-2">/</span> <strong>Maintenance Records</strong>
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
                <p class="page-eyebrow">MAINTENANCE MONITORING</p>
                <h1 class="page-title">Maintenance Records</h1>
                <p class="page-description">
                    Review inspection findings, maintenance actions, and repair results recorded by IT Personnel.
                </p>
            </div>

            <!-- STATISTICS -->
            <?php
            $statCards = [
                ['Total Records', $stats['total'], 'bi-clipboard-data'],
                ['Personnel Involved', $stats['personnel_count'], 'bi-people'],
                ['Inspections Recorded', $stats['inspected'], 'bi-search'],
                ['Maintenance Dates Recorded', $stats['maintained'], 'bi-tools']
            ];
            ?>

            <div class="row g-3 mb-4">
                <?php foreach ($statCards as $card): ?>
                    <div class="col-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top">
                                <span class="stat-label"><?= e($card[0]) ?></span>
                                <span class="stat-icon"><i class="bi <?= e($card[2]) ?>"></i></span>
                            </div>
                            <h3 class="stat-value"><?= (int) $card[1] ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SEARCH AND FILTERS -->
            <section class="content-card mb-4">
                <div class="card-heading">
                    <h5 class="card-title"><i class="bi bi-funnel me-2"></i> Search and Filters</h5>
                    <span class="card-subtitle">Find maintenance records using the report reference, personnel, unit, category, or status.</span>
                </div>
                <div class="filter-body">
                    <form method="GET" class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Search Records</label>
                            <input type="text" name="search" class="form-control" placeholder="Reference, personnel, findings, or actions..." value="<?= e($search) ?>">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label">Issue Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach ($allowedStatuses as $reportStatus): ?>
                                    <option value="<?= e($reportStatus) ?>" <?= $statusFilter === $reportStatus ? 'selected' : '' ?>>
                                        <?= e($reportStatus) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['category_id'] ?>" <?= (string) $categoryFilter === (string) $category['category_id'] ? 'selected' : '' ?>>
                                        <?= e($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label">Computer Unit</label>
                            <select name="unit" class="form-select">
                                <option value="">All Units</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?= (int) $unit['unit_id'] ?>" <?= (string) $unitFilter === (string) $unit['unit_id'] ? 'selected' : '' ?>>
                                        <?= e($unit['unit_code']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn-primary-custom flex-grow-1"><i class="bi bi-search"></i> Apply</button>
                            <a href="maintenance-records.php" class="btn-light-custom">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- MAINTENANCE RECORDS TABLE -->
            <section class="content-card">
                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title">All Maintenance Records</h5>
                        <span class="card-subtitle">Review the documented maintenance activities and responsible IT Personnel.</span>
                    </div>
                    <span class="result-count"><?= count($maintenanceRecords) ?> RESULT(S)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Maintenance Record</th>
                                <th>Computer Unit</th>
                                <th>Category</th>
                                <th>Responsible Personnel</th>
                                <th>Maintenance Date</th>
                                <th>Issue Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($maintenanceRecords)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="bi bi-tools"></i>
                                        <strong class="d-block text-dark">No maintenance records found</strong>
                                        <span>Maintenance records will appear here after IT Personnel document their inspection or maintenance activities.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($maintenanceRecords as $record): ?>
                                    <tr>
                                        <td>
                                            <div class="record-reference"><?= e($record['reference_no'] ?: ('REPORT-' . $record['report_id'])) ?></div>
                                            <div class="personnel-email mt-1">Record #<?= (int) $record['maintenance_id'] ?></div>
                                        </td>
                                        <td><span class="unit-code"><?= e($record['unit_code'] ?? 'Unknown Unit') ?></span></td>
                                        <td><span class="category-text"><?= e($record['category_name'] ?? 'Unknown Category') ?></span></td>
                                        <td>
                                            <div class="personnel-name"><?= e($record['personnel_name'] ?? 'Unknown Personnel') ?></div>
                                            <div class="personnel-email"><?= e($record['personnel_email'] ?? '—') ?></div>
                                        </td>
                                        <td><div class="date-text"><?= e(pcare_maintenance_date($record['maintenance_date'])) ?></div></td>
                                        <td>
                                            <span class="status-badge <?= e(pcare_maintenance_status_class($record['issue_status'] ?? '')) ?>">
                                                <span class="status-dot"></span>
                                                <?= e($record['issue_status'] ?? 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn-view" data-bs-toggle="modal" data-bs-target="#maintenanceModal<?= (int) $record['maintenance_id'] ?>">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <i class="bi bi-info-circle me-1"></i> Showing <?= count($maintenanceRecords) ?> maintenance record(s). Historical maintenance records are preserved.
                </div>
            </section>

            <footer class="page-footer">
                PCare · Administrator Portal · SLSU Lucena Campus
            </footer>
        </main>
    </div>

    <!-- MAINTENANCE DETAIL MODALS -->
    <?php foreach ($maintenanceRecords as $record): ?>
        <div class="modal fade" id="maintenanceModal<?= (int) $record['maintenance_id'] ?>" tabindex="-1" aria-labelledby="maintenanceModalLabel<?= (int) $record['maintenance_id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <h5 class="modal-title" id="maintenanceModalLabel<?= (int) $record['maintenance_id'] ?>">Maintenance Record Details</h5>
                            <div class="modal-description">
                                Record #<?= (int) $record['maintenance_id'] ?>
                                <span class="mx-1">·</span>
                                Reference: <strong><?= e($record['reference_no'] ?: ('REPORT-' . $record['report_id'])) ?></strong>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <!-- GENERAL INFORMATION -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Computer Unit</span>
                                    <div class="detail-value"><?= e($record['unit_code'] ?? 'Unknown Unit') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Issue Category</span>
                                    <div class="detail-value"><?= e($record['category_name'] ?? 'Unknown Category') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Responsible IT Personnel</span>
                                    <div class="detail-value"><?= e($record['personnel_name'] ?? 'Unknown Personnel') ?></div>
                                    <div class="personnel-email mt-1"><?= e($record['personnel_email'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Current Issue Status</span>
                                    <span class="status-badge <?= e(pcare_maintenance_status_class($record['issue_status'] ?? '')) ?>">
                                        <span class="status-dot"></span>
                                        <?= e($record['issue_status'] ?? 'Unknown') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Inspection Date</span>
                                    <div class="detail-value"><?= e(pcare_maintenance_date($record['inspection_date'])) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Maintenance Date</span>
                                    <div class="detail-value"><?= e(pcare_maintenance_date($record['maintenance_date'])) ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-section">
                                    <span class="detail-label">Record Created</span>
                                    <div class="detail-value"><?= e(pcare_maintenance_date($record['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- ORIGINAL ISSUE DESCRIPTION -->
                        <div class="mb-4">
                            <span class="detail-label">Original Reported Issue</span>
                            <div class="detail-description"><?= e($record['issue_description'] ?? 'No issue description available.') ?></div>
                        </div>

                        <!-- INSPECTION FINDINGS -->
                        <div class="mb-4">
                            <span class="detail-label">Inspection Findings</span>
                            <div class="detail-description"><?= e($record['findings'] ?: 'No findings recorded.') ?></div>
                        </div>

                        <!-- ACTION TAKEN -->
                        <div class="mb-4">
                            <span class="detail-label">Action Taken</span>
                            <div class="detail-description"><?= e($record['action_taken'] ?: 'No maintenance actions recorded.') ?></div>
                        </div>

                        <!-- REPAIR RESULT -->
                        <div>
                            <span class="detail-label">Repair Result</span>
                            <div class="detail-description"><?= e($record['repair_result'] ?: 'No repair result recorded.') ?></div>
                        </div>
                    </div>
                    
                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- External JS -->
    <script src="../assets/js/admin-maintenance.js"></script>

</body>
</html>