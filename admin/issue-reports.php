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

    <!-- BAGONG LAYOUT CSS (Floating Dock) -->
    <style>
        /* --- TOP HEADER --- */
        .custom-topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #ffffff; display: flex; justify-content: space-between;
            align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        .brand-area { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-icon { font-size: 24px; color: #0d2f24; }
        .brand-name { display: block; font-weight: 800; font-size: 18px; color: #0d2f24; line-height: 1; }
        .brand-caption { display: block; font-size: 9px; letter-spacing: 1px; color: #666; font-weight: 600; }

        /* --- FLOATING DOCK --- */
        .floating-dock {
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%);
            display: flex; flex-direction: row; gap: 8px; background-color: #0d2f24;
            padding: 12px 24px; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000;
            max-width: 95%; overflow-x: auto;
        }
        .floating-dock::-webkit-scrollbar { display: none; }
        .dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #a0b8b0; text-decoration: none; padding: 10px 16px;
            border-radius: 16px; transition: all 0.3s ease; white-space: nowrap;
        }
        .dock-item i { font-size: 20px; margin-bottom: 4px; }
        .dock-item span { font-size: 11px; font-weight: 500; }
        .dock-item:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; transform: translateY(-4px); }
        .dock-item.active { background-color: #1a4a3a; color: #ffffff; }

        /* --- FIX CONTENT SPACING --- */
        .main-wrapper {
            margin-left: 0 !important;
            padding-top: 70px; 
            padding-bottom: 120px; 
            width: 100%;
        }
    </style>
</head>
<body>

<!-- BAGONG TOP HEADER (Logo & Profile) -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                <?= e(pcare_report_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2;"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;"><?= is_super_admin() ? 'Super Administrator' : 'System Administrator' ?></div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Sign Out">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
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

<!-- FLOATING BOTTOM DOCK (Menu) -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="users.php" class="dock-item">
        <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="computer-units.php" class="dock-item">
        <i class="bi bi-pc-display"></i><span>Computers</span>
    </a>
    <a href="categories.php" class="dock-item">
        <i class="bi bi-grid"></i><span>Categories</span>
    </a>
    <a href="issue-reports.php" class="dock-item active">
        <i class="bi bi-clipboard-data"></i><span>Reports</span>
    </a>
    <a href="maintenance-records.php" class="dock-item">
        <i class="bi bi-tools"></i><span>Maintenance</span>
    </a>
    <a href="analytics.php" class="dock-item">
        <i class="bi bi-bar-chart-line"></i><span>Analytics</span>
    </a>
</nav>

<!-- LOCAL BOOTSTRAP JS -->
<script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>

<!-- External JS -->
<script src="../assets/js/admin-issue-reports.js"></script>

</body>
</html>