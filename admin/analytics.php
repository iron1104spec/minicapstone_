
<?php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

if (!function_exists('pcare_analytics_initials')) {

    function pcare_analytics_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        return $initials ?: 'A';
    }
}

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$dbError = false;

$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'unresolved' => 0,
    'maintenance' => 0
];

$monthlyLabels = [];
$monthlyData = [];

$categoryLabels = [];
$categoryData = [];

$unitLabels = [];
$unitData = [];

$statusLabels = [];
$statusData = [];

/*
|--------------------------------------------------------------------------
| DATABASE ANALYTICS
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | ISSUE REPORT STATISTICS
    |--------------------------------------------------------------------------
    */

    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) AS total,

            COALESCE(
                SUM(status = 'Pending'),
                0
            ) AS pending,

            COALESCE(
                SUM(status IN ('Under Inspection', 'Under Repair')),
                0
            ) AS in_progress,

            COALESCE(
                SUM(status = 'Resolved'),
                0
            ) AS resolved,

            COALESCE(
                SUM(status = 'Unresolved'),
                0
            ) AS unresolved

        FROM issue_reports
    ");

    $reportStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $stats['total'] = (int) $reportStats['total'];
    $stats['pending'] = (int) $reportStats['pending'];
    $stats['in_progress'] = (int) $reportStats['in_progress'];
    $stats['resolved'] = (int) $reportStats['resolved'];
    $stats['unresolved'] = (int) $reportStats['unresolved'];

    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE RECORD COUNT
    |--------------------------------------------------------------------------
    */

    $maintenanceStmt = $pdo->query("
        SELECT COUNT(*)
        FROM maintenance_records
    ");

    $stats['maintenance'] = (int) $maintenanceStmt->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | MONTHLY REPORTS - LAST 12 MONTHS
    |--------------------------------------------------------------------------
    */

    $startMonth = new DateTimeImmutable(
        date('Y-m-01', strtotime('-11 months'))
    );

    $startDate = $startMonth->format('Y-m-d');

    $monthlyStmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS month_key,
            COUNT(*) AS total

        FROM issue_reports

        WHERE created_at >= :start_date

        GROUP BY DATE_FORMAT(created_at, '%Y-%m')

        ORDER BY month_key ASC
    ");

    $monthlyStmt->execute([
        'start_date' => $startDate
    ]);

    $monthlyRows = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare all 12 months, including months with zero reports.
    $monthlyMap = [];

    foreach ($monthlyRows as $row) {
        $monthlyMap[$row['month_key']] = (int) $row['total'];
    }

    $monthCursor = $startMonth;

    for ($i = 0; $i < 12; $i++) {

        $monthKey = $monthCursor->format('Y-m');

        $monthlyLabels[] = $monthCursor->format('M Y');

        $monthlyData[] = $monthlyMap[$monthKey] ?? 0;

        $monthCursor = $monthCursor->modify('+1 month');
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTS BY CATEGORY
    |--------------------------------------------------------------------------
    */

    $categoryStmt = $pdo->query("
        SELECT
            COALESCE(ic.category_name, 'Uncategorized') AS category_name,
            COUNT(ir.report_id) AS total

        FROM issue_reports ir

        LEFT JOIN issue_categories ic
            ON ir.category_id = ic.category_id

        GROUP BY
            ic.category_id,
            ic.category_name

        ORDER BY total DESC
    ");

    $categoryRows = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categoryRows as $row) {

        $categoryLabels[] = $row['category_name'];

        $categoryData[] = (int) $row['total'];
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTS BY COMPUTER UNIT
    |--------------------------------------------------------------------------
    */

    $unitStmt = $pdo->query("
        SELECT
            COALESCE(cu.unit_code, 'Unknown Unit') AS unit_code,
            COUNT(ir.report_id) AS total

        FROM issue_reports ir

        LEFT JOIN computer_units cu
            ON ir.unit_id = cu.unit_id

        GROUP BY
            cu.unit_id,
            cu.unit_code

        ORDER BY total DESC, unit_code ASC
    ");

    $unitRows = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($unitRows as $row) {

        $unitLabels[] = $row['unit_code'];

        $unitData[] = (int) $row['total'];
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTS BY STATUS
    |--------------------------------------------------------------------------
    */

    $statusStmt = $pdo->query("
        SELECT
            status,
            COUNT(*) AS total

        FROM issue_reports

        GROUP BY status

        ORDER BY total DESC
    ");

    $statusRows = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($statusRows as $row) {

        $statusLabels[] = $row['status'] ?: 'Unknown';

        $statusData[] = (int) $row['total'];
    }

} catch (PDOException $e) {

    error_log('PCare Analytics Error: ' . $e->getMessage());

    $dbError = true;
}

/*
|--------------------------------------------------------------------------
| CALCULATE RESOLUTION RATE
|--------------------------------------------------------------------------
*/

$resolutionRate = $stats['total'] > 0
    ? round(($stats['resolved'] / $stats['total']) * 100, 1)
    : 0;

$startLabel = date('M Y', strtotime('-11 months'));
$endLabel = date('M Y');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports & Analytics | PCare</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        :root {
            --primary: #4E968B;
            --deep-green: #245B54;
            --sidebar: #193F3A;
            --soft-mint: #E5F0ED;
            --background: #F7F8F6;
            --text: #303C39;
            --muted: #84918B;
            --border: #E8EEEB;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        a {
            text-decoration: none;
        }

        /* SIDEBAR */

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            background: var(--sidebar);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 25px 16px;
            transition: transform .25s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 10px;
            margin-bottom: 38px;
            color: white;
        }

        .brand-icon {
            width: 39px;
            height: 39px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.13);
            border-radius: 11px;
            font-size: 19px;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .brand-subtitle {
            display: block;
            color: rgba(255,255,255,.55);
            font-size: 9px;
            letter-spacing: 1.1px;
            font-weight: 700;
            margin-top: 2px;
        }

        .sidebar-label {
            padding: 0 11px;
            margin: 0 0 11px;
            color: rgba(255,255,255,.43);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.2px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 12px;
            color: rgba(255,255,255,.72);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            transition: .2s ease;
        }

        .sidebar-link i {
            font-size: 16px;
            width: 19px;
            text-align: center;
        }

        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.09);
        }

        .sidebar-link.active {
            background: rgba(255,255,255,.15);
            color: white;
            box-shadow: inset 3px 0 0 #82C4B5;
        }

        .sidebar-bottom {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,.12);
            padding-top: 17px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 9px;
            margin-bottom: 10px;
        }

        .admin-avatar {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,.16);
            color: white;
            font-size: 11px;
            font-weight: 800;
        }

        .admin-name {
            font-size: 11px;
            font-weight: 700;
            color: white;
        }

        .admin-role {
            color: rgba(255,255,255,.5);
            font-size: 9px;
            margin-top: 3px;
        }

        .logout-link {
            color: rgba(255,255,255,.65);
        }

        .logout-link:hover {
            color: white;
        }

        /* MAIN LAYOUT */

        .main-wrapper {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 34px;
        }

        .breadcrumb-text {
            color: var(--muted);
            font-size: 11px;
        }

        .breadcrumb-text strong {
            color: var(--deep-green);
            font-weight: 700;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 17px;
            color: var(--muted);
            font-size: 11px;
        }

        .topbar-dot {
            width: 7px;
            height: 7px;
            background: #51A77A;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .mobile-toggle {
            display: none;
            border: 0;
            background: transparent;
            color: var(--deep-green);
            font-size: 22px;
        }

        .main-content {
            max-width: 1600px;
            padding: 32px 34px;
            margin: auto;
        }

        /* PAGE HEADING */

        .page-eyebrow {
            color: var(--primary);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.3px;
            margin-bottom: 8px;
        }

        .page-title {
            color: var(--deep-green);
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.9px;
            margin: 0 0 7px;
        }

        .page-description {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
            line-height: 1.6;
        }

        /* BUTTONS */

        .btn-primary-custom,
        .btn-light-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            transition: .2s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary-custom {
            background: var(--deep-green);
            color: white;
            border: 1px solid var(--deep-green);
            padding: 10px 15px;
        }

        .btn-primary-custom:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .btn-light-custom {
            background: white;
            border: 1px solid var(--border);
            color: #5F6E67;
            padding: 9px 13px;
        }

        .btn-light-custom:hover {
            background: #F5F8F6;
            color: var(--deep-green);
        }

        /* STATISTICS */

        .stat-card {
            height: 100%;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 17px;
            transition: .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(36,91,84,.055);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 600;
        }

        .stat-icon {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 9px;
            background: var(--soft-mint);
            color: var(--deep-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .stat-value {
            color: var(--deep-green);
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.8px;
            margin: 11px 0 0;
        }

        .stat-note {
            color: #98A39D;
            font-size: 9px;
            margin-top: 5px;
        }

        /* CHART CARDS */

        .chart-card {
            height: 100%;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .chart-heading {
            padding: 18px 21px;
            border-bottom: 1px solid var(--border);
        }

        .chart-title {
            color: var(--deep-green);
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .chart-subtitle {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
            line-height: 1.5;
        }

        .chart-body {
            position: relative;
            padding: 20px;
            min-height: 300px;
        }

        .chart-body canvas {
            width: 100% !important;
            max-height: 290px;
        }

        .chart-empty {
            min-height: 260px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--muted);
            font-size: 11px;
            text-align: center;
            gap: 8px;
        }

        .chart-empty i {
            font-size: 30px;
            color: #A8B8AF;
        }

        /* REPORTING PERIOD */

        .period-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 7px;
            padding: 8px 11px;
            font-size: 10px;
            font-weight: 700;
        }

        /* FOOTER */

        .page-footer {
            text-align: center;
            color: #A0AAA4;
            font-size: 10px;
            padding: 25px 0 5px;
        }

        .sidebar-overlay {
            display: none;
        }

        /* PRINT */

        @media print {

            body {
                background: white !important;
                font-size: 10px;
            }

            .sidebar,
            .topbar,
            .mobile-toggle,
            .sidebar-overlay,
            .print-button,
            .page-footer {
                display: none !important;
            }

            .main-wrapper {
                margin-left: 0 !important;
            }

            .main-content {
                padding: 10px !important;
                max-width: 100% !important;
            }

            .stat-card,
            .chart-card {
                break-inside: avoid;
                box-shadow: none !important;
            }

            .chart-body {
                min-height: 240px;
            }

            .page-title {
                font-size: 22px;
            }

        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-toggle {
                display: inline-flex;
                align-items: center;
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 1030;
                background: rgba(16,35,31,.42);
            }
        }

        @media (max-width: 768px) {

            .topbar {
                height: 62px;
                padding: 0 17px;
            }

            .topbar-right {
                gap: 8px;
                font-size: 9px;
            }

            .main-content {
                padding: 25px 15px;
            }

            .page-title {
                font-size: 22px;
            }

            .stat-card {
                padding: 14px;
            }

            .stat-value {
                font-size: 22px;
            }

            .chart-body {
                min-height: 260px;
                padding: 14px;
            }
        }

        @media (max-width: 480px) {

            .topbar-right .topbar-date {
                display: none;
            }

        }

    </style>

</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">

        <a href="dashboard.php" class="brand">

            <span class="brand-icon">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>

            <span>
                <span class="brand-name">PCare</span>
                <span class="brand-subtitle">LABORATORY MANAGEMENT</span>
            </span>

        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="sidebar-link">
                <i class="bi bi-grid-1x2"></i>
                Dashboard
            </a>

            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                User Management
            </a>

            <a href="computer-units.php" class="sidebar-link">
                <i class="bi bi-pc-display"></i>
                Computer Units
            </a>

            <a href="categories.php" class="sidebar-link">
                <i class="bi bi-tags"></i>
                Issue Categories
            </a>

            <a href="issue-reports.php" class="sidebar-link">
                <i class="bi bi-clipboard2-pulse"></i>
                Issue Reports
            </a>

            <a href="maintenance-records.php" class="sidebar-link">
                <i class="bi bi-tools"></i>
                Maintenance Records
            </a>

            <a href="analytics.php" class="sidebar-link active">
                <i class="bi bi-bar-chart"></i>
                Reports & Analytics
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-avatar">
                    <?= e(pcare_analytics_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
                </div>

                <div>

                    <div class="admin-name">
                        <?= e($_SESSION['full_name'] ?? 'Administrator') ?>
                    </div>

                    <div class="admin-role">
                        System Administrator
                    </div>

                </div>

            </div>

            <a href="../logout.php" class="sidebar-link logout-link">
                <i class="bi bi-box-arrow-left"></i>
                Sign Out
            </a>

        </div>

    </aside>

    <!-- MAIN CONTENT -->

    <div class="main-wrapper">

        <header class="topbar">

            <div class="d-flex align-items-center gap-3">

                <button
                    type="button"
                    class="mobile-toggle"
                    id="sidebarToggle"
                    aria-label="Toggle navigation"
                >
                    <i class="bi bi-list"></i>
                </button>

                <div class="breadcrumb-text">
                    Administrator
                    <span class="mx-2">/</span>
                    <strong>Reports & Analytics</strong>
                </div>

            </div>

            <div class="topbar-right">

                <span>
                    <span class="topbar-dot"></span>
                    Administrator Portal
                </span>

                <span class="topbar-date">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('F d, Y') ?>
                </span>

            </div>

        </header>

        <main class="main-content">

            <!-- PAGE HEADING -->

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <p class="page-eyebrow">
                        SYSTEM PERFORMANCE
                    </p>

                    <h1 class="page-title">
                        Reports & Analytics
                    </h1>

                    <p class="page-description">
                        Monitor issue reports, maintenance activity, and computer unit performance using database records.
                    </p>

                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">

                    <span class="period-label">
                        <i class="bi bi-calendar-range"></i>
                        Last 12 Months
                    </span>

                    <button
                        type="button"
                        class="btn-primary-custom print-button"
                        onclick="window.print()"
                    >
                        <i class="bi bi-printer"></i>
                        Print Report
                    </button>

                </div>

            </div>

            <!-- DATABASE ERROR NOTICE -->

            <?php if ($dbError): ?>

                <div class="alert alert-danger mb-4">

                    <i class="bi bi-exclamation-triangle me-2"></i>

                    Unable to retrieve all analytics data. Please check the PHP error log and database connection.

                </div>

            <?php endif; ?>

            <!-- REPORT SUMMARY -->

            <div class="row g-3 mb-4">

                <div class="col-6 col-md-4 col-xl-2">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Total Reports
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-clipboard-data"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= number_format($stats['total']) ?>
                        </h3>

                        <div class="stat-note">
                            All submitted issues
                        </div>

                    </div>

                </div>

                <div class="col-6 col-md-4 col-xl-2">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Pending
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-clock-history"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= number_format($stats['pending']) ?>
                        </h3>

                        <div class="stat-note">
                            Awaiting inspection
                        </div>

                    </div>

                </div>

                <div class="col-6 col-md-4 col-xl-2">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                In Progress
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-tools"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= number_format($stats['in_progress']) ?>
                        </h3>

                        <div class="stat-note">
                            Inspection or repair
                        </div>

                    </div>

                </div>

                <div class="col-6 col-md-4 col-xl-2">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Resolved
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-check-circle"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= number_format($stats['resolved']) ?>
                        </h3>

                        <div class="stat-note">
                            Successfully resolved
                        </div>

                    </div>

                </div>

                <div class="col-6 col-md-4 col-xl-2">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Unresolved
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-exclamation-circle"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= number_format($stats['unresolved']) ?>
                        </h3>

                        <div class="stat-note">
                            Not resolved
                        </div>

                    </div>

                </div>

                <div class="col-6 col-md-4 col-xl-2">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Maintenance
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-wrench-adjustable"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= number_format($stats['maintenance']) ?>
                        </h3>

                        <div class="stat-note">
                            Recorded activities
                        </div>

                    </div>

                </div>

            </div>

            <!-- RESOLUTION RATE -->

            <section class="chart-card mb-4">

                <div class="chart-heading">

                    <h5 class="chart-title">
                        Overall Resolution Rate
                    </h5>

                    <span class="chart-subtitle">
                        Percentage of all submitted reports currently marked as Resolved.
                    </span>

                </div>

                <div class="chart-body">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div>

                            <div
                                style="font-size:34px;font-weight:800;color:#245B54;letter-spacing:-1px;"
                            >
                                <?= number_format($resolutionRate, 1) ?>%
                            </div>

                            <div class="text-secondary small mt-1">
                                <?= number_format($stats['resolved']) ?>
                                resolved out of
                                <?= number_format($stats['total']) ?>
                                total reports
                            </div>

                        </div>

                        <div style="flex:1;min-width:180px;max-width:520px;">

                            <div
                                class="progress"
                                role="progressbar"
                                aria-label="Resolution rate"
                                aria-valuenow="<?= $resolutionRate ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                style="height:12px;background:#E5F0ED;border-radius:20px;"
                            >

                                <div
                                    class="progress-bar"
                                    style="width:<?= $resolutionRate ?>%;background:#4E968B;border-radius:20px;"
                                ></div>

                            </div>

                            <div class="d-flex justify-content-between mt-2 text-secondary" style="font-size:9px;">
                                <span>0%</span>
                                <span>100%</span>
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- MONTHLY REPORT TREND -->

            <section class="chart-card mb-4">

                <div class="chart-heading">

                    <h5 class="chart-title">
                        Monthly Issue Reports
                    </h5>

                    <span class="chart-subtitle">
                        Number of issue reports submitted each month from <?= e($startLabel) ?> to <?= e($endLabel) ?>.
                    </span>

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

                            <h5 class="chart-title">
                                Reports by Issue Category
                            </h5>

                            <span class="chart-subtitle">
                                Distribution of submitted issues across the database categories.
                            </span>

                        </div>

                        <div class="chart-body">

                            <?php if (!empty($categoryLabels)): ?>

                                <canvas id="categoryChart"></canvas>

                            <?php else: ?>

                                <div class="chart-empty">
                                    <i class="bi bi-pie-chart"></i>
                                    No category report data available yet.
                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                </div>

                <div class="col-12 col-xl-6">

                    <section class="chart-card">

                        <div class="chart-heading">

                            <h5 class="chart-title">
                                Reports by Status
                            </h5>

                            <span class="chart-subtitle">
                                Current distribution of issue report statuses.
                            </span>

                        </div>

                        <div class="chart-body">

                            <?php if (!empty($statusLabels)): ?>

                                <canvas id="statusChart"></canvas>

                            <?php else: ?>

                                <div class="chart-empty">
                                    <i class="bi bi-bar-chart"></i>
                                    No issue status data available yet.
                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                </div>

            </div>

            <!-- COMPUTER UNIT CHART -->

            <section class="chart-card mb-4">

                <div class="chart-heading">

                    <h5 class="chart-title">
                        Reports per Computer Unit
                    </h5>

                    <span class="chart-subtitle">
                        Number of issue reports associated with each registered computer unit.
                    </span>

                </div>

                <div class="chart-body">

                    <?php if (!empty($unitLabels)): ?>

                        <canvas id="unitChart"></canvas>

                    <?php else: ?>

                        <div class="chart-empty">
                            <i class="bi bi-pc-display"></i>
                            No computer unit report data available yet.
                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <!-- REPORT INFORMATION -->

            <section class="chart-card">

                <div class="chart-heading">

                    <h5 class="chart-title">
                        Analytics Information
                    </h5>

                    <span class="chart-subtitle">
                        Notes about the displayed report data.
                    </span>

                </div>

                <div class="p-4">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <div class="detail-section">

                                <span class="detail-label">
                                    Reporting Period
                                </span>

                                <div class="detail-value">
                                    <?= e($startLabel) ?> – <?= e($endLabel) ?>
                                </div>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="detail-section">

                                <span class="detail-label">
                                    Data Source
                                </span>

                                <div class="detail-value">
                                    PCare MySQL Database
                                </div>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="detail-section">

                                <span class="detail-label">
                                    Project Coverage
                                </span>

                                <div class="detail-value">
                                    B204 Computer Laboratory
                                </div>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="detail-section">

                                <span class="detail-label">
                                    Report Generated
                                </span>

                                <div class="detail-value">
                                    <?= date('F d, Y h:i A') ?>
                                </div>

                            </div>

                        </div>

                    </div>

                    <p class="text-secondary mt-3 mb-0" style="font-size:10px;line-height:1.6;">
                        These analytics are calculated from the records currently stored in the PCare database.
                        The monthly trend covers the last 12 months. Other charts use all available issue reports.
                        The resolution rate is calculated using resolved reports divided by total reports.
                    </p>

                </div>

            </section>

            <footer class="page-footer">
                PCare · Administrator Portal · SLSU Lucena Campus
            </footer>

        </main>

    </div>

    <!-- CHART.JS CONFIGURATION -->

    <script>

        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 10;
        Chart.defaults.color = '#84918B';

        const chartColors = [
            '#4E968B',
            '#245B54',
            '#82B8AA',
            '#A9CFC3',
            '#6B9E91',
            '#D1E4DC',
            '#688B81',
            '#B5CFC5',
            '#8BB3A5',
            '#49776C'
        ];

        /*
        |--------------------------------------------------------------------------
        | MONTHLY REPORT CHART
        |--------------------------------------------------------------------------
        */

        const monthlyLabels = <?= json_encode(
            $monthlyLabels,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const monthlyData = <?= json_encode(
            $monthlyData,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const monthlyCanvas = document.getElementById('monthlyChart');

        if (monthlyCanvas) {

            new Chart(monthlyCanvas, {

                type: 'line',

                data: {
                    labels: monthlyLabels,

                    datasets: [{
                        label: 'Issue Reports',
                        data: monthlyData,
                        borderColor: '#4E968B',
                        backgroundColor: 'rgba(78,150,139,0.12)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#245B54'
                    }]
                },

                options: {

                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            backgroundColor: '#245B54',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },

                    scales: {

                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: '#EDF1EF'
                            }
                        },

                        x: {
                            grid: {
                                display: false
                            }
                        }

                    }

                }

            });

        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY DISTRIBUTION CHART
        |--------------------------------------------------------------------------
        */

        const categoryLabels = <?= json_encode(
            $categoryLabels,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const categoryData = <?= json_encode(
            $categoryData,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const categoryCanvas = document.getElementById('categoryChart');

        if (categoryCanvas) {

            new Chart(categoryCanvas, {

                type: 'doughnut',

                data: {

                    labels: categoryLabels,

                    datasets: [{

                        data: categoryData,
                        backgroundColor: chartColors,
                        borderColor: '#FFFFFF',
                        borderWidth: 3,
                        hoverOffset: 7

                    }]

                },

                options: {

                    responsive: true,
                    maintainAspectRatio: false,

                    cutout: '65%',

                    plugins: {

                        legend: {
                            position: 'bottom',

                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 17
                            }
                        },

                        tooltip: {
                            backgroundColor: '#245B54',
                            padding: 12,
                            cornerRadius: 8
                        }

                    }

                }

            });

        }

        /*
        |--------------------------------------------------------------------------
        | STATUS DISTRIBUTION CHART
        |--------------------------------------------------------------------------
        */

        const statusLabels = <?= json_encode(
            $statusLabels,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const statusData = <?= json_encode(
            $statusData,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const statusColors = {
            'Pending': '#D6A04D',
            'Under Inspection': '#6B91BA',
            'Under Repair': '#9476B8',
            'Resolved': '#5A9B75',
            'Unresolved': '#C66F61'
        };

        const statusCanvas = document.getElementById('statusChart');

        if (statusCanvas) {

            new Chart(statusCanvas, {

                type: 'doughnut',

                data: {

                    labels: statusLabels,

                    datasets: [{

                        data: statusData,

                        backgroundColor: statusLabels.map(
                            label => statusColors[label] || '#A0AAA4'
                        ),

                        borderColor: '#FFFFFF',
                        borderWidth: 3,
                        hoverOffset: 7

                    }]

                },

                options: {

                    responsive: true,
                    maintainAspectRatio: false,

                    cutout: '65%',

                    plugins: {

                        legend: {
                            position: 'bottom',

                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 17
                            }
                        },

                        tooltip: {
                            backgroundColor: '#245B54',
                            padding: 12,
                            cornerRadius: 8
                        }

                    }

                }

            });

        }

        /*
        |--------------------------------------------------------------------------
        | COMPUTER UNIT CHART
        |--------------------------------------------------------------------------
        */

        const unitLabels = <?= json_encode(
            $unitLabels,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const unitData = <?= json_encode(
            $unitData,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const unitCanvas = document.getElementById('unitChart');

        if (unitCanvas) {

            new Chart(unitCanvas, {

                type: 'bar',

                data: {

                    labels: unitLabels,

                    datasets: [{

                        label: 'Issue Reports',
                        data: unitData,
                        backgroundColor: '#4E968B',
                        hoverBackgroundColor: '#245B54',
                        borderRadius: 6,
                        maxBarThickness: 28

                    }]

                },

                options: {

                    indexAxis: 'y',

                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {
                            backgroundColor: '#245B54',
                            padding: 12,
                            cornerRadius: 8
                        }

                    },

                    scales: {

                        x: {
                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color: '#EDF1EF'
                            }
                        },

                        y: {
                            grid: {
                                display: false
                            }
                        }

                    }

                }

            });

        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function closeSidebar() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }

        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', closeSidebar);

    </script>

</body>
</html>
