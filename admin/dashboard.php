<?php
// admin/dashboard.php
require_once __DIR__ . '/dashboard-logic.php';

$isSuperAdmin   = is_super_admin();
$roleLabel      = $isSuperAdmin ? 'Super Administrator' : 'Administrator';
$roleLabelUpper = $isSuperAdmin ? 'SUPER ADMINISTRATOR' : 'ADMINISTRATOR';
$roleTitle      = $isSuperAdmin ? 'Super Admin Dashboard' : 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($roleTitle) ?> | LabSync</title>

    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=123">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="brand">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
        </span>
    </a>

    <div class="nav-label">MAIN MENU</div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link-custom active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="users.php" class="nav-link-custom"><i class="bi bi-people"></i> User Management</a>
        <a href="computer-units.php" class="nav-link-custom"><i class="bi bi-pc-display"></i> Computer Units</a>
        <a href="categories.php" class="nav-link-custom"><i class="bi bi-grid"></i> Issue Categories</a>
        <a href="issue-reports.php" class="nav-link-custom"><i class="bi bi-clipboard-data"></i> Issue Reports</a>
        <a href="maintenance-records.php" class="nav-link-custom"><i class="bi bi-tools"></i> Maintenance Records</a>
        <a href="analytics.php" class="nav-link-custom"><i class="bi bi-bar-chart-line"></i> Reports & Analytics</a>
    </nav>

    <div class="sidebar-bottom">
        <div class="nav-label">ACCOUNT</div>
        <a href="../logout.php" class="nav-link-custom"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
        <div class="sidebar-profile">
            <div class="profile-avatar"><?= e($adminInitials) ?></div>
            <div class="overflow-hidden">
                <div class="profile-name text-truncate"><?= e($adminName) ?></div>
                <div class="profile-role"><?= e($roleLabel) ?></div>
            </div>
        </div>
    </div>
</aside>

<div class="main-wrapper">
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="mobile-menu" id="menuToggle" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <span class="topbar-label"><?= e($roleLabel) ?> Workspace</span>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i> <?= e(date('F d, Y')) ?></span>
            <div class="topbar-avatar"><?= e($adminInitials) ?></div>
        </div>
    </header>

    <main class="dashboard-content">
        <section class="welcome-section d-flex justify-content-between align-items-center mb-5">
            <div>
                <div class="welcome-label"><?= e($roleLabelUpper) ?> WORKSPACE</div>
                <h1 class="welcome-title">Welcome back, <?= e($adminName) ?>!</h1>
                <p class="welcome-description">
                    Here's what's happening across your LabSync system. Review computer issue reports, monitor maintenance progress, and manage your laboratory records in one place.
                </p>
            </div>
            <a href="issue-reports.php" class="report-button">
                <i class="bi bi-clipboard-data"></i> View Issue Reports
            </a>
        </section>

        <section class="mb-5">
            <div class="mb-3">
                <h2 class="section-heading mb-1">System Overview</h2>
                <p class="section-description mb-0">Summary of registered users and system records.</p>
            </div>
            
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon icon-total"><i class="bi bi-people"></i></div>
                        </div>
                        <div class="stat-number"><?= $totalUsers ?></div>
                        <div class="stat-label mt-2">Active Users</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon icon-ongoing"><i class="bi bi-pc-display"></i></div>
                        </div>
                        <div class="stat-number"><?= $totalUnits ?></div>
                        <div class="stat-label mt-2">Computer Units</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon icon-pending"><i class="bi bi-clipboard-data"></i></div>
                        </div>
                        <div class="stat-number"><?= $totalReports ?></div>
                        <div class="stat-label mt-2">Issue Reports</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon icon-resolved"><i class="bi bi-tools"></i></div>
                        </div>
                        <div class="stat-number"><?= $totalMaintenance ?></div>
                        <div class="stat-label mt-2">Maintenance Records</div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-heading mb-1">Recent Issue Reports</h2>
                    <p class="section-description mb-0">Latest computer issues submitted to LabSync.</p>
                </div>
                <a href="issue-reports.php" class="view-all">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="reports-panel">
                <?php if (empty($recentReports)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h5>No Reports Yet</h5>
                        <p>No issue reports have been submitted to the system yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Reference No.</th>
                                    <th>Reporter</th>
                                    <th>Computer Unit</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReports as $report): ?>
                                    <tr>
                                        <td>
                                            <span class="reference-number"><?= e($report['reference_no']) ?></span>
                                        </td>
                                        <td class="fw-bold text-dark"><?= e($report['reporter_name']) ?></td>
                                        <td><?= e($report['unit_code']) ?></td>
                                        <td><?= e($report['category_name']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= e(strtolower(str_replace(' ', '', $report['status']))) ?>">
                                                <?= e($report['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<!-- LOCAL BOOTSTRAP JS -->
<script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>
<script src="../assets/js/admin-dashboard.js"></script>

</body>
</html>