<?php
// admin/users-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();
$currentUserId = (int) $_SESSION['user_id'];
$isSuperAdmin  = is_super_admin();

// HELPER FUNCTION
if (!function_exists('pcare_user_initials')) {
    function pcare_user_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'U';
    }
}

// HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    // ADD USER
    if ($action === 'add_user') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';

        if ($fullName === '' || $email === '' || $idNumber === '' || $department === '' || $contactNo === '' || $password === '' || $confirmPassword === '') {
            header('Location: users.php?notice=required'); exit;
        }

        if (strlen($fullName) > 150 || strlen($email) > 150 || strlen($idNumber) > 50 || strlen($department) > 100 || strlen($contactNo) > 30) {
            header('Location: users.php?notice=required'); exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: users.php?notice=invalidemail'); exit;
        }

        // ROLE VALIDATION — Super Admin can create Admin; regular Admin cannot.
        $allowedNewRoles = $isSuperAdmin
            ? ['student', 'personnel', 'admin']
            : ['student', 'personnel'];

        if (!in_array($role, $allowedNewRoles, true)) {
            header('Location: users.php?notice=restricted'); exit;
        }

        if (strlen($password) < 8) {
            header('Location: users.php?notice=weakpassword'); exit;
        }

        if ($password !== $confirmPassword) {
            header('Location: users.php?notice=passwordmismatch'); exit;
        }

        $duplicateStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email OR id_number = :id_number LIMIT 1");
        $duplicateStmt->execute(['email' => $email, 'id_number' => $idNumber]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: users.php?notice=duplicate'); exit;
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, id_number, department, contact_no, is_active) VALUES (:full_name, :email, :password_hash, :role, :id_number, :department, :contact_no, 1)");
            $insertStmt->execute(['full_name' => $fullName, 'email' => $email, 'password_hash' => $passwordHash, 'role' => $role, 'id_number' => $idNumber, 'department' => $department, 'contact_no' => $contactNo]);
            header('Location: users.php?notice=created'); exit;
        } catch (PDOException $e) {
            error_log('PCare Add User Error: ' . $e->getMessage());
            header('Location: users.php?notice=createerror'); exit;
        }
    }

    // EDIT USER
    if ($action === 'edit_user') {
        $targetUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $requestedRole = $_POST['role'] ?? '';

        if (!$targetUserId) { header('Location: users.php?notice=invalid'); exit; }

        if ($fullName === '' || $email === '' || strlen($fullName) > 150 || strlen($email) > 150 || strlen($idNumber) > 50 || strlen($department) > 100 || strlen($contactNo) > 30) {
            header('Location: users.php?notice=editrequired'); exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: users.php?notice=invalidemail'); exit;
        }

        $targetStmt = $pdo->prepare("SELECT user_id, role FROM users WHERE user_id = :user_id LIMIT 1");
        $targetStmt->execute(['user_id' => $targetUserId]);
        $targetUser = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) { header('Location: users.php?notice=notfound'); exit; }

        // ROLE RESTRICTIONS
        if ($targetUser['role'] === 'super_admin') {
            // No one can edit Super Admin accounts through this module.
            header('Location: users.php?notice=restricted_superadmin'); exit;
        }

        if ($targetUser['role'] === 'admin' && !$isSuperAdmin && $targetUserId !== $currentUserId) {
            // Regular Admin cannot edit other Admin accounts (self-edit is allowed).
            header('Location: users.php?notice=restricted_admin'); exit;
        }

        if ($targetUser['role'] === 'admin') {
            // Admin role stays locked (cannot be promoted or demoted).
            $newRole = 'admin';
        } else {
            if (!in_array($requestedRole, ['student', 'personnel'], true)) {
                header('Location: users.php?notice=invalidrole'); exit;
            }
            $newRole = $requestedRole;
        }

        $duplicateEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id <> :user_id LIMIT 1");
        $duplicateEmail->execute(['email' => $email, 'user_id' => $targetUserId]);

        if ($duplicateEmail->fetch(PDO::FETCH_ASSOC)) {
            header('Location: users.php?notice=duplicate'); exit;
        }

        if ($idNumber !== '') {
            $duplicateId = $pdo->prepare("SELECT user_id FROM users WHERE id_number = :id_number AND user_id <> :user_id LIMIT 1");
            $duplicateId->execute(['id_number' => $idNumber, 'user_id' => $targetUserId]);
            if ($duplicateId->fetch(PDO::FETCH_ASSOC)) {
                header('Location: users.php?notice=duplicate'); exit;
            }
        }

        try {
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email, id_number = :id_number, department = :department, contact_no = :contact_no, role = :role WHERE user_id = :user_id");
            $updateStmt->execute([
                'full_name' => $fullName, 'email' => $email, 'id_number' => $idNumber !== '' ? $idNumber : null,
                'department' => $department !== '' ? $department : null, 'contact_no' => $contactNo !== '' ? $contactNo : null,
                'role' => $newRole, 'user_id' => $targetUserId
            ]);

            if ($targetUserId === $currentUserId) {
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
            }

            header('Location: users.php?notice=updated'); exit;
        } catch (PDOException $e) {
            error_log('PCare Edit User Error: ' . $e->getMessage());
            header('Location: users.php?notice=editerror'); exit;
        }
    }

    // ACTIVATE / DEACTIVATE USER
    if ($action === 'change_status') {
        $targetUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $newStatus = isset($_POST['new_status']) ? (int) $_POST['new_status'] : -1;

        if (!$targetUserId || !in_array($newStatus, [0, 1], true)) {
            header('Location: users.php?notice=invalid'); exit;
        }

        if ($targetUserId === $currentUserId && $newStatus === 0) {
            header('Location: users.php?notice=self'); exit;
        }

        $targetStmt = $pdo->prepare("SELECT user_id, role, is_active FROM users WHERE user_id = :user_id LIMIT 1");
        $targetStmt->execute(['user_id' => $targetUserId]);
        $targetUser = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) { header('Location: users.php?notice=notfound'); exit; }

        // ROLE RESTRICTIONS
        if ($targetUser['role'] === 'super_admin') {
            // Super Admin accounts cannot be activated or deactivated through this module.
            header('Location: users.php?notice=restricted_superadmin'); exit;
        }

        if ($targetUser['role'] === 'admin' && !$isSuperAdmin) {
            // Only Super Admin can change the status of Admin accounts.
            header('Location: users.php?notice=restricted_admin'); exit;
        }

        if ($targetUser['role'] === 'admin' && (int) $targetUser['is_active'] === 1 && $newStatus === 0) {
            // Last-admin protection now counts both Admin and Super Admin.
            $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1")->fetchColumn();
            if ((int) $adminCount <= 1) { header('Location: users.php?notice=lastadmin'); exit; }
        }

        if ((int) $targetUser['is_active'] === $newStatus) {
            header('Location: users.php?notice=unchanged'); exit;
        }

        $updateStmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE user_id = :user_id");
        $updateStmt->execute(['status' => $newStatus, 'user_id' => $targetUserId]);

        header('Location: users.php?notice=' . ($newStatus === 1 ? 'activated' : 'deactivated'));
        exit;
    }

    header('Location: users.php?notice=invalid'); exit;
}

