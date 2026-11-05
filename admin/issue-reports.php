
<?php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

if (!function_exists('pcare_report_status_class')) {

    function pcare_report_status_class(string $status): string
    {
        $classes = [
            'Pending' => 'pending',
            'Under Inspection' => 'inspection',
            'Under Repair' => 'repair',
            'Resolved' => 'resolved',
            'Unresolved' => 'unresolved'
        ];

        return $classes[$status] ?? 'default';
    }

}

if (!function_exists('pcare_report_date')) {

    function pcare_report_date(?string $date): string
    {
        if (empty($date)) {
            return '—';
        }

        $timestamp = strtotime($date);

        return $timestamp
            ? date('M d, Y h:i A', $timestamp)
            : '—';
    }

}

if (!function_exists('pcare_report_initials')) {

    function pcare_report_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        return $initials ?: 'U';
    }

}

/*
|--------------------------------------------------------------------------
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$categoryFilter = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);

$unitFilter = filter_input(
    INPUT_GET,
    'unit',
    FILTER_VALIDATE_INT
);

$allowedStatuses = [
    'Pending',
    'Under Inspection',
    'Under Repair',
    'Resolved',
    'Unresolved'
];

/*
|--------------------------------------------------------------------------
| GET CATEGORY OPTIONS
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->query("
    SELECT category_id, category_name
    FROM issue_categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| GET COMPUTER UNIT OPTIONS
|--------------------------------------------------------------------------
*/

$unitStmt = $pdo->query("
    SELECT unit_id, unit_code
    FROM computer_units
    ORDER BY unit_code ASC
");

$units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FETCH ISSUE REPORTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ir.report_id,
        ir.reference_no,
        ir.reporter_id,
        ir.unit_id,
        ir.category_id,
        ir.description,
        ir.photo_path,
        ir.status,
        ir.assigned_to,
        ir.created_at,
        ir.updated_at,
        ir.resolved_at,

        reporter.full_name AS reporter_name,
        reporter.email AS reporter_email,

        cu.unit_code,

        ic.category_name,

        assigned.full_name AS assigned_personnel

    FROM issue_reports ir

    LEFT JOIN users reporter
        ON ir.reporter_id = reporter.user_id

    LEFT JOIN computer_units cu
        ON ir.unit_id = cu.unit_id

    LEFT JOIN issue_categories ic
        ON ir.category_id = ic.category_id

    LEFT JOIN users assigned
        ON ir.assigned_to = assigned.user_id

    WHERE 1 = 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            ir.reference_no LIKE :reference_search
            OR reporter.full_name LIKE :name_search
            OR reporter.email LIKE :email_search
            OR cu.unit_code LIKE :unit_search
            OR ic.category_name LIKE :category_search
            OR ir.description LIKE :description_search
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params['reference_search'] = $searchTerm;
    $params['name_search'] = $searchTerm;
    $params['email_search'] = $searchTerm;
    $params['unit_search'] = $searchTerm;
    $params['category_search'] = $searchTerm;
    $params['description_search'] = $searchTerm;
}

if (in_array($statusFilter, $allowedStatuses, true)) {

    $sql .= " AND ir.status = :status";

    $params['status'] = $statusFilter;
}

if ($categoryFilter !== false && $categoryFilter !== null && $categoryFilter > 0) {

    $sql .= " AND ir.category_id = :category_id";

    $params['category_id'] = $categoryFilter;
}

if ($unitFilter !== false && $unitFilter !== null && $unitFilter > 0) {

    $sql .= " AND ir.unit_id = :unit_id";

    $params['unit_id'] = $unitFilter;
}

$sql .= " ORDER BY ir.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ISSUE REPORT STATISTICS
|--------------------------------------------------------------------------
*/

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Under Inspection'), 0) AS inspection,
        COALESCE(SUM(status = 'Under Repair'), 0) AS repair,
        COALESCE(SUM(status = 'Resolved'), 0) AS resolved,
        COALESCE(SUM(status = 'Unresolved'), 0) AS unresolved
    FROM issue_reports
")->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FETCH ISSUE STATUS HISTORY
|--------------------------------------------------------------------------
*/

