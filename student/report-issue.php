
<?php

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

    <title>Report an Issue | LabSync</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary: #4E968B;
            --deep-green: #245B54;
            --soft-mint: #E5F0ED;
            --background: #F7F8F6;
            --text: #303C39;
            --muted: #7B8985;
            --border: #E7ECE9;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        .page-container {
            max-width: 1050px;
            margin: 0 auto;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--deep-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 22px;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .page-heading h1 {
            font-size: clamp(24px, 4vw, 30px);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 9px;
        }

        .page-heading p {
            color: var(--muted);
            line-height: 1.7;
            max-width: 600px;
            margin: 0;
        }

        .form-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 22px;
            align-items: start;
        }

        .form-card,
        .info-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(36, 91, 84, .035);
        }

        .form-card {
            overflow: hidden;
        }

        .form-card-header {
            padding: 23px 25px;
            border-bottom: 1px solid var(--border);
        }

        .form-card-header h2 {
            font-size: 16px;
            font-weight: 750;
            margin: 0 0 6px;
        }

        .form-card-header p {
            color: var(--muted);
            font-size: 11px;
            margin: 0;
        }

        .form-card-body {
            padding: 25px;
        }

        .form-label {
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .form-control,
        .form-select {
            min-height: 45px;
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            box-shadow: none !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78, 150, 139, .12) !important;
        }

        textarea.form-control {
            min-height: 140px;
            resize: vertical;
            line-height: 1.7;
        }

        .form-text {
            color: var(--muted);
            font-size: 10px;
            line-height: 1.6;
            margin-top: 8px;
        }

        .description-footer {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 8px;
        }

        .character-count {
            color: var(--muted);
            font-size: 10px;
            white-space: nowrap;
        }

        .upload-area {
            padding: 18px;
            border: 1px dashed #B9D2CB;
            border-radius: 10px;
            background: #FAFCFB;
        }

        .upload-area .form-control {
            background: transparent;
        }

        .photo-preview {
            display: none;
            max-width: 180px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 9px;
            margin-top: 14px;
            border: 1px solid var(--border);
        }

        .submit-button {
            background: var(--deep-green);
            border: 1px solid var(--deep-green);
            color: var(--white);
            border-radius: 9px;
            padding: 12px 19px;
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .submit-button:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
            transform: translateY(-1px);
        }

        .cancel-button {
            background: var(--white);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 12px 19px;
            font-size: 12px;
            font-weight: 650;
        }

        .cancel-button:hover {
            background: var(--background);
            border-color: #C9D5D0;
        }

        .info-card {
            padding: 22px;
        }

        .info-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 12px;
            font-size: 19px;
            margin-bottom: 17px;
        }

        .info-card h3 {
            font-size: 14px;
            font-weight: 750;
            margin-bottom: 9px;
        }

        .info-card p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
            margin-bottom: 17px;
        }

        .info-step {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 13px 0;
            border-top: 1px solid var(--border);
        }

        .step-number {
            flex-shrink: 0;
            width: 25px;
            height: 25px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 10px;
            font-weight: 800;
        }

        .info-step strong {
            display: block;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .info-step span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.5;
        }

        .alert {
            border: none;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.6;
        }

        .alert-success {
            background: #E8F5EC;
            color: #24613B;
        }

        .alert-danger {
            background: #FCECEC;
            color: #9B3434;
        }

        @media (max-width: 767px) {

            .form-layout {
                grid-template-columns: 1fr;
            }

            .form-card-header,
            .form-card-body {
                padding: 20px;
            }

            .info-card {
                padding: 20px;
            }

            .page-heading {
                margin-bottom: 20px;
            }

        }

    </style>

</head>

