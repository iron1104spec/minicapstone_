<?php
// ==========================================
// LABSYNC - LOGIN
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session may have expired. Please refresh and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($password === '') $errors[] = 'Please enter your password.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $errors[] = 'Invalid email or password.';
                } else {
                    $passwordIsValid = password_verify($password, $user['password_hash']);
                    $accountIsActive = (int) $user['is_active'] === 1;

                    if (!$passwordIsValid || !$accountIsActive) {
                        $errors[] = 'Invalid email or password, or your account is not yet active.';
                    } elseif (!in_array($user['role'], ['student', 'staff', 'faculty', 'personnel', 'admin', 'super_admin'], true)) {
                        $errors[] = 'Your account has an invalid role.';
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = (int) $user['user_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        unset($_SESSION['registration_completed']);

                        switch ($user['role']) {
                            case 'student': case 'staff': case 'faculty': redirect('student/dashboard.php'); exit;
                            case 'personnel': redirect('it-personnel/dashboard.php'); exit;
                            case 'admin': case 'super_admin': redirect('admin/dashboard.php'); exit;
                            default: $errors[] = 'Unable to identify your account role.'; break;
                        }
                    }
                }
            } catch (PDOException $ex) {
                error_log('LabSync login error: ' . $ex->getMessage());
                $errors[] = 'Unable to sign in right now.';
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
    <title>Sign In | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #438C7F; --deep: #16443C; --mint: #E3F2EC;
            --text: #1B2D26; --muted: #5C7268; --border: #D2E0D9;
        }

        /* BAGONG ANIMATION PARA SA TEXT SA KALIWA */
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (min-width: 1025px) {
            html, body { height: 100vh !important; overflow: hidden !important; margin: 0; }
        }

        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            /* ITO ANG BINAGO: .jpg at may ?v=2 */
            background: url('assets/images/Lucena.jpg?v=2') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
        }

        .overlay {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(15,51,44,0.90), rgba(22,68,60,0.95));
            z-index: 1;
        }

        .split-layout {
            display: flex; width: 100%; min-height: 100vh; position: relative; z-index: 2;
        }

        .left-panel {
            flex: 1.2;
            display: flex; flex-direction: column; justify-content: center;
            padding: 10vw;
            color: white;
        }

        /* NAKA-SET SA OPACITY 0 PARA HINDI AGAD LUMABAS BAGO MAG-ANIMATE */
        .left-panel .brand-logo { opacity: 0; font-size: 32px; font-weight: 800; color: white; text-decoration: none; margin-bottom: 25px; display: block; animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: 0.1s; }
        .left-panel .tag { opacity: 0; display: inline-block; font-size: 13px; font-weight: 800; letter-spacing: 2px; color: #A9D8CA; margin-bottom: 15px; text-transform: uppercase; animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: 0.2s; }
        .left-panel h1 { opacity: 0; font-size: 60px; font-weight: 800; line-height: 1.1; margin: 0 0 20px 0; letter-spacing: -2px; animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: 0.3s; }
        .left-panel h1 span { color: #6DB5A9; }
        
        /* JUSTIFIED TEXT NA MAY ANIMATION DIN */
        .left-panel p { 
            opacity: 0;
            font-size: 17px; 
            line-height: 1.7; 
            color: rgba(255,255,255,0.85); 
            max-width: 100%; 
            width: 100%;
            text-align: justify; 
            text-align-last: left;
            animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
            animation-delay: 0.4s;
        }

        .right-panel {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: #FFFFFF; width: 100%; max-width: 440px; padding: 35px 40px;
            border-radius: 20px; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4);
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .brand-section { text-align: center; margin-bottom: 20px; }
        .brand-logo-dark { color: var(--deep); font-size: 26px; font-weight: 800; text-decoration: none; letter-spacing: -0.5px; }
        .brand-subtitle { display: block; color: var(--primary); font-size: 10px; font-weight: 800; letter-spacing: 1.5px; margin-top: 3px; text-transform: uppercase; }

        .form-header { margin-bottom: 25px; text-align: center; }
        .form-header h2 { font-size: 28px; font-weight: 800; color: var(--deep); margin: 0 0 5px 0; letter-spacing: -1px; }
        .form-header p { font-size: 14px; color: var(--muted); margin: 0; }

        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        
        .input-box {
            display: flex; align-items: center; background: #FBFCFB; border: 1.5px solid var(--border);
            border-radius: 10px; padding: 0 14px; height: 44px; transition: 0.2s;
        }
        .input-box:focus-within { border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(67,140,127,0.12); }
        .input-box i { color: #91A29C; font-size: 16px; margin-right: 10px; }
        .input-box input { width: 100%; border: none; background: transparent; outline: none; font-size: 14px; color: var(--text); font-weight: 500; }
        
        .password-toggle { background: none; border: none; color: #91A29C; cursor: pointer; font-size: 16px; }
        
        .btn-submit {
            width: 100%; height: 46px; background: var(--deep); color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; margin-top: 10px; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(22,68,60,0.2); }

        .alert-error { background: #FFF5F5; color: #A83D3D; border: 1px solid #F2D0D0; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 15px; }
        
        .divider { display: flex; align-items: center; gap: 15px; margin: 25px 0 15px; }
        .divider span { flex: 1; height: 1.5px; background: var(--primary); opacity: 0.3; }
        .divider small { color: var(--deep); font-size: 11px; font-weight: 800; letter-spacing: 1px; }

        @media (max-width: 1024px) {
            body { display: block; overflow-y: auto; }
            .split-layout { flex-direction: column; }
            .left-panel { padding: 50px 30px; text-align: center; }
            .left-panel h1 { font-size: 40px; }
            .left-panel p { margin: 0 auto; text-align: center; text-align-last: center; }
            .right-panel { padding: 30px 20px; }
            .login-card { padding: 30px 25px; }
        }
    </style>
</head>
<body>

    <div class="overlay"></div>

    <div class="split-layout">
        <!-- KALIWA: Welcome Text -->
        <div class="left-panel">
            <a href="index.php" class="brand-logo">LabSync.</a>
            <span class="tag">B204 Computer Laboratory · SLSU Lucena</span>
            <h1>Smarter Lab Management <span>Starts Here.</span></h1>
            <p>A centralized platform for reporting computer issues, tracking maintenance progress, and keeping laboratory records organized.</p>
        </div>

        <!-- KANAN: Login Card -->
        <div class="right-panel">
            <div class="login-card">
                <div class="brand-section">
                    <a href="index.php" class="brand-logo-dark">LabSync.</a>
                    <span class="brand-subtitle">B204 Computer Laboratory</span>
                </div>

                <div class="form-header">
                    <h2>Welcome back</h2>
                    <p>Sign in to continue to your account.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert-error" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="email">Email Address</label>
                        <div class="input-box">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="name@example.com" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-box">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Sign In <i class="bi bi-arrow-right"></i></button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <span style="font-size: 14px; color: var(--muted);">Don't have an account?</span>
                    <a href="insert.php" style="color: var(--primary); font-weight: 700; text-decoration: none; margin-left: 5px;">Create an account</a>
                </div>

                <div class="divider"><span></span><small>LABSYNC · SLSU</small><span></span></div>
            </div>
        </div>
    </div>

    <!-- PASSWORD SCRIPT -->
    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    </script>
</body>
</html>