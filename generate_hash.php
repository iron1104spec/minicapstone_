<?php

$password = 'Admin@12345';

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($hash === false) {
    die('Password hashing failed.');
}

echo '<h3>Generated Password Hash:</h3>';
echo '<textarea rows="5" cols="80">' . htmlspecialchars($hash) . '</textarea>';

echo '<p>Hash length: ' . strlen($hash) . '</p>';

?>