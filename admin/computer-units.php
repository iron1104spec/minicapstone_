<?php
// admin/computer-units.php
require_once __DIR__ . '/computer-units-logic.php';

// Safe fallback para sa avatar function
if (!function_exists('pcare_user_initials')) {
    function pcare_user_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'U';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Units | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">
    
    <!-- PREMIUM CSS -->
    <link rel="stylesheet" href="../assets/css/admin-computer-units.css?v=123">
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
            <span>
                <span class="brand-name">LabSync</span>
                <span class="brand-subtitle">LABORATORY MANAGEMENT</span>
            </span>
        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="users.php" class="sidebar-link"><i class="bi bi-people"></i> User Management</a>
            <a href="computer-units.php" class="sidebar-link active"><i class="bi bi-pc-display"></i> Computer Units</a>
            <a href="categories.php" class="sidebar-link"><i class="bi bi-tags"></i> Issue Categories</a>
            <a href="issue-reports.php" class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link"><i class="bi bi-tools"></i> Maintenance Records</a>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?= e(pcare_user_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
                </div>
                <div>
                    <div class="admin-name"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                    <div class="admin-role"><?= is_super_admin() ? 'Super Administrator' : 'System Administrator' ?></div>
                </div>
            </div>
            <a href="../logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="mobile-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="breadcrumb-text">
                    <?= is_super_admin() ? 'Super Administrator' : 'Administrator' ?> <span class="mx-2">/</span> <strong>Computer Units</strong>
                </div>
            </div>
            <div class="topbar-right">
                <span><span class="topbar-dot"></span> <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?></span>
                <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?></span>
            </div>
        </header>

        <main class="main-content">
            <!-- HERO BANNER -->
            <section class="welcome-section">
                <div>
                    <div class="welcome-label">LABORATORY ASSET MONITORING</div>
                    <h1 class="welcome-title">Computer Units</h1>
                    <p class="welcome-description">
                        Monitor registered computer units and their current operational status. Keep track of active PCs, units under maintenance, and inactive hardware.
                    </p>
                </div>
            </section>

            <!-- STATISTICS (Eksaktong tugma sa CSS nth-child) -->
            <div class="row g-3 mb-4">
                <?php
                $statCards = [
                    ['Total Units', $stats['total'], 'bi-pc-display'],
                    ['Active Units', $stats['active'], 'bi-check-circle'],
                    ['Under Maintenance', $stats['maintenance'], 'bi-tools'],
                    ['Inactive Units', $stats['inactive'], 'bi-pc-display-horizontal']
                ];
                ?>
                <?php foreach ($statCards as $card): ?>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon"><i class="bi <?= e($card[2]) ?>"></i></div>
                            </div>
                            <div class="stat-value"><?= (int) $card[1] ?></div>
                            <div class="stat-label"><?= e($card[0]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- TABLE SECTION -->
            <section class="content-card">
                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title">Registered Computer Units</h5>
                        <span class="card-subtitle">Complete list of computers assigned to B204 Computer Laboratory.</span>
                    </div>
                    <span class="result-count"><?= count($units) ?> RESULT(S)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Unit Code</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($units)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="bi bi-pc-display"></i>
                                        <strong class="d-block text-dark">No computer units found</strong>
                                        <span>Registered units will appear here.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($units as $unit): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-table">
                                                    <i class="bi bi-pc-display"></i>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?= e($unit['unit_code']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e($unit['location']) ?></td>
                                        <td>
                                            <span class="status-badge <?= e(unitStatusClass($unit['unit_status'])) ?>">
                                                <?= e($unit['unit_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($unit['remarks']) ? e($unit['remarks']) : '<span class="text-muted">—</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <i class="bi bi-info-circle me-1"></i> Showing <?= count($units) ?> registered unit(s).
                </div>
            </section>

            <footer class="page-footer">
                LabSync · <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?> · SLSU Lucena Campus
            </footer>
        </main>
    </div>

    <script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>
    <script src="../assets/js/admin-users.js"></script>
</body>
</html>