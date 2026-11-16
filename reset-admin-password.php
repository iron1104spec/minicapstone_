
<?php

require_once _DIR_ . '/config/db.php';

$newPassword = 'LabSyncAdmin2026!';
$adminEmail = 'admin@labsync.local';

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE users
    SET password_hash = ?
    WHERE email = ?
    AND role = 'admin'
");

$stmt->execute([$hash, $adminEmail]);

$check = $pdo->prepare("
    SELECT password_hash
    FROM users
    WHERE email = ?
    AND role = 'admin'
");

$check->execute([$adminEmail]);
$user = $check->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($newPassword, $user['password_hash'])) {
    echo "SUCCESS! Admin password successfully reset.";
} else {
    echo "FAILED! Admin password was not updated.";
}