
<?php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

// Retrieve computer units from the existing database.
$stmt = $pdo->query("
    SELECT
        unit_id,
        unit_code,
        location,
        unit_status,
        remarks,
        created_at,
        updated_at
    FROM computer_units
    ORDER BY unit_code ASC
");

$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Database-driven statistics.
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(unit_status = 'Active') AS active,
        SUM(unit_status = 'Under Maintenance') AS maintenance,
        SUM(unit_status = 'Inactive') AS inactive
    FROM computer_units
")->fetch(PDO::FETCH_ASSOC);

function unitStatusClass(string $status): string
{
    return match ($status) {
        'Active' => 'active',
        'Under Maintenance' => 'maintenance',
        'Inactive' => 'inactive',
        default => 'unknown'
    };
}

function unitStatusIcon(string $status): string
{
    return match ($status) {
        'Active' => 'bi-check-circle',
        'Under Maintenance' => 'bi-tools',
        'Inactive' => 'bi-x-circle',
        default => 'bi-question-circle'
    };
}

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

    <style>

        :root {
            --primary: #4E968B;
            --deep-green: #245B54;
            --sidebar: #193F3A;
            --soft-mint: #E5F0ED;
            --background: #F6F8F6;
            --text: #303C39;
            --muted: #84918B;
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
        }

        /* STATISTICS */

        .stat-card {
            height: 100%;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 19px;
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
        }

        .stat-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 600;
        }

        .stat-icon {
            width: 35px;
            height: 35px;
            border-radius: 9px;
            background: var(--soft-mint);
            color: var(--deep-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .stat-value {
            color: var(--deep-green);
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -.8px;
            margin: 11px 0 0;
        }

        /* CONTENT CARD */

        .content-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-heading {
            padding: 19px 21px;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            color: var(--deep-green);
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .card-subtitle {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }

        /* UNIT GRID */

        .unit-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 15px;
            padding: 21px;
        }

        .unit-card {
            background: #FFFFFF;
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 17px;
            transition: .2s ease;
        }

        .unit-card:hover {
            transform: translateY(-3px);
            border-color: #B9D5CB;
            box-shadow: 0 9px 24px rgba(36,91,84,.07);
        }

        .unit-icon {
            width: 39px;
            height: 39px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 18px;
            margin-bottom: 16px;
        }

        .unit-code {
            color: var(--deep-green);
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.3px;
            margin-bottom: 5px;
        }

        .unit-location {
            color: var(--muted);
            font-size: 10px;
            margin-bottom: 14px;
        }

        .unit-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 700;
            max-width: 100%;
        }

        .unit-status.active {
            background: #E7F4EB;
            color: #39805A;
        }

        .unit-status.maintenance {
            background: #FFF4DF;
            color: #A97823;
        }

        .unit-status.inactive {
            background: #F0F1F0;
            color: #7B8580;
        }

        .unit-status.unknown {
            background: #F0F1F0;
            color: #7B8580;
        }

        .unit-status i {
            font-size: 10px;
        }

        .unit-remarks {
            color: #89958E;
            font-size: 10px;
            margin-top: 12px;
            line-height: 1.5;
        }

        /* TABLE */

        .table {
            font-size: 11px;
        }

        .table thead th {
            background: #F8FAF9;
            color: #84918B;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .65px;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        .table tbody td {
            padding: 14px 16px;
            color: #637169;
            border-color: #EEF2EF;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #FBFCFB;
        }

        .table-unit-code {
            color: var(--deep-green);
            font-weight: 800;
        }

        .table-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .table-status.active {
            background: #E7F4EB;
            color: #39805A;
        }

        .table-status.maintenance {
            background: #FFF4DF;
            color: #A97823;
        }

        .table-status.inactive,
        .table-status.unknown {
            background: #F0F1F0;
            color: #7B8580;
        }

        .table-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .empty-state {
            padding: 55px 20px !important;
            text-align: center;
            color: var(--muted) !important;
        }

        .empty-state i {
            display: block;
            font-size: 30px;
            color: #A8B8AF;
            margin-bottom: 10px;
        }

        .table-footer {
            border-top: 1px solid var(--border);
            padding: 13px 20px;
            color: var(--muted);
            font-size: 10px;
        }

        .page-footer {
            text-align: center;
            color: #A0AAA4;
            font-size: 10px;
            padding: 25px 0 5px;
        }

        .sidebar-overlay {
            display: none;
        }

        /* RESPONSIVE */

        @media (max-width: 1200px) {
            .unit-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

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

            .unit-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                padding: 15px;
                gap: 10px;
            }

            .unit-card {
                padding: 13px;
            }

        }

        @media (max-width: 480px) {

            .topbar-right .topbar-date {
                display: none;
            }

            .unit-grid {
                grid-template-columns: 1fr 1fr;
            }

        }

    </style>

