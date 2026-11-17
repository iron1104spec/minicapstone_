<?php
// it-personnel/issue-reports.php
require_once __DIR__ . '/issue-reports-logic.php';

$fullName = $_SESSION['full_name'] ?? 'IT Personnel';

// Kunin ang stats dynamically
$totalCount = count($reports);
$pendingCount = 0;
$inspectionCount = 0;
$repairCount = 0;
$resolvedCount = 0;
$unresolvedCount = 0;

foreach ($reports as $r) {
    $s = strtolower($r['status']);
    if (strpos($s, 'pending') !== false) $pendingCount++;
    elseif (strpos($s, 'inspection') !== false) $inspectionCount++;
    elseif (strpos($s, 'repair') !== false) $repairCount++;
    elseif (strpos($s, 'resolved') !== false) $resolvedCount++;
    else $unresolvedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Reports | LabSync</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & BACKGROUND PATTERN (Eksaktong kopya sa Admin) --- */
        body {
            background-color: #f4f7f6;
            background-image: radial-gradient(#d1dbd6 1px, transparent 1px);
            background-size: 20px 20px;
            font-family: 'Inter', sans-serif;
        }

        /* --- TOP HEADER (LabSync Branding) --- */
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

        /* --- HERO BANNER (Eksaktong kopya sa Admin) --- */
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

        /* --- STAT CARDS (Parehas na parehas sa Admin) --- */
        .stat-card {
            background: #ffffff; 
            border-radius: 10px; 
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); 
            border: 1px solid #e2e8f0;
            border-top: 4px solid #1a4a3a; /* Green line sa taas */
            height: 100%; 
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-top { margin-bottom: 15px; }
        
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            background: #e6f4ea; /* Light green */
            color: #0f3d2e; /* Dark green */
        }
        
        .stat-number { font-size: 28px; font-weight: 800; color: #0d2f24; line-height: 1; margin-bottom: 5px; }
        .stat-label { font-size: 13px; font-weight: 600; color: #475569; }

        /* --- CONTENT CARDS (Filters at Table) --- */
        .content-card {
            background: #ffffff; 
            border-radius: 10px; 
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            border-top: 4px solid #1a4a3a;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .card-heading { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
        .card-title { font-size: 16px; font-weight: 700; color: #0d2f24; margin: 0 0 4px 0; }
        .card-subtitle { font-size: 13px; color: #64748b; }
        
        /* --- FORM CONTROLS --- */
        .form-label { font-size: 13px; font-weight: 600; color: #475569; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; font-size: 14px; }
        .form-control:focus, .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25); }
        
        .btn-apply { background-color: #0f3d2e; color: white; border-radius: 8px; font-weight: 600; }
        .btn-apply:hover { background-color: #1a5c47; color: white; }
        .btn-reset { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600; }
        .btn-reset:hover { background-color: #e2e8f0; }

        /* --- TABLE STYLES --- */
        .table-custom th {
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #64748b; font-weight: 700; background-color: #f8fafc;
            padding: 15px 24px; border-bottom: 1px solid #e2e8f0;
        }
        .table-custom td { padding: 15px 24px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    </style>
</head>

<body>

<!-- TOP HEADER (LabSync na!) -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span> <!-- PINALITAN NA -->
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span> <!-- PINALITAN NA -->
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px; font-weight: 500;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                IT
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2; color: #1a1a1a;"><?= e($fullName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;">IT Personnel</div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    <main class="dashboard-content">
        
        <!-- HERO BANNER (Gaya sa Admin) -->
        <section class="welcome-section">
            <div style="position: relative; z-index: 2;">
                <div class="welcome-label">ISSUE MONITORING</div>
                <h1 class="welcome-title">Issue Reports</h1>
                <p class="welcome-description">
                    Monitor submitted computer issues and review their maintenance progress. Ensure every problem is tracked and resolved efficiently.
                </p>
            </div>
        </section>

        <?php if ($databaseError): ?>
            <div class="alert alert-danger" style="border-radius: 12px;">
                Unable to retrieve issue reports. Please try again later.
            </div>
        <?php else: ?>

            <!-- STAT CARDS (Gaya sa Admin) -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-clipboard-data"></i></div>
                        </div>
                        <div class="stat-number"><?= $totalCount ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                        </div>
                        <div class="stat-number"><?= $pendingCount ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-search"></i></div>
                        </div>
                        <div class="stat-number"><?= $inspectionCount ?></div>
                        <div class="stat-label">Under Inspection</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-tools"></i></div>
                        </div>
                        <div class="stat-number"><?= $repairCount ?></div>
                        <div class="stat-label">Under Repair</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        </div>
                        <div class="stat-number"><?= $resolvedCount ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
                        </div>
                        <div class="stat-number"><?= $unresolvedCount ?></div>
                        <div class="stat-label">Unresolved</div>
                    </div>
                </div>
            </div>

            <!-- SEARCH AND FILTERS (Gaya sa Admin) -->
            <section class="content-card">
                <div class="card-heading">
                    <h5 class="card-title"><i class="bi bi-funnel me-2"></i> Search and Filters</h5>
                    <span class="card-subtitle">Find reports using their reference number, reporter, computer unit, category, or status.</span>
                </div>
                <div class="p-4">
                    <form method="GET" action="issue-reports.php" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Search Reports</label>
                            <input type="text" name="search" class="form-control" placeholder="Reference number, reporter, or PC unit" value="<?= e($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Filter by Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach ($allowedStatuses as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                        <?= e($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-apply flex-grow-1" style="padding: 10px;">Apply</button>
                            <a href="issue-reports.php" class="btn btn-reset" style="padding: 10px;">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- REPORTS TABLE (Gaya sa Admin) -->
            <section class="content-card">
                <div class="card-heading d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">All Submitted Reports</h5>
                        <span class="card-subtitle">Review the submitted reports and open their details.</span>
                    </div>
                    <span class="badge bg-secondary" style="font-size: 12px; padding: 6px 12px; border-radius: 20px;"><?= count($reports) ?> RESULT(S)</span>
                </div>

                <div class="table-responsive">
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-search text-muted" style="font-size: 40px;"></i>
                            <h5 class="mt-3">No Reports Found</h5>
                            <p class="text-muted mb-0">No issue reports match your search or filter.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Reference No.</th>
                                    <th>Reported By</th>
                                    <th>Computer Unit</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Assigned Personnel</th>
                                    <th>Date Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td class="fw-bold" style="color: #0f3d2e;">
                                            <a href="issue-details.php?id=<?= (int) $report['report_id'] ?>" class="text-success text-decoration-none">
                                                <?= e($report['reference_no']) ?>
                                            </a>
                                        </td>
                                        <td class="fw-semibold"><?= e($report['reporter_name']) ?></td>
                                        <td><?= e($report['unit_code']) ?></td>
                                        <td><?= e($report['category_name']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?= e($statusClasses[$report['status']] ?? 'bg-secondary') ?>" style="padding: 6px 12px;">
                                                <?= e($report['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e($report['assigned_name'] ?? 'Not Assigned') ?></td>
                                        <td><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

        <?php endif; ?>
    </main>
</div>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="issue-reports.php" class="dock-item active">
        <i class="bi bi-clipboard-data"></i><span>Issue Reports</span>
    </a>
    <a href="maintenance-records.php" class="dock-item">
        <i class="bi bi-tools"></i><span>Maintenance</span>
    </a>
</nav>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>