<body>

    <!-- SHARED STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>


    <main class="container py-4 py-lg-5">

        <div class="page-container">

            <!-- PAGE HEADING -->
            <div class="page-heading">

                <a href="dashboard.php" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>

                <h1>Report a Computer Issue</h1>

                <p>
                    Experiencing a computer problem?
                    Submit a report so the IT personnel can review
                    and monitor the issue.
                </p>

            </div>


            <!-- SUCCESS MESSAGE -->
            <?php if ($successMessage): ?>

                <div class="alert alert-success mb-4" role="alert">

                    <i class="bi bi-check-circle-fill me-2"></i>

                    <?= e($successMessage) ?>

                </div>

            <?php endif; ?>


            <!-- DATABASE ERROR -->
            <?php if ($databaseError): ?>

                <div class="alert alert-danger mb-4" role="alert">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    Unable to load computer units and issue categories.
                    Please check your database connection.

                </div>

            <?php endif; ?>


            <!-- VALIDATION ERRORS -->
            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger mb-4" role="alert">

                    <strong>
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        Please check the following:
                    </strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <div class="form-layout">

                <!-- REPORT FORM -->
                <section class="form-card">

                    <div class="form-card-header">

                        <h2>
                            <i class="bi bi-clipboard-plus me-2"
                               style="color: #4E968B;"></i>
                            Issue Report Details
                        </h2>

                        <p>
                            Complete the information below to submit your report.
                        </p>

                    </div>


                    <div class="form-card-body">

                        <form
                            method="POST"
                            action=""
                            enctype="multipart/form-data"
                        >

                            <?= csrf_field() ?>


                            <!-- COMPUTER UNIT -->
                            <div class="mb-4">

                                <label for="unit_id" class="form-label">
                                    Computer Unit
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    class="form-select"
                                    id="unit_id"
                                    name="unit_id"
                                    required
                                >

                                    <option value="">
                                        Select computer unit
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

                                <div class="form-text">
                                    Select the computer where you encountered the problem.
                                </div>

                            </div>


                            <!-- ISSUE CATEGORY -->
                            <div class="mb-4">

                                <label for="category_id" class="form-label">
                                    Issue Category
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    class="form-select"
                                    id="category_id"
                                    name="category_id"
                                    required
                                >

                                    <option value="">
                                        Select issue category
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

                                <div class="form-text">
                                    Choose the category that best describes the problem.
                                </div>

                            </div>


                            <!-- DESCRIPTION -->
                            <div class="mb-4">

                                <label for="description" class="form-label">
                                    Problem Description
                                    <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    class="form-control"
                                    id="description"
                                    name="description"
                                    rows="5"
                                    maxlength="3000"
                                    placeholder="Describe the computer problem you encountered. Include what happened and any error messages you noticed."
                                    required
                                ><?= e($description) ?></textarea>

                                <div class="description-footer">

                                    <small class="form-text">
                                        Provide enough details to help IT personnel understand the issue.
                                    </small>

                                    <span class="character-count">
                                        <span id="characterCount"><?= strlen($description) ?></span>/3000
                                    </span>

                                </div>

                            </div>


                            <!-- OPTIONAL PHOTO -->
                            <div class="mb-4">

                                <label for="photo" class="form-label">
                                    Attach Photo
                                    <span class="text-muted fw-normal">(Optional)</span>
                                </label>

                                <div class="upload-area">

                                    <div class="d-flex align-items-center gap-2 mb-2">

                                        <i class="bi bi-image fs-5"
                                           style="color: #4E968B;"></i>

                                        <span class="fw-semibold">
                                            Upload an image of the problem
                                        </span>

                                    </div>

                                    <input
                                        type="file"
                                        class="form-control"
                                        id="photo"
                                        name="photo"
                                        accept=".jpg,.jpeg,.png,.webp"
                                    >

                                    <div class="form-text">
                                        Accepted formats: JPG, PNG, WEBP.
                                        Maximum file size: 2 MB.
                                    </div>

                                    <img
                                        id="photoPreview"
                                        class="photo-preview"
                                        alt="Selected image preview"
                                    >

                                </div>

                            </div>


                            <!-- SUBMIT BUTTONS -->
                            <div class="d-flex flex-wrap gap-2 pt-2">

                                <button
                                    type="submit"
                                    class="btn submit-button"
                                    <?= ($databaseError || empty($units) || empty($categories))
                                        ? 'disabled'
                                        : '' ?>
                                >

                                    <i class="bi bi-send me-2"></i>
                                    Submit Report

                                </button>

                                <a
                                    href="dashboard.php"
                                    class="btn cancel-button"
                                >
                                    Cancel
                                </a>

                            </div>

                        </form>

                    </div>

                </section>


                <!-- INFORMATION PANEL -->
                <aside class="info-card">

                    <div class="info-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>

                    <h3>What happens next?</h3>

                    <p>
                        After submitting your report, the IT personnel
                        can review the details and document the maintenance process.
                    </p>


                    <div class="info-step">

                        <div class="step-number">01</div>

                        <div>
                            <strong>Report Submitted</strong>
                            <span>
                                Your report is saved with a Pending status.
                            </span>
                        </div>

                    </div>


                    <div class="info-step">

                        <div class="step-number">02</div>

                        <div>
                            <strong>Issue Inspection</strong>
                            <span>
                                IT personnel can review the reported problem.
                            </span>
                        </div>

                    </div>


                    <div class="info-step">

                        <div class="step-number">03</div>

                        <div>
                            <strong>Maintenance Tracking</strong>
                            <span>
                                Progress and maintenance activities can be documented.
                            </span>
                        </div>

                    </div>


                    <div class="info-step">

                        <div class="step-number">04</div>

                        <div>
                            <strong>View Report Updates</strong>
                            <span>
                                Monitor your report through the My Reports page.
                            </span>
                        </div>

                    </div>

                </aside>

            </div>

        </div>

    </main>


    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Character counter.
        const descriptionInput = document.getElementById('description');
        const characterCount = document.getElementById('characterCount');

        descriptionInput.addEventListener('input', function () {
            characterCount.textContent = this.value.length;
        });


        // Optional photo preview.
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photoPreview');

        photoInput.addEventListener('change', function () {

            const file = this.files[0];

            if (!file) {
                photoPreview.src = '';
                photoPreview.style.display = 'none';
                return;
            }

            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!allowedTypes.includes(file.type)) {
                photoPreview.src = '';
                photoPreview.style.display = 'none';
                return;
            }

            const imageUrl = URL.createObjectURL(file);

            photoPreview.src = imageUrl;
            photoPreview.style.display = 'block';

            photoPreview.onload = function () {
                URL.revokeObjectURL(imageUrl);
            };

        });

    </script>

</body>
</html>
