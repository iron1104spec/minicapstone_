
<?php

require_once __DIR__ . '/csrf.php';

$fullName = $_SESSION['full_name'] ?? 'Student';
$currentPage = basename($_SERVER['PHP_SELF']);

// Keep My Reports highlighted when viewing individual report details.
$isReportsPage = in_array(
    $currentPage,
    ['my-reports.php', 'report-details.php'],
    true
);

?>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">

    <div class="container">

        <!-- PCare BRAND -->
        <a class="navbar-brand fw-bold" href="dashboard.php">
            PCare
        </a>

        <!-- MOBILE MENU BUTTON -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#studentNavbar"
            aria-controls="studentNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- NAVIGATION LINKS -->
        <div class="collapse navbar-collapse" id="studentNavbar">

            <ul class="navbar-nav me-auto ms-lg-4">

                <!-- DASHBOARD -->
                <li class="nav-item">

                    <a
                        class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
                        href="dashboard.php"
                        <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>
                    >
                        Dashboard
                    </a>

                </li>


                <!-- REPORT AN ISSUE -->
                <li class="nav-item">

                    <a
                        class="nav-link <?= $currentPage === 'report-issue.php' ? 'active' : '' ?>"
                        href="report-issue.php"
                        <?= $currentPage === 'report-issue.php' ? 'aria-current="page"' : '' ?>
                    >
                        Report an Issue
                    </a>

                </li>


                <!-- MY REPORTS -->
                <li class="nav-item">

                    <a
                        class="nav-link <?= $isReportsPage ? 'active' : '' ?>"
                        href="my-reports.php"
                        <?= $isReportsPage ? 'aria-current="page"' : '' ?>
                    >
                        My Reports
                    </a>

                </li>


                <!-- MY PROFILE -->
                <li class="nav-item">

                    <a
                        class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>"
                        href="profile.php"
                        <?= $currentPage === 'profile.php' ? 'aria-current="page"' : '' ?>
                    >
                        My Profile
                    </a>

                </li>

            </ul>


            <!-- USER INFORMATION AND LOGOUT -->
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mt-3 mt-lg-0">

                <span class="text-white">
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

    </div>

</nav>


<!-- BOOTSTRAP JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