</head>

<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <a href="dashboard.php" class="brand">

            <span class="brand-icon">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>

            <span>
                <span class="brand-name">LabSync</span>
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

            <a href="computer-units.php" class="sidebar-link active">
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

            <a href="analytics.php" class="sidebar-link">
                <i class="bi bi-bar-chart"></i>
                Reports & Analytics
            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-avatar">AD</div>

                <div>
                    <div class="admin-name">Administrator</div>
                    <div class="admin-role">System Administrator</div>
                </div>

            </div>

            <a href="../logout.php" class="sidebar-link logout-link">
                <i class="bi bi-box-arrow-left"></i>
                Sign Out
            </a>

        </div>

    </aside>


    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">


        <!-- TOPBAR -->
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
                    Administrator <span class="mx-2">/</span>
                    <strong>Computer Units</strong>
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
            <div class="mb-4">

                <p class="page-eyebrow">
                    LABORATORY ASSET MONITORING
                </p>

                <h1 class="page-title">
                    Computer Units
                </h1>

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
                            <span class="stat-icon">
                                <i class="bi bi-pc-display"></i>
                            </span>
                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['total'] ?>
                        </h3>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-card">

                        <div class="stat-top">
                            <span class="stat-label">Active Units</span>
                            <span class="stat-icon">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['active'] ?>
                        </h3>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-card">

                        <div class="stat-top">
                            <span class="stat-label">Under Maintenance</span>
                            <span class="stat-icon">
                                <i class="bi bi-tools"></i>
                            </span>
                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['maintenance'] ?>
                        </h3>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-card">

                        <div class="stat-top">
                            <span class="stat-label">Inactive Units</span>
                            <span class="stat-icon">
                                <i class="bi bi-pc-display"></i>
                            </span>
                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['inactive'] ?>
                        </h3>

                    </div>

                </div>

            </div>


            <!-- UNIT OVERVIEW -->
            <section class="content-card mb-4">

                <div class="card-heading">

                    <h5 class="card-title">
                        <i class="bi bi-grid me-2"></i>
                        Computer Unit Overview
                    </h5>

                    <span class="card-subtitle">
                        Visual overview of computer units registered in the database.
                    </span>

                </div>


                <div class="unit-grid">

                    <?php if (empty($units)): ?>

                        <div class="empty-state" style="grid-column: 1 / -1;">

                            <i class="bi bi-pc-display"></i>

                            <strong class="d-block text-dark">
                                No computer units found
                            </strong>

                            <span>
                                There are currently no registered computer units.
                            </span>

                        </div>

                    <?php else: ?>

                        <?php foreach ($units as $unit): ?>

                            <?php $unitStatus = (string) $unit['unit_status']; ?>

                            <div class="unit-card">

                                <div class="unit-icon">
                                    <i class="bi bi-pc-display-horizontal"></i>
                                </div>

                                <div class="unit-code">
                                    <?= e($unit['unit_code']) ?>
                                </div>

                                <div class="unit-location">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= e($unit['location'] ?: 'Location not specified') ?>
                                </div>

                                <span class="unit-status <?= e(unitStatusClass($unitStatus)) ?>">

                                    <i class="bi <?= e(unitStatusIcon($unitStatus)) ?>"></i>

                                    <?= e($unitStatus) ?>

                                </span>

                                <?php if (!empty($unit['remarks'])): ?>

                                    <div class="unit-remarks">
                                        <?= e($unit['remarks']) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>


            <!-- UNIT DETAILS TABLE -->
            <section class="content-card">

                <div class="card-heading">

                    <h5 class="card-title">
                        Registered Computer Units
                    </h5>

                    <span class="card-subtitle">
                        Detailed information retrieved from the computer_units table.
                    </span>

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

                                    <strong class="d-block text-dark">
                                        No computer units available
                                    </strong>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($units as $unit): ?>

                                <?php $unitStatus = (string) $unit['unit_status']; ?>

                                <tr>

                                    <td>
                                        <span class="table-unit-code">
                                            <?= e($unit['unit_code']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($unit['location'] ?: '—') ?>
                                    </td>

                                    <td>

                                        <span class="table-status <?= e(unitStatusClass($unitStatus)) ?>">

                                            <span class="table-dot"></span>

                                            <?= e($unitStatus) ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?= e($unit['remarks'] ?: '—') ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <div class="table-footer">

                    <i class="bi bi-info-circle me-1"></i>

                    Showing <?= count($units) ?> registered computer unit(s).

                    This page currently provides view-only access.

                </div>

            </section>


            <footer class="page-footer">
                LabSync · Administrator Portal · SLSU Lucena Campus
            </footer>

        </main>

    </div>


    <script>

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
