
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();

// ==========================================
// DASHBOARD STATISTICS
// ==========================================

$totalUsers = (int) $pdo->query("
    SELECT COUNT(*) FROM users WHERE is_active = 1
")->fetchColumn();

$totalUnits = (int) $pdo->query("
    SELECT COUNT(*) FROM computer_units
")->fetchColumn();

$totalReports = (int) $pdo->query("
    SELECT COUNT(*) FROM issue_reports
")->fetchColumn();

$issueStats = $pdo->query("
    SELECT
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status IN ('Under Inspection', 'Under Repair') THEN 1 ELSE 0 END) AS ongoing,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) AS unresolved
    FROM issue_reports
")->fetch(PDO::FETCH_ASSOC);

$pendingReports = (int) ($issueStats['pending'] ?? 0);
$ongoingReports = (int) ($issueStats['ongoing'] ?? 0);
$resolvedReports = (int) ($issueStats['resolved'] ?? 0);
$unresolvedReports = (int) ($issueStats['unresolved'] ?? 0);

$totalMaintenance = (int) $pdo->query("
    SELECT COUNT(*) FROM maintenance_records
")->fetchColumn();

// ==========================================
// RECENT ISSUE REPORTS
// ==========================================

$stmt = $pdo->query("
    SELECT
        r.report_id,
        r.reference_no,
        r.status,
        r.created_at,
        reporter.full_name AS reporter_name,
        c.unit_code,
        cat.category_name
    FROM issue_reports r
    INNER JOIN users reporter
        ON r.reporter_id = reporter.user_id
    INNER JOIN computer_units c
        ON r.unit_id = c.unit_id
    INNER JOIN issue_categories cat
        ON r.category_id = cat.category_id
    ORDER BY r.created_at DESC, r.report_id DESC
    LIMIT 5
");

$recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// ADMINISTRATOR INFORMATION
// ==========================================

$adminName = $_SESSION['full_name'] ?? 'Administrator';

$stmt = $pdo->prepare("
    SELECT full_name
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'user_id' => $_SESSION['user_id']
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $adminName = $admin['full_name'];
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function adminStatusClass(string $status): string
{
    switch ($status) {
        case 'Pending':
            return 'pending';

        case 'Under Inspection':
            return 'inspection';

        case 'Under Repair':
            return 'repair';

        case 'Resolved':
            return 'resolved';

        case 'Unresolved':
            return 'unresolved';

        default:
            return 'default';
    }
}

function adminInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }

    return $initials ?: 'AD';
}

$adminInitials = adminInitials($adminName);

$statusTotal = $pendingReports + $ongoingReports
    + $resolvedReports + $unresolvedReports;

function statusPercentage(int $value, int $total): int
{
    return $total > 0 ? (int) round(($value / $total) * 100) : 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary: #4E968B;
            --deep-green: #245B54;
            --sidebar: #193F3A;
            --sidebar-hover: #28534C;
            --soft-mint: #E5F0ED;
            --background: #F6F8F6;
            --text: #303C39;
            --muted: #84918C;
            --border: #E8EEEB;
            --white: #FFFFFF;
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
            position: fixed;
            inset: 0 auto 0 0;
            background: var(--sidebar);
            color: white;
            padding: 25px 17px;
            display: flex;
            flex-direction: column;
            z-index: 1050;
            transition: transform .25s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 10px;
            color: white;
            margin-bottom: 37px;
        }

        .brand-icon {
            width: 39px;
            height: 39px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.12);
            font-size: 19px;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .brand-caption {
            display: block;
            font-size: 9px;
            color: #B7CEC7;
            font-weight: 600;
            letter-spacing: 1.2px;
            margin-top: 2px;
        }

        .nav-label {
            color: #91ADA4;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.3px;
            padding: 0 12px;
            margin: 0 0 11px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 13px;
            border-radius: 9px;
            color: #C6D7D1;
            font-size: 12px;
            font-weight: 600;
            transition: .2s ease;
        }

        .nav-link-custom i {
            font-size: 16px;
            width: 19px;
            text-align: center;
        }

        .nav-link-custom:hover {
            background: var(--sidebar-hover);
            color: white;
        }

        .nav-link-custom.active {
            color: white;
            background: rgba(255,255,255,.13);
            box-shadow: inset 3px 0 0 #8CC9BA;
        }

        .sidebar-bottom {
            margin-top: auto;
        }

        .sidebar-profile {
            border-top: 1px solid rgba(255,255,255,.12);
            padding: 19px 7px 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #D8EBE5;
            color: var(--deep-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
        }

        .profile-name {
            color: white;
            font-size: 11px;
            font-weight: 700;
        }

        .profile-role {
            color: #A8C0B7;
            font-size: 10px;
            margin-top: 3px;
        }

        /* MAIN LAYOUT */

        .main-wrapper {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            height: 73px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
        }

        .topbar-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-date {
            color: var(--muted);
            font-size: 11px;
        }

        .topbar-avatar {
            width: 35px;
            height: 35px;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
        }

        .mobile-menu {
            display: none;
            border: 0;
            background: transparent;
            color: var(--deep-green);
            font-size: 22px;
        }

        .page-content {
            max-width: 1600px;
            padding: 34px 35px;
            margin: auto;
        }

        /* PAGE INTRO */

        .page-eyebrow {
            color: var(--primary);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.4px;
            margin-bottom: 9px;
        }

        .page-title {
            color: var(--deep-green);
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 7px;
        }

        .page-description {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
        }

        .logout-btn {
            border: 1px solid var(--border);
            background: white;
            color: #68766F;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            transition: .2s ease;
        }

        .logout-btn:hover {
            border-color: #E7C8C4;
            background: #FFF5F3;
            color: #A3483A;
        }

        /* WELCOME PANEL */

        .welcome-panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(115deg, #245B54 0%, #36796E 100%);
            color: white;
            border-radius: 16px;
            padding: 25px 29px;
            margin-bottom: 27px;
            box-shadow: 0 9px 25px rgba(36,91,84,.10);
        }

        .welcome-panel::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            right: 45px;
            top: -110px;
            box-shadow: 0 0 0 28px rgba(255,255,255,.035),
                        0 0 0 58px rgba(255,255,255,.025);
        }

        .welcome-label {
            color: #C5E2D9;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .welcome-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.5px;
            margin-bottom: 7px;
        }

        .welcome-description {
            color: #D8E9E3;
            font-size: 11px;
            margin: 0;
            max-width: 550px;
            line-height: 1.7;
        }

        .welcome-icon {
            position: absolute;
            right: 55px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.15);
            font-size: 75px;
        }

        /* SECTION HEADINGS */

        .section-title {
            color: var(--deep-green);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -.3px;
            margin-bottom: 5px;
        }

        .section-subtitle {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 0;
        }

        .section-header {
            margin-bottom: 16px;
        }

        /* STAT CARDS */

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 19px;
            height: 100%;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(36,91,84,.07);
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stat-number {
            color: var(--deep-green);
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -1px;
            margin: 0;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-footnote {
            color: #9AA59F;
            font-size: 10px;
            margin-top: 12px;
        }

        /* STATUS OVERVIEW */

        .status-panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 21px;
            height: 100%;
        }

        .status-item {
            margin-bottom: 18px;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-name {
            color: #64736C;
            font-size: 11px;
            font-weight: 600;
        }

        .status-count {
            color: var(--deep-green);
            font-size: 12px;
            font-weight: 800;
        }

        .progress-track {
            height: 6px;
            border-radius: 10px;
            background: #EEF2EF;
            overflow: hidden;
            margin-top: 9px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
        }

        .status-total {
            background: #F7F9F8;
            border-radius: 10px;
            padding: 13px 14px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-total span:first-child {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
        }

        .status-total strong {
            color: var(--deep-green);
            font-size: 16px;
        }

        /* QUICK ACCESS */

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            height: 100%;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            transition: .2s ease;
        }

        .quick-link:hover {
            border-color: #A9CFC4;
            background: #FCFDFC;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(36,91,84,.05);
        }

        .quick-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 17px;
        }

        .quick-title {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .quick-description {
            color: var(--muted);
            font-size: 10px;
            margin: 0;
        }

        .quick-arrow {
            margin-left: auto;
            color: #A6B3AC;
            font-size: 13px;
        }

        /* REPORT TABLE */

        .section-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
            overflow: hidden;
        }

        .table-heading {
            padding: 19px 21px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #F8FAF9;
            color: #829089;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            padding: 14px 17px;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        .table tbody td {
            color: #596960;
            font-size: 11px;
            padding: 15px 17px;
            vertical-align: middle;
            border-bottom: 1px solid #F0F3F1;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: #FBFDFC;
        }

        .report-link {
            color: var(--deep-green);
            font-weight: 800;
            white-space: nowrap;
        }

        .report-link:hover {
            color: var(--primary);
        }

        .reporter-name {
            color: var(--text);
            font-weight: 600;
        }

        .unit-label {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            background: #F1F5F3;
            color: #52665D;
            font-size: 10px;
            font-weight: 700;
        }

        /* STATUS BADGES */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 9px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-badge::before {
            content: "";
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-badge.pending {
            background: #FFF4D9;
            color: #986A00;
        }

        .status-badge.inspection {
            background: #E4F4FA;
            color: #26728F;
        }

        .status-badge.repair {
            background: #E9E9FF;
            color: #5556A5;
        }

        .status-badge.resolved {
            background: #E2F3E9;
            color: #287447;
        }

        .status-badge.unresolved {
            background: #FCE8E8;
            color: #AD4141;
        }

        .status-badge.default {
            background: #EDF0EF;
            color: #64716C;
        }

        .view-all {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 800;
        }

        .view-all:hover {
            color: var(--primary);
        }

        .empty-state {
            padding: 42px 20px !important;
            text-align: center;
            color: var(--muted) !important;
        }

        .empty-state i {
            font-size: 29px;
            color: #A7B8AF;
        }

        /* FOOTER */

        .page-footer {
            text-align: center;
            color: #9AA59F;
            font-size: 10px;
            line-height: 1.8;
            padding: 28px 0 5px;
        }

        .sidebar-overlay {
            display: none;
        }

        /* RESPONSIVE */

        @media (max-width: 991px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(18,38,34,.45);
                z-index: 1040;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-menu {
                display: inline-flex;
                align-items: center;
            }

            .topbar {
                padding: 0 20px;
            }

            .page-content {
                padding: 28px 20px;
            }

        }

        @media (max-width: 576px) {

            .topbar {
                height: 63px;
                padding: 0 15px;
            }

            .topbar-date {
                display: none;
            }

            .page-content {
                padding: 24px 14px;
            }

            .page-title {
                font-size: 22px;
            }

            .welcome-panel {
                padding: 22px;
            }

            .welcome-title {
                font-size: 17px;
            }

            .welcome-icon {
                right: 20px;
                font-size: 55px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 24px;
            }

            .stat-icon {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            .table-heading {
                padding: 16px;
            }

        }

    </style>

</head>

<body>

<!-- MOBILE OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <a href="dashboard.php" class="brand">

        <span class="brand-icon">
            <i class="bi bi-pc-display-horizontal"></i>
        </span>

        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
        </span>

    </a>

    <div class="nav-label">MAIN MENU</div>

    <nav class="sidebar-nav">

        <a href="dashboard.php" class="nav-link-custom active">
            <i class="bi bi-grid-1x2-fill"></i>
            Dashboard
        </a>

        <a href="users.php" class="nav-link-custom">
            <i class="bi bi-people"></i>
            User Management
        </a>

        <a href="computer-units.php" class="nav-link-custom">
            <i class="bi bi-pc-display"></i>
            Computer Units
        </a>

        <a href="categories.php" class="nav-link-custom">
            <i class="bi bi-grid"></i>
            Issue Categories
        </a>

        <a href="issue-reports.php" class="nav-link-custom">
            <i class="bi bi-clipboard-data"></i>
            Issue Reports
        </a>

        <a href="maintenance-records.php" class="nav-link-custom">
            <i class="bi bi-tools"></i>
            Maintenance Records
        </a>

        <a href="analytics.php" class="nav-link-custom">
            <i class="bi bi-bar-chart-line"></i>
            Reports & Analytics
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="nav-label">ACCOUNT</div>

        <a href="../logout.php" class="nav-link-custom">
            <i class="bi bi-box-arrow-right"></i>
            Sign Out
        </a>

        <div class="sidebar-profile">

            <div class="profile-avatar">
                <?= e($adminInitials) ?>
            </div>

            <div class="overflow-hidden">

                <div class="profile-name text-truncate">
                    <?= e($adminName) ?>
                </div>

                <div class="profile-role">
                    Administrator
                </div>

            </div>

        </div>

    </div>

</aside>


<!-- MAIN WRAPPER -->
<div class="main-wrapper">

    <!-- TOPBAR -->
    <header class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button
                type="button"
                class="mobile-menu"
                id="menuToggle"
                aria-label="Toggle navigation"
            >
                <i class="bi bi-list"></i>
            </button>

            <span class="topbar-label">
                Administrator Workspace
            </span>

        </div>

        <div class="topbar-right">

            <span class="topbar-date">
                <i class="bi bi-calendar3 me-1"></i>
                <?= e(date('F d, Y')) ?>
            </span>

            <div class="topbar-avatar">
                <?= e($adminInitials) ?>
            </div>

        </div>

    </header>


    <!-- PAGE CONTENT -->
    <main class="page-content">

        <!-- PAGE INTRO -->
        <section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>

                <div class="page-eyebrow">
                    LABSYNC / OVERVIEW
                </div>

                <h1 class="page-title">
                    Administrator Dashboard
                </h1>

                <p class="page-description">
                    Monitor your computer laboratories and manage system activities.
                </p>

            </div>

            <a href="../logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right me-1"></i>
                Sign Out
            </a>

        </section>


        <!-- WELCOME PANEL -->
        <section class="welcome-panel">

            <div class="position-relative" style="z-index:1;">

                <div class="welcome-label">
                    ADMINISTRATOR WORKSPACE
                </div>

                <h2 class="welcome-title">
                    Welcome back, <?= e($adminName) ?>!
                </h2>

                <p class="welcome-description">
                    Here's what's happening across your LabSync system.
                    Review computer issue reports, monitor maintenance progress,
                    and manage your laboratory records in one place.
                </p>

            </div>

            <i class="bi bi-pc-display-horizontal welcome-icon"></i>

        </section>


        <!-- MAIN STATISTICS -->
        <section class="mb-4">

            <div class="section-header">

                <h2 class="section-title">
                    System Overview
                </h2>

                <p class="section-subtitle">
                    Summary of registered users and system records.
                </p>

            </div>

            <div class="row g-3">

                <div class="col-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <p class="stat-label">Active Users</p>
                                <h3 class="stat-number"><?= $totalUsers ?></h3>
                            </div>

                            <div class="stat-icon" style="background:#E5F0ED;color:#245B54;">
                                <i class="bi bi-people"></i>
                            </div>

                        </div>

                        <div class="stat-footnote">
                            <i class="bi bi-person-check me-1"></i>
                            Currently active accounts
                        </div>

                    </div>

                </div>


                <div class="col-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <p class="stat-label">Computer Units</p>
                                <h3 class="stat-number"><?= $totalUnits ?></h3>
                            </div>

                            <div class="stat-icon" style="background:#E8F1F8;color:#397A9A;">
                                <i class="bi bi-pc-display"></i>
                            </div>

                        </div>

                        <div class="stat-footnote">
                            <i class="bi bi-hdd-stack me-1"></i>
                            Registered computer units
                        </div>

                    </div>

                </div>


                <div class="col-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <p class="stat-label">Issue Reports</p>
                                <h3 class="stat-number"><?= $totalReports ?></h3>
                            </div>

                            <div class="stat-icon" style="background:#FFF2DC;color:#A8781B;">
                                <i class="bi bi-clipboard-data"></i>
                            </div>

                        </div>

                        <div class="stat-footnote">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Total submitted reports
                        </div>

                    </div>

                </div>


                <div class="col-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <p class="stat-label">Maintenance Records</p>
                                <h3 class="stat-number"><?= $totalMaintenance ?></h3>
                            </div>

                            <div class="stat-icon" style="background:#E5F3E9;color:#378054;">
                                <i class="bi bi-tools"></i>
                            </div>

                        </div>

                        <div class="stat-footnote">
                            <i class="bi bi-journal-check me-1"></i>
                            Documented maintenance activities
                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- ISSUE STATUS OVERVIEW -->
        <section class="mb-4">

            <div class="section-header">

                <h2 class="section-title">
                    Issue Status Overview
                </h2>

                <p class="section-subtitle">
                    Current distribution of reported computer issues.
                </p>

            </div>

            <div class="status-panel">

                <div class="row g-4">

                    <div class="col-12 col-md-6">

                        <div class="status-item">

                            <div class="d-flex justify-content-between">

                                <span class="status-name">
                                    <i class="bi bi-circle-fill me-2" style="color:#D9A441;font-size:7px;"></i>
                                    Pending
                                </span>

                                <span class="status-count">
                                    <?= $pendingReports ?>
                                </span>

                            </div>

                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    style="width:<?= statusPercentage($pendingReports, $statusTotal) ?>%;background:#D9A441;"
                                ></div>
                            </div>

                        </div>


                        <div class="status-item">

                            <div class="d-flex justify-content-between">

                                <span class="status-name">
                                    <i class="bi bi-circle-fill me-2" style="color:#4B9AB8;font-size:7px;"></i>
                                    Ongoing
                                </span>

                                <span class="status-count">
                                    <?= $ongoingReports ?>
                                </span>

                            </div>

                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    style="width:<?= statusPercentage($ongoingReports, $statusTotal) ?>%;background:#4B9AB8;"
                                ></div>
                            </div>

                        </div>

                    </div>


                    <div class="col-12 col-md-6">

                        <div class="status-item">

                            <div class="d-flex justify-content-between">

                                <span class="status-name">
                                    <i class="bi bi-circle-fill me-2" style="color:#4B9A69;font-size:7px;"></i>
                                    Resolved
                                </span>

                                <span class="status-count">
                                    <?= $resolvedReports ?>
                                </span>

                            </div>

                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    style="width:<?= statusPercentage($resolvedReports, $statusTotal) ?>%;background:#4B9A69;"
                                ></div>
                            </div>

                        </div>


                        <div class="status-item">

                            <div class="d-flex justify-content-between">

                                <span class="status-name">
                                    <i class="bi bi-circle-fill me-2" style="color:#C66C6C;font-size:7px;"></i>
                                    Unresolved
                                </span>

                                <span class="status-count">
                                    <?= $unresolvedReports ?>
                                </span>

                            </div>

                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    style="width:<?= statusPercentage($unresolvedReports, $statusTotal) ?>%;background:#C66C6C;"
                                ></div>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="status-total">

                    <span>
                        Total issue reports
                    </span>

                    <strong>
                        <?= $statusTotal ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- QUICK ACCESS -->
        <section class="mb-4">

            <div class="section-header">

                <h2 class="section-title">
                    Quick Access
                </h2>

                <p class="section-subtitle">
                    Navigate to the main system management modules.
                </p>

            </div>

            <div class="row g-3">

                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="users.php" class="quick-link">

                        <div class="quick-icon">
                            <i class="bi bi-people"></i>
                        </div>

                        <div>
                            <div class="quick-title">User Management</div>
                            <p class="quick-description">Manage user accounts</p>
                        </div>

                        <i class="bi bi-arrow-up-right quick-arrow"></i>

                    </a>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="computer-units.php" class="quick-link">

                        <div class="quick-icon">
                            <i class="bi bi-pc-display"></i>
                        </div>

                        <div>
                            <div class="quick-title">Computer Units</div>
                            <p class="quick-description">Manage registered units</p>
                        </div>

                        <i class="bi bi-arrow-up-right quick-arrow"></i>

                    </a>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="categories.php" class="quick-link">

                        <div class="quick-icon">
                            <i class="bi bi-grid"></i>
                        </div>

                        <div>
                            <div class="quick-title">Issue Categories</div>
                            <p class="quick-description">Manage issue categories</p>
                        </div>

                        <i class="bi bi-arrow-up-right quick-arrow"></i>

                    </a>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="analytics.php" class="quick-link">

                        <div class="quick-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>

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

                    <h2 class="section-title mb-1">
                        Recent Issue Reports
                    </h2>

                    <p class="section-subtitle">
                        Latest computer issues submitted to LabSync.
                    </p>

                </div>

                <a href="issue-reports.php" class="view-all">
                    View all
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>

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

                                    <p class="fw-bold mt-2 mb-1">
                                        No issue reports yet
                                    </p>

                                    <p class="mb-0">
                                        Submitted reports will appear here.
                                    </p>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($recentReports as $report): ?>

                                <tr>

                                    <td>

                                        <a href="issue-reports.php" class="report-link">
                                            <?= e($report['reference_no']) ?>
                                        </a>

                                    </td>

                                    <td class="reporter-name">
                                        <?= e($report['reporter_name']) ?>
                                    </td>

                                    <td>
                                        <span class="unit-label">
                                            <?= e($report['unit_code']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($report['category_name']) ?>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= e(date(
                                            'M d, Y · h:i A',
                                            strtotime($report['created_at'])
                                        )) ?>
                                    </td>

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

                <a href="issue-reports.php" class="view-all">
                    Go to Issue Reports
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>

            </div>

        </section>


        <!-- FOOTER -->
        <footer class="page-footer">

            <div class="fw-bold">
                LabSync
            </div>

            <div>
                Computer Issue Reporting and Maintenance Tracking System
            </div>

            <div>
                SLSU Lucena Campus Computer Laboratories
            </div>

        </footer>

    </main>

</div>


<!-- MOBILE SIDEBAR SCRIPT -->
<script>

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }

    menuToggle.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

    document.querySelectorAll('.nav-link-custom').forEach(link => {
        link.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    });
  
</script>

</body>
</html>
