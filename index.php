
<?php
// ============================================
// LABSYNC HOMEPAGE
// ============================================

require_once __DIR__ . '/config/db.php';

// Default values
$computerUnits = [];
$issueCategories = [];
$databaseError = false;

// Escape output
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Retrieve database information
try {
    $unitQuery = $pdo->query("
        SELECT unit_id, unit_code, unit_status
        FROM computer_units
        ORDER BY unit_id ASC
    ");
    $computerUnits = $unitQuery->fetchAll();

    $categoryQuery = $pdo->query("
        SELECT category_id, category_name, description
        FROM issue_categories
        WHERE is_active = 1
        ORDER BY category_id ASC
    ");
    $issueCategories = $categoryQuery->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $databaseError = true;
}

$unitCount = count($computerUnits);
$categoryCount = count($issueCategories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="LabSync - Computer Issue Reporting and Maintenance Tracking System for SLSU Lucena Campus.">

    <title>LabSync | Computer Laboratory Support</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-light">

<!-- =========================================
     NAVIGATION
========================================= -->

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 labsync-navbar">

    <div class="container">

        <a class="navbar-brand d-flex align-items-center fw-bold"
           href="index.php">

            <i class="bi bi-pc-display me-2"></i>
            LabSync

        </a>

        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav mx-auto mb-3 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="#home">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#services">Services</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#how-it-works">How It Works</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>

            </ul>

            <div class="d-flex flex-column flex-lg-row gap-2">

                <a href="login.php" class="btn btn-custom-outline px-4 py-2">
                    Sign In
                </a>

                <a href="insert.php" class="btn btn-custom-primary px-4 py-2">
                    Create Account
                </a>

            </div>

        </div>

    </div>

</nav>


<main>

<!-- =========================================
     HERO SECTION
========================================= -->

<section id="home" class="labsync-hero">

    <div class="container">

        <div class="row align-items-center g-5">

            <!-- HERO CONTENT -->

            <div class="col-lg-6">

                <div class="hero-label mb-4">

                    <span class="hero-label-dot"></span>

                    SLSU LUCENA CAMPUS · COMPUTER LABORATORIES

                </div>

                <h1 class="hero-heading mb-4">

                    Better Computer Support.

                    <span>Smarter Maintenance.</span>

                </h1>

                <p class="hero-description mb-4">

                    LabSync provides a centralized platform for reporting
                    computer issues, monitoring repair progress, and
                    documenting maintenance activities across the
                    computer laboratories of SLSU Lucena Campus.

                </p>

                <div class="d-flex flex-wrap gap-3 mb-5">

                    <a href="insert.php" class="hero-btn-primary">

                        Get Started

                        <i class="bi bi-arrow-right"></i>

                    </a>

                    <a href="#about" class="hero-btn-secondary">

                        Explore LabSync

                    </a>

                </div>

                <div class="hero-trust-note">

                    <i class="bi bi-shield-check"></i>

                    Organized reporting. Transparent maintenance tracking.

                </div>

            </div>


            <!-- CAMPUS IMAGE -->

            <div class="col-lg-6">

                <div class="hero-image-wrapper">

                    <img
                        src="assets/images/Lucena.png"
                        alt="SLSU Lucena Campus"
                        class="hero-campus-image"
                    >

                    <div class="hero-image-overlay"></div>

                    <div class="hero-image-caption">

                        <div class="hero-caption-icon">

                            <i class="bi bi-geo-alt-fill"></i>

                        </div>

                        <div>

                            <span>PROJECT LOCATION</span>

                            <h5>SLSU Lucena Campus</h5>

                            <p>
                                Initial implementation: B204 Computer Laboratory
                            </p>

                        </div>

                    </div>

                    <div class="hero-floating-card">

                        <i class="bi bi-pc-display"></i>

                        <div>

                            <strong>LabSync</strong>

                            <span>Computer Support Platform</span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     ABOUT SECTION
========================================= -->

<section id="about" class="py-5 bg-white">

    <div class="container py-5">

        <div class="row align-items-center justify-content-between g-5">

            <div class="col-lg-5">

                <span class="section-eyebrow">ABOUT THE PLATFORM</span>

                <h2 class="section-heading mt-3 mb-4">
                    A more organized way to manage computer concerns.
                </h2>

            </div>

            <div class="col-lg-6">

                <p class="text-muted mb-3">

                    LabSync is a web-based computer issue reporting
                    and maintenance tracking system designed to
                    support the computer laboratories of SLSU
                    Lucena Campus.

                </p>

                <p class="text-muted mb-0">

                    It provides a centralized platform where students
                    and authorized users can report computer-related
                    problems, while IT personnel can inspect reports,
                    document maintenance activities, and monitor
                    issue progress.

                </p>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     SERVICES SECTION
========================================= -->

<section id="services" class="py-5 bg-light">

    <div class="container py-5">

        <div class="text-center mb-5">

            <span class="section-eyebrow">CORE CAPABILITIES</span>

            <h2 class="section-heading mt-3">
                What LabSync Offers
            </h2>

            <p class="text-muted mt-3">

                Essential tools for organized computer support
                and maintenance documentation.

            </p>

        </div>


        <div class="row g-4">

            <!-- ISSUE REPORTING -->

            <div class="col-md-6 col-lg-3">

                <div class="card h-100 border-0 shadow-sm service-card">

                    <div class="card-body p-4">

                        <div class="icon-box mb-4">

                            <i class="bi bi-tools"></i>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Issue Reporting
                        </h5>

                        <p class="text-muted small mb-0">

                            Submit computer-related problems by selecting
                            a unit, category, and describing the issue.

                        </p>

                    </div>

                </div>

            </div>


            <!-- STATUS TRACKING -->

            <div class="col-md-6 col-lg-3">

                <div class="card h-100 border-0 shadow-sm service-card">

                    <div class="card-body p-4">

                        <div class="icon-box mb-4">

                            <i class="bi bi-search"></i>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Status Tracking
                        </h5>

                        <p class="text-muted small mb-0">

                            Monitor the progress of submitted
                            computer issue reports.

                        </p>

                    </div>

                </div>

            </div>


            <!-- MAINTENANCE RECORDS -->

            <div class="col-md-6 col-lg-3">

                <div class="card h-100 border-0 shadow-sm service-card">

                    <div class="card-body p-4">

                        <div class="icon-box mb-4">

                            <i class="bi bi-journal-text"></i>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Maintenance Records
                        </h5>

                        <p class="text-muted small mb-0">

                            Document inspection findings,
                            troubleshooting actions, and repair results.

                        </p>

                    </div>

                </div>

            </div>


            <!-- ISSUE HISTORY -->

            <div class="col-md-6 col-lg-3">

                <div class="card h-100 border-0 shadow-sm service-card">

                    <div class="card-body p-4">

                        <div class="icon-box mb-4">

                            <i class="bi bi-clock-history"></i>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Issue History
                        </h5>

                        <p class="text-muted small mb-0">

                            Maintain historical records of reported
                            problems and their status updates.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     HOW IT WORKS
========================================= -->

<section id="how-it-works" class="py-5 bg-white">

    <div class="container py-5">

        <div class="text-center mb-5">

            <span class="section-eyebrow">SIMPLE WORKFLOW</span>

            <h2 class="section-heading mt-3">
                How LabSync Works
            </h2>

            <p class="text-muted mt-3">
                A structured process for reporting and addressing computer issues.
            </p>

        </div>

        <div class="row g-4">

            <?php
            $steps = [
                [
                    '01',
                    'Submit a Report',
                    'A student or authorized user submits a computer issue report.',
                    'bi-file-earmark-plus'
                ],
                [
                    '02',
                    'Inspect the Issue',
                    'IT personnel review the reported concern and inspect the computer.',
                    'bi-search'
                ],
                [
                    '03',
                    'Document Maintenance',
                    'Personnel record findings, troubleshooting actions, and repair results.',
                    'bi-clipboard-check'
                ],
                [
                    '04',
                    'Track Progress',
                    'The report status and maintenance history are available for monitoring.',
                    'bi-graph-up-arrow'
                ]
            ];
            ?>

            <?php foreach ($steps as $step): ?>

                <div class="col-md-6 col-lg-3">

                    <div class="workflow-card h-100">

                        <span class="workflow-number">
                            <?= $escape($step[0]) ?>
                        </span>

                        <div class="icon-box mb-4">

                            <i class="bi <?= $escape($step[3]) ?>"></i>

                        </div>

                        <h5 class="fw-bold mb-3">
                            <?= $escape($step[1]) ?>
                        </h5>

                        <p class="text-muted small mb-0">
                            <?= $escape($step[2]) ?>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- =========================================
     PROJECT COVERAGE
========================================= -->

<section id="project-scope" class="project-scope-section py-5">

    <div class="container py-5">

        <div class="row align-items-center g-5">

            <div class="col-lg-5">

                <span class="section-eyebrow">PROJECT COVERAGE</span>

                <h2 class="section-heading mt-3 mb-4">

                    Supporting the computer laboratories of SLSU Lucena Campus.

                </h2>

                <p class="text-muted">

                    LabSync is designed to support organized computer
                    issue reporting and maintenance monitoring across
                    the campus laboratories.

                </p>

                <p class="text-muted small">

                    Initial implementation: B204 Computer Laboratory.

                </p>


                <?php if ($databaseError): ?>

                    <div class="alert alert-danger">

                        Computer unit information is currently unavailable.

                    </div>

                <?php else: ?>

                    <div class="d-flex gap-5 mt-4">

                        <div>

                            <h2 class="coverage-number">
                                <?= $unitCount ?>
                            </h2>

                            <p class="coverage-label">
                                Registered Units
                            </p>

                        </div>

                        <div>

                            <h2 class="coverage-number">
                                <?= $categoryCount ?>
                            </h2>

                            <p class="coverage-label">
                                Active Categories
                            </p>

                        </div>

                    </div>

                <?php endif; ?>

            </div>


            <!-- COMPUTER UNIT TABLE -->

            <div class="col-lg-7">

                <div class="coverage-table-card">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <div>

                            <span class="section-eyebrow">
                                SYSTEM OVERVIEW
                            </span>

                            <h5 class="fw-bold mt-2 mb-0">
                                Computer Unit Information
                            </h5>

                        </div>

                        <i class="bi bi-pc-display-horizontal coverage-table-icon"></i>

                    </div>


                    <?php if ($databaseError): ?>

                        <p class="text-danger">
                            Computer unit data is currently unavailable.
                        </p>

                    <?php elseif (!empty($computerUnits)): ?>

                        <div class="table-responsive">

                            <table class="table align-middle mb-0">

                                <thead>

                                    <tr>
                                        <th>Unit Code</th>
                                        <th>Status</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($computerUnits as $unit): ?>

                                        <tr>

                                            <td class="fw-semibold">

                                                <?= $escape($unit['unit_code']) ?>

                                            </td>

                                            <td>

                                                <?php if ($unit['unit_status'] === 'Active'): ?>

                                                    <span class="unit-status status-active">
                                                        Active
                                                    </span>

                                                <?php elseif ($unit['unit_status'] === 'Under Maintenance'): ?>

                                                    <span class="unit-status status-maintenance">
                                                        Under Maintenance
                                                    </span>

                                                <?php else: ?>

                                                    <span class="unit-status status-inactive">
                                                        <?= $escape($unit['unit_status']) ?>
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <p class="text-muted">
                            No computer units have been registered yet.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     CONTACT SECTION
========================================= -->

<section id="contact" class="py-5 bg-white">

    <div class="container py-5">

        <div class="contact-panel text-center">

            <div class="contact-icon mb-4">

                <i class="bi bi-envelope-paper"></i>

            </div>

            <span class="section-eyebrow">
                NEED ASSISTANCE?
            </span>

            <h2 class="section-heading mt-3 mb-3">
                Contact and Support
            </h2>

            <p class="text-muted mx-auto mb-4">

                For laboratory-related concerns or assistance
                regarding computer units, please coordinate
                with authorized laboratory personnel.

            </p>

            <p class="text-muted small fst-italic mb-0">

                Official contact information will be provided
                once confirmed by the appropriate representatives.

            </p>

        </div>

    </div>

</section>


<!-- =========================================
     FINAL CALL TO ACTION
========================================= -->

<section class="final-cta-section py-5">

    <div class="container py-4">

        <div class="final-cta-content text-center">

            <span class="cta-eyebrow">
                GET STARTED WITH LABSYNC
            </span>

            <h2 class="text-white fw-bold mt-3 mb-3">

                Computer support starts with a report.

            </h2>

            <p class="text-white-50 mb-4">

                Create an account to access the LabSync platform.

            </p>

            <a href="insert.php" class="cta-button">

                Create Your Account

                <i class="bi bi-arrow-right"></i>

            </a>

        </div>

    </div>

</section>

</main>


<!-- =========================================
     FOOTER
========================================= -->

<footer class="labsync-footer py-5">

    <div class="container">

        <div class="row g-4 align-items-center">

            <div class="col-md-7 text-center text-md-start">

                <h3 class="fw-bold mb-3">

                    <i class="bi bi-pc-display me-2"></i>
                    LabSync

                </h3>

                <p class="footer-description mb-0">

                    A Web-Based Computer Issue Reporting and
                    Maintenance Tracking System for the Computer
                    Laboratories of SLSU Lucena Campus.

                </p>

            </div>

            <div class="col-md-5 text-center text-md-end">

                <a href="index.php" class="footer-link">Home</a>

                <a href="login.php" class="footer-link">Sign In</a>

                <a href="insert.php" class="footer-link">Create Account</a>

            </div>

        </div>

        <hr class="footer-divider my-4">

        <div class="text-center">

            <p class="footer-small mb-2">

                &copy; <?= date('Y') ?> LabSync. All rights reserved.

            </p>

            <p class="footer-small fst-italic mb-0">

                Academic project. Not an official SLSU-adopted platform.

            </p>

        </div>

    </div>

</footer>


<!-- BOOTSTRAP JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
