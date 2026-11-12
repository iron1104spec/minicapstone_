<?php
// admin/dashboard.php
// Kinukuha lang natin yung data galing sa logic file
require_once __DIR__ . '/dashboard-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Link to External CSS -->
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>

<!-- MOBILE OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
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
                <div class="profile-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="mobile-menu" id="menuToggle" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <span class="topbar-label">Administrator Workspace</span>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i> <?= e(date('F d, Y')) ?></span>
            <div class="topbar-avatar"><?= e($adminInitials) ?></div>
        </div>
    </header>

    <main class="page-content">
        <!-- PAGE INTRO -->
        <section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <div class="page-eyebrow">LABSYNC / OVERVIEW</div>
                <h1 class="page-title">Administrator Dashboard</h1>
                <p class="page-description">Monitor your computer laboratories and manage system activities.</p>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
        </section>

        <!-- WELCOME PANEL -->
        <section class="welcome-panel">
            <div class="position-relative" style="z-index:1;">
                <div class="welcome-label">ADMINISTRATOR WORKSPACE</div>
                <h2 class="welcome-title">Welcome back, <?= e($adminName) ?>!</h2>
                <p class="welcome-description">
                    Here's what's happening across your LabSync system. Review computer issue reports, monitor maintenance progress, and manage your laboratory records in one place.
                </p>
            </div>
            <i class="bi bi-pc-display-horizontal welcome-icon"></i>
        </section>

        <!-- MAIN STATISTICS -->
        <section class="mb-4">
            <div class="section-header">
                <h2 class="section-title">System Overview</h2>
                <p class="section-subtitle">Summary of registered users and system records.</p>
            </div>
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Active Users</p>
                                <h3 class="stat-number"><?= $totalUsers ?></h3>
                            </div>
                            <div class="stat-icon" style="background:#E5F0ED;color:#245B54;"><i class="bi bi-people"></i></div>
                        </div>
                        <div class="stat-footnote"><i class="bi bi-person-check me-1"></i> Currently active accounts</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Computer Units</p>
                                <h3 class="stat-number"><?= $totalUnits ?></h3>
                            </div>
                            <div class="stat-icon" style="background:#E8F1F8;color:#397A9A;"><i class="bi bi-pc-display"></i></div>
                        </div>
                        <div class="stat-footnote"><i class="bi bi-hdd-stack me-1"></i> Registered computer units</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Issue Reports</p>
                                <h3 class="stat-number"><?= $totalReports ?></h3>
                            </div>
                            <div class="stat-icon" style="background:#FFF2DC;color:#A8781B;"><i class="bi bi-clipboard-data"></i></div>
                        </div>
                        <div class="stat-footnote"><i class="bi bi-file-earmark-text me-1"></i> Total submitted reports</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Maintenance Records</p>
                                <h3 class="stat-number"><?= $totalMaintenance ?></h3>
                            </div>
                            <div class="stat-icon" style="background:#E5F3E9;color:#378054;"><i class="bi bi-tools"></i></div>
                        </div>
                        <div class="stat-footnote"><i class="bi bi-journal-check me-1"></i> Documented maintenance activities</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ISSUE STATUS OVERVIEW -->
        <section class="mb-4">
            <div class="section-header">
                <h2 class="section-title">Issue Status Overview</h2>
                <p class="section-subtitle">Current distribution of reported computer issues.</p>
            </div>
            <div class="status-panel">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="status-item">
                            <div class="d-flex justify-content-between">
                                <span class="status-name"><i class="bi bi-circle-fill me-2" style="color:#D9A441;font-size:7px;"></i> Pending</span>
                                <span class="status-count"><?= $pendingReports ?></span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= statusPercentage($pendingReports, $statusTotal) ?>%;background:#D9A441;"></div>
                            </div>
                        </div>
                        <div class="status-item">
                            <div class="d-flex justify-content-between">
                                <span class="status-name"><i class="bi bi-circle-fill me-2" style="color:#4B9AB8;font-size:7px;"></i> Ongoing</span>
                                <span class="status-count"><?= $ongoingReports ?></span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= statusPercentage($ongoingReports, $statusTotal) ?>%;background:#4B9AB8;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="status-item">
                            <div class="d-flex justify-content-between">
                                <span class="status-name"><i class="bi bi-circle-fill me-2" style="color:#4B9A69;font-size:7px;"></i> Resolved</span>
                                <span class="status-count"><?= $resolvedReports ?></span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= statusPercentage($resolvedReports, $statusTotal) ?>%;background:#4B9A69;"></div>
                            </div>
                        </div>
                        <div class="status-item">
                            <div class="d-flex justify-content-between">
                                <span class="status-name"><i class="bi bi-circle-fill me-2" style="color:#C66C6C;font-size:7px;"></i> Unresolved</span>
                                <span class="status-count"><?= $unresolvedReports ?></span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= statusPercentage($unresolvedReports, $statusTotal) ?>%;background:#C66C6C;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="status-total">
                    <span>Total issue reports</span>
                    <strong><?= $statusTotal ?></strong>
                </div>
            </div>
        </section>

        <!-- QUICK ACCESS -->
        <section class="mb-4">
            <div class="section-header">
                <h2 class="section-title">Quick Access</h2>
                <p class="section-subtitle">Navigate to the main system management modules.</p>
            </div>
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="users.php" class="quick-link">
                        <div class="quick-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="quick-title">User Management</div>
                            <p class="quick-description">Manage user accounts</p>
                        </div>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="computer-units.php" class="quick-link">
                        <div class="quick-icon"><i class="bi bi-pc-display"></i></div>
                        <div>
                            <div class="quick-title">Computer Units</div>
                            <p class="quick-description">Manage registered units</p>
                        </div>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="categories.php" class="quick-link">
                        <div class="quick-icon"><i class="bi bi-grid"></i></div>
                        <div>
                            <div class="quick-title">Issue Categories</div>
                            <p class="quick-description">Manage issue categories</p>
                        </div>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="analytics.php" class="quick-link">
                        <div class="quick-icon"><i class="bi bi-bar-chart-line"></i></div>
                        <div>
                            <div class="quick-title">Reports & Analytics</div>
                            <p class="quick-description">Review system data</p>
                        </div>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- RECENT ISSUE REPORTS -->
        <section class="section-card">
            <div class="table-heading">
                <div>
                    <h2 class="section-title mb-1">Recent Issue Reports</h2>
                    <p class="section-subtitle">Latest computer issues submitted to LabSync.</p>
                </div>
                <a href="issue-reports.php" class="view-all">View all <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Reference Number</th>
                            <th>Reporter</th>
                            <th>Computer Unit</th>
                            <th>Category</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentReports)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p class="fw-bold mt-2 mb-1">No issue reports yet</p>
                                    <p class="mb-0">Submitted reports will appear here.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentReports as $report): ?>
                                <tr>
                                    <td>
                                        <a href="issue-reports.php" class="report-link"><?= e($report['reference_no']) ?></a>
                                    </td>
                                    <td class="reporter-name"><?= e($report['reporter_name']) ?></td>
                                    <td><span class="unit-label"><?= e($report['unit_code']) ?></span></td>
                                    <td><?= e($report['category_name']) ?></td>
                                    <td class="text-nowrap"><?= e(date('M d, Y · h:i A', strtotime($report['created_at']))) ?></td>
                                    <td>
                                        <span class="status-badge <?= e(adminStatusClass($report['status'])) ?>">
                                            <?= e($report['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-top">
                <a href="issue-reports.php" class="view-all">Go to Issue Reports <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="page-footer">
            <div class="fw-bold">LabSync</div>
            <div>Computer Issue Reporting and Maintenance Tracking System</div>
            <div>SLSU Lucena Campus Computer Laboratories</div>
        </footer>
    </main>
</div>

<!-- Link to External JS -->
<script src="../assets/js/admin-dashboard.js"></script>

</body>
</html>