
<?php

// Generate or retrieve CSRF token
function csrf_token(): string
{
    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}

// Generate hidden CSRF input
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        e(csrf_token()) .
        '">';
}

// Verify submitted CSRF token
function csrf_verify($submittedToken): bool
{
    if (
        !is_string($submittedToken) ||
        empty($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $submittedToken
    );
}

