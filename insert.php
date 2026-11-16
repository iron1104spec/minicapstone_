<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$successMessage = '';

$fullName = '';
$email = '';
$idNumber = '';
$department = '';
$contactNo = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session may have expired. Please refresh and try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $idNumber = trim((string) ($_POST['id_number'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $contactNo = trim((string) ($_POST['contact_no'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 150) $errors['full_name'] = 'Enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $errors['email'] = 'Enter a valid email.';
        if (!preg_match('/^[A-Za-z0-9\-]{3,50}$/', $idNumber)) $errors['id_number'] = 'Use 3 to 50 letters, numbers, or hyphens.';
        if (mb_strlen($department) < 2 || mb_strlen($department) > 100) $errors['department'] = 'Enter your department.';
        if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)) $errors['contact_no'] = 'Enter a valid contact number.';
        if (!in_array($role, ['student', 'staff', 'faculty', 'personnel'], true)) $errors['role'] = 'Select a valid role.';
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) $errors['password'] = 'Use at least 8 characters with letters and numbers.';
        if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

        if (empty($errors)) {
            try {
                $pdo = db();
                $check = $pdo->prepare('SELECT email, id_number FROM users WHERE email = :email OR id_number = :id_number LIMIT 2');
                $check->execute([':email' => $email, ':id_number' => $idNumber]);
                $existing = $check->fetchAll(PDO::FETCH_ASSOC);

                foreach ($existing as $row) {
                    if (strtolower((string) $row['email']) === $email) $errors['email'] = 'Email already registered.';
                    if ((string) $row['id_number'] === $idNumber) $errors['id_number'] = 'ID number already registered.';
                }

                if (empty($errors)) {
                    $isActive = in_array($role, ['student', 'staff', 'faculty'], true) ? 1 : 0;
                    $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, id_number, department, contact_no, is_active) VALUES (:full_name, :email, :password_hash, :role, :id_number, :department, :contact_no, :is_active)');
                    
                    $insert->execute([
                        ':full_name' => $fullName, ':email' => $email, ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':role' => $role, ':id_number' => $idNumber, ':department' => $department, ':contact_no' => $contactNo, ':is_active' => $isActive
                    ]);

                    $newUserId = (int) $pdo->lastInsertId();

                    if (in_array($role, ['student', 'staff', 'faculty'], true)) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role; 
                        unset($_SESSION['registration_completed']);
                        redirect('student/dashboard.php');
                    } elseif ($role === 'personnel') {
                        $successMessage = 'Your IT Personnel account is pending admin approval.';
                        $fullName = ''; $email = ''; $idNumber = ''; $department = ''; $contactNo = ''; $role = '';
                    }
                }
            } catch (PDOException $ex) {
                error_log('LabSync registration error: ' . $ex->getMessage());
                $errors['form'] = 'Could not create account right now. Try again.';
            }
        }
    }
}

