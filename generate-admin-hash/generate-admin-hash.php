<?php
$hash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) >= 8) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $error = 'Password must be at least 8 characters.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Admin Password Hash</title>
</head>
<body>

<h2>Generate Admin Password Hash</h2>

<form method="POST">
    <input type="password" name="password"
           placeholder="Enter your Admin password" required>
    <button type="submit">Generate Hash</button>
</form>

<?php if (!empty($error)): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($hash !== ''): ?>
    <p>Your password hash:</p>
    <textarea rows="4" cols="70" readonly><?= htmlspecialchars($hash) ?></textarea>
<?php endif; ?>

</body>
</html>