$historyStmt = $pdo->query("
    SELECT
        h.history_id,
        h.report_id,
        h.old_status,
        h.new_status,
        h.changed_by,
        h.remarks,
        h.changed_at,
        changer.full_name AS changed_by_name

    FROM issue_status_history h

    LEFT JOIN users changer
        ON h.changed_by = changer.user_id

    ORDER BY h.changed_at DESC
");

$allHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$reportHistory = [];

foreach ($allHistory as $history) {

    $reportId = (int) $history['report_id'];

    if (!isset($reportHistory[$reportId])) {
        $reportHistory[$reportId] = [];
    }

    $reportHistory[$reportId][] = $history;
}

/*
|--------------------------------------------------------------------------
| STATUS LABELS
|--------------------------------------------------------------------------
*/

$statusLabels = [
    'Pending' => 'Pending',
    'Under Inspection' => 'Under Inspection',
    'Under Repair' => 'Under Repair',
    'Resolved' => 'Resolved',
    'Unresolved' => 'Unresolved'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue Reports | PCare</title>

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

        /* MAIN CONTENT */

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

        /* CONTENT CARDS */

        .content-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-heading {
            padding: 18px 21px;
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

        .filter-body {
            padding: 19px 21px;
        }

        /* FORMS */

        .form-label {
            color: #56675F;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .form-control,
        .form-select {
            min-height: 40px;
            border: 1px solid #DFE7E2;
            border-radius: 7px;
            color: var(--text);
            font-size: 11px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78,150,139,.11);
        }

        .form-control::placeholder {
            color: #A5B0AA;
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

        .report-reference {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 800;
        }

        .reporter-name {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 700;
        }

        .reporter-email {
            color: #98A39D;
            font-size: 10px;
            margin-top: 3px;
        }

        .unit-code {
            display: inline-block;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 6px;
            padding: 6px 9px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .category-text {
            font-size: 10px;
            font-weight: 600;
            color: #64746B;
        }

        /* STATUS BADGES */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-badge.pending {
            background: #FFF5E5;
            color: #A57425;
        }

        .status-badge.inspection {
            background: #EAF1FA;
            color: #496F9E;
        }

        .status-badge.repair {
            background: #F2EBF8;
            color: #8058A4;
        }

        .status-badge.resolved {
            background: #E7F4EB;
            color: #39805A;
        }

        .status-badge.unresolved {
            background: #FFF0ED;
            color: #A3483A;
        }

        .status-badge.default {
            background: #F0F1F0;
            color: #7B8580;
        }

        /* VIEW BUTTON */

        .btn-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 10px;
            background: #EAF1FA;
            color: #496F9E;
            border: 1px solid #D8E4F2;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 700;
            transition: .2s ease;
            white-space: nowrap;
        }

        .btn-view:hover {
            background: #496F9E;
            color: white;
        }

        .result-count {
            display: inline-flex;
            align-items: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 800;
        }

        .table-footer {
            border-top: 1px solid var(--border);
            padding: 13px 20px;
            color: var(--muted);
            font-size: 10px;
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

        /* REPORT DETAILS */

        .detail-label {
            display: block;
            color: #8B9790;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .detail-value {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .detail-section {
            background: #F8FAF9;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 13px;
            height: 100%;
        }

        .description-box {
            background: #F8FAF9;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 14px;
            color: #56675F;
            font-size: 11px;
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .report-photo {
            display: block;
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: #F8FAF9;
        }

        .photo-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--deep-green);
            font-size: 10px;
            font-weight: 700;
        }

        /* STATUS HISTORY */

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .history-item {
            position: relative;
            padding: 0 0 19px 22px;
            border-left: 2px solid #DCEAE4;
            margin-left: 5px;
        }

        .history-item:last-child {
            border-left-color: transparent;
            padding-bottom: 0;
        }

        .history-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
            box-shadow: 0 0 0 2px #DCEAE4;
            position: absolute;
            left: -6px;
            top: 2px;
        }

        .history-title {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 700;
        }

        .history-meta {
            color: #96A19B;
            font-size: 9px;
            margin-top: 4px;
        }

        .history-remarks {
            color: #637169;
            font-size: 10px;
            margin-top: 6px;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        /* ALERTS AND MODALS */

        .modal-content {
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(36,91,84,.15);
        }

        .modal-header,
        .modal-footer {
            border-color: var(--border);
        }

        .modal-title {
            color: var(--deep-green);
            font-size: 15px;
            font-weight: 800;
        }

        .modal-description {
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
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

            .filter-body {
                padding: 16px;
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

            <a href="issue-reports.php" class="sidebar-link active">
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

                <div class="admin-avatar">
                    <?= e(pcare_report_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
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
                    <strong>Issue Reports</strong>
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
                    ISSUE MONITORING
                </p>

                <h1 class="page-title">
                    Issue Reports
                </h1>

                <p class="page-description">
                    Monitor submitted computer issues and review their maintenance progress.
                </p>

            </div>

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

                                <span class="stat-label">
                                    <?= e($card[0]) ?>
                                </span>

                                <span class="stat-icon">
                                    <i class="bi <?= e($card[2]) ?>"></i>
                                </span>

                            </div>

                            <h3 class="stat-value">
                                <?= (int) $card[1] ?>
                            </h3>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- SEARCH AND FILTERS -->

            <section class="content-card mb-4">

                <div class="card-heading">

                    <h5 class="card-title">
                        <i class="bi bi-funnel me-2"></i>
                        Search and Filters
                    </h5>

                    <span class="card-subtitle">
                        Find reports using their reference number, reporter, computer unit, category, or status.
                    </span>

                </div>

                <div class="filter-body">

                    <form method="GET" class="row g-3">

                        <div class="col-12 col-lg-4">

                            <label class="form-label">
                                Search Reports
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Reference number, reporter, or issue..."
                                value="<?= e($search) ?>"
                            >

                        </div>

                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status" class="form-select">

                                <option value="">All Statuses</option>

                                <?php foreach ($allowedStatuses as $reportStatus): ?>

                                    <option
                                        value="<?= e($reportStatus) ?>"
                                        <?= $statusFilter === $reportStatus ? 'selected' : '' ?>
                                    >
                                        <?= e($reportStatus) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Category
                            </label>

                            <select name="category" class="form-select">

                                <option value="">All Categories</option>

                                <?php foreach ($categories as $category): ?>

                                    <option
                                        value="<?= (int) $category['category_id'] ?>"
                                        <?= (string) $categoryFilter === (string) $category['category_id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($category['category_name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Computer Unit
                            </label>

                            <select name="unit" class="form-select">

                                <option value="">All Units</option>

                                <?php foreach ($units as $unit): ?>

                                    <option
                                        value="<?= (int) $unit['unit_id'] ?>"
                                        <?= (string) $unitFilter === (string) $unit['unit_id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($unit['unit_code']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-6 col-lg-2 d-flex align-items-end gap-2">

                            <button
                                type="submit"
                                class="btn-primary-custom flex-grow-1"
                            >
                                <i class="bi bi-search"></i>
                                Apply
                            </button>

                            <a href="issue-reports.php" class="btn-light-custom">
                                Reset
                            </a>

                        </div>

                    </form>

                </div>

            </section>

            <!-- ISSUE REPORTS TABLE -->

            <section class="content-card">

                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <h5 class="card-title">
                            All Issue Reports
                        </h5>

                        <span class="card-subtitle">
                            Review the submitted reports and open their details.
                        </span>

                    </div>

                    <span class="result-count">
                        <?= count($reports) ?> RESULT(S)
                    </span>

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

                                        <strong class="d-block text-dark">
                                            No issue reports found
                                        </strong>

                                        <span>
                                            Try adjusting your filters or search terms.
                                        </span>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($reports as $report): ?>

                                    <tr>

                                        <td>

                                            <span class="report-reference">
                                                <?= e($report['reference_no'] ?: ('REPORT-' . $report['report_id'])) ?>
                                            </span>

                                        </td>

                                        <td>

                                            <div class="reporter-name">
                                                <?= e($report['reporter_name'] ?? 'Unknown User') ?>
                                            </div>

                                            <div class="reporter-email">
                                                <?= e($report['reporter_email'] ?? '—') ?>
                                            </div>

                                        </td>

                                        <td>

                                            <span class="unit-code">
                                                <?= e($report['unit_code'] ?? 'Unknown Unit') ?>
                                            </span>

                                        </td>

                                        <td>

                                            <span class="category-text">
                                                <?= e($report['category_name'] ?? 'Unknown Category') ?>
                                            </span>

                                        </td>

                                        <td>

                                            <span class="status-badge <?= e(pcare_report_status_class($report['status'])) ?>">

                                                <span class="status-dot"></span>

                                                <?= e($report['status']) ?>

                                            </span>

                                        </td>

                                        <td>
                                            <?= e(pcare_report_date($report['created_at'])) ?>
                                        </td>

                                        <td>

                                            <button
                                                type="button"
                                                class="btn-view"
                                                data-bs-toggle="modal"
                                                data-bs-target="#reportModal<?= (int) $report['report_id'] ?>"
                                            >
                                                <i class="bi bi-eye"></i>
                                                View Details
                                            </button>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="table-footer">

                    <i class="bi bi-info-circle me-1"></i>

                    Showing <?= count($reports) ?> report(s).

                    Issue reports are preserved for historical monitoring.

                </div>

            </section>

            <footer class="page-footer">
                PCare · Administrator Portal · SLSU Lucena Campus
            </footer>

        </main>

    </div>

    <!--
    |--------------------------------------------------------------------------
    | REPORT DETAILS MODALS
    |--------------------------------------------------------------------------
    -->

    <?php foreach ($reports as $report): ?>

        <?php

        $reportId = (int) $report['report_id'];

        $historyItems = $reportHistory[$reportId] ?? [];

        $storedPhotoPath = trim((string) ($report['photo_path'] ?? ''));

        $photoUrl = '';

        if ($storedPhotoPath !== '') {

            $normalizedPhotoPath = str_replace(
                '\\',
                '/',
                $storedPhotoPath
            );

            $photoUrl = '../' . ltrim($normalizedPhotoPath, '/');
        }

        ?>

        <div
            class="modal fade"
            id="reportModal<?= $reportId ?>"
            tabindex="-1"
            aria-labelledby="reportModalLabel<?= $reportId ?>"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">

                <div class="modal-content">

                    <div class="modal-header px-4 py-3">

                        <div>

                            <h5
                                class="modal-title"
                                id="reportModalLabel<?= $reportId ?>"
                            >
                                Issue Report Details
                            </h5>

                            <div class="modal-description">

                                Reference Number:
                                <strong>
                                    <?= e($report['reference_no'] ?: ('REPORT-' . $reportId)) ?>
                                </strong>

                            </div>

                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>

                    </div>

                    <div class="modal-body p-4">

                        <!-- REPORT INFORMATION -->

                        <div class="row g-3 mb-4">

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Reporter
                                    </span>

                                    <div class="detail-value">
                                        <?= e($report['reporter_name'] ?? 'Unknown User') ?>
                                    </div>

                                    <div class="text-secondary small mt-1">
                                        <?= e($report['reporter_email'] ?? '—') ?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Computer Unit
                                    </span>

                                    <div class="detail-value">
                                        <?= e($report['unit_code'] ?? 'Unknown Unit') ?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Issue Category
                                    </span>

                                    <div class="detail-value">
                                        <?= e($report['category_name'] ?? 'Unknown Category') ?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Current Status
                                    </span>

                                    <span class="status-badge <?= e(pcare_report_status_class($report['status'])) ?>">

                                        <span class="status-dot"></span>

                                        <?= e($report['status']) ?>

                                    </span>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Assigned IT Personnel
                                    </span>

                                    <div class="detail-value">
                                        <?= e($report['assigned_personnel'] ?? 'Not yet assigned') ?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Date Submitted
                                    </span>

                                    <div class="detail-value">
                                        <?= e(pcare_report_date($report['created_at'])) ?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Last Updated
                                    </span>

                                    <div class="detail-value">
                                        <?= e(pcare_report_date($report['updated_at'])) ?>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="detail-section">

                                    <span class="detail-label">
                                        Date Resolved
                                    </span>

                                    <div class="detail-value">
                                        <?= e(pcare_report_date($report['resolved_at'])) ?>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- ISSUE DESCRIPTION -->

                        <div class="mb-4">

                            <span class="detail-label">
                                Issue Description
                            </span>

                            <div class="description-box"><?= e($report['description']) ?></div>

                        </div>

                        <!-- ATTACHED PHOTO -->

                        <div class="mb-4">

                            <span class="detail-label">
                                Attached Photo
                            </span>

                            <?php if ($photoUrl !== ''): ?>

                                <a
                                    href="<?= e($photoUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="photo-link mb-2"
                                >
                                    <i class="bi bi-image"></i>
                                    Open attached image
                                </a>

                                <img
                                    src="<?= e($photoUrl) ?>"
                                    alt="Issue report attachment"
                                    class="report-photo mt-2"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="description-box">
                                    No photo was attached to this report.
                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- STATUS HISTORY -->

                        <div>

                            <span class="detail-label mb-3">
                                Issue Status History
                            </span>

                            <?php if (empty($historyItems)): ?>

                                <div class="description-box">
                                    No status history has been recorded for this report.
                                </div>

                            <?php else: ?>

                                <div class="history-list">

                                    <?php foreach ($historyItems as $history): ?>

                                        <div class="history-item">

                                            <span class="history-dot"></span>

                                            <div class="history-title">

                                                <?php if (!empty($history['old_status'])): ?>

                                                    <?= e($history['old_status']) ?>
                                                    <i class="bi bi-arrow-right mx-1"></i>

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

                                                <div class="history-remarks">
                                                    <?= e($history['remarks']) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="modal-footer px-4 py-3">

                        <button
                            type="button"
                            class="btn btn-light btn-sm"
                            data-bs-dismiss="modal"
                        >
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

    <!-- BOOTSTRAP JAVASCRIPT -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SIDEBAR JAVASCRIPT -->

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
