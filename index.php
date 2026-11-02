<?php
// ============================================
// PCARE HOMEPAGE
// Connect to MySQL database
// ============================================

require_once __DIR__ . '/config/db.php';

// Prepare default values
$computerUnits = [];
$issueCategories = [];
$databaseError = false;

// Escape output to prevent XSS
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Retrieve computer units and issue categories
try {
    // Get computer units from database
    $unitQuery = $pdo->query("
        SELECT unit_id, unit_code, unit_status
        FROM computer_units
        ORDER BY unit_id ASC
    ");
    $computerUnits = $unitQuery->fetchAll();

    // Get active issue categories
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
    <meta name="description" content="PCare - Computer Issue Reporting and Maintenance Tracking System">
    <title>PCare | Computer Support and Maintenance</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<!-- =========================================
     HEADER / NAVIGATION
========================================= -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php" style="color: var(--deep-green); font-size: 1.5rem;">
            <i class="bi bi-pc-display me-2" style="color: var(--primary-teal);"></i> PCare
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <a href="login.php" class="btn btn-custom-primary px-4 py-2">Sign In</a>
        </div>
    </div>
</nav>

<main>

<!-- =========================================
     HERO SECTION
========================================= -->
<section id="home" class="hero-section text-center d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <span class="badge bg-mint text-dark mb-3 px-3 py-2 rounded-pill fw-semibold tracking-wide">B204 COMPUTER LABORATORY</span>
                <h1 class="display-3 fw-bolder mb-4" style="color: var(--deep-green); line-height: 1.1;">
                    Better Computer Support.<br>More Organized Maintenance.
                </h1>
                <p class="lead text-muted mb-5 px-md-5">
                    PCare provides a convenient platform for reporting computer issues, tracking their status, and documenting maintenance activities in the B204 Computer Laboratory of SLSU Lucena Campus.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="login.php" class="btn btn-custom-primary btn-lg px-5 py-3">Access PCare</a>
                    <a href="#about" class="btn btn-outline-custom btn-lg px-5 py-3">Explore PCare</a>
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
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h2 class="fw-bold mb-4" style="color: var(--deep-green);">About PCare</h2>
                <p class="lead text-muted mb-4">
                    PCare is a web-based computer issue reporting and maintenance tracking system designed for the B204 Computer Laboratory of SLSU Lucena Campus.
                </p>
                <p class="text-muted">
                    It provides a centralized platform where students and authorized users can report computer-related problems, while IT personnel can inspect reports, document maintenance activities, and monitor issue progress.
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
            <h2 class="fw-bold" style="color: var(--deep-green);">What PCare Offers</h2>
            <p class="text-muted">Essential tools for organized computer support and maintenance documentation.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm service-card">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box mx-auto mb-4 bg-mint text-teal rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-tools fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Issue Reporting</h5>
                        <p class="text-muted small mb-0">Submit computer-related problems by selecting a unit, category, and describing the issue.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm service-card">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box mx-auto mb-4 bg-mint text-teal rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-search fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Status Tracking</h5>
                        <p class="text-muted small mb-0">Monitor the progress of your submitted computer issue reports in real-time.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm service-card">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box mx-auto mb-4 bg-mint text-teal rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-journal-text fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Maintenance Records</h5>
                        <p class="text-muted small mb-0">Document inspection findings, troubleshooting actions, and repair results.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm service-card">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box mx-auto mb-4 bg-mint text-teal rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-clock-history fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Issue History</h5>
                        <p class="text-muted small mb-0">Maintain historical records of reported problems and their status updates.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     PROJECT SCOPE SECTION (DATABASE DATA)
========================================= -->
<section id="project-scope" class="py-5" style="background-color: var(--soft-mint);">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-3" style="color: var(--deep-green);">Project Coverage</h2>
                <h4 class="mb-4">B204 Computer Laboratory</h4>
                <p class="text-muted">PCare is specifically designed for the computer units included in the project's defined scope.</p>
                
                <?php if ($databaseError): ?>
                    <div class="alert alert-danger">Computer unit information is currently unavailable.</div>
                <?php else: ?>
                    <div class="d-flex gap-4 mt-4">
                        <div>
                            <h2 class="display-5 fw-bold text-teal mb-0"><?= $unitCount ?></h2>
                            <p class="text-muted small fw-semibold text-uppercase">Registered Units</p>
                        </div>
                        <div>
                            <h2 class="display-5 fw-bold text-teal mb-0"><?= $categoryCount ?></h2>
                            <p class="text-muted small fw-semibold text-uppercase">Active Categories</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Computer Unit Information</h5>
                        
                        <?php if ($databaseError): ?>
                            <p class="text-danger">Data unavailable.</p>
                        <?php elseif (!empty($computerUnits)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Unit Code</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($computerUnits as $unit): ?>
                                            <tr>
                                                <td class="fw-bold"><?= $escape($unit['unit_code']) ?></td>
                                                <td>
                                                    <?php if ($unit['unit_status'] === 'Active'): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3">Active</span>
                                                    <?php elseif ($unit['unit_status'] === 'Under Maintenance'): ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3">Under Maintenance</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-3"><?= $escape($unit['unit_status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No computer units have been registered yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     CONTACT SECTION
========================================= -->
<section id="contact" class="py-5 bg-white">
    <div class="container py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <i class="bi bi-envelope-paper fs-1 text-teal mb-3 d-block"></i>
                <h2 class="fw-bold mb-3" style="color: var(--deep-green);">Contact and Support</h2>
                <p class="text-muted mb-4">
                    For laboratory-related concerns or assistance regarding computer units, please coordinate with authorized laboratory personnel.
                </p>
                <p class="text-muted fst-italic">
                    Official contact information will be provided once confirmed by the appropriate representatives.
                </p>
            </div>
        </div>
    </div>
</section>

</main>

<!-- =========================================
     FOOTER
========================================= -->
<footer class="py-5 text-center text-md-start" style="background-color: var(--deep-green); color: white;">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto text-center">
                <h3 class="fw-bold mb-3"><i class="bi bi-pc-display me-2"></i>PCare</h3>
                <p class="text-white-50 mb-4">
                    A Web-Based Computer Issue Reporting and Maintenance Tracking System for SLSU Lucena Campus - B204 Computer Laboratory.
                </p>
                <p class="text-white-50 small mb-0">
                    &copy; <?= date('Y') ?> PCare. All rights reserved. <br>
                    <span class="fst-italic">Academic project. Not an official SLSU-adopted platform.</span>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>