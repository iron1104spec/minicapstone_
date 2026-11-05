
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();

/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
| This page has its own initials function.
| It does not depend on the function inside users.php.
*/

if (!function_exists('pcare_categories_initials')) {
    function pcare_categories_initials(string $name): string
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
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify the submitted CSRF token.
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | ADD CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'add_category') {

        $categoryName = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($categoryName === '') {
            header('Location: categories.php?notice=required');
            exit;
        }

        // Check for an existing category with the same name.
        $duplicateStmt = $pdo->prepare("
            SELECT category_id
            FROM issue_categories
            WHERE LOWER(category_name) = LOWER(:category_name)
            LIMIT 1
        ");

        $duplicateStmt->execute([
            'category_name' => $categoryName
        ]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: categories.php?notice=duplicate');
            exit;
        }

        try {

            $insertStmt = $pdo->prepare("
                INSERT INTO issue_categories (
                    category_name,
                    description,
                    is_active
                ) VALUES (
                    :category_name,
                    :description,
                    1
                )
            ");

            $insertStmt->execute([
                'category_name' => $categoryName,
                'description' => $description
            ]);

            header('Location: categories.php?notice=created');
            exit;

        } catch (PDOException $e) {

            error_log('PCare Add Category Error: ' . $e->getMessage());

            header('Location: categories.php?notice=createerror');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit_category') {

        $categoryId = filter_input(
            INPUT_POST,
            'category_id',
            FILTER_VALIDATE_INT
        );

        $categoryName = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$categoryId || $categoryName === '') {
            header('Location: categories.php?notice=invalid');
            exit;
        }

        // Confirm that the category exists.
        $checkStmt = $pdo->prepare("
            SELECT category_id
            FROM issue_categories
            WHERE category_id = :category_id
            LIMIT 1
        ");

        $checkStmt->execute([
            'category_id' => $categoryId
        ]);

        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: categories.php?notice=notfound');
            exit;
        }

        // Prevent duplicate category names.
        $duplicateStmt = $pdo->prepare("
            SELECT category_id
            FROM issue_categories
            WHERE LOWER(category_name) = LOWER(:category_name)
              AND category_id <> :category_id
            LIMIT 1
        ");

        $duplicateStmt->execute([
            'category_name' => $categoryName,
            'category_id' => $categoryId
        ]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: categories.php?notice=duplicate');
            exit;
        }

        try {

            $updateStmt = $pdo->prepare("
                UPDATE issue_categories
                SET
                    category_name = :category_name,
                    description = :description
                WHERE category_id = :category_id
            ");

            $updateStmt->execute([
                'category_name' => $categoryName,
                'description' => $description,
                'category_id' => $categoryId
            ]);

            header('Location: categories.php?notice=updated');
            exit;

        } catch (PDOException $e) {

            error_log('PCare Edit Category Error: ' . $e->getMessage());

            header('Location: categories.php?notice=editerror');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE / DEACTIVATE CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'change_status') {

        $categoryId = filter_input(
            INPUT_POST,
            'category_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = isset($_POST['new_status'])
            ? (int) $_POST['new_status']
            : -1;

        if (
            !$categoryId ||
            !in_array($newStatus, [0, 1], true)
        ) {
            header('Location: categories.php?notice=invalid');
            exit;
        }

        $checkStmt = $pdo->prepare("
            SELECT category_id, is_active
            FROM issue_categories
            WHERE category_id = :category_id
            LIMIT 1
        ");

        $checkStmt->execute([
            'category_id' => $categoryId
        ]);

        $category = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            header('Location: categories.php?notice=notfound');
            exit;
        }

        if ((int) $category['is_active'] === $newStatus) {
            header('Location: categories.php?notice=unchanged');
            exit;
        }

        $updateStmt = $pdo->prepare("
            UPDATE issue_categories
            SET is_active = :status
            WHERE category_id = :category_id
        ");

        $updateStmt->execute([
            'status' => $newStatus,
            'category_id' => $categoryId
        ]);

        header(
            'Location: categories.php?notice=' .
            ($newStatus === 1 ? 'activated' : 'deactivated')
        );
        exit;
    }

    header('Location: categories.php?notice=invalid');
    exit;
}

/*
|--------------------------------------------------------------------------
| SEARCH AND FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        category_id,
        category_name,
        description,
        is_active
    FROM issue_categories
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            category_name LIKE :search_name
            OR description LIKE :search_description
        )
    ";

    $term = '%' . $search . '%';

    $params['search_name'] = $term;
    $params['search_description'] = $term;
}

if ($status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($status === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY category_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CATEGORY STATISTICS
|--------------------------------------------------------------------------
*/

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(is_active = 1), 0) AS active,
        COALESCE(SUM(is_active = 0), 0) AS inactive
    FROM issue_categories
")->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$notices = [

    'created' => [
        'type' => 'success',
        'message' => 'The issue category was created successfully.'
    ],

    'updated' => [
        'type' => 'success',
        'message' => 'The issue category was updated successfully.'
    ],

    'activated' => [
        'type' => 'success',
        'message' => 'The issue category was activated successfully.'
    ],

    'deactivated' => [
        'type' => 'success',
        'message' => 'The issue category was deactivated successfully.'
    ],

    'required' => [
        'type' => 'danger',
        'message' => 'Please enter a category name.'
    ],

    'duplicate' => [
        'type' => 'warning',
        'message' => 'This category name already exists.'
    ],

    'invalid' => [
        'type' => 'danger',
        'message' => 'Invalid category action. Please try again.'
    ],

    'notfound' => [
        'type' => 'danger',
        'message' => 'The selected category could not be found.'
    ],

    'createerror' => [
        'type' => 'danger',
        'message' => 'Unable to create the category. Please check the server error log.'
    ],

    'editerror' => [
        'type' => 'danger',
        'message' => 'Unable to update the category. Please check the server error log.'
    ],

    'unchanged' => [
        'type' => 'info',
        'message' => 'The category already has the selected status.'
    ]

];

$notice = $_GET['notice'] ?? '';
$notification = $notices[$notice] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue Categories | PCare</title>

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

        /* BUTTONS */

        .btn-primary-custom,
        .btn-light-custom,
        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            transition: .2s ease;
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
            transform: translateY(-1px);
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
            padding: 18px;
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
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.8px;
            margin: 12px 0 0;
        }

        /* CONTENT CARDS */

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
            padding: 15px 16px;
            color: #637169;
            border-color: #EEF2EF;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #FBFCFB;
        }

        .category-icon {
            width: 37px;
            height: 37px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 16px;
        }

        .category-name {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 700;
        }

        .category-description {
            color: #98A39D;
            font-size: 10px;
            margin-top: 4px;
            max-width: 380px;
        }

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

        .status-badge.active {
            background: #E7F4EB;
            color: #39805A;
        }

        .status-badge.inactive {
            background: #F0F1F0;
            color: #7B8580;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        /* ACTION BUTTONS */

        .action-button {
            padding: 7px 9px;
            font-size: 9px;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .btn-edit {
            background: #EAF1FA;
            color: #496F9E;
            border-color: #D8E4F2;
        }

        .btn-edit:hover {
            background: #496F9E;
            color: white;
        }

        .btn-activate {
            background: #E7F4EB;
            color: #39805A;
            border-color: #D2E9D9;
        }

        .btn-activate:hover {
            background: #39805A;
            color: white;
        }

        .btn-deactivate {
            background: #FFF0ED;
            color: #A3483A;
            border-color: #F1D8D2;
        }

        .btn-deactivate:hover {
            background: #A3483A;
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

        /* ALERTS AND MODALS */

        .alert {
            border: 0;
            border-radius: 9px;
            font-size: 11px;
        }

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

            .page-heading-actions {
                width: 100%;
            }

            .page-heading-actions button {
                width: 100%;
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

            <a href="categories.php" class="sidebar-link active">
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

                <div class="admin-avatar">
                    <?= e(pcare_categories_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
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
                    <strong>Issue Categories</strong>
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
                        SYSTEM CONFIGURATION
                    </p>

                    <h1 class="page-title">
                        Issue Categories
                    </h1>

                    <p class="page-description">
                        Manage the categories that users can select when reporting computer issues.
                    </p>

                </div>

                <div class="page-heading-actions">

                    <button
                        type="button"
                        class="btn-primary-custom"
                        data-bs-toggle="modal"
                        data-bs-target="#addCategoryModal"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Add Category
                    </button>

                </div>

            </div>

            <!-- NOTIFICATIONS -->

            <?php if ($notification): ?>

                <div
                    class="alert alert-<?= e($notification['type']) ?> alert-dismissible fade show mb-4"
                    role="alert"
                >

                    <i class="bi bi-info-circle me-2"></i>

                    <?= e($notification['message']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>

                </div>

            <?php endif; ?>

            <!-- STATISTICS -->

            <div class="row g-3 mb-4">

                <div class="col-12 col-md-4">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Total Categories
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-tags"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['total'] ?>
                        </h3>

                    </div>

                </div>

                <div class="col-12 col-md-4">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Active Categories
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-check-circle"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['active'] ?>
                        </h3>

                    </div>

                </div>

                <div class="col-12 col-md-4">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Inactive Categories
                            </span>

                            <span class="stat-icon">
                                <i class="bi bi-pause-circle"></i>
                            </span>

                        </div>

                        <h3 class="stat-value">
                            <?= (int) $stats['inactive'] ?>
                        </h3>

                    </div>

                </div>

            </div>

            <!-- SEARCH AND FILTERS -->

            <section class="content-card mb-4">

                <div class="card-heading">

                    <h5 class="card-title">
                        <i class="bi bi-funnel me-2"></i>
                        Search and Filters
                    </h5>

                    <span class="card-subtitle">
                        Search by category name or description.
                    </span>

                </div>

                <div class="filter-body">

                    <form method="GET" class="row g-3">

                        <div class="col-12 col-lg-7">

                            <label class="form-label">
                                Search Category
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Enter category name or description"
                                value="<?= e($search) ?>"
                            >

                        </div>

                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status" class="form-select">

                                <option value="">All Statuses</option>

                                <option
                                    value="active"
                                    <?= $status === 'active' ? 'selected' : '' ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="inactive"
                                    <?= $status === 'inactive' ? 'selected' : '' ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>

                        <div class="col-6 col-lg-3 d-flex align-items-end gap-2">

                            <button
                                type="submit"
                                class="btn-primary-custom flex-grow-1"
                            >
                                <i class="bi bi-search"></i>
                                Apply
                            </button>

                            <a href="categories.php" class="btn-light-custom">
                                Reset
                            </a>

                        </div>

                    </form>

                </div>

            </section>

            <!-- CATEGORY TABLE -->

            <section class="content-card">

                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <h5 class="card-title">
                            Registered Categories
                        </h5>

                        <span class="card-subtitle">
                            View and manage the issue categories stored in PCare.
                        </span>

                    </div>

                    <span class="result-count">
                        <?= count($categories) ?> RESULT(S)
                    </span>

                </div>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead>

                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($categories)): ?>

                                <tr>

                                    <td colspan="4" class="empty-state">

                                        <i class="bi bi-tags"></i>

                                        <strong class="d-block text-dark">
                                            No categories found
                                        </strong>

                                        <span>
                                            Try changing your search or add a new category.
                                        </span>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($categories as $category): ?>

                                    <tr>

                                        <td>

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="category-icon">
                                                    <i class="bi bi-tag"></i>
                                                </div>

                                                <div class="category-name">
                                                    <?= e($category['category_name']) ?>
                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <div class="category-description">
                                                <?= e($category['description'] ?: 'No description provided.') ?>
                                            </div>

                                        </td>

                                        <td>

                                            <span class="status-badge <?= (int) $category['is_active'] === 1 ? 'active' : 'inactive' ?>">

                                                <span class="status-dot"></span>

                                                <?= (int) $category['is_active'] === 1
                                                    ? 'Active'
                                                    : 'Inactive' ?>

                                            </span>

                                        </td>

                                        <td>

                                            <div class="d-flex flex-wrap gap-2">

                                                <!-- EDIT BUTTON -->

                                                <button
                                                    type="button"
                                                    class="action-button btn-edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editCategoryModal<?= (int) $category['category_id'] ?>"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                    Edit
                                                </button>

                                                <!-- ACTIVATE / DEACTIVATE -->

                                                <form
                                                    method="POST"
                                                    onsubmit="return confirm('Are you sure you want to <?= (int) $category['is_active'] === 1 ? 'deactivate' : 'activate' ?> this category?');"
                                                >

                                                    <?= csrf_field() ?>

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="change_status"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="category_id"
                                                        value="<?= (int) $category['category_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="<?= (int) $category['is_active'] === 1 ? 0 : 1 ?>"
                                                    >

                                                    <?php if ((int) $category['is_active'] === 1): ?>

                                                        <button
                                                            type="submit"
                                                            class="action-button btn-deactivate"
                                                        >
                                                            <i class="bi bi-pause-circle"></i>
                                                            Deactivate
                                                        </button>

                                                    <?php else: ?>

                                                        <button
                                                            type="submit"
                                                            class="action-button btn-activate"
                                                        >
                                                            <i class="bi bi-check-circle"></i>
                                                            Activate
                                                        </button>

                                                    <?php endif; ?>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="table-footer">

                    <i class="bi bi-info-circle me-1"></i>

                    Showing <?= count($categories) ?> category record(s).

                    Deactivated categories are preserved in the database.

                </div>

            </section>

            <footer class="page-footer">
                PCare · Administrator Portal · SLSU Lucena Campus
            </footer>

        </main>

    </div>

    <!-- EDIT CATEGORY MODALS -->

    <?php foreach ($categories as $category): ?>

        <div
            class="modal fade"
            id="editCategoryModal<?= (int) $category['category_id'] ?>"
            tabindex="-1"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <div class="modal-header px-4 py-3">

                        <div>

                            <h5 class="modal-title">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Category
                            </h5>

                            <div class="modal-description">
                                Update the selected issue category.
                            </div>

                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>

                    </div>

                    <form method="POST">

                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="edit_category"
                        >

                        <input
                            type="hidden"
                            name="category_id"
                            value="<?= (int) $category['category_id'] ?>"
                        >

                        <div class="modal-body p-4">

                            <div class="mb-3">

                                <label class="form-label">
                                    Category Name
                                </label>

                                <input
                                    type="text"
                                    name="category_name"
                                    class="form-control"
                                    value="<?= e($category['category_name']) ?>"
                                    required
                                >

                            </div>

                            <div>

                                <label class="form-label">
                                    Description
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Enter category description"
                                ><?= e($category['description'] ?? '') ?></textarea>

                            </div>

                        </div>

                        <div class="modal-footer px-4 py-3">

                            <button
                                type="button"
                                class="btn-light-custom"
                                data-bs-dismiss="modal"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="btn-primary-custom"
                            >
                                <i class="bi bi-check2-circle"></i>
                                Save Changes
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

    <!-- ADD CATEGORY MODAL -->

    <div
        class="modal fade"
        id="addCategoryModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header px-4 py-3">

                    <div>

                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>
                            Add Issue Category
                        </h5>

                        <div class="modal-description">
                            Create a category for computer issue reports.
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>

                <form method="POST">

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="action"
                        value="add_category"
                    >

                    <div class="modal-body p-4">

                        <div class="mb-3">

                            <label class="form-label">
                                Category Name
                            </label>

                            <input
                                type="text"
                                name="category_name"
                                class="form-control"
                                placeholder="Example: Hardware"
                                required
                            >

                        </div>

                        <div>

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                placeholder="Describe the issue category..."
                            ></textarea>

                        </div>

                    </div>

                    <div class="modal-footer px-4 py-3">

                        <button
                            type="button"
                            class="btn-light-custom"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="btn-primary-custom"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Add Category
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

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
