<?php
// student/report-issue.php
require_once __DIR__ . '/report-issue-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue | LabSync</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- External CSS -->
     <link rel="stylesheet" href="../assets/css/student-report-issue.css?v=3">
</head>

<body>

    <!-- SHARED STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>

    <main class="container py-4 py-lg-5">
        <div class="page-container">

            <!-- PAGE HEADING -->
            <div class="page-heading">
                <a href="dashboard.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <h1>Report a Computer Issue</h1>
                <p>
                    Experiencing a computer problem? Submit a report so the IT personnel can review and monitor the issue.
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
                    Unable to load computer units and issue categories. Please check your database connection.
                </div>
            <?php endif; ?>

            <!-- VALIDATION ERRORS -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <strong><i class="bi bi-exclamation-circle-fill me-2"></i> Please check the following:</strong>
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
                            <i class="bi bi-clipboard-plus me-2 text-brand"></i> Issue Report Details
                        </h2>
                        <p>Complete the information below to submit your report.</p>
                    </div>

                    <div class="form-card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?= csrf_field() ?>

                            <!-- COMPUTER UNIT -->
                            <div class="mb-4">
                                <label for="unit_id" class="form-label">Computer Unit <span class="text-danger">*</span></label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    <option value="">Select computer unit</option>
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?= (int) $unit['unit_id'] ?>" <?= $unitId === (int) $unit['unit_id'] ? 'selected' : '' ?>>
                                            <?= e($unit['unit_code']) ?> (<?= e($unit['unit_status']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the computer where you encountered the problem.</div>
                            </div>

                            <!-- ISSUE CATEGORY -->
                            <div class="mb-4">
                                <label for="category_id" class="form-label">Issue Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select issue category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['category_id'] ?>" <?= $categoryId === (int) $category['category_id'] ? 'selected' : '' ?>>
                                            <?= e($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the category that best describes the problem.</div>
                            </div>

                            <!-- DESCRIPTION -->
                            <div class="mb-4">
                                <label for="description" class="form-label">Problem Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" maxlength="3000" placeholder="Describe the computer problem you encountered. Include what happened and any error messages you noticed." required><?= e($description) ?></textarea>
                                <div class="description-footer">
                                    <small class="form-text">Provide enough details to help IT personnel understand the issue.</small>
                                    <span class="character-count"><span id="characterCount"><?= strlen($description) ?></span>/3000</span>
                                </div>
                            </div>

                            <!-- OPTIONAL PHOTO -->
                            <div class="mb-4">
                                <label for="photo" class="form-label">Attach Photo <span class="text-muted fw-normal">(Optional)</span></label>
                                <div class="upload-area">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-image fs-5 text-brand"></i>
                                        <span class="fw-semibold">Upload an image of the problem</span>
                                    </div>
                                    <input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp">
                                    <div class="form-text">Accepted formats: JPG, PNG, WEBP. Maximum file size: 2 MB.</div>
                                    <img id="photoPreview" class="photo-preview" alt="Selected image preview">
                                </div>
                            </div>

                            <!-- SUBMIT BUTTONS -->
                            <div class="d-flex flex-wrap gap-2 pt-2">
                                <button type="submit" class="btn submit-button" <?= ($databaseError || empty($units) || empty($categories)) ? 'disabled' : '' ?>>
                                    <i class="bi bi-send me-2"></i> Submit Report
                                </button>
                                <a href="dashboard.php" class="btn cancel-button">Cancel</a>
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
                    <p>After submitting your report, the IT personnel can review the details and document the maintenance process.</p>

                    <div class="info-step">
                        <div class="step-number">01</div>
                        <div>
                            <strong>Report Submitted</strong>
                            <span>Your report is saved with a Pending status.</span>
                        </div>
                    </div>
                    <div class="info-step">
                        <div class="step-number">02</div>
                        <div>
                            <strong>Issue Inspection</strong>
                            <span>IT personnel can review the reported problem.</span>
                        </div>
                    </div>
                    <div class="info-step">
                        <div class="step-number">03</div>
                        <div>
                            <strong>Maintenance Tracking</strong>
                            <span>Progress and maintenance activities can be documented.</span>
                        </div>
                    </div>
                    <div class="info-step">
                        <div class="step-number">04</div>
                        <div>
                            <strong>View Report Updates</strong>
                            <span>Monitor your report through the My Reports page.</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- External JS -->
    <script src="../assets/js/student-report-issue.js"></script>

</body>
</html>