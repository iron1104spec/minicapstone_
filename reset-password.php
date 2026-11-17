<?php
// reset-password.php (Root folder)
session_start();

require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$message = '';
$validToken = false;

$pdo = db();

// Check kung valid ang token
if ($token && $email) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? LIMIT 1");
    $stmt->execute([$email, $token]);
    if ($stmt->fetch()) {
        $validToken = true;
    } else {
        $message = "<div class='alert alert-danger' style='font-size: 13px;'>Invalid or expired reset token. Please request a new link.</div>";
    }
}

// Process bagong password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $message = "<div class='alert alert-danger' style='font-size: 13px;'>Passwords do not match.</div>";
    } elseif (strlen($newPassword) < 8) {
        $message = "<div class='alert alert-danger' style='font-size: 13px;'>Password must be at least 8 characters.</div>";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password sa users table
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        // Burahin ang token
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        $message = "<div class='alert alert-success' style='font-size: 13px;'>Password successfully reset! You can now <a href='login.php' class='fw-bold'>Login</a>.</div>";
        $validToken = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | LabSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #245B54; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .card { border-radius: 15px; padding: 40px; width: 100%; max-width: 420px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .brand-name { font-weight: 800; color: #245B54; font-size: 24px; text-align: center; margin-bottom: 20px; display: block;}
        .form-label { font-size: 13px; font-weight: 600; }
        .form-control { border-radius: 8px; padding: 12px 15px; font-size: 14px; }
        .btn-custom { background: #245B54; color: white; border-radius: 8px; font-weight: 700; padding: 12px; width: 100%; }
        .btn-custom:hover { background: #1a4a3a; color: white; }
    </style>
</head>
<body>
    <div class="card">
        <span class="brand-name">LabSync.</span>
        <h4 class="text-center fw-bold mb-2" style="color: #245B54;">Reset Password</h4>
        <p class="text-center text-muted mb-4" style="font-size: 13px;">Enter your new password below.</p>
        
        <?= $message ?>

        <?php if ($validToken): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                <button type="submit" class="btn btn-custom">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>