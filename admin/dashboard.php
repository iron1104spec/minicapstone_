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

    <!-- BAGONG LAYOUT CSS (Floating Dock) -->
    <style>
        /* --- TOP HEADER --- */
        .custom-topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #ffffff; display: flex; justify-content: space-between;
            align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 9999;
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
            border: 1px solid rgba(255, 255, 255, 0.1); z-index: 9999;
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

        /* ========================================================= */
        /* --- ITO ANG PWERSAHANG PAGBABAGO (FORCE OVERRIDE) --- */
        /* ========================================================= */
        .sidebar, .sidebar-overlay, .mobile-toggle, .topbar {
            display: none !important; /* Pilitin itago ang lumang sidebar at topbar */
        }
        body {
            padding-left: 0 !important;
            margin-left: 0 !important;
            background-color: #f4f7f6; /* Color ng background mo */
        }
        .main-wrapper, .dashboard-content, .main-content {
            margin-left: 0 !important;
            padding-top: 90px !important; /* Space para sa itaas */
            padding-bottom: 120px !important; /* Space para sa dock sa baba */
            padding-left: 20px !important;
            padding-right: 20px !important;
            width: 100% !important;
            max-width: 100vw !important;
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
            <i class="bi bi-calendar3 me-1"></i> <?= e(date('F d, Y')) ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                <?= e($adminInitials) ?>
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2;"><?= e($adminName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;"><?= e($roleLabel) ?></div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Sign Out">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
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

<!-- FLOATING BOTTOM DOCK (Menu) -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item active">
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
    <a href="issue-reports.php" class="dock-item">
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
<script src="../assets/js/admin-dashboard.js"></script>

</body>
</html>