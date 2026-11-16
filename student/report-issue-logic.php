<?php
// student/report-issue-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();
$userId = (int) $_SESSION['user_id'];

$errors = [];
$successMessage = flash_get('success');

$unitId = 0;
$categoryId = 0;
$description = '';

$units = [];
$categories = [];
$databaseError = false;

// Load computer units and active issue categories.
try {
    $stmt = $pdo->query("SELECT unit_id, unit_code, unit_status FROM computer_units ORDER BY unit_code ASC");
    $units = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT category_id, category_name FROM issue_categories WHERE is_active = 1 ORDER BY category_name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $databaseError = true;
}

// Process report submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    } else {
        $unitId = (int) ($_POST['unit_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        // Validate computer unit.
        $validUnitIds = array_map('intval', array_column($units, 'unit_id'));
        if (!in_array($unitId, $validUnitIds, true)) {
            $errors[] = 'Please select a valid computer unit.';
        }

        // Validate issue category.
        $validCategoryIds = array_map('intval', array_column($categories, 'category_id'));
        if (!in_array($categoryId, $validCategoryIds, true)) {
            $errors[] = 'Please select a valid issue category.';
        }

        // Validate description.
        if ($description === '') {
            $errors[] = 'Please describe the computer problem.';
        } elseif (strlen($description) > 3000) {
            $errors[] = 'The description must not exceed 3,000 characters.';
        }

        // Validate optional photo.
        $photo = $_FILES['photo'] ?? null;
        $photoExtension = null;

        if ($photo && $photo['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($photo['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'The photo upload failed. Please try again.';
            } elseif ($photo['size'] > 2 * 1024 * 1024) {
                $errors[] = 'The photo must not exceed 2 MB.';
            } else {
                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($photo['tmp_name']);

                if (!isset($allowedTypes[$mimeType])) {
                    $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
                } else {
                    $photoExtension = $allowedTypes[$mimeType];
                }
            }
        }

        // Save the report if all validations passed.
        if (empty($errors)) {
            $uploadedFilePath = null;

            try {
                $referenceNo = 'PC-B204-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $photoPath = null;

                // Move the optional image into the uploads folder.
                if ($photoExtension !== null) {
                    $uploadDirectory = __DIR__ . '/../uploads/issues';

                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        throw new RuntimeException('Unable to create the upload directory.');
                    }

                    $fileName = bin2hex(random_bytes(16)) . '.' . $photoExtension;
                    $destination = $uploadDirectory . '/' . $fileName;

                    if (!move_uploaded_file($photo['tmp_name'], $destination)) {
                        throw new RuntimeException('Unable to save the uploaded photo.');
                    }

                    $uploadedFilePath = $destination;
                    $photoPath = 'uploads/issues/' . $fileName;
                }

                $pdo->beginTransaction();

                // Insert the issue report.
                $stmt = $pdo->prepare("
                    INSERT INTO issue_reports (
                        reference_no, reporter_id, unit_id, category_id, description, photo_path, status, assigned_to, created_at, updated_at
                    ) VALUES (
                        :reference_no, :reporter_id, :unit_id, :category_id, :description, :photo_path, 'Pending', NULL, NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    'reference_no' => $referenceNo,
                    'reporter_id' => $userId,
                    'unit_id' => $unitId,
                    'category_id' => $categoryId,
                    'description' => $description,
                    'photo_path' => $photoPath
                ]);

                $reportId = (int) $pdo->lastInsertId();

                // Create the initial status history entry.
                $stmt = $pdo->prepare("
                    INSERT INTO issue_status_history (
                        report_id, old_status, new_status, changed_by, remarks, changed_at
                    ) VALUES (
                        :report_id, NULL, 'Pending', :changed_by, :remarks, NOW()
                    )
                ");
                $stmt->execute([
                    'report_id' => $reportId,
                    'changed_by' => $userId,
                    'remarks' => 'Initial report submission'
                ]);

                $pdo->commit();

                flash_set('success', 'Your report was submitted successfully. Reference Number: ' . $referenceNo);
                redirect('report-issue.php');

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
                    unlink($uploadedFilePath);
                }
                error_log($e->getMessage());
                $errors[] = 'Unable to submit your report. Please try again.';
            }
        }
    }
}