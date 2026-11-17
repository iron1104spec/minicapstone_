<?php
// student/my-reports.php
require_once __DIR__ . '/my-reports-logic.php';

// Fallback kung wala sa logic file
$fullName = $_SESSION['full_name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports | LabSync</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Font -->
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
        
        .btn-back-header {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; font-weight: 600; margin-bottom: 15px;
            position: relative; z-index: 2; transition: color 0.2s;
        }
        .btn-back-header:hover { color: #ffffff; }

        .btn-primary-custom {
            background: #ffffff; color: #0f3d2e; font-weight: 700; font-size: 13px;
            padding: 12px 20px; border-radius: 10px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
            border: none; position: relative; z-index: 2;
        }
        .btn-primary-custom:hover { background: #f0fdf4; transform: translateY(-2px); color: #0f3d2e; }

        /* --- STAT CARD (Summary) --- */
        .stat-card-large {
            background: #ffffff; border-radius: 10px; padding: 25px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;
            border-top: 4px solid #1a4a3a; display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem;
        }
        .stat-title { font-size: 14px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;}
        .stat-number { font-size: 36px; font-weight: 800; color: #0d2f24; line-height: 1; margin-top: 5px; }
        .stat-icon-large { 
            width: 60px; height: 60px; border-radius: 12px; background: #e6f4ea; color: #0f3d2e; 
            display: flex; align-items: center; justify-content: center; font-size: 28px;
        }

        /* --- CONTENT CARDS --- */
        .content-card {
            background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-top: 4px solid #1a4a3a;
            overflow: hidden; margin-bottom: 2rem;
        }
        .card-heading { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
        .card-title { font-size: 18px; font-weight: 700; color: #0d2f24; margin: 0 0 4px 0; }
        .card-subtitle { font-size: 13px; color: #64748b; }
        
        /* --- FORM CONTROLS (Search) --- */
        .search-box-custom { position: relative; width: 100%; max-width: 300px; }
        .search-box-custom i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .search-box-custom input { padding-left: 40px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; }
        .search-box-custom input:focus { border-color: #10b981; box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25); }

        /* --- TABLE STYLES --- */
        .table-custom th {
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #64748b; font-weight: 700; background-color: #f8fafc;
            padding: 15px 24px; border-bottom: 1px solid #e2e8f0;
        }
        .table-custom td { padding: 15px 24px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .description-cell {
            max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* --- EMPTY STATE --- */
        .empty-state-custom { padding: 50px 20px; text-align: center; }
        .empty-state-custom i { font-size: 50px; color: #cbd5e1; margin-bottom: 15px; display: block; }
        .empty-state-custom h3 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
        .empty-state-custom p { font-size: 14px; color: #64748b; margin-bottom: 20px; }
        
        .btn-dark-green {
            background-color: #0f3d2e; color: white; border-radius: 8px; font-weight: 600; padding: 10px 20px; border: none; text-decoration: none;
        }
        .btn-dark-green:hover { background-color: #1a5c47; color: white; }

        .btn-view {
            background-color: #f1f5f9; color: #0f3d2e; font-size: 12px; font-weight: 600;
            border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s;
        }
        .btn-view:hover { background-color: #e2e8f0; color: #0d2f24; }
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
                ST
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2; color: #1a1a1a;"><?= htmlspecialchars($fullName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;">Student</div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<<<<<<< HEAD
<!-- MAIN CONTENT -->
<div class="main-wrapper">
    <main class="dashboard-content container">
        
        <!-- HERO BANNER -->
        <section class="welcome-section d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div style="position: relative; z-index: 2;">
                <a href="dashboard.php" class="btn-back-header">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <div class="welcome-label">REPORT HISTORY</div>
                <h1 class="welcome-title">My Reports</h1>
                <p class="welcome-description">
                    View and monitor the computer issues you have submitted.
                </p>
            </div>
            <div style="position: relative; z-index: 2;">
                <a href="report-issue.php" class="btn-primary-custom">
                    <i class="bi bi-plus-lg"></i> Report an Issue
                </a>
            </div>
        </section>

        <?php if ($databaseError): ?>
            <div class="alert alert-danger mb-4" style="border-radius: 10px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Unable to retrieve your reports. Please try again later.
            </div>
        <?php else: ?>

            <!-- SUMMARY STAT CARD -->
            <div class="stat-card-large">
                <div>
                    <div class="stat-title">Total Submitted Reports</div>
                    <div class="stat-number"><?= count($reports) ?></div>
                </div>
                <div class="stat-icon-large">
=======
    <main class="dashboard-content">
        <!-- PAGE HEADER -->
        <div class="page-heading">
            <a href="dashboard.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1>My Reports</h1>
                    <p>View and monitor the computer issues you have submitted.</p>
                </div>
                <a href="report-issue.php" class="primary-button">
                    <i class="bi bi-plus-lg"></i> Report an Issue
                </a>
            </div>
        </div>

        <!-- DATABASE ERROR -->
        <?php if ($databaseError): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Unable to retrieve your reports. Please try again later.
            </div>
        <?php else: ?>

            <!-- REPORT COUNT -->
            <div class="summary-card">
                <div>
                    <div class="summary-label">Total Submitted Reports</div>
                    <div class="summary-number"><?= count($reports) ?></div>
                </div>
                <div class="summary-icon">
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
                    <i class="bi bi-clipboard-data"></i>
                </div>
            </div>

<<<<<<< HEAD
            <!-- REPORT HISTORY TABLE -->
            <section class="content-card">
                <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="card-title">Report History</h5>
                        <span class="card-subtitle">A record of your submitted computer issue reports.</span>
                    </div>
                    
                    <?php if (!empty($reports)): ?>
                        <div class="search-box-custom">
                            <i class="bi bi-search"></i>
                            <input type="search" id="reportSearch" class="form-control" placeholder="Search your reports..." aria-label="Search your reports">
=======
            <!-- REPORT HISTORY -->
            <section class="reports-card">
                <div class="reports-header">
                    <div>
                        <h2>Report History</h2>
                        <p>A record of your submitted computer issue reports.</p>
                    </div>
                    
                    <?php if (!empty($reports)): ?>
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="search" id="reportSearch" placeholder="Search your reports..." aria-label="Search your reports">
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($reports)): ?>
                    <!-- EMPTY STATE -->
<<<<<<< HEAD
                    <div class="empty-state-custom">
                        <i class="bi bi-clipboard-x"></i>
                        <h3>No Reports Yet</h3>
                        <p>You haven't submitted any computer issue reports.<br>Your submitted reports will appear here.</p>
                        <a href="report-issue.php" class="btn-dark-green">
                            <i class="bi bi-plus-lg me-1"></i> Submit Your First Report
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-custom mb-0" id="reportsTable">
                            <thead>
                                <tr>
                                    <th>Reference No.</th>
                                    <th>Computer Unit</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="reportsTableBody">
                                <?php foreach ($reports as $report): ?>
                                    <tr class="report-row">
                                        <td class="fw-bold" style="color: #0f3d2e;">
                                            <span class="reference-number"><?= e($report['reference_no']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($report['unit_code']) ?></span>
                                        </td>
                                        <td><?= e($report['category_name']) ?></td>
                                        <td class="description-cell" title="<?= e($report['description']) ?>"><?= e($report['description']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?= e(getStatusClass($report['status'])) ?>" style="padding: 6px 12px;">
                                                <?= e($report['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
                                        <td>
                                            <a href="report-details.php?id=<?= (int) $report['report_id'] ?>" class="btn-view">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No Results Message for Search -->
                    <div id="noResults" class="text-center py-5 text-muted d-none">
                        <i class="bi bi-search mb-2" style="font-size: 30px;"></i>
                        <p class="mb-0">No reports match your search.</p>
=======
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-clipboard-x"></i>
                        </div>
                        <h3>No Reports Yet</h3>
                        <p>
                            You haven't submitted any computer issue reports.
                            Your submitted reports will appear here.
                        </p>
                        <a href="report-issue.php" class="primary-button">
                            <i class="bi bi-plus-lg"></i> Submit Your First Report
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Reference No.</th>
                                        <th>Computer Unit</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Date Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="reportsTableBody">
                                    <?php foreach ($reports as $report): ?>
                                        <tr class="report-row">
                                            <td>
                                                <span class="reference-number"><?= e($report['reference_no']) ?></span>
                                            </td>
                                            <td>
                                                <span class="unit-label"><?= e($report['unit_code']) ?></span>
                                            </td>
                                            <td><?= e($report['category_name']) ?></td>
                                            <td class="description-cell"><?= e($report['description']) ?></td>
                                            <td>
                                                <span class="status-badge <?= e(getStatusClass($report['status'])) ?>">
                                                    <?= e($report['status']) ?>
                                                </span>
                                            </td>
                                            <td class="date-label"><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
                                            <td>
                                                <a href="report-details.php?id=<?= (int) $report['report_id'] ?>" class="view-button">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="no-results" id="noResults">
                            <i class="bi bi-search me-1"></i>
                            No reports match your search.
                        </div>
>>>>>>> 4b37017b6e6ecde208543512d7318550dfbf8e60
                    </div>
                <?php endif; ?>
            </section>

        <?php endif; ?>
    </main>
</div>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="report-issue.php" class="dock-item">
        <i class="bi bi-plus-circle"></i><span>Report an Issue</span>
    </a>
    <a href="my-reports.php" class="dock-item active">
        <i class="bi bi-file-earmark-text"></i><span>My Reports</span>
    </a>
    <a href="profile.php" class="dock-item">
        <i class="bi bi-person-circle"></i><span>My Profile</span>
    </a>
</nav>

<!-- BOOTSTRAP JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- External JS (Kung may search logic dito, okay lang. Kung wala, gumagana pa rin yung HTML structure) -->
<script src="../assets/js/student-my-reports.js"></script>

</body>
</html>