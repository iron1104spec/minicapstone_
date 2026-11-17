<?php
// forgot-password.php (Root folder)

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pdo = db();

    // Check kung existing ang email
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Gumawa ng token
        $token = bin2hex(random_bytes(50));
        
        // I-save sa database
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmt->execute([$email, $token]);

        $resetLink = "http://localhost/minicapstone/reset-password.php?token=" . $token . "&email=" . urlencode($email);
        
        // DEMO MODE: Ipapakita yung link sa screen imbes na i-email
        $message = "<div class='alert alert-success' style='font-size: 13px;'>
                        <strong>System Demo Mode:</strong><br>
                        A reset link has been generated. <br><br>
                        <a href='$resetLink' class='fw-bold text-decoration-none'>CLICK HERE TO RESET PASSWORD</a>
                    </div>";
    } else {
        // Security: Wag sabihin kung existing o hindi
        $message = "<div class='alert alert-success' style='font-size: 13px;'>If that email exists in our system, a reset link has been generated.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | LabSync</title>
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
        <h4 class="text-center fw-bold mb-2" style="color: #245B54;">Forgot Password</h4>
        <p class="text-center text-muted mb-4" style="font-size: 13px;">Enter your email and we'll generate a reset link.</p>
        
        <?= $message ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-custom mb-3">Generate Reset Link</button>
            <div class="text-center">
                <a href="login.php" class="text-decoration-none fw-semibold" style="color: #4E968B; font-size: 13px;">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>