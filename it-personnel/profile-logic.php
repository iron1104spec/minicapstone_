<?php
// it-personnel/profile-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();
$userId = (int) $_SESSION['user_id'];

// Retrieve the logged-in personnel's information
$stmt = $pdo->prepare("
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
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('User account not found.');
}

$accountStatus = (int) $user['is_active'] === 1 ? 'Active' : 'Inactive';
$initial = strtoupper(substr(trim($user['full_name']), 0, 1));

$createdDate = !empty($user['created_at'])
    ? date('F d, Y', strtotime($user['created_at']))
    : 'Not available';