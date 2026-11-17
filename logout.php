<?php
// logout.php
session_start();

// Burahin lahat ng session data
$_SESSION = array();

// Burahin ang session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Sirain ang session
session_destroy();

// Balik sa login page
header("Location: login.php");
exit;
?>