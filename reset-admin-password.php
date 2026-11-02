
<?php

require_once __DIR__ . '/config/db.php';

$newPassword = 'LabSyncAdmin2026!';

$stmt = $pdo->prepare("
    UPDATE users
    SET password_hash = ?
    WHERE email = ?
    AND role = 'admin'
");

$stmt->execute([
    password_hash($newPassword, PASSWORD_DEFAULT),
    'pcare.admin@example.com'
]);

echo $stmt->rowCount() > 0
    ? 'Admin password successfully reset!'
    : 'No account updated. Check the admin account.';
