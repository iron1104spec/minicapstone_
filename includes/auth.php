
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
 */
function require_role($allowed_roles): void
{
    require_login();

    // Accept either one role or an array of roles.
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // Check whether the logged-in user has permission.
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        http_response_code(403);

        exit('Access Denied. You do not have permission to view this page.');
    }
}
