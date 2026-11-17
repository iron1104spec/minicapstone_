<?php 
// Kunin yung current filename para ma-highlight kung anong active na page
$currentPage = basename($_SERVER['PHP_SELF']); 
?>

<!-- TOP HEADER -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                <?= isset($adminInitials) ? e($adminInitials) : 'SA' ?>
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2;"><?= isset($adminName) ? e($adminName) : 'Admin' ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;"><?= isset($roleLabel) ? e($roleLabel) : 'Administrator' ?></div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Sign Out">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="users.php" class="dock-item <?= $currentPage == 'users.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="computer-units.php" class="dock-item <?= $currentPage == 'computer-units.php' ? 'active' : '' ?>">
        <i class="bi bi-pc-display"></i><span>Computers</span>
    </a>
    <a href="categories.php" class="dock-item <?= $currentPage == 'categories.php' ? 'active' : '' ?>">
        <i class="bi bi-grid"></i><span>Categories</span>
    </a>
    <a href="issue-reports.php" class="dock-item <?= $currentPage == 'issue-reports.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-data"></i><span>Reports</span>
    </a>
    <a href="maintenance-records.php" class="dock-item <?= $currentPage == 'maintenance-records.php' ? 'active' : '' ?>">
        <i class="bi bi-tools"></i><span>Maintenance</span>
    </a>
    <a href="analytics.php" class="dock-item <?= $currentPage == 'analytics.php' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line"></i><span>Analytics</span>
    </a>
</nav>