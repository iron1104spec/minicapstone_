
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please refresh the page.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email.';
        }

        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare(
                "SELECT * FROM users WHERE email = :email LIMIT 1"
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (
                !$user ||
                !password_verify($password, $user['password_hash']) ||
                (int)$user['is_active'] !== 1
            ) {
                $errors[] = 'Invalid email or password.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                redirect('index.php');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PCare</title>
</head>
<body>

    <h1>PCare</h1>
    <h2>Welcome Back!</h2>
    <p>Sign in to your account.</p>

    <?php foreach ($errors as $error): ?>
        <p style="color:red;">
            <?= e($error) ?>
        </p>
    <?php endforeach; ?>

    <form method="POST">

        <?= csrf_field() ?>

        <label>Email Address:</label><br>
        <input
            type="email"
            name="email"
            value="<?= e($email) ?>"
            required
        >

        <br><br>

        <label>Password:</label><br>
        <input
            type="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">Sign In</button>

    </form>

    <p>
        Don't have an account?
        <a href="insert.php">Register here</a>
    </p>

    <a href="index.php">Back to Home</a>

</body>
</html>
