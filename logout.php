
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Only allow logout through a valid POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !csrf_verify($_POST['csrf_token'] ?? null)) {
    redirect('index.php');
}

// Clear all session data.
$_SESSION = [];

// Remove the session cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session.
session_destroy();

// Return to homepage.
header('Location: index.php');
exit;
