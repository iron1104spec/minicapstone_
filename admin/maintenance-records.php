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

    <!-- BAGONG LAYOUT CSS (Floating Dock) -->
    <style>
        /* --- TOP HEADER --- */
        .custom-topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #ffffff; display: flex; justify-content: space-between;
            align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 1000;
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
            border: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000;
            max-width: 95%; overflow-x: auto;
        }
        .floating-dock::-webkit-scrollbar { display: none; }
        .dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #a0b8b0; text-decoration: none; padding: 10px 16px;
            border-radius: 16px; transition: all 0.3s ease; white-space: nowrap;
        }
        .dock-item i { font-size: 20px; margin-bottom: 4px; }
        .dock-item span { font-size: 11px; font-weight: 500; }
        .dock-item:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; transform: translateY(-4px); }
        .dock-item.active { background-color: #1a4a3a; color: #ffffff; }

        /* --- FIX CONTENT SPACING --- */
        .main-wrapper {
            margin-left: 0 !important;
            padding-top: 70px; 
            padding-bottom: 120px; 
            width: 100%;
        }
    </style>
</head>
<body>

<!-- BAGONG TOP HEADER (Logo & Profile) -->
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
                <?= e(pcare_user_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2;"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;"><?= is_super_admin() ? 'Super Administrator' : 'System Administrator' ?></div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Sign Out">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
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

<!-- FLOATING BOTTOM DOCK (Menu) -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="users.php" class="dock-item">
        <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="computer-units.php" class="dock-item">
        <i class="bi bi-pc-display"></i><span>Computers</span>
    </a>
    <a href="categories.php" class="dock-item">
        <i class="bi bi-grid"></i><span>Categories</span>
    </a>
    <a href="issue-reports.php" class="dock-item">
        <i class="bi bi-clipboard-data"></i><span>Reports</span>
    </a>
    <a href="maintenance-records.php" class="dock-item active">
        <i class="bi bi-tools"></i><span>Maintenance</span>
    </a>
    <a href="analytics.php" class="dock-item">
        <i class="bi bi-bar-chart-line"></i><span>Analytics</span>
    </a>
</nav>

<script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>
<script src="../assets/js/admin-users.js"></script>
</body>
</html>