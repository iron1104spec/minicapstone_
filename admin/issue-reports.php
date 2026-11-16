<?php
// admin/issue-reports.php
require_once __DIR__ . '/issue-reports-logic.php';

// Safe fallback para sa avatar function
if (!function_exists('pcare_report_initials')) {
    function pcare_report_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'U';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Reports | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">

    <!-- PREMIUM CSS -->
    <link rel="stylesheet" href="../assets/css/admin-issue-reports.css?v=123">
</head>
<body>

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
            <a href="computer-units.php" class="sidebar-link"><i class="bi bi-pc-display"></i> Computer Units</a>
            <a href="categories.php" class="sidebar-link"><i class="bi bi-tags"></i> Issue Categories</a>
            <a href="issue-reports.php" class="sidebar-link active"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link"><i class="bi bi-tools"></i> Maintenance Records</a>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?= e(pcare_report_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
                </div>
                <div>
                    <div class="admin-name"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                    <div class="admin-role"><?= is_super_admin() ? 'Super Administrator' : 'System Administrator' ?></div>
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
                    <?= is_super_admin() ? 'Super Administrator' : 'Administrator' ?> <span class="mx-2">/</span> <strong>Issue Reports</strong>
                </div>
            </div>
            <div class="topbar-right">
                <span><span class="topbar-dot"></span> <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?></span>
                <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?></span>
            </div>
        </header>

        <main class="main-content">
            <!-- HERO BANNER -->
            <section class="welcome-section">
                <div>
                    <div class="welcome-label">ISSUE MONITORING</div>
                    <h1 class="welcome-title">Issue Reports</h1>
                    <p class="welcome-description">
                        Monitor submitted computer issues and review their maintenance progress. Ensure every problem is tracked and resolved efficiently.
                    </p>
                </div>
            </section>

            <!-- STATISTICS -->
            <?php
            $statCards = [
                ['Total Reports', $stats['total'], 'bi-clipboard-data'],
                ['Pending', $stats['pending'], 'bi-clock-history'],
                ['Under Inspection', $stats['inspection'], 'bi-search'],
                ['Under Repair', $stats['repair'], 'bi-tools'],
                ['Resolved', $stats['resolved'], 'bi-check-circle'],
                ['Unresolved', $stats['unresolved'], 'bi-exclamation-circle']
            ];
            ?>

            <div class="row g-3 mb-4">
                <?php foreach ($statCards as $card): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon"><i class="bi <?= e($card[2]) ?>"></i></div>
                            </div>
                            <div class="stat-value"><?= (int) $card[1] ?></div>
                            <div class="stat-label"><?= e($card[0]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SEARCH AND FILTERS -->
            <section class="content-card mb-4">
                <div class="card-heading">
                    <h5 class="card-title"><i class="bi bi-funnel me-2"></i> Search and Filters</h5>
                    <span class="card-subtitle">Find reports using their reference number, reporter, computer unit, category, or status.</span>
                </div>
                <div class="filter-body">
                    <form method="GET" class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Search Reports</label>
                            <input type="text" name="search" class="form-control" placeholder="Reference number, reporter, or issue..." value="<?= e($search) ?>">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label">Status</label>
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
                            <button type="submit" class="btn-primary-custom flex-grow-1" style="justify-content: center;"><i class="bi bi-search"></i> Apply</button>
                            <a href="issue-reports.php" class="btn-light-custom">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- ISSUE REPORTS TABLE -->
            <section class="content-card">
                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title">All Issue Reports</h5>
                        <span class="card-subtitle">Review the submitted reports and open their details.</span>
                    </div>
                    <span class="result-count"><?= count($reports) ?> RESULT(S)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Reporter</th>
                                <th>Computer Unit</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="bi bi-clipboard-x"></i>
                                        <strong class="d-block text-dark">No issue reports found</strong>
                                        <span>Try adjusting your filters or search terms.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><span class="report-reference"><?= e($report['reference_no'] ?: ('REPORT-' . $report['report_id'])) ?></span></td>
                                        <td>
                                            <div class="reporter-name"><?= e($report['reporter_name'] ?? 'Unknown User') ?></div>
                                            <div class="reporter-email"><?= e($report['reporter_email'] ?? '—') ?></div>
                                        </td>
                                        <td><span class="unit-code"><?= e($report['unit_code'] ?? 'Unknown Unit') ?></span></td>
                                        <td><span class="category-text"><?= e($report['category_name'] ?? 'Unknown Category') ?></span></td>
                                        <td>
                                            <span class="status-badge <?= e(pcare_report_status_class($report['status'])) ?>">
                                                <?= e($report['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e(pcare_report_date($report['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn-view" data-bs-toggle="modal" data-bs-target="#reportModal<?= (int) $report['report_id'] ?>">
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
                    <i class="bi bi-info-circle me-1"></i> Showing <?= count($reports) ?> report(s). Issue reports are preserved for historical monitoring.
                </div>
            </section>

            <footer class="page-footer">
                LabSync · <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?> · SLSU Lucena Campus
            </footer>
        </main>
    </div>

    <!-- REPORT DETAILS MODALS -->
    <?php foreach ($reports as $report): ?>
        <?php
        $reportId = (int) $report['report_id'];
        $historyItems = $reportHistory[$reportId] ?? [];
        $storedPhotoPath = trim((string) ($report['photo_path'] ?? ''));
        $photoUrl = '';

        if ($storedPhotoPath !== '') {
            $normalizedPhotoPath = str_replace('\\', '/', $storedPhotoPath);
            $photoUrl = '../' . ltrim($normalizedPhotoPath, '/');
        }
        ?>
        <div class="modal fade" id="reportModal<?= $reportId ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <h5 class="modal-title">Issue Report Details</h5>
                            <div class="modal-description">
                                Reference Number: <strong><?= e($report['reference_no'] ?: ('REPORT-' . $reportId)) ?></strong>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Reporter</span>
                                    <div class="detail-value"><?= e($report['reporter_name'] ?? 'Unknown User') ?></div>
                                    <div class="text-secondary small mt-1"><?= e($report['reporter_email'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Computer Unit</span>
                                    <div class="detail-value"><?= e($report['unit_code'] ?? 'Unknown Unit') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Issue Category</span>
                                    <div class="detail-value"><?= e($report['category_name'] ?? 'Unknown Category') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Current Status</span>
                                    <span class="status-badge <?= e(pcare_report_status_class($report['status'])) ?>">
                                        <?= e($report['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Assigned IT Personnel</span>
                                    <div class="detail-value"><?= e($report['assigned_personnel'] ?? 'Not yet assigned') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Date Submitted</span>
                                    <div class="detail-value"><?= e(pcare_report_date($report['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Last Updated</span>
                                    <div class="detail-value"><?= e(pcare_report_date($report['updated_at'])) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <span class="detail-label">Date Resolved</span>
                                    <div class="detail-value"><?= e(pcare_report_date($report['resolved_at'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <span class="detail-label">Issue Description</span>
                            <div class="description-box"><?= e($report['description']) ?></div>
                        </div>

                        <div class="mb-4">
                            <span class="detail-label">Attached Photo</span>
                            <?php if ($photoUrl !== ''): ?>
                                <a href="<?= e($photoUrl) ?>" target="_blank" rel="noopener noreferrer" class="photo-link mb-2">
                                    <i class="bi bi-image"></i> Open attached image
                                </a>
                                <img src="<?= e($photoUrl) ?>" alt="Issue report attachment" class="report-photo mt-2" loading="lazy">
                            <?php else: ?>
                                <div class="description-box">No photo was attached to this report.</div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <span class="detail-label mb-3">Issue Status History</span>
                            <?php if (empty($historyItems)): ?>
                                <div class="description-box">No status history has been recorded for this report.</div>
                            <?php else: ?>
                                <div class="history-list">
                                    <?php foreach ($historyItems as $history): ?>
                                        <div class="history-item">
                                            <span class="history-dot"></span>
                                            <div class="history-title">
                                                <?php if (!empty($history['old_status'])): ?>
                                                    <?= e($history['old_status']) ?> <i class="bi bi-arrow-right mx-1"></i>
                                                <?php else: ?>
                                                    Initial status:
                                                <?php endif; ?>
                                                <?= e($history['new_status']) ?>
                                            </div>
                                            <div class="history-meta">
                                                <?= e(pcare_report_date($history['changed_at'])) ?>
                                                <span class="mx-1">·</span>
                                                <?= e($history['changed_by_name'] ?? 'System / Unknown User') ?>
                                            </div>
                                            <?php if (!empty($history['remarks'])): ?>
                                                <div class="history-remarks"><?= e($history['remarks']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- LOCAL BOOTSTRAP JS -->
    <script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>

    <!-- External JS -->
    <script src="../assets/js/admin-issue-reports.js"></script>

</body>
</html>