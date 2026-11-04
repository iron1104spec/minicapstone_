<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registration-first access
if (empty($_SESSION['registration_completed'])) {
    header('Location: insert.php');
    exit;
}

// Database connection
require_once __DIR__ . '/config/db.php';

// Escape output safely
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Get computer units
$computerUnits = [];
$totalUnits = 0;

try {
    $unitQuery = $pdo->query("
        SELECT unit_id, unit_code, location, unit_status
        FROM computer_units
        ORDER BY unit_code ASC
    ");

    $computerUnits = $unitQuery->fetchAll(PDO::FETCH_ASSOC);
    $totalUnits = count($computerUnits);

} catch (PDOException $e) {
    $computerUnits = [];
}

// Get active issue categories
$categories = [];
$totalCategories = 0;

try {
    $categoryQuery = $pdo->query("
        SELECT category_id, category_name, description
        FROM issue_categories
        WHERE is_active = 1
        ORDER BY category_name ASC
    ");

    $categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);
    $totalCategories = count($categories);

} catch (PDOException $e) {
    $categories = [];
}

// Current year
$currentYear = date('Y');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LabSync | Computer Laboratory Support System</title>

    <meta name="description"
        content="LabSync is a web-based computer issue reporting and maintenance tracking system for the computer laboratories of SLSU Lucena Campus.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/style.css?v=15">

</head>

<body>

    <!-- =========================
         NAVIGATION
    ========================== -->

    <header class="site-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">

                <a class="navbar-brand" href="index.php">
                    <span class="brand-icon">
                        <i class="bi bi-pc-display-horizontal"></i>
                    </span>

                    <span class="brand-name">Lab<span>Sync</span></span>
                </a>

                <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation"
                    aria-expanded="false"
                    aria-label="Toggle navigation">

                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavigation">

                    <ul class="navbar-nav mx-auto">

                        <li class="nav-item">
                            <a class="nav-link active" href="#home">Home</a>
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

                    <div class="nav-actions">

                        <a href="login.php" class="btn btn-login">
                            Sign In
                        </a>

                        <a href="insert.php" class="btn btn-primary-custom">
                            Create Account
                            <i class="bi bi-arrow-up-right"></i>
                        </a>

                    </div>

                </div>

            </div>
        </nav>
    </header>


    <main>

        <!-- =========================
             HERO SECTION
        ========================== -->

        <section class="hero" id="home">

            <div class="container">

                <div class="row align-items-center">

                    <div class="col-lg-6">

                        <div class="hero-content">

                            <div class="hero-label">
                                <span class="status-dot"></span>
                                COMPUTER LABORATORY SUPPORT SYSTEM
                            </div>

                            <h1>
                                Better Computer Support.
                                <span>Smarter Maintenance.</span>
                            </h1>

                            <p class="hero-description">
                                LabSync provides a centralized platform for reporting
                                computer issues, tracking maintenance progress, and
                                keeping computer laboratory records organized at
                                SLSU Lucena Campus.
                            </p>

                            <div class="hero-buttons">

                                <a href="login.php" class="btn btn-primary-custom">
                                    Get Started
                                    <i class="bi bi-arrow-right"></i>
                                </a>

                                <a href="#about" class="btn btn-outline-custom">
                                    Explore LabSync
                                </a>

                            </div>

                            <div class="hero-note">
                                <i class="bi bi-shield-check"></i>
                                Organized reporting. Transparent maintenance tracking.
                            </div>

                        </div>

                    </div>


                    <!-- Product Preview -->

                    <div class="col-lg-6">

                        <div class="hero-visual">

                            <div class="hero-image-wrapper">

                                <img src="assets/images/Lucena.png"
                                    alt="SLSU Lucena Campus"
                                    class="hero-campus-image">

                                <div class="image-overlay"></div>

                                <div class="hero-image-caption">
                                    <span>B204 COMPUTER LABORATORY</span>
                                    <strong>Supporting a better laboratory experience.</strong>
                                </div>

                            </div>


                            <div class="floating-dashboard">

                                <div class="preview-header">

                                    <div>
                                        <span class="preview-label">LABORATORY OVERVIEW</span>
                                        <h5>Computer Units</h5>
                                    </div>

                                    <span class="preview-icon">
                                        <i class="bi bi-grid"></i>
                                    </span>

                                </div>


                                <div class="preview-unit-grid">

                                    <?php if (!empty($computerUnits)): ?>

                                        <?php foreach ($computerUnits as $unit): ?>

                                            <?php
                                            $unitStatus = strtolower($unit['unit_status'] ?? '');

                                            $statusClass = 'unit-active';

                                            if (strpos($unitStatus, 'maintenance') !== false) {
                                                $statusClass = 'unit-maintenance';
                                            } elseif (strpos($unitStatus, 'inactive') !== false) {
                                                $statusClass = 'unit-inactive';
                                            }
                                            ?>

                                            <div class="preview-unit <?php echo $statusClass; ?>">

                                                <span class="unit-indicator"></span>

                                                <span>
                                                    <?php echo $escape($unit['unit_code']); ?>
                                                </span>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <p class="text-muted small">
                                            No computer unit records available.
                                        </p>

                                    <?php endif; ?>

                                </div>


                                <div class="preview-footer">
                                    <span>
                                        <i class="bi bi-pc-display"></i>
                                        <?php echo $totalUnits; ?> registered units
                                    </span>

                                    <span class="preview-status">
                                        Database connected
                                    </span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             PROJECT INFORMATION STRIP
        ========================== -->

        <section class="information-strip">

            <div class="container">

                <div class="row g-4">

                    <div class="col-6 col-lg-3">

                        <div class="info-item">
                            <span class="info-number">
                                <?php echo $totalUnits; ?>
                            </span>

                            <span class="info-label">
                                Registered Computer Units
                            </span>
                        </div>

                    </div>


                    <div class="col-6 col-lg-3">

                        <div class="info-item">
                            <span class="info-number">
                                <?php echo $totalCategories; ?>
                            </span>

                            <span class="info-label">
                                Active Issue Categories
                            </span>
                        </div>

                    </div>


                    <div class="col-6 col-lg-3">

                        <div class="info-item">
                            <span class="info-number">03</span>

                            <span class="info-label">
                                System User Roles
                            </span>
                        </div>

                    </div>


                    <div class="col-6 col-lg-3">

                        <div class="info-item">
                            <span class="info-number">B204</span>

                            <span class="info-label">
                                Initial Implementation
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             ABOUT SECTION
        ========================== -->

        <section class="about-section section-padding" id="about">

            <div class="container">

                <div class="row align-items-center g-5">

                    <div class="col-lg-5">

                        <div class="section-heading">

                            <span class="section-eyebrow">
                                ABOUT LABSYNC
                            </span>

                            <h2>
                                A more organized way to manage computer laboratory concerns.
                            </h2>

                        </div>

                    </div>


                    <div class="col-lg-7">

                        <div class="about-content">

                            <p>
                                LabSync is a proposed web-based computer issue reporting
                                and maintenance tracking system designed for the computer
                                laboratories of SLSU Lucena Campus.
                            </p>

                            <p>
                                It provides a centralized platform where students and
                                staff can report computer-related problems while
                                authorized IT personnel can inspect reported issues,
                                document maintenance activities, and monitor their progress.
                            </p>

                            <p>
                                Through organized digital records, LabSync aims to make
                                computer issue reporting and maintenance documentation
                                more accessible, structured, and manageable.
                            </p>

                            <a href="#services" class="text-link">
                                Discover the features
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             SERVICES / FEATURES
        ========================== -->

        <section class="services-section section-padding" id="services">

            <div class="container">

                <div class="section-heading text-center">

                    <span class="section-eyebrow">
                        WHAT LABSYNC OFFERS
                    </span>

                    <h2>
                        Everything organized in one platform.
                    </h2>

                    <p>
                        A centralized approach to computer issue reporting
                        and maintenance tracking.
                    </p>

                </div>


                <div class="row g-4 mt-4">

                    <!-- Feature 1 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="feature-card">

                            <div class="feature-icon">
                                <i class="bi bi-clipboard-plus"></i>
                            </div>

                            <h4>Computer Issue Reporting</h4>

                            <p>
                                Submit computer-related concerns through an organized
                                digital reporting form.
                            </p>

                        </div>

                    </div>


                    <!-- Feature 2 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="feature-card">

                            <div class="feature-icon">
                                <i class="bi bi-activity"></i>
                            </div>

                            <h4>Issue Status Tracking</h4>

                            <p>
                                Monitor the progress of reported issues from submission
                                to resolution.
                            </p>

                        </div>

                    </div>


                    <!-- Feature 3 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="feature-card">

                            <div class="feature-icon">
                                <i class="bi bi-tools"></i>
                            </div>

                            <h4>Maintenance Records</h4>

                            <p>
                                Document inspection findings, maintenance actions,
                                and repair results.
                            </p>

                        </div>

                    </div>


                    <!-- Feature 4 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="feature-card">

                            <div class="feature-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>

                            <h4>Computer Issue History</h4>

                            <p>
                                Keep a record of reported concerns and their
                                corresponding status updates.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             ISSUE CATEGORIES
        ========================== -->

        <section class="categories-section section-padding">

            <div class="container">

                <div class="row align-items-center g-5">

                    <div class="col-lg-5">

                        <div class="section-heading">

                            <span class="section-eyebrow">
                                ISSUE CLASSIFICATION
                            </span>

                            <h2>
                                Different concerns. One organized reporting system.
                            </h2>

                            <p>
                                Reports are classified using the active issue
                                categories registered in the system.
                            </p>

                        </div>

                    </div>


                    <div class="col-lg-7">

                        <div class="category-list">

                            <?php if (!empty($categories)): ?>

                                <?php foreach ($categories as $category): ?>

                                    <div class="category-item">

                                        <div class="category-icon">
                                            <i class="bi bi-folder2-open"></i>
                                        </div>

                                        <div class="category-details">

                                            <h5>
                                                <?php echo $escape($category['category_name']); ?>
                                            </h5>

                                            <p>
                                                <?php
                                                echo $escape(
                                                    $category['description'] ?: 'Computer-related concern classification.'
                                                );
                                                ?>
                                            </p>

                                        </div>

                                        <i class="bi bi-arrow-up-right category-arrow"></i>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <p class="text-muted">
                                    No active issue categories are currently available.
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             HOW IT WORKS
        ========================== -->

        <section class="process-section section-padding" id="how-it-works">

            <div class="container">

                <div class="section-heading text-center">

                    <span class="section-eyebrow">
                        THE PROCESS
                    </span>

                    <h2>
                        From reporting to resolution.
                    </h2>

                    <p>
                        A structured process for managing computer laboratory concerns.
                    </p>

                </div>


                <div class="row g-4 mt-4">

                    <!-- Step 1 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="process-card">

                            <span class="process-number">01</span>

                            <div class="process-icon">
                                <i class="bi bi-send"></i>
                            </div>

                            <h4>Submit a Report</h4>

                            <p>
                                Students and staff submit a report describing
                                the computer issue.
                            </p>

                        </div>

                    </div>


                    <!-- Step 2 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="process-card">

                            <span class="process-number">02</span>

                            <div class="process-icon">
                                <i class="bi bi-search"></i>
                            </div>

                            <h4>Inspect the Issue</h4>

                            <p>
                                Authorized IT personnel review the report
                                and inspect the affected unit.
                            </p>

                        </div>

                    </div>


                    <!-- Step 3 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="process-card">

                            <span class="process-number">03</span>

                            <div class="process-icon">
                                <i class="bi bi-wrench-adjustable"></i>
                            </div>

                            <h4>Document Maintenance</h4>

                            <p>
                                Inspection findings and maintenance actions
                                are recorded in the system.
                            </p>

                        </div>

                    </div>


                    <!-- Step 4 -->

                    <div class="col-md-6 col-lg-3">

                        <div class="process-card">

                            <span class="process-number">04</span>

                            <div class="process-icon">
                                <i class="bi bi-check2-circle"></i>
                            </div>

                            <h4>Track Progress</h4>

                            <p>
                                Users can monitor the report status and
                                review its recorded updates.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             PROJECT COVERAGE
        ========================== -->

        <section class="coverage-section section-padding" id="coverage">

            <div class="container">

                <div class="row align-items-center g-5">

                    <div class="col-lg-5">

                        <div class="section-heading">

                            <span class="section-eyebrow">
                                PROJECT COVERAGE
                            </span>

                            <h2>
                                Designed for organized computer laboratory management.
                            </h2>

                            <p>
                                The initial implementation covers the ten registered
                                computer units in the B204 Computer Laboratory.
                                The project is intended for the computer laboratories
                                of SLSU Lucena Campus.
                            </p>

                        </div>

                    </div>


                    <div class="col-lg-7">

                        <div class="coverage-card">

                            <div class="coverage-header">

                                <div>
                                    <span class="coverage-label">
                                        INITIAL IMPLEMENTATION
                                    </span>

                                    <h4>B204 Computer Laboratory</h4>
                                </div>

                                <span class="coverage-count">
                                    <?php echo $totalUnits; ?> Units
                                </span>

                            </div>


                            <div class="computer-unit-grid">

                                <?php if (!empty($computerUnits)): ?>

                                    <?php foreach ($computerUnits as $unit): ?>

                                        <?php
                                        $unitStatus = strtolower($unit['unit_status'] ?? '');

                                        $statusClass = 'unit-active';

                                        if (strpos($unitStatus, 'maintenance') !== false) {
                                            $statusClass = 'unit-maintenance';
                                        } elseif (strpos($unitStatus, 'inactive') !== false) {
                                            $statusClass = 'unit-inactive';
                                        }
                                        ?>

                                        <div class="computer-unit <?php echo $statusClass; ?>">

                                            <i class="bi bi-pc-display"></i>

                                            <span>
                                                <?php echo $escape($unit['unit_code']); ?>
                                            </span>

                                        </div>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <p class="text-muted">
                                        No computer unit records available.
                                    </p>

                                <?php endif; ?>

                            </div>


                            <div class="coverage-legend">

                                <span>
                                    <i class="legend-dot active-dot"></i>
                                    Active
                                </span>

                                <span>
                                    <i class="legend-dot maintenance-dot"></i>
                                    Under Maintenance
                                </span>

                                <span>
                                    <i class="legend-dot inactive-dot"></i>
                                    Inactive
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             CONTACT SECTION
        ========================== -->

        <section class="contact-section section-padding" id="contact">

            <div class="container">

                <div class="contact-card">

                    <div class="row align-items-center g-4">

                        <div class="col-lg-8">

                            <span class="section-eyebrow">
                                SUPPORT AND ASSISTANCE
                            </span>

                            <h2>
                                Have a computer laboratory concern?
                            </h2>

                            <p>
                                Use LabSync to submit a computer issue report
                                and keep track of its progress.
                            </p>

                        </div>


                        <div class="col-lg-4 text-lg-end">

                            <a href="login.php" class="btn btn-primary-custom">
                                Access LabSync
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =========================
             CALL TO ACTION
        ========================== -->

        <section class="cta-section">

            <div class="container">

                <div class="cta-content text-center">

                    <span class="section-eyebrow">
                        GET STARTED WITH LABSYNC
                    </span>

                    <h2>
                        A more organized way to report and track computer issues.
                    </h2>

                    <p>
                        Create an account to access the LabSync reporting platform.
                    </p>

                    <a href="insert.php" class="btn btn-light-custom">
                        Create Your Account
                        <i class="bi bi-arrow-up-right"></i>
                    </a>

                </div>

            </div>

        </section>

    </main>


    <!-- =========================
         FOOTER
    ========================== -->

    <footer class="site-footer">

        <div class="container">

            <div class="row g-4">

                <div class="col-lg-5">

                    <a href="index.php" class="footer-brand">
                        <span class="brand-icon">
                            <i class="bi bi-pc-display-horizontal"></i>
                        </span>

                        <span class="brand-name">
                            Lab<span>Sync</span>
                        </span>
                    </a>

                    <p class="footer-description">
                        A proposed web-based computer issue reporting and
                        maintenance tracking system for the computer laboratories
                        of SLSU Lucena Campus.
                    </p>

                </div>


                <div class="col-6 col-lg-3">

                    <h5>Quick Links</h5>

                    <ul class="footer-links">

                        <li>
                            <a href="#home">Home</a>
                        </li>

                        <li>
                            <a href="#about">About</a>
                        </li>

                        <li>
                            <a href="#services">Services</a>
                        </li>

                        <li>
                            <a href="#how-it-works">How It Works</a>
                        </li>

                        <li>
                            <a href="#coverage">Project Coverage</a>
                        </li>

                    </ul>

                </div>


                <div class="col-6 col-lg-4">

                    <h5>Account</h5>

                    <ul class="footer-links">

                        <li>
                            <a href="login.php">Sign In</a>
                        </li>

                        <li>
                            <a href="insert.php">Create Account</a>
                        </li>

                    </ul>

                </div>

            </div>


            <div class="footer-bottom">

                <p>
                    &copy; <?php echo $currentYear; ?> LabSync.
                    Academic project for demonstration purposes.
                </p>

                <p>
                    SLSU Lucena Campus
                </p>

            </div>

        </div>

    </footer>


    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Main JavaScript -->
    <script src="assets/js/main.js"></script>

</body>

</html>