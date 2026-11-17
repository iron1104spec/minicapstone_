
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

// Load computer units and active categories.
try {
    $stmt = $pdo->query("
        SELECT unit_id, unit_code, unit_status
        FROM computer_units
        ORDER BY unit_code ASC
    ");
    $units = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT category_id, category_name
        FROM issue_categories
        WHERE is_active = 1
        ORDER BY category_name ASC
    ");
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

        // Validate selected computer unit.
        $validUnitIds = array_map(
            'intval',
            array_column($units, 'unit_id')
        );

        if (!in_array($unitId, $validUnitIds, true)) {
            $errors[] = 'Please select a valid computer unit.';
        }

        // Validate selected category.
        $validCategoryIds = array_map(
            'intval',
            array_column($categories, 'category_id')
        );

        if (!in_array($categoryId, $validCategoryIds, true)) {
            $errors[] = 'Please select a valid issue category.';
        }

        // Validate description.
        if ($description === '') {
            $errors[] = 'Please describe the computer issue.';
        } elseif (mb_strlen($description) > 3000) {
            $errors[] = 'Description must not exceed 3000 characters.';
        }

        // Validate optional photo.
        $hasPhoto = isset($_FILES['photo'])
            && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasPhoto) {

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'There was a problem uploading your photo.';

            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo must not exceed 2MB.';

            } else {

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES['photo']['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mimeType])) {
                    $errors[] = 'Only JPG, PNG, and WEBP photos are allowed.';
                }
            }
        }

        // Save report if there are no validation errors.
        if (empty($errors)) {

            $uploadedFilePath = null;

            try {

                $referenceNo = 'PC-B204-' .
                    date('Ymd') . '-' .
                    strtoupper(bin2hex(random_bytes(4)));

                $photoPath = null;

                // Move uploaded photo if provided.
                if ($hasPhoto) {

                    $uploadDirectory = __DIR__ . '/../uploads/issues/';

                    if (
                        !is_dir($uploadDirectory) &&
                        !mkdir($uploadDirectory, 0755, true)
                    ) {
                        throw new RuntimeException(
                            'Unable to create photo upload directory.'
                        );
                    }

                    $extension = $allowedTypes[$mimeType];
                    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

                    $uploadedFilePath = $uploadDirectory . $fileName;

                    if (!move_uploaded_file(
                        $_FILES['photo']['tmp_name'],
                        $uploadedFilePath
                    )) {
                        throw new RuntimeException(
                            'Unable to move uploaded photo.'
                        );
                    }

                    $photoPath = 'uploads/issues/' . $fileName;
                }

                // Start database transaction.
                $pdo->beginTransaction();

                // Insert report.
                $stmt = $pdo->prepare("
                    INSERT INTO issue_reports (
                        reference_no,
                        reporter_id,
                        unit_id,
                        category_id,
                        description,
                        photo_path,
                        status,
                        assigned_to,
                        created_at,
                        updated_at
                    ) VALUES (
                        :reference_no,
                        :reporter_id,
                        :unit_id,
                        :category_id,
                        :description,
                        :photo_path,
                        'Pending',
                        NULL,
                        NOW(),
                        NOW()
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

                // Insert initial status history.
                $stmt = $pdo->prepare("
                    INSERT INTO issue_status_history (
                        report_id,
                        old_status,
                        new_status,
                        changed_by,
                        remarks,
                        changed_at
                    ) VALUES (
                        :report_id,
                        NULL,
                        'Pending',
                        :changed_by,
                        :remarks,
                        NOW()
                    )
                ");

                $stmt->execute([
                    'report_id' => $reportId,
                    'changed_by' => $userId,
                    'remarks' => 'Initial report submission'
                ]);

                // Commit both inserts.
                $pdo->commit();

                flash_set(
                    'success',
                    'Your report was submitted successfully. Reference Number: ' . $referenceNo
                );

                redirect('report-issue.php');
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (
                    $uploadedFilePath !== null &&
                    file_exists($uploadedFilePath)
                ) {
                    unlink($uploadedFilePath);
                }

                error_log($e->getMessage());

                // TEMPORARY DEBUGGING: display actual error.
                $errors[] = 'DEBUG ERROR: ' . $e->getMessage();
            }
        }
    }
}