function field_error(array $errors, string $key): void {
    if (isset($errors[$key])) echo '<p class="register-error" role="alert">' . e($errors[$key]) . '</p>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #438C7F; --deep: #16443C; --mint: #E3F2EC;
            --text: #1B2D26; --muted: #5C7268; --border: #D2E0D9;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* PIPIGILAN ANG SCROLL SA DESKTOP */
        @media (min-width: 1025px) {
            html, body { height: 100vh !important; overflow: hidden !important; margin: 0; }
        }

        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background: url('assets/images/Lucena.jpg?v=2') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
        }

        /* BINAWASAN ANG OPACITY PARA MAKITA ANG DETAILS NG BACKGROUND */
        .overlay {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(15,51,44,0.85), rgba(22,68,60,0.90));
            z-index: 1;
        }

        .split-layout {
            display: flex; width: 100%; min-height: 100vh; position: relative; z-index: 2;
            max-width: 1600px; margin: 0 auto;
        }

        /* KALIWA: WELCOME TEXT */
        .left-panel {
            flex: 1;
            display: flex; flex-direction: column; justify-content: center;
            padding: 8vw;
            color: white;
        }

        .left-panel .brand-logo { opacity: 0; font-size: 36px; font-weight: 800; color: white; text-decoration: none; margin-bottom: 20px; display: block; letter-spacing: -1px; animation: slideInLeft 0.8s ease forwards; animation-delay: 0.1s; }
        .left-panel .tag { opacity: 0; display: inline-block; font-size: 13px; font-weight: 800; letter-spacing: 2px; color: #A9D8CA; margin-bottom: 20px; text-transform: uppercase; animation: slideInLeft 0.8s ease forwards; animation-delay: 0.2s; }
        .left-panel h1 { opacity: 0; font-size: 54px; font-weight: 800; line-height: 1.15; margin: 0 0 24px 0; letter-spacing: -1.5px; animation: slideInLeft 0.8s ease forwards; animation-delay: 0.3s; }
        .left-panel h1 span { color: #6DB5A9; }
        
        /* INAYOS ANG JUSTIFICATION PARA MADALING BASAHIN */
        .left-panel p { 
            opacity: 0;
            font-size: 16px; 
            line-height: 1.8; 
            color: rgba(255,255,255,0.9); 
            max-width: 520px; 
            text-align: left; 
            animation: slideInLeft 0.8s ease forwards; 
            animation-delay: 0.4s;
        }

        /* KANAN: FORM CARD */
        .right-panel {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            padding: 30px;
        }

        .register-card {
            background: #FFFFFF; width: 100%; max-width: 480px; padding: 40px;
            border-radius: 24px; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4);
            animation: fadeInUp 0.8s ease forwards;
        }

        .form-header { margin-bottom: 30px; text-align: left; }
        .form-header h2 { font-size: 28px; font-weight: 800; color: var(--deep); margin: 0 0 6px 0; letter-spacing: -0.8px; }
        .form-header p { font-size: 14px; color: var(--muted); margin: 0; }

        .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field.full { grid-column: 1 / -1; }
        .field label { display: block; font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        
        /* PINAGLAKI ANG MGA INPUTS PARA MUKHANG PREMIUM */
        .input-box { display: flex; align-items: center; background: #FBFCFB; border: 1.5px solid var(--border); border-radius: 10px; padding: 0 14px; height: 46px; transition: 0.2s; }
        .input-box:focus-within { border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(67,140,127,0.12); }
        .input-box i { color: #91A29C; font-size: 16px; margin-right: 10px; }
        .input-box input, .input-box select { width: 100%; border: none; background: transparent; outline: none; font-size: 14px; color: var(--text); font-weight: 500; }
        
        .btn-submit {
            width: 100%; height: 50px; background: var(--deep); color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: 700; margin-top: 25px; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(22,68,60,0.2); }

        .alert-error { background: #FFF5F5; color: #A83D3D; border: 1px solid #F2D0D0; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }
        .alert-success { background: #F0F9F4; color: #245B54; border: 1px solid #C8E5D8; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }
        .register-error { color: #C44747; font-size: 11px; font-weight: 600; margin-top: 4px; display: block; }

        /* RESPONSIVE PARA SA MOBILE */
        @media (max-width: 1024px) {
            body { display: block; overflow-y: auto; }
            .split-layout { flex-direction: column; }
            .left-panel { padding: 60px 30px; text-align: center; }
            .left-panel h1 { font-size: 42px; }
            .left-panel p { margin: 0 auto; text-align: center; }
            .right-panel { padding: 30px 20px; }
            .fields { grid-template-columns: 1fr; gap: 14px; }
        }
    </style>
</head>
<body>

    <div class="overlay"></div>

    <div class="split-layout">
        <!-- KALIWA -->
        <div class="left-panel">
            <a href="index.php" class="brand-logo">LabSync.</a>
            <span class="tag">B204 Computer Laboratory · SLSU Lucena</span>
            <h1>Join the LabSync <br><span>Community.</span></h1>
            <p>Report issues, track maintenance, and help keep our computer laboratories in top condition. Create your account today.</p>
        </div>

        <!-- KANAN -->
        <div class="right-panel">
            <div class="register-card">
                <div class="form-header">
                    <h2>Create an account</h2>
                    <p>Enter your details below to get started.</p>
                </div>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert-error"><i class="bi bi-exclamation-circle"></i> <?= e($errors['form']) ?></div>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <div class="alert-success"><i class="bi bi-check-circle"></i> <?= e($successMessage) ?></div>
                <?php endif; ?>

                <form method="POST" action="insert.php">
                    <?= csrf_field() ?>
                    <div class="fields">
                        <div class="field full">
                            <label>Full Name</label>
                            <div class="input-box"><i class="bi bi-person"></i><input type="text" name="full_name" value="<?= e($fullName) ?>" placeholder="Enter full name" required></div>
                            <?php field_error($errors, 'full_name'); ?>
                        </div>

                        <div class="field full">
                            <label>Email Address</label>
                            <div class="input-box"><i class="bi bi-envelope"></i><input type="email" name="email" value="<?= e($email) ?>" placeholder="name@example.com" required></div>
                            <?php field_error($errors, 'email'); ?>
                        </div>

                        <div class="field full">
                            <label>Account Role</label>
                            <div class="input-box">
                                <i class="bi bi-person-badge"></i>
                                <select name="role" required>
                                    <option value="">Select role</option>
                                    <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                                    <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="faculty" <?= $role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                                    <option value="personnel" <?= $role === 'personnel' ? 'selected' : '' ?>>IT Personnel</option>
                                </select>
                            </div>
                            <?php field_error($errors, 'role'); ?>
                        </div>

                        <div class="field">
                            <label>ID Number</label>
                            <div class="input-box"><i class="bi bi-person-vcard"></i><input type="text" name="id_number" value="<?= e($idNumber) ?>" placeholder="ID number" required></div>
                            <?php field_error($errors, 'id_number'); ?>
                        </div>

                        <div class="field">
                            <label>Contact Number</label>
                            <div class="input-box"><i class="bi bi-telephone"></i><input type="text" name="contact_no" value="<?= e($contactNo) ?>" placeholder="09XX XXX XXXX" required></div>
                            <?php field_error($errors, 'contact_no'); ?>
                        </div>

                        <div class="field full">
                            <label>Department</label>
                            <div class="input-box"><i class="bi bi-building"></i><input type="text" name="department" value="<?= e($department) ?>" placeholder="Enter department" required></div>
                            <?php field_error($errors, 'department'); ?>
                        </div>

                        <div class="field">
                            <label>Password</label>
                            <div class="input-box"><i class="bi bi-lock"></i><input type="password" name="password" placeholder="Create password" required></div>
                            <?php field_error($errors, 'password'); ?>
                        </div>

                        <div class="field">
                            <label>Confirm Password</label>
                            <div class="input-box"><i class="bi bi-lock-fill"></i><input type="password" name="confirm_password" placeholder="Repeat password" required></div>
                            <?php field_error($errors, 'confirm_password'); ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Create Account <i class="bi bi-arrow-right"></i></button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <span style="font-size: 14px; color: var(--muted);">Already have an account?</span>
                    <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none; margin-left: 5px;">Sign In</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>