<?php
// admin/analytics.php
require_once __DIR__ . '/analytics-logic.php';

// Safe fallback para sa avatar function
if (!function_exists('pcare_analytics_initials')) {
    function pcare_analytics_initials(string $name): string {
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
    <title>Reports & Analytics | LabSync</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">

    <!-- Chart.js (External) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/admin-analytics.css?v=123">
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
            <a href="issue-reports.php" class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link"><i class="bi bi-tools"></i> Maintenance Records</a>
            <a href="analytics.php" class="sidebar-link active"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?= e(pcare_analytics_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
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
                    <?= is_super_admin() ? 'Super Administrator' : 'Administrator' ?> <span class="mx-2">/</span> <strong>Reports & Analytics</strong>
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
                    <div class="welcome-label">SYSTEM PERFORMANCE</div>
                    <h1 class="welcome-title">Reports & Analytics</h1>
                    <p class="welcome-description">
                        Monitor issue reports, maintenance activity, and computer unit performance using database records.
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap" style="position: relative; z-index: 1;">
                    <span class="period-label" style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3);"><i class="bi bi-calendar-range"></i> Last 12 Months</span>
                    <button type="button" class="report-button print-button" onclick="window.print()" style="padding: 10px 18px; border-radius: 10px; font-size: 12px;">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </section>

            <!-- DATABASE ERROR NOTICE -->
            <?php if ($dbError): ?>
                <div class="alert alert-danger mb-4" style="border-radius: 12px; border: none; box-shadow: var(--shadow-sm);">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Unable to retrieve all analytics data. Please check the PHP error log and database connection.
                </div>
            <?php endif; ?>

            <!-- REPORT SUMMARY -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-clipboard-data"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($stats['total']) ?></div>
                        <div class="stat-label">Total Reports</div>
                        <div class="stat-note">All submitted issues</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($stats['pending']) ?></div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-note">Awaiting inspection</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-tools"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($stats['in_progress']) ?></div>
                        <div class="stat-label">In Progress</div>
                        <div class="stat-note">Inspection or repair</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($stats['resolved']) ?></div>
                        <div class="stat-label">Resolved</div>
                        <div class="stat-note">Successfully resolved</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($stats['unresolved']) ?></div>
                        <div class="stat-label">Unresolved</div>
                        <div class="stat-note">Not resolved</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-wrench-adjustable"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($stats['maintenance']) ?></div>
                        <div class="stat-label">Maintenance</div>
                        <div class="stat-note">Recorded activities</div>
                    </div>
                </div>
            </div>

            <!-- RESOLUTION RATE -->
            <section class="chart-card mb-4">
                <div class="chart-heading">
                    <h5 class="chart-title">Overall Resolution Rate</h5>
                    <span class="chart-subtitle">Percentage of all submitted reports currently marked as Resolved.</span>
                </div>
                <div class="chart-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <div style="font-size:34px;font-weight:800;color:var(--deep-green);letter-spacing:-1px;">
                                <?= number_format($resolutionRate, 1) ?>%
                            </div>
                            <div class="text-secondary small mt-1">
                                <?= number_format($stats['resolved']) ?> resolved out of <?= number_format($stats['total']) ?> total reports
                            </div>
                        </div>
                        <div style="flex:1;min-width:180px;max-width:520px;">
                            <div class="progress" role="progressbar" aria-label="Resolution rate" aria-valuenow="<?= $resolutionRate ?>" aria-valuemin="0" aria-valuemax="100" style="height:12px;background:var(--soft-mint);border-radius:20px;">
                                <div class="progress-bar" style="width:<?= $resolutionRate ?>%;background:var(--primary);border-radius:20px;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-secondary" style="font-size:9px;">
                                <span>0%</span><span>100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MONTHLY REPORT TREND -->
            <section class="chart-card mb-4">
                <div class="chart-heading">
                    <h5 class="chart-title">Monthly Issue Reports</h5>
                    <span class="chart-subtitle">Number of issue reports submitted each month from <?= e($startLabel) ?> to <?= e($endLabel) ?>.</span>
                </div>
                <div class="chart-body">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </section>

            <!-- CATEGORY AND STATUS CHARTS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <section class="chart-card">
                        <div class="chart-heading">
                            <h5 class="chart-title">Reports by Issue Category</h5>
                            <span class="chart-subtitle">Distribution of submitted issues across the database categories.</span>
                        </div>
                        <div class="chart-body">
                            <?php if (!empty($categoryLabels)): ?>
                                <canvas id="categoryChart"></canvas>
                            <?php else: ?>
                                <div class="chart-empty"><i class="bi bi-pie-chart"></i> No category report data available yet.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-6">
                    <section class="chart-card">
                        <div class="chart-heading">
                            <h5 class="chart-title">Reports by Status</h5>
                            <span class="chart-subtitle">Current distribution of issue report statuses.</span>
                        </div>
                        <div class="chart-body">
                            <?php if (!empty($statusLabels)): ?>
                                <canvas id="statusChart"></canvas>
                            <?php else: ?>
                                <div class="chart-empty"><i class="bi bi-bar-chart"></i> No issue status data available yet.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- COMPUTER UNIT CHART -->
            <section class="chart-card mb-4">
                <div class="chart-heading">
                    <h5 class="chart-title">Reports per Computer Unit</h5>
                    <span class="chart-subtitle">Number of issue reports associated with each registered computer unit.</span>
                </div>
                <div class="chart-body">
                    <?php if (!empty($unitLabels)): ?>
                        <canvas id="unitChart"></canvas>
                    <?php else: ?>
                        <div class="chart-empty"><i class="bi bi-pc-display"></i> No computer unit report data available yet.</div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- REPORT INFORMATION -->
            <section class="chart-card">
                <div class="chart-heading">
                    <h5 class="chart-title">Analytics Information</h5>
                    <span class="chart-subtitle">Notes about the displayed report data.</span>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-section">
                                <span class="detail-label">Reporting Period</span>
                                <div class="detail-value"><?= e($startLabel) ?> – <?= e($endLabel) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-section">
                                <span class="detail-label">Data Source</span>
                                <div class="detail-value">LabSync MySQL Database</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-section">
                                <span class="detail-label">Project Coverage</span>
                                <div class="detail-value">B204 Computer Laboratory</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-section">
                                <span class="detail-label">Report Generated</span>
                                <div class="detail-value"><?= date('F d, Y h:i A') ?></div>
                            </div>
                        </div>
                    </div>
                    <p class="text-secondary mt-3 mb-0" style="font-size:10px;line-height:1.6;">
                        These analytics are calculated from the records currently stored in the LabSync database.
                        The monthly trend covers the last 12 months. Other charts use all available issue reports.
                        The resolution rate is calculated using resolved reports divided by total reports.
                    </p>
                </div>
            </section>

            <footer class="page-footer">
                LabSync · <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?> · SLSU Lucena Campus
            </footer>
        </main>
    </div>

    <!-- PASSING PHP DATA TO EXTERNAL JS -->
    <script>
        window.monthlyLabels = <?= json_encode($monthlyLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        window.monthlyData = <?= json_encode($monthlyData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        
        window.categoryLabels = <?= json_encode($categoryLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        window.categoryData = <?= json_encode($categoryData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        
        window.statusLabels = <?= json_encode($statusLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        window.statusData = <?= json_encode($statusData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        
        window.unitLabels = <?= json_encode($unitLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        window.unitData = <?= json_encode($unitData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    </script>

    <!-- External JS -->
    <script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>
    <script src="../assets/js/admin-analytics.js"></script>

</body>
</html>