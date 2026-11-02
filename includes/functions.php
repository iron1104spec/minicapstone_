
<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape output to prevent XSS
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Retrieve old form input
function old(string $key): string
{
    return e($_POST[$key] ?? '');
}

// Check if user is logged in
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

// Redirect to another page
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

// Save a flash notification
function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

// Get a flash notification
function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];

    unset($_SESSION['_flash'][$key]);

    return $message;
}

// Return the existing PDO connection
function db(): PDO
{
    global $pdo;

    if (!$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not available.'
        );
    }

    return $pdo;
}

