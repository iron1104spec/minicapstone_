
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();

$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Student';

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

    // Verify CSRF token.
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid request. Please refresh the page and try again.';

    } else {

        // Get submitted form values.
        $unitId = (int) ($_POST['unit_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        // Validate computer unit.
        $validUnitIds = array_map(
            'intval',
            array_column($units, 'unit_id')
        );

        if (!in_array($unitId, $validUnitIds, true)) {
            $errors[] = 'Please select a valid computer unit.';
        }

        // Validate issue category.
        $validCategoryIds = array_map(
            'intval',
            array_column($categories, 'category_id')
        );

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

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

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

                // Generate a unique report reference number.
                $referenceNo = 'PC-B204-' .
                    date('Ymd') . '-' .
                    strtoupper(bin2hex(random_bytes(4)));

                $photoPath = null;

                // Move the optional image into the uploads folder.
                if ($photoExtension !== null) {

                    $uploadDirectory = __DIR__ .
                        '/../uploads/issues';

                    if (
                        !is_dir($uploadDirectory) &&
                        !mkdir($uploadDirectory, 0755, true) &&
                        !is_dir($uploadDirectory)
                    ) {
                        throw new RuntimeException(
                            'Unable to create the upload directory.'
                        );
                    }

                    $fileName = bin2hex(random_bytes(16)) .
                        '.' . $photoExtension;

                    $destination = $uploadDirectory . '/' . $fileName;

                    if (!move_uploaded_file(
                        $photo['tmp_name'],
                        $destination
                    )) {
                        throw new RuntimeException(
                            'Unable to save the uploaded photo.'
                        );
                    }

                    $uploadedFilePath = $destination;
                    $photoPath = 'uploads/issues/' . $fileName;
                }

                // Begin database transaction.
                $pdo->beginTransaction();

                // Insert the issue report.
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

                // Get the ID of the newly created report.
                $reportId = (int) $pdo->lastInsertId();

                // Create the initial status history entry.
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

                // Save both database operations.
                $pdo->commit();

                // Show a success message after redirecting.
                flash_set(
                    'success',
                    'Your report was submitted successfully. Reference Number: ' .
                    $referenceNo
                );

                redirect('report-issue.php');

            } catch (Throwable $e) {

                // Undo database changes if something goes wrong.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                // Remove the uploaded image if database saving failed.
                if (
                    $uploadedFilePath !== null &&
                    file_exists($uploadedFilePath)
                ) {
                    unlink($uploadedFilePath);
                }

                error_log($e->getMessage());

                $errors[] = 'Unable to submit your report. Please try again.';
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

    <title>Report an Issue | PCare</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <!-- NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">

        <div class="container">

            <a class="navbar-brand fw-bold" href="dashboard.php">
                PCare
            </a>

            <div class="ms-auto d-flex align-items-center">

                <span class="text-white me-3">
                    <?= e($fullName) ?>
                </span>

                <form action="../logout.php" method="POST">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-outline-light btn-sm"
                    >
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </nav>


    <!-- MAIN CONTENT -->
    <main class="container py-5">

        <div class="mb-4">

            <a href="dashboard.php" class="text-decoration-none">
                ← Back to Dashboard
            </a>

            <h2 class="mt-3">Report a Computer Issue</h2>

            <p class="text-muted">
                Please provide the details of the computer problem
                you experienced in the B204 Computer Laboratory.
            </p>

        </div>


        <!-- SUCCESS MESSAGE -->
        <?php if ($successMessage): ?>

            <div class="alert alert-success">
                <?= e($successMessage) ?>
            </div>

        <?php endif; ?>


        <!-- DATABASE ERROR -->
        <?php if ($databaseError): ?>

            <div class="alert alert-danger">
                Unable to load computer units and issue categories.
                Please check your database connection.
            </div>

        <?php endif; ?>


        <!-- VALIDATION ERRORS -->
        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <strong>Please check the following:</strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($errors as $error): ?>

                        <li><?= e($error) ?></li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- REPORT FORM -->
        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form
                    method="POST"
                    action=""
                    enctype="multipart/form-data"
                >

                    <?= csrf_field() ?>


                    <!-- COMPUTER UNIT -->
                    <div class="mb-4">

                        <label for="unit_id" class="form-label fw-semibold">
                            Computer Unit
                        </label>

                        <select
                            class="form-select"
                            id="unit_id"
                            name="unit_id"
                            required
                        >

                            <option value="">
                                Select Computer Unit
                            </option>

                            <?php foreach ($units as $unit): ?>

                                <option
                                    value="<?= (int) $unit['unit_id'] ?>"
                                    <?= $unitId === (int) $unit['unit_id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= e($unit['unit_code']) ?>
                                    (<?= e($unit['unit_status']) ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- ISSUE CATEGORY -->
                    <div class="mb-4">

                        <label for="category_id" class="form-label fw-semibold">
                            Issue Category
                        </label>

                        <select
                            class="form-select"
                            id="category_id"
                            name="category_id"
                            required
                        >

                            <option value="">
                                Select Issue Category
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int) $category['category_id'] ?>"
                                    <?= $categoryId === (int) $category['category_id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= e($category['category_name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->
                    <div class="mb-4">

                        <label for="description" class="form-label fw-semibold">
                            Problem Description
                        </label>

                        <textarea
                            class="form-control"
                            id="description"
                            name="description"
                            rows="5"
                            maxlength="3000"
                            placeholder="Describe the computer problem you encountered..."
                            required
                        ><?= e($description) ?></textarea>

                        <small class="text-muted">
                            Explain what happened and when you noticed the problem.
                        </small>

                    </div>


                    <!-- OPTIONAL PHOTO -->
                    <div class="mb-4">

                        <label for="photo" class="form-label fw-semibold">
                            Attach Photo (Optional)
                        </label>

                        <input
                            type="file"
                            class="form-control"
                            id="photo"
                            name="photo"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small class="text-muted">
                            Accepted formats: JPG, PNG, WEBP.
                            Maximum file size: 2 MB.
                        </small>

                    </div>


                    <!-- SUBMIT BUTTONS -->
                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-success"
                            <?= ($databaseError || empty($units) || empty($categories))
                                ? 'disabled'
                                : '' ?>
                        >
                            Submit Report
                        </button>

                        <a
                            href="dashboard.php"
                            class="btn btn-outline-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </main>

</body>
</html>
