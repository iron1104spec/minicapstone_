<?php
// student/report-issue.php
require_once __DIR__ . '/report-issue-logic.php';

// Fallback kung wala sa logic file
$fullName = $_SESSION['full_name'] ?? 'Student';
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

    <style>
        /* --- GLOBAL & BACKGROUND PATTERN --- */
        body {
            background-color: #f4f7f6;
            background-image: radial-gradient(#d1dbd6 1px, transparent 1px);
            background-size: 20px 20px;
            font-family: 'Inter', sans-serif;
        }

        /* --- TOP HEADER --- */
        .custom-topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #ffffff; display: flex; justify-content: space-between;
            align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 9999;
        }
        .brand-area { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-icon { font-size: 24px; color: #0d2f24; }
        .brand-name { display: block; font-weight: 800; font-size: 18px; color: #0d2f24; line-height: 1; }
        .brand-caption { display: block; font-size: 9px; letter-spacing: 1px; color: #666; font-weight: 600; }

        /* --- FLOATING DOCK --- */
        .floating-dock {
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%);
            display: flex; flex-direction: row; gap: 8px; background-color: #0d2f24;
            padding: 12px 24px; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1); z-index: 9999;
            max-width: 95%; overflow-x: auto;
        }
        .floating-dock::-webkit-scrollbar { display: none; }
        .dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #a0b8b0; text-decoration: none; padding: 10px 20px;
            border-radius: 16px; transition: all 0.3s ease; white-space: nowrap;
        }
        .dock-item i { font-size: 20px; margin-bottom: 4px; }
        .dock-item span { font-size: 11px; font-weight: 500; }
        .dock-item:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; transform: translateY(-4px); }
        .dock-item.active { background-color: #1a4a3a; color: #ffffff; }

        /* --- MAIN CONTENT SPACING --- */
        .main-wrapper {
            padding-top: 90px; 
            padding-bottom: 120px; 
            width: 100%;
        }
        .dashboard-content { padding: 20px 30px; }

        /* --- HERO BANNER --- */
        .welcome-section {
            background: linear-gradient(135deg, #0f3d2e 0%, #1a5c47 100%);
            border-radius: 20px;
            padding: 40px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(13, 47, 36, 0.15);
            margin-bottom: 2rem;
        }
        .welcome-section::before {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 300px; height: 300px; border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
        .welcome-label {
            display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
            background: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 20px;
            margin-bottom: 15px; text-transform: uppercase;
        }
        .welcome-title { font-size: 32px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px; }
        .welcome-description { font-size: 14px; color: rgba(255, 255, 255, 0.8); max-width: 600px; line-height: 1.6; margin: 0; }
        
        .btn-back-header {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; font-weight: 600; margin-bottom: 15px;
            position: relative; z-index: 2; transition: color 0.2s;
        }
        .btn-back-header:hover { color: #ffffff; }

        /* --- CONTENT CARDS --- */
        .content-card {
            background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-top: 4px solid #1a4a3a;
            overflow: hidden; margin-bottom: 2rem; padding: 30px;
        }
        
        .form-card-header h2 { font-size: 20px; font-weight: 700; color: #0d2f24; margin-bottom: 5px; }
        .form-card-header p { font-size: 14px; color: #64748b; margin-bottom: 25px; }

        /* --- FORM CONTROLS --- */
        .form-label { font-size: 13px; font-weight: 600; color: #1e293b; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; font-size: 14px; }
        .form-control:focus, .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25); }
        .form-text { font-size: 12px; color: #64748b; margin-top: 5px; }

        .btn-submit { background-color: #0f3d2e; color: white; border-radius: 8px; font-weight: 600; padding: 12px 24px; border: none; display: inline-flex; align-items: center; gap: 8px;}
        .btn-submit:hover { background-color: #1a5c47; color: white; }
        .btn-cancel { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600; padding: 12px 24px; text-decoration: none;}
        .btn-cancel:hover { background-color: #e2e8f0; color: #1e293b; }

        /* Info Panel Steps */
        .info-step { display: flex; gap: 15px; margin-bottom: 20px; }
        .step-number {
            width: 32px; height: 32px; border-radius: 50%; background: #e6f4ea; color: #0f3d2e;
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;
        }
        .info-step strong { display: block; font-size: 14px; color: #1e293b; margin-bottom: 2px; }
        .info-step span { font-size: 13px; color: #64748b; line-height: 1.4; display: block; }
    </style>
</head>

<body>

<!-- TOP HEADER -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">STUDENT PORTAL</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px; font-weight: 500;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                ST
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2; color: #1a1a1a;"><?= htmlspecialchars($fullName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;">Student</div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<<<<<<< HEAD
<!-- MAIN CONTENT -->
<div class="main-wrapper">
    <main class="dashboard-content container">
        
        <!-- HERO BANNER -->
        <section class="welcome-section">
            <div style="position: relative; z-index: 2;">
                <a href="dashboard.php" class="btn-back-header">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <div class="welcome-label">ISSUE REPORTING</div>
                <h1 class="welcome-title">Report a Computer Issue</h1>
                <p class="welcome-description">
                    Experiencing a computer problem? Submit a report so the IT personnel can review and monitor the issue.
                </p>
            </div>
        </section>

        <!-- NOTIFICATIONS -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success mb-4" style="border-radius: 10px;" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= e($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($databaseError): ?>
            <div class="alert alert-danger mb-4" style="border-radius: 10px;" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Unable to load computer units and issue categories. Please check your database connection.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-4" style="border-radius: 10px;" role="alert">
                <strong><i class="bi bi-exclamation-circle-fill me-2"></i> Please check the following:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- LEFT COLUMN: FORM -->
            <div class="col-lg-8">
                <section class="content-card h-100">
                    <div class="form-card-header">
                        <h2><i class="bi bi-clipboard-plus me-2" style="color: #0f3d2e;"></i> Issue Report Details</h2>
                        <p>Complete the information below to submit your report.</p>
                    </div>

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
                            <div class="d-flex justify-content-between mt-2">
                                <small class="form-text">Provide enough details to help IT personnel understand the issue.</small>
                                <span class="form-text"><span id="characterCount"><?= strlen($description) ?></span>/3000</span>
                            </div>
                        </div>

                        <!-- OPTIONAL PHOTO -->
                        <div class="mb-4">
                            <label for="photo" class="form-label">Attach Photo <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">Accepted formats: JPG, PNG, WEBP. Maximum file size: 2 MB.</div>
                            <img id="photoPreview" class="mt-3 rounded shadow-sm d-none" style="max-height: 200px; object-fit: cover;" alt="Selected image preview">
                        </div>

                        <!-- SUBMIT BUTTONS -->
                        <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-submit" <?= ($databaseError || empty($units) || empty($categories)) ? 'disabled' : '' ?>>
                                <i class="bi bi-send"></i> Submit Report
                            </button>
                            <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
                        </div>
                    </form>
                </section>
            </div>

            <!-- RIGHT COLUMN: INFO PANEL -->
            <div class="col-lg-4">
                <aside class="content-card h-100">
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-info-circle-fill" style="color: #0f3d2e; font-size: 20px;"></i>
                            <h3 class="m-0 fw-bold" style="font-size: 18px; color: #0d2f24;">What happens next?</h3>
                        </div>
                        <p class="text-muted" style="font-size: 13px;">After submitting your report, the IT personnel can review the details and document the maintenance process.</p>
                    </div>

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
                    <div class="info-step mb-0">
                        <div class="step-number">04</div>
                        <div>
                            <strong>View Report Updates</strong>
                            <span>Monitor your report through the My Reports page.</span>
                        </div>
                    </div>
                </aside>
=======
    <main class="dashboard-content">
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
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
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

    </main>
</div>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="report-issue.php" class="dock-item active">
        <i class="bi bi-plus-circle"></i><span>Report an Issue</span>
    </a>
    <a href="my-reports.php" class="dock-item">
        <i class="bi bi-file-earmark-text"></i><span>My Reports</span>
    </a>
    <a href="profile.php" class="dock-item">
        <i class="bi bi-person-circle"></i><span>My Profile</span>
    </a>
</nav>

<!-- BOOTSTRAP JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- External JS -->
<script src="../assets/js/student-report-issue.js"></script>

</body>
</html> /* assets/css/student-report-issue.css */