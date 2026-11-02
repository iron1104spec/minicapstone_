
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

<style>
    .labsync-navbar {
        background: #245B54;
        font-family: 'Inter', sans-serif;
        padding: 13px 0;
    }

    .labsync-navbar .navbar-brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #FFFFFF;
        font-size: 21px;
        font-weight: 800;
        letter-spacing: -.7px;
    }

    .labsync-navbar .brand-icon {
        width: 35px;
        height: 35px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: rgba(255,255,255,.14);
        color: #FFFFFF;
        font-size: 17px;
    }

    .labsync-navbar .nav-link {
        padding: 10px 13px !important;
        color: rgba(255,255,255,.76);
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px;
        transition: background .2s ease, color .2s ease;
    }

    .labsync-navbar .nav-link:hover,
    .labsync-navbar .nav-link.active {
        background: rgba(255,255,255,.13);
        color: #FFFFFF;
    }

    .labsync-navbar .user-name {
        color: #FFFFFF;
        font-size: 12px;
        font-weight: 600;
    }

    .labsync-navbar .logout-button {
        padding: 8px 13px;
        border: 1px solid rgba(255,255,255,.45);
        border-radius: 8px;
        color: #FFFFFF;
        background: transparent;
        font-size: 11px;
        font-weight: 700;
        transition: background .2s ease;
    }

    .labsync-navbar .logout-button:hover {
        background: rgba(255,255,255,.13);
    }

    .labsync-navbar .navbar-toggler {
        border-color: rgba(255,255,255,.3);
        box-shadow: none;
    }

    @media (max-width: 991px) {
        .labsync-navbar .navbar-collapse {
            padding-top: 15px;
        }

        .labsync-navbar .nav-link {
            margin-bottom: 3px;
        }

        .labsync-navbar .user-section {
            align-items: flex-start !important;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.15);
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark labsync-navbar shadow-sm">

    <div class="container">

        <!-- LABSYNC BRAND -->
        <a class="navbar-brand" href="dashboard.php">

            <span class="brand-icon">
                <i class="bi bi-pc-display-horizontal"></i>
            </span>

            LabSync

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
                        <i class="bi bi-grid me-1"></i>
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
                        <i class="bi bi-plus-circle me-1"></i>
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
                        <i class="bi bi-clipboard-check me-1"></i>
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
                        <i class="bi bi-person-circle me-1"></i>
                        My Profile
                    </a>

                </li>

            </ul>

            <!-- USER INFORMATION AND LOGOUT -->
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mt-3 mt-lg-0 user-section">

                <span class="user-name">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= e($fullName) ?>
                </span>

                <form action="../logout.php" method="POST">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="logout-button"
                    >
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </div>

</nav>

<!-- BOOTSTRAP JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
