<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Check whether the user is logged in
 * and whether the account is still active.
 */
function require_login(): void
{
    // Redirect users who are not logged in.
    if (!is_logged_in()) {
        header('Location: /minicapstone/login.php');
        exit;
    }

    // Check the user's latest account status and role.
    $stmt = db()->prepare("
        SELECT role, is_active
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        'user_id' => $_SESSION['user_id']
    ]);

    $user = $stmt->fetch();

    // End the session if the account no longer exists or is inactive.
    if (!$user || (int) $user['is_active'] !== 1) {
        $_SESSION = [];
        session_destroy();

        header('Location: /minicapstone/login.php');
        exit;
    }

    // Update the role using the database value.
    $_SESSION['role'] = $user['role'];
}


/**
 * Allow access only to specified user roles.
 *
 * Super Admin automatically inherits every Admin privilege,
 * so pages protected with require_role('admin') are also
 * accessible to Super Admin accounts.
 */
function require_role($allowed_roles): void
{
    require_login();

    // Accept either one role or an array of roles.
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // Super Admin inherits all Admin privileges.
    if (in_array('admin', $allowed_roles, true)) {
        $allowed_roles[] = 'super_admin';
    }

    // Check whether the logged-in user has permission.
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        http_response_code(403);

        exit('Access Denied. You do not have permission to view this page.');
    }
}


/**
 * Check whether the current user is a Super Admin.
 *
 * Use this helper in pages that need to display
 * Super-Admin-only controls (e.g., managing Admin accounts).
 */
function is_super_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}