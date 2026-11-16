<?php
// student/profile-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();
$userId = (int) $_SESSION['user_id'];

$errors = [];
$databaseError = false;
$successMessage = flash_get('success');

$profile = null;

$fullName = '';
$email = '';
$idNumber = '';
$department = '';
$contactNo = '';

try {
    $stmt = $pdo->prepare("
        SELECT user_id, full_name, email, id_number, department, contact_no, role
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute(['user_id' => $userId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        throw new RuntimeException('Student account not found.');
    }

    $fullName = $profile['full_name'];
    $email = $profile['email'];
    $idNumber = $profile['id_number'];
    $department = $profile['department'];
    $contactNo = $profile['contact_no'];

} catch (Throwable $e) {
    error_log($e->getMessage());
    $databaseError = true;
}

// PROCESS PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $idNumber = trim($_POST['id_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullName) > 150) {
            $errors[] = 'Full name must not exceed 150 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors[] = 'Email address is too long.';
        }

        if ($idNumber === '') {
            $errors[] = 'Student ID number is required.';
        } elseif (strlen($idNumber) > 50 || !preg_match('/^[A-Za-z0-9-]+$/', $idNumber)) {
            $errors[] = 'Please enter a valid student ID number.';
        }

        if ($department === '') {
            $errors[] = 'Department is required.';
        } elseif (strlen($department) > 100) {
            $errors[] = 'Department must not exceed 100 characters.';
        }

        if ($contactNo === '') {
            $errors[] = 'Contact number is required.';
        } elseif (strlen($contactNo) > 30 || !preg_match('/^[0-9+\-\s()]{7,30}$/', $contactNo)) {
            $errors[] = 'Please enter a valid contact number.';
        }

        // CHECK DUPLICATE EMAIL OR ID
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT user_id
                    FROM users
                    WHERE (email = :email OR id_number = :id_number)
                    AND user_id <> :user_id
                    LIMIT 1
                ");

                $stmt->execute([
                    'email' => $email,
                    'id_number' => $idNumber,
                    'user_id' => $userId
                ]);

                if ($stmt->fetch()) {
                    $errors[] = 'Email address or student ID is already used by another account.';
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to validate your account information.';
            }
        }

        // UPDATE PROFILE
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET
                        full_name = :full_name,
                        email = :email,
                        id_number = :id_number,
                        department = :department,
                        contact_no = :contact_no,
                        updated_at = NOW()
                    WHERE user_id = :user_id
                    AND role = 'student'
                ");

                $stmt->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'id_number' => $idNumber,
                    'department' => $department,
                    'contact_no' => $contactNo,
                    'user_id' => $userId
                ]);

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                flash_set('success', 'Your profile has been updated successfully.');
                redirect('profile.php');

            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to update your profile. Please try again.';
            }
        }
    }
}

// PROFILE INITIALS
$nameParts = preg_split('/\s+/', trim($fullName));
$initials = '';

if (!empty($nameParts[0])) {
    $initials .= strtoupper(substr($nameParts[0], 0, 1));
}

if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

if ($initials === '') {
    $initials = 'S';
}