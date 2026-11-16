<?php
// create-super-admin.php
// ONE-TIME SCRIPT — DELETE THIS FILE AFTER RUNNING IT.

require_once __DIR__ . '/config/db.php';

// ==========================================
// EDIT THESE VALUES BEFORE RUNNING
// ==========================================
$fullName   = 'Super Administrator';
$email      = 'superadmin@labsync.local';
$password   = 'ChangeMe123!';   // ← CHANGE THIS to a strong password (min 8 chars)
$department = 'Administration';
$contactNo  = '00000000000';

$pdo = db();

// Check duplicate email
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    die("ERROR: An account with this email already exists.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users
        (full_name, email, password_hash, role, id_number, department, contact_no, is_active)
    VALUES
        (:full_name, :email, :hash, 'super_admin', NULL, :department, :contact_no, 1)
");

try {
    $stmt->execute([
        'full_name'   => $fullName,
        'email'       => $email,
        'hash'        => $hash,
        'department'  => $department,
        'contact_no'  => $contactNo,
    ]);

    echo "=========================================\n";
    echo "  SUPER ADMIN ACCOUNT CREATED\n";
    echo "=========================================\n";
    echo "User ID : " . $pdo->lastInsertId() . "\n";
    echo "Name    : $fullName\n";
    echo "Email   : $email\n";
    echo "Password: $password\n";
    echo "Role    : super_admin\n";
    echo "=========================================\n";
    echo "IMPORTANT:\n";
    echo "1. Delete this file NOW: create-super-admin.php\n";
    echo "2. Note: Super Admin accounts CANNOT be edited\n";
    echo "   through the Users page. If you need to change\n";
    echo "   the password later, run another helper script.\n";
    echo "=========================================\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}