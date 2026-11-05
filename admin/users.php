
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();
$currentUserId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

if (!function_exists('pcare_user_initials')) {
    function pcare_user_initials(string $name): string
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
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * IMPORTANT:
     * csrf_verify() requires the submitted token as an argument.
     * The token comes from csrf_field() in each form.
     */

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | ADD USER
    |--------------------------------------------------------------------------
    */

    if ($action === 'add_user') {

        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (
            $fullName === '' ||
            $email === '' ||
            $idNumber === '' ||
            $department === '' ||
            $contactNo === '' ||
            $password === '' ||
            $confirmPassword === ''
        ) {
            header('Location: users.php?notice=required');
            exit;
        }

        if (
            strlen($fullName) > 150 ||
            strlen($email) > 150 ||
            strlen($idNumber) > 50 ||
            strlen($department) > 100 ||
            strlen($contactNo) > 30
        ) {
            header('Location: users.php?notice=required');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: users.php?notice=invalidemail');
            exit;
        }

        // Public account creation through this page is limited to these roles.
        if (!in_array($role, ['student', 'personnel'], true)) {
            header('Location: users.php?notice=invalidrole');
            exit;
        }

        if (strlen($password) < 8) {
            header('Location: users.php?notice=weakpassword');
            exit;
        }

        if ($password !== $confirmPassword) {
            header('Location: users.php?notice=passwordmismatch');
            exit;
        }

        // Check whether the email or ID number is already registered.
        $duplicateStmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE email = :email
               OR id_number = :id_number
            LIMIT 1
        ");

        $duplicateStmt->execute([
            'email' => $email,
            'id_number' => $idNumber
        ]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: users.php?notice=duplicate');
            exit;
        }

        try {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insertStmt = $pdo->prepare("
                INSERT INTO users (
                    full_name,
                    email,
                    password_hash,
                    role,
                    id_number,
                    department,
                    contact_no,
                    is_active
                ) VALUES (
                    :full_name,
                    :email,
                    :password_hash,
                    :role,
                    :id_number,
                    :department,
                    :contact_no,
                    1
                )
            ");

            $insertStmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'id_number' => $idNumber,
                'department' => $department,
                'contact_no' => $contactNo
            ]);

            header('Location: users.php?notice=created');
            exit;

        } catch (PDOException $e) {

            error_log('PCare Add User Error: ' . $e->getMessage());

            header('Location: users.php?notice=createerror');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT USER
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit_user') {

        $targetUserId = filter_input(
            INPUT_POST,
            'user_id',
            FILTER_VALIDATE_INT
        );

        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $requestedRole = $_POST['role'] ?? '';

        if (!$targetUserId) {
            header('Location: users.php?notice=invalid');
            exit;
        }

        if (
            $fullName === '' ||
            $email === '' ||
            strlen($fullName) > 150 ||
            strlen($email) > 150 ||
            strlen($idNumber) > 50 ||
            strlen($department) > 100 ||
            strlen($contactNo) > 30
        ) {
            header('Location: users.php?notice=editrequired');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: users.php?notice=invalidemail');
            exit;
        }

        // Confirm that the account exists.
        $targetStmt = $pdo->prepare("
            SELECT user_id, role
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $targetStmt->execute([
            'user_id' => $targetUserId
        ]);

        $targetUser = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            header('Location: users.php?notice=notfound');
            exit;
        }

        // Existing administrator accounts cannot be changed to another role.
        if ($targetUser['role'] === 'admin') {

            $newRole = 'admin';

        } else {

            if (!in_array($requestedRole, ['student', 'personnel'], true)) {
                header('Location: users.php?notice=invalidrole');
                exit;
            }

            $newRole = $requestedRole;
        }

        // Check duplicate email, excluding the account being edited.
        $duplicateEmail = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE email = :email
              AND user_id <> :user_id
            LIMIT 1
        ");

        $duplicateEmail->execute([
            'email' => $email,
            'user_id' => $targetUserId
        ]);

        if ($duplicateEmail->fetch(PDO::FETCH_ASSOC)) {
            header('Location: users.php?notice=duplicate');
            exit;
        }

        // Check duplicate ID number when the field is not empty.
        if ($idNumber !== '') {

            $duplicateId = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE id_number = :id_number
                  AND user_id <> :user_id
                LIMIT 1
            ");

            $duplicateId->execute([
                'id_number' => $idNumber,
                'user_id' => $targetUserId
            ]);

            if ($duplicateId->fetch(PDO::FETCH_ASSOC)) {
                header('Location: users.php?notice=duplicate');
                exit;
            }
        }

        try {

            $updateStmt = $pdo->prepare("
                UPDATE users
                SET
                    full_name = :full_name,
                    email = :email,
                    id_number = :id_number,
                    department = :department,
                    contact_no = :contact_no,
                    role = :role
                WHERE user_id = :user_id
            ");

            $updateStmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'id_number' => $idNumber !== '' ? $idNumber : null,
                'department' => $department !== '' ? $department : null,
                'contact_no' => $contactNo !== '' ? $contactNo : null,
                'role' => $newRole,
                'user_id' => $targetUserId
            ]);

            // Update the current admin's session information when necessary.
            if ($targetUserId === $currentUserId) {
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
            }

            header('Location: users.php?notice=updated');
            exit;

        } catch (PDOException $e) {

            error_log('PCare Edit User Error: ' . $e->getMessage());

            header('Location: users.php?notice=editerror');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE / DEACTIVATE USER
    |--------------------------------------------------------------------------
    */

    if ($action === 'change_status') {

        $targetUserId = filter_input(
            INPUT_POST,
            'user_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = isset($_POST['new_status'])
            ? (int) $_POST['new_status']
            : -1;

        if (
            !$targetUserId ||
            !in_array($newStatus, [0, 1], true)
        ) {
            header('Location: users.php?notice=invalid');
            exit;
        }

        // Prevent an admin from deactivating their own account.
        if (
            $targetUserId === $currentUserId &&
            $newStatus === 0
        ) {
            header('Location: users.php?notice=self');
            exit;
        }

        $targetStmt = $pdo->prepare("
            SELECT user_id, role, is_active
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $targetStmt->execute([
            'user_id' => $targetUserId
        ]);

        $targetUser = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            header('Location: users.php?notice=notfound');
            exit;
        }

        // Do not deactivate the last active administrator.
        if (
            $targetUser['role'] === 'admin' &&
            (int) $targetUser['is_active'] === 1 &&
            $newStatus === 0
        ) {

            $adminCount = $pdo->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'admin'
                  AND is_active = 1
            ")->fetchColumn();

            if ((int) $adminCount <= 1) {
                header('Location: users.php?notice=lastadmin');
                exit;
            }
        }

        if ((int) $targetUser['is_active'] === $newStatus) {
            header('Location: users.php?notice=unchanged');
            exit;
        }

        $updateStmt = $pdo->prepare("
            UPDATE users
            SET is_active = :status
            WHERE user_id = :user_id
        ");

        $updateStmt->execute([
            'status' => $newStatus,
            'user_id' => $targetUserId
        ]);

        header(
            'Location: users.php?notice=' .
            ($newStatus === 1 ? 'activated' : 'deactivated')
        );
        exit;
    }

    header('Location: users.php?notice=invalid');
    exit;
}

/*
|--------------------------------------------------------------------------
| SEARCH AND FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedRoles = ['student', 'personnel', 'admin'];

$sql = "
    SELECT
        user_id,
        full_name,
        email,
        role,
        id_number,
        department,
        contact_no,
        is_active,
        created_at
    FROM users
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            full_name LIKE :search_name
            OR email LIKE :search_email
            OR id_number LIKE :search_id
        )
    ";

    $term = '%' . $search . '%';

    $params['search_name'] = $term;
    $params['search_email'] = $term;
    $params['search_id'] = $term;
}

if (in_array($role, $allowedRoles, true)) {
    $sql .= " AND role = :role";
    $params['role'] = $role;
}

if ($status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($status === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| USER STATISTICS
|--------------------------------------------------------------------------
*/

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(is_active = 1), 0) AS active,
        COALESCE(SUM(is_active = 0), 0) AS inactive,
        COALESCE(SUM(role = 'student'), 0) AS students,
        COALESCE(SUM(role = 'personnel'), 0) AS personnel,
        COALESCE(SUM(role = 'admin'), 0) AS administrators
    FROM users
")->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$notices = [

    'created' => [
        'type' => 'success',
        'message' => 'The new user account has been created successfully.'
    ],

    'updated' => [
        'type' => 'success',
        'message' => 'User information has been updated successfully.'
    ],

    'activated' => [
        'type' => 'success',
        'message' => 'The user account has been activated successfully.'
    ],

    'deactivated' => [
        'type' => 'success',
        'message' => 'The user account has been deactivated successfully.'
    ],

    'self' => [
        'type' => 'warning',
        'message' => 'You cannot deactivate your own administrator account.'
    ],

    'lastadmin' => [
        'type' => 'warning',
        'message' => 'You cannot deactivate the last active administrator.'
    ],

    'notfound' => [
        'type' => 'danger',
        'message' => 'The selected user account could not be found.'
    ],

    'invalid' => [
        'type' => 'danger',
        'message' => 'Invalid account action. Please try again.'
    ],

    'invalidrole' => [
        'type' => 'danger',
        'message' => 'Invalid account role selected.'
    ],

    'required' => [
        'type' => 'danger',
        'message' => 'Please complete all required fields.'
    ],

    'editrequired' => [
        'type' => 'danger',
        'message' => 'Please check the user information. Some fields are invalid or too long.'
    ],

    'invalidemail' => [
        'type' => 'danger',
        'message' => 'Please enter a valid email address.'
    ],

    'weakpassword' => [
        'type' => 'warning',
        'message' => 'Password must contain at least 8 characters.'
    ],

    'passwordmismatch' => [
        'type' => 'danger',
        'message' => 'The passwords do not match.'
    ],

    'duplicate' => [
        'type' => 'warning',
        'message' => 'The email address or ID number is already registered.'
    ],

    'createerror' => [
        'type' => 'danger',
        'message' => 'Unable to create the account. Please check the server error log.'
    ],

    'editerror' => [
        'type' => 'danger',
        'message' => 'Unable to update the account. Please check the server error log.'
    ],

    'unchanged' => [
        'type' => 'info',
        'message' => 'The account already has the selected status.'
    ]

];

$notice = $_GET['notice'] ?? '';
$notification = $notices[$notice] ?? null;

$roleLabels = [
    'student' => 'Student',
    'personnel' => 'IT Personnel',
    'admin' => 'Administrator'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Management | PCare</title>

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
            text-decoration: none;
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
            width: 33px;
            height: 33px;
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

        .form-control::placeholder {
            color: #A5B0AA;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78,150,139,.11);
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

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 160px;
        }

        .user-avatar-table {
            width: 35px;
            height: 35px;
            flex-shrink: 0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 10px;
            font-weight: 800;
        }

        .user-name {
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 700;
        }

        .user-email {
            color: #98A39D;
            font-size: 10px;
            margin-top: 3px;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 6px;
            background: #F0F4F2;
            color: #5E7167;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .role-badge.admin {
            background: #F0EBF8;
            color: #7657A4;
        }

        .role-badge.personnel {
            background: #EAF1FA;
            color: #496F9E;
        }

        .role-badge.student {
            background: #EAF4EF;
            color: #438064;
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

        .current-account {
            color: #9AA59F;
            font-size: 10px;
            white-space: nowrap;
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

        /* NOTIFICATIONS */

        .alert {
            border: 0;
            border-radius: 9px;
            font-size: 11px;
        }

        /* MODALS */

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

        .password-note {
            background: #F5F8F6;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 11px 13px;
            color: #718078;
            font-size: 10px;
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

            <a href="users.php" class="sidebar-link active">
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

            <a href="analytics.php" class="sidebar-link">
                <i class="bi bi-bar-chart"></i>
                Reports & Analytics
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-avatar">
                    <?= e(pcare_user_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
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
                    <strong>User Management</strong>
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
                        ACCOUNT ADMINISTRATION
                    </p>

                    <h1 class="page-title">
                        User Management
                    </h1>

                    <p class="page-description">
                        Manage registered accounts, user roles, and system access.
                    </p>

                </div>

                <div class="page-heading-actions">

                    <button
                        type="button"
                        class="btn-primary-custom"
                        data-bs-toggle="modal"
                        data-bs-target="#addUserModal"
                    >
                        <i class="bi bi-person-plus"></i>
                        Add New User
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

            <!-- USER STATISTICS -->

            <?php
            $statCards = [
                ['Total Users', $stats['total'], 'bi-people'],
                ['Active Users', $stats['active'], 'bi-person-check'],
                ['Inactive Users', $stats['inactive'], 'bi-person-x'],
                ['Students', $stats['students'], 'bi-mortarboard'],
                ['IT Personnel', $stats['personnel'], 'bi-tools'],
                ['Administrators', $stats['administrators'], 'bi-shield-check']
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
                        Find accounts using their name, email, ID number, role, or status.
                    </span>

                </div>

                <div class="filter-body">

                    <form method="GET" class="row g-3">

                        <div class="col-12 col-lg-5">

                            <label class="form-label">
                                Search Users
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Enter name, email, or ID number"
                                value="<?= e($search) ?>"
                            >

                        </div>

                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                User Role
                            </label>

                            <select name="role" class="form-select">

                                <option value="">All Roles</option>

                                <option
                                    value="student"
                                    <?= $role === 'student' ? 'selected' : '' ?>
                                >
                                    Student
                                </option>

                                <option
                                    value="personnel"
                                    <?= $role === 'personnel' ? 'selected' : '' ?>
                                >
                                    IT Personnel
                                </option>

                                <option
                                    value="admin"
                                    <?= $role === 'admin' ? 'selected' : '' ?>
                                >
                                    Administrator
                                </option>

                            </select>

                        </div>

                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Account Status
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

                        <div class="col-12 col-lg-3 d-flex align-items-end gap-2">

                            <button
                                type="submit"
                                class="btn-primary-custom flex-grow-1"
                            >
                                <i class="bi bi-search"></i>
                                Apply Filters
                            </button>

                            <a href="users.php" class="btn-light-custom">
                                Reset
                            </a>

                        </div>

                    </form>

                </div>

            </section>

            <!-- REGISTERED USERS TABLE -->

            <section class="content-card">

                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <h5 class="card-title">
                            Registered Accounts
                        </h5>

                        <span class="card-subtitle">
                            Review user information and manage account access.
                        </span>

                    </div>

                    <span class="result-count">
                        <?= count($users) ?> RESULT(S)
                    </span>

                </div>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead>

                            <tr>
                                <th>User Information</th>
                                <th>ID Number</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Registered</th>
                                <th>Account Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($users)): ?>

                                <tr>

                                    <td colspan="7" class="empty-state">

                                        <i class="bi bi-people"></i>

                                        <strong class="d-block text-dark">
                                            No user accounts found
                                        </strong>

                                        <span>
                                            Try adjusting your search or filter settings.
                                        </span>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($users as $user): ?>

                                    <tr>

                                        <td>

                                            <div class="user-cell">

                                                <div class="user-avatar-table">
                                                    <?= e(pcare_user_initials($user['full_name'])) ?>
                                                </div>

                                                <div>

                                                    <div class="user-name">
                                                        <?= e($user['full_name']) ?>
                                                    </div>

                                                    <div class="user-email">
                                                        <?= e($user['email']) ?>
                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                        <td>
                                            <?= e($user['id_number'] ?? '—') ?>
                                        </td>

                                        <td>
                                            <?= e($user['department'] ?? '—') ?>
                                        </td>

                                        <td>

                                            <span class="role-badge <?= e($user['role']) ?>">
                                                <?= e($roleLabels[$user['role']] ?? $user['role']) ?>
                                            </span>

                                        </td>

                                        <td>

                                            <span class="status-badge <?= (int) $user['is_active'] === 1 ? 'active' : 'inactive' ?>">

                                                <span class="status-dot"></span>

                                                <?= (int) $user['is_active'] === 1
                                                    ? 'Active'
                                                    : 'Inactive' ?>

                                            </span>

                                        </td>

                                        <td>
                                            <?= e(date('M d, Y', strtotime($user['created_at']))) ?>
                                        </td>

                                        <td>

                                            <div class="d-flex flex-wrap gap-2">

                                                <!-- EDIT BUTTON -->

                                                <button
                                                    type="button"
                                                    class="action-button btn-edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal<?= (int) $user['user_id'] ?>"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                    Edit
                                                </button>

                                                <!-- ACTIVE ACCOUNT -->

                                                <?php if ((int) $user['is_active'] === 1): ?>

                                                    <?php if ((int) $user['user_id'] === $currentUserId): ?>

                                                        <span class="current-account">
                                                            <i class="bi bi-shield-check me-1"></i>
                                                            Current account
                                                        </span>

                                                    <?php else: ?>

                                                        <form
                                                            method="POST"
                                                            onsubmit="return confirm('Are you sure you want to deactivate this account? The user will no longer be able to log in.');"
                                                        >

                                                            <?= csrf_field() ?>

                                                            <input
                                                                type="hidden"
                                                                name="action"
                                                                value="change_status"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= (int) $user['user_id'] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="new_status"
                                                                value="0"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="action-button btn-deactivate"
                                                            >
                                                                <i class="bi bi-person-x"></i>
                                                                Deactivate
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                <?php else: ?>

                                                    <!-- INACTIVE ACCOUNT -->

                                                    <form
                                                        method="POST"
                                                        onsubmit="return confirm('Are you sure you want to activate this account?');"
                                                    >

                                                        <?= csrf_field() ?>

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="change_status"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="user_id"
                                                            value="<?= (int) $user['user_id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="new_status"
                                                            value="1"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="action-button btn-activate"
                                                        >
                                                            <i class="bi bi-person-check"></i>
                                                            Activate
                                                        </button>

                                                    </form>

                                                <?php endif; ?>

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

                    Showing <?= count($users) ?> account(s).

                    User records are preserved when accounts are deactivated.

                </div>

            </section>

            <footer class="page-footer">
                PCare · Administrator Portal · SLSU Lucena Campus
            </footer>

        </main>

    </div>

    <!--
    |--------------------------------------------------------------------------
    | EDIT USER MODALS
    | These are placed outside the table to maintain valid HTML.
    |--------------------------------------------------------------------------
    -->

    <?php foreach ($users as $user): ?>

        <div
            class="modal fade"
            id="editUserModal<?= (int) $user['user_id'] ?>"
            tabindex="-1"
            aria-labelledby="editUserModalLabel<?= (int) $user['user_id'] ?>"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-dialog-centered modal-lg">

                <div class="modal-content">

                    <div class="modal-header px-4 py-3">

                        <div>

                            <h5
                                class="modal-title"
                                id="editUserModalLabel<?= (int) $user['user_id'] ?>"
                            >
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit User Information
                            </h5>

                            <div class="modal-description">
                                Update the selected account details.
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
                            value="edit_user"
                        >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= (int) $user['user_id'] ?>"
                        >

                        <div class="modal-body p-4">

                            <div class="row g-3">

                                <div class="col-12">

                                    <label class="form-label">
                                        Full Name
                                    </label>

                                    <input
                                        type="text"
                                        name="full_name"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?= e($user['full_name']) ?>"
                                        required
                                    >

                                </div>

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?= e($user['email']) ?>"
                                        required
                                    >

                                </div>

                                <div class="col-md-6">

                                    <label class="form-label">
                                        ID Number
                                    </label>

                                    <input
                                        type="text"
                                        name="id_number"
                                        class="form-control"
                                        maxlength="50"
                                        value="<?= e($user['id_number'] ?? '') ?>"
                                    >

                                </div>

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Department
                                    </label>

                                    <input
                                        type="text"
                                        name="department"
                                        class="form-control"
                                        maxlength="100"
                                        value="<?= e($user['department'] ?? '') ?>"
                                    >

                                </div>

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Contact Number
                                    </label>

                                    <input
                                        type="text"
                                        name="contact_no"
                                        class="form-control"
                                        maxlength="30"
                                        value="<?= e($user['contact_no'] ?? '') ?>"
                                    >

                                </div>

                                <div class="col-12">

                                    <label class="form-label">
                                        Account Role
                                    </label>

                                    <?php if ($user['role'] === 'admin'): ?>

                                        <input
                                            type="text"
                                            class="form-control"
                                            value="Administrator"
                                            disabled
                                        >

                                        <input
                                            type="hidden"
                                            name="role"
                                            value="admin"
                                        >

                                        <small class="text-secondary d-block mt-2">
                                            Administrator roles cannot be changed through this form.
                                        </small>

                                    <?php else: ?>

                                        <select
                                            name="role"
                                            class="form-select"
                                            required
                                        >

                                            <option
                                                value="student"
                                                <?= $user['role'] === 'student' ? 'selected' : '' ?>
                                            >
                                                Student
                                            </option>

                                            <option
                                                value="personnel"
                                                <?= $user['role'] === 'personnel' ? 'selected' : '' ?>
                                            >
                                                IT Personnel
                                            </option>

                                        </select>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="password-note mt-4">

                                <i class="bi bi-shield-lock me-2"></i>

                                Passwords are not changed in this form.
                                The user's current password will remain unchanged.

                            </div>

                        </div>

                        <div class="modal-footer px-4 py-3">

                            <button
                                type="button"
                                class="btn-light-custom px-3"
                                data-bs-dismiss="modal"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="btn-primary-custom px-4"
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

    <!--
    |--------------------------------------------------------------------------
    | ADD USER MODAL
    |--------------------------------------------------------------------------
    -->

    <div
        class="modal fade"
        id="addUserModal"
        tabindex="-1"
        aria-labelledby="addUserModalLabel"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content">

                <div class="modal-header px-4 py-3">

                    <div>

                        <h5 class="modal-title" id="addUserModalLabel">
                            <i class="bi bi-person-plus me-2"></i>
                            Create User Account
                        </h5>

                        <div class="modal-description">
                            Add a Student or IT Personnel account to PCare.
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
                        value="add_user"
                    >

                    <div class="modal-body p-4">

                        <div class="row g-3">

                            <div class="col-12">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="full_name"
                                    class="form-control"
                                    maxlength="150"
                                    placeholder="Enter complete name"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    maxlength="150"
                                    placeholder="example@email.com"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    ID Number
                                </label>

                                <input
                                    type="text"
                                    name="id_number"
                                    class="form-control"
                                    maxlength="50"
                                    placeholder="Enter ID number"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Department
                                </label>

                                <input
                                    type="text"
                                    name="department"
                                    class="form-control"
                                    maxlength="100"
                                    placeholder="Enter department"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Contact Number
                                </label>

                                <input
                                    type="text"
                                    name="contact_no"
                                    class="form-control"
                                    maxlength="30"
                                    placeholder="Enter contact number"
                                    required
                                >

                            </div>

                            <div class="col-12">

                                <label class="form-label">
                                    Account Role
                                </label>

                                <select
                                    name="role"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select account role
                                    </option>

                                    <option value="student">
                                        Student
                                    </option>

                                    <option value="personnel">
                                        IT Personnel
                                    </option>

                                </select>

                                <small class="text-secondary d-block mt-2">
                                    Administrator accounts cannot be created through this form.
                                </small>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Password
                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    minlength="8"
                                    placeholder="At least 8 characters"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Confirm Password
                                </label>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control"
                                    minlength="8"
                                    placeholder="Re-enter password"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                        </div>

                        <div class="password-note mt-4">

                            <i class="bi bi-shield-lock me-2"></i>

                            New accounts created by the administrator will be active.
                            Passwords are securely hashed before being stored.

                        </div>

                    </div>

                    <div class="modal-footer px-4 py-3">

                        <button
                            type="button"
                            class="btn-light-custom px-3"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="btn-primary-custom px-4"
                        >
                            <i class="bi bi-person-plus"></i>
                            Create Account
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