// SEARCH AND FILTER
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$allowedRoles = ['student', 'personnel', 'admin', 'super_admin'];

$sql = "SELECT user_id, full_name, email, role, id_number, department, contact_no, is_active, created_at FROM users WHERE 1 = 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (full_name LIKE :search_name OR email LIKE :search_email OR id_number LIKE :search_id)";
    $term = '%' . $search . '%';
    $params['search_name'] = $term; $params['search_email'] = $term; $params['search_id'] = $term;
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

// USER STATISTICS
$stats = $pdo->query("
    SELECT COUNT(*) AS total, COALESCE(SUM(is_active = 1), 0) AS active, COALESCE(SUM(is_active = 0), 0) AS inactive, COALESCE(SUM(role = 'student'), 0) AS students, COALESCE(SUM(role = 'personnel'), 0) AS personnel, COALESCE(SUM(role = 'admin'), 0) AS administrators
    FROM users
")->fetch(PDO::FETCH_ASSOC);

// NOTIFICATIONS
$notices = [
    'created' => ['type' => 'success', 'message' => 'The new user account has been created successfully.'],
    'updated' => ['type' => 'success', 'message' => 'User information has been updated successfully.'],
    'activated' => ['type' => 'success', 'message' => 'The user account has been activated successfully.'],
    'deactivated' => ['type' => 'success', 'message' => 'The user account has been deactivated successfully.'],
    'self' => ['type' => 'warning', 'message' => 'You cannot deactivate your own administrator account.'],
    'lastadmin' => ['type' => 'warning', 'message' => 'You cannot deactivate the last active administrator.'],
    'notfound' => ['type' => 'danger', 'message' => 'The selected user account could not be found.'],
    'invalid' => ['type' => 'danger', 'message' => 'Invalid account action. Please try again.'],
    'invalidrole' => ['type' => 'danger', 'message' => 'Invalid account role selected.'],
    'restricted' => ['type' => 'danger', 'message' => 'You do not have permission to assign that account role.'],
    'restricted_admin' => ['type' => 'danger', 'message' => 'Only a Super Administrator can manage Administrator accounts.'],
    'restricted_superadmin' => ['type' => 'danger', 'message' => 'Super Administrator accounts cannot be modified through this module.'],
    'required' => ['type' => 'danger', 'message' => 'Please complete all required fields.'],
    'editrequired' => ['type' => 'danger', 'message' => 'Please check the user information. Some fields are invalid or too long.'],
    'invalidemail' => ['type' => 'danger', 'message' => 'Please enter a valid email address.'],
    'weakpassword' => ['type' => 'warning', 'message' => 'Password must contain at least 8 characters.'],
    'passwordmismatch' => ['type' => 'danger', 'message' => 'The passwords do not match.'],
    'duplicate' => ['type' => 'warning', 'message' => 'The email address or ID number is already registered.'],
    'createerror' => ['type' => 'danger', 'message' => 'Unable to create the account. Please check the server error log.'],
    'editerror' => ['type' => 'danger', 'message' => 'Unable to update the account. Please check the server error log.'],
    'unchanged' => ['type' => 'info', 'message' => 'The account already has the selected status.']
];

$notice = $_GET['notice'] ?? '';
$notification = $notices[$notice] ?? null;

$roleLabels = [
    'student' => 'Student',
    'personnel' => 'IT Personnel',
    'admin' => 'Administrator',
    'super_admin' => 'Super Administrator'
];