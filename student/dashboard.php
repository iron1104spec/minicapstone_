<?php
// student/dashboard.php
// I-require mo yung tamang logic file mo dito
// require_once __DIR__ . '/dashboard-logic.php';

// Fallback kung wala sa logic file
$studentName = $_SESSION['full_name'] ?? 'Arrone'; 

// Placeholder variables (Palitan mo ito ng totoong variables galing sa logic file mo)
$totalReports = 3;
$pendingReports = 1;
$ongoingReports = 1;
$resolvedReports = 1;

// Placeholder array para sa recent reports table (Palitan mo ito ng totoong query result mo)
$recentReports = [
    ['reference_no' => 'PC-B204-20261107-7F31071F', 'unit_code' => 'PC-06', 'category_name' => 'Hardware', 'status' => 'Pending', 'created_at' => '2026-11-07 14:30:00'],
    ['reference_no' => 'PC-B204-20261103-9519AA1E', 'unit_code' => 'PC-04', 'category_name' => 'Software', 'status' => 'Pending', 'created_at' => '2026-11-05 12:30:00'],
    ['reference_no' => 'PC-B204-20261104-C9AA47C3', 'unit_code' => 'PC-01', 'category_name' => 'Hardware', 'status' => 'Under Repair', 'created_at' => '2026-11-05 12:30:00'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | LabSync</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & BACKGROUND PATTERN --- */
        body {
            background-color: #f4f7f6;
            background-image: radial-gradient(#d1dbd6 1px, transparent 1px);
            background-size: 20px 20px;
            font-family: 'Inter', sans-serif;
        }

        /* --- TOP HEADER --- */
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

        /* --- HERO BANNER --- */
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
        .report-button {
            background: #ffffff; color: #0f3d2e; font-weight: 700; font-size: 13px;
            padding: 12px 20px; border-radius: 10px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            border: none; position: relative; z-index: 2;
        }
        .report-button:hover { background: #f0fdf4; transform: translateY(-2px); color: #0f3d2e; }

        /* --- STAT CARDS --- */
        .stat-card {
            background: #ffffff; border-radius: 10px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;
            border-top: 4px solid #1a4a3a; height: 100%; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-top { margin-bottom: 15px; }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; background: #e6f4ea; color: #0f3d2e;
        }
        .stat-number { font-size: 28px; font-weight: 800; color: #0d2f24; line-height: 1; margin-bottom: 5px; }
        .stat-label { font-size: 13px; font-weight: 600; color: #475569; }

        /* --- CONTENT CARDS --- */
        .content-card {
            background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-top: 4px solid #1a4a3a;
            overflow: hidden; margin-bottom: 2rem;
        }
        .card-heading { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
        .card-title { font-size: 16px; font-weight: 700; color: #0d2f24; margin: 0 0 4px 0; }
        .card-subtitle { font-size: 13px; color: #64748b; }

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

<!-- TOP HEADER -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">STUDENT PORTAL</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px; font-weight: 500;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                SA <!-- Palitan mo ng initials ng student kung meron -->
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2; color: #1a1a1a;"><?= htmlspecialchars($studentName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;">Student</div>
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
        
        <!-- HERO BANNER -->
        <section class="welcome-section d-flex justify-content-between align-items-center">
            <div style="position: relative; z-index: 2;">
                <div class="welcome-label">STUDENT / STAFF PORTAL</div>
                <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($studentName) ?>!</h1>
                <p class="welcome-description">
                    Here's an overview of your computer issue reports and their current maintenance progress.
                </p>
            </div>
            <a href="report-issue.php" class="report-button">
                <i class="bi bi-plus-lg"></i> Report an Issue
            </a>
        </section>

        <!-- REPORT OVERVIEW HEADER -->
        <div class="mb-3">
            <h5 class="fw-bold mb-1" style="color: #0d2f24;">Report Overview</h5>
            <p class="text-muted small mb-0">A summary of your submitted computer issue reports.</p>
        </div>

        <!-- STAT CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-clipboard-data"></i></div>
                    </div>
                    <div class="stat-number"><?= $totalReports ?></div>
                    <div class="stat-label">Total Reports</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon" style="background: #fef3c7; color: #b45309;"><i class="bi bi-clock-history"></i></div>
                    </div>
                    <div class="stat-number"><?= $pendingReports ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon" style="background: #dbeafe; color: #1d4ed8;"><i class="bi bi-tools"></i></div>
                    </div>
                    <div class="stat-number"><?= $ongoingReports ?></div>
                    <div class="stat-label">Ongoing</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon" style="background: #d1fae5; color: #047857;"><i class="bi bi-check-circle"></i></div>
                    </div>
                    <div class="stat-number"><?= $resolvedReports ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <!-- RECENT REPORTS TABLE -->
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <h5 class="fw-bold mb-1" style="color: #0d2f24;">My Recent Reports</h5>
                <p class="text-muted small mb-0">Your five most recently submitted reports.</p>
            </div>
            <a href="my-reports.php" class="btn btn-sm btn-outline-success fw-semibold" style="border-radius: 8px;">View All <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <section class="content-card">
            <div class="table-responsive">
                <table class="table table-hover table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Computer Unit</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReports as $report): ?>
                            <tr>
                                <td class="fw-bold" style="color: #0f3d2e;"><?= htmlspecialchars($report['reference_no']) ?></td>
                                <td><?= htmlspecialchars($report['unit_code']) ?></td>
                                <td><?= htmlspecialchars($report['category_name']) ?></td>
                                <td>
                                    <?php 
                                    $statusClass = 'bg-warning text-dark';
                                    if (strtolower($report['status']) == 'resolved') $statusClass = 'bg-success';
                                    if (strtolower($report['status']) == 'under repair') $statusClass = 'bg-primary';
                                    ?>
                                    <span class="badge rounded-pill <?= $statusClass ?>" style="padding: 6px 12px;">
                                        <?= htmlspecialchars($report['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item active">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="report-issue.php" class="dock-item">
        <i class="bi bi-plus-circle"></i><span>Report an Issue</span>
    </a>
    <a href="my-reports.php" class="dock-item">
        <i class="bi bi-file-earmark-text"></i><span>My Reports</span>
    </a>
    <a href="profile.php" class="dock-item">
        <i class="bi bi-person-circle"></i><span>My Profile</span>
    </a>
</nav>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>