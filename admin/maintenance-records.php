<?php
// admin/maintenance-records.php
require_once __DIR__ . '/maintenance-records-logic.php';

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
    <title>Maintenance Records | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">
    
    <!-- PREMIUM CSS -->
    <link rel="stylesheet" href="../assets/css/admin-maintenance-records.css?v=123">
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
            <a href="computer-units.php" class="sidebar-link"><i class="bi bi-pc-display"></i> Computer Units</a>
            <a href="categories.php" class="sidebar-link"><i class="bi bi-tags"></i> Issue Categories</a>
            <a href="issue-reports.php" class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link active"><i class="bi bi-tools"></i> Maintenance Records</a>
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
                    <?= is_super_admin() ? 'Super Administrator' : 'Administrator' ?> <span class="mx-2">/</span> <strong>Maintenance Records</strong>
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
                    <div class="welcome-label">LABORATORY MAINTENANCE</div>
                    <h1 class="welcome-title">Maintenance Records</h1>
                    <p class="welcome-description">
                        Track the history of repairs, inspections, and maintenance tasks performed on computer units. Ensure every issue is documented and resolved efficiently.
                    </p>
                </div>
            </section>

            <!-- TABLE SECTION -->
            <section class="content-card">
                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title">Maintenance History</h5>
                        <span class="card-subtitle">Complete log of all maintenance activities.</span>
                    </div>
                    <span class="result-count"><?= count($records ?? $maintenance_records ?? []) ?> RESULT(S)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Computer Unit</th>
                                <th>Issue/Reference</th>
                                <th>Performed By</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maintenanceList = $records ?? $maintenance_records ?? []; 
                            ?>
                            
                            <?php if (empty($maintenanceList)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="bi bi-tools"></i>
                                        <strong class="d-block text-dark">No maintenance records found</strong>
                                        <span>Completed maintenance tasks will appear here.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($maintenanceList as $record): ?>
                                    <tr>
                                        <td><?= e(date('M d, Y', strtotime($record['created_at'] ?? $record['maintenance_date']))) ?></td>
                                        <td><span class="unit-code"><?= e($record['unit_code']) ?></span></td>
                                        <td><span class="report-reference"><?= e($record['reference_no'] ?? 'N/A') ?></span></td>
                                        <td><div class="reporter-name"><?= e($record['performed_by'] ?? $record['personnel_name'] ?? 'IT Personnel') ?></div></td>
                                        <td>
                                            <?php $statusStr = strtolower(str_replace(' ', '', $record['status'] ?? 'completed')); ?>
                                            <span class="status-badge <?= e($statusStr) ?>">
                                                <?= e($record['status'] ?? 'Completed') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="maintenance-details.php?id=<?= (int) ($record['maintenance_id'] ?? 0) ?>" class="btn-view">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <i class="bi bi-info-circle me-1"></i> Showing <?= count($maintenanceList) ?> maintenance record(s).
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