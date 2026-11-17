<?php
// it-personnel/issue-details.php

// 1. Kuhanin ang ID galing sa URL. Kung walang ID, babalik sa issue-reports.php
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: issue-reports.php");
    exit();
}

$report_id = (int)$_GET['id'];

// 2. I-require mo yung logic file mo (Siguraduhin na tama ang path nito)
// Kailangan nasa logic file mo yung $pdo connection
require_once __DIR__ . '/../config/db.php'; // I-ADJUST ITO base sa configuration mo

if (!function_exists('e')) {
    function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); }
}

session_start();
$fullName = $_SESSION['full_name'] ?? 'IT Personnel';

// 3. HANDLE STATUS UPDATE (Kapag pinindot yung Update button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['status'];
    $update_report_id = (int)$_POST['report_id'];
    
    // Kung magiging "Resolved", lagyan natin ng timestamp ang resolved_at
    if ($new_status === 'Resolved') {
        $stmt = $pdo->prepare("UPDATE issue_reports SET status = ?, resolved_at = NOW(), updated_at = NOW() WHERE report_id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE issue_reports SET status = ?, resolved_at = NULL, updated_at = NOW() WHERE report_id = ?");
    }
    
    if ($stmt->execute([$new_status, $update_report_id])) {
        // Redirect gamit ang tamang ID
        header("Location: issue-details.php?id=$update_report_id&updated=1");
        exit();
    } else {
        $error = "Failed to update status.";
    }
}

// 4. FETCH REPORT DETAILS (Kunin ang data sa database)
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as reporter_name, u.email as reporter_email, 
           c.category_name, cu.unit_code, cu.location as unit_location,
           p.full_name as assigned_name
    FROM issue_reports r
    LEFT JOIN users u ON r.reporter_id = u.user_id
    LEFT JOIN issue_categories c ON r.category_id = c.category_id
    LEFT JOIN computer_units cu ON r.unit_id = cu.unit_id
    LEFT JOIN users p ON r.assigned_to = p.user_id
    WHERE r.report_id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

// Kung wala talagang mahanap, saka lang mag-eecho ng error
if (!$report) {
    die("<div style='padding: 50px; text-align: center;'><h3>Report ID #$report_id not found in the database.</h3><a href='issue-reports.php'>Go Back</a></div>");
}

// Variables para sa UI
$referenceNo = $report['reference_no'];
$dateSubmitted = date('M d, Y h:i A', strtotime($report['created_at']));
$unitCode = $report['unit_code'];
$categoryName = $report['category_name'];
$description = $report['description'];
$reporterName = $report['reporter_name'];
$reporterEmail = $report['reporter_email'];
$assignedName = $report['assigned_name'] ?? 'Not Assigned';
$location = $report['unit_location'] ?? 'B204 Computer Laboratory';
$currentStatus = $report['status'];

// Badge color logic
$statusBadgeClass = 'bg-warning text-dark'; // Default
if ($currentStatus == 'Resolved') $statusBadgeClass = 'bg-success';
if ($currentStatus == 'Under Repair') $statusBadgeClass = 'bg-primary';
if ($currentStatus == 'Under Inspection') $statusBadgeClass = 'bg-info text-dark';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Details | LabSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ... (I-paste mo dito yung CSS styles mo na galing sa previous code, wag mo nang baguhin) ... */
        body { background-color: #f4f7f6; background-image: radial-gradient(#d1dbd6 1px, transparent 1px); background-size: 20px 20px; font-family: 'Inter', sans-serif; }
        .custom-topbar { position: fixed; top: 0; left: 0; width: 100%; height: 70px; background: #ffffff; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 9999; }
        .brand-area { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-icon { font-size: 24px; color: #0d2f24; }
        .brand-name { display: block; font-weight: 800; font-size: 18px; color: #0d2f24; line-height: 1; }
        .brand-caption { display: block; font-size: 9px; letter-spacing: 1px; color: #666; font-weight: 600; }
        .floating-dock { position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); display: flex; flex-direction: row; gap: 8px; background-color: #0d2f24; padding: 12px 24px; border-radius: 50px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); z-index: 9999; max-width: 95%; overflow-x: auto; }
        .floating-dock::-webkit-scrollbar { display: none; }
        .dock-item { display: flex; flex-direction: column; align-items: center; justify-content: center; color: #a0b8b0; text-decoration: none; padding: 10px 20px; border-radius: 16px; transition: all 0.3s ease; white-space: nowrap; }
        .dock-item i { font-size: 20px; margin-bottom: 4px; }
        .dock-item span { font-size: 11px; font-weight: 500; }
        .dock-item:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; transform: translateY(-4px); }
        .dock-item.active { background-color: #1a4a3a; color: #ffffff; }
        .main-wrapper { padding-top: 90px; padding-bottom: 180px; width: 100%; }
        .dashboard-content { padding: 20px 30px; }
        .welcome-section { background: linear-gradient(135deg, #0f3d2e 0%, #1a5c47 100%); border-radius: 20px; padding: 40px; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(13, 47, 36, 0.15); margin-bottom: 2rem; }
        .welcome-section::before { content: ''; position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; border-radius: 50%; background: rgba(255, 255, 255, 0.05); }
        .welcome-label { display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; background: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 20px; margin-bottom: 15px; text-transform: uppercase; }
        .welcome-title { font-size: 28px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px; }
        .welcome-description { font-size: 14px; color: rgba(255, 255, 255, 0.8); max-width: 600px; line-height: 1.6; margin: 0; }
        .content-card { background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-top: 4px solid #1a4a3a; overflow: hidden; padding: 24px; margin-bottom: 24px; }
        .detail-label { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 16px; }
        .btn-back { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; font-size: 13px; font-weight: 600; border-radius: 8px; padding: 8px 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 20px; transition: all 0.2s; }
        .btn-back:hover { background: #f1f5f9; color: #1e293b; }
        .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; font-size: 14px; }
        .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25); }
        .btn-save { background-color: #0f3d2e; color: white; border-radius: 8px; font-weight: 600; padding: 10px 20px; border: none; }
        .btn-save:hover { background-color: #1a5c47; color: white; }
    </style>
</head>
<body>

<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">IT</div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2; color: #1a1a1a;"><?= e($fullName) ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;">IT Personnel</div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</header>

<div class="main-wrapper">
    <main class="dashboard-content">
        
        <a href="issue-reports.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Issue Reports</a>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;">
                <i class="bi bi-check-circle me-2"></i> Status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="welcome-section d-flex justify-content-between align-items-center">
            <div style="position: relative; z-index: 2;">
                <div class="welcome-label">ISSUE DETAILS</div>
                <h1 class="welcome-title"><?= e($referenceNo) ?></h1>
                <p class="welcome-description">Review the submitted computer issue report and manage its status.</p>
            </div>
            <div style="position: relative; z-index: 2;">
                <span class="badge <?= e($statusBadgeClass) ?>" style="font-size: 15px; padding: 12px 24px; border-radius: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <?= e($currentStatus) ?>
                </span>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="content-card">
                    <h5 class="fw-bold mb-4" style="color: #0f172a;">Report Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <span class="detail-label">Reference Number</span>
                            <div class="detail-value"><?= e($referenceNo) ?></div> 
                        </div>
                        <div class="col-md-6">
                            <span class="detail-label">Date Submitted</span>
                            <div class="detail-value"><?= e($dateSubmitted) ?></div>
                        </div>
                        <div class="col-md-6">
                            <span class="detail-label">Computer Unit</span>
                            <div class="detail-value"><?= e($unitCode) ?></div>
                        </div>
                        <div class="col-md-6">
                            <span class="detail-label">Issue Category</span>
                            <div class="detail-value"><?= e($categoryName) ?></div>
                        </div>
                    </div>
                    <hr class="text-muted my-2">
                    <span class="detail-label mt-3">Issue Description</span>
                    <div class="p-3 bg-light rounded" style="font-size: 14px; color: #334155; border: 1px solid #e2e8f0;">
                        <?= e($description) ?>
                    </div>
                </div>

                <div class="content-card">
                    <h5 class="fw-bold mb-2" style="color: #0f172a;">Manage Issue Status</h5>
                    <p class="text-muted small mb-4">Update the progress of this reported computer issue.</p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <!-- ITO YUNG FIX: Dapat laging naka-set yung report_id -->
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report_id) ?>"> 
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" style="font-size: 13px;">New Status</label>
                                <select name="status" class="form-select">
                                    <option value="Pending" <?= $currentStatus == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Under Inspection" <?= $currentStatus == 'Under Inspection' ? 'selected' : '' ?>>Under Inspection</option>
                                    <option value="Under Repair" <?= $currentStatus == 'Under Repair' ? 'selected' : '' ?>>Under Repair</option>
                                    <option value="Resolved" <?= $currentStatus == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-save w-100">
                                    <i class="bi bi-check2-circle me-1"></i> Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="content-card">
                    <h5 class="fw-bold mb-4" style="color: #0f172a;">Reporter Information</h5>
                    <span class="detail-label">Full Name</span>
                    <div class="detail-value"><?= e($reporterName) ?></div>
                    <span class="detail-label">Email Address</span>
                    <div class="detail-value mb-0" style="word-break: break-all;"><?= e($reporterEmail) ?></div>
                </div>
                <div class="content-card">
                    <h5 class="fw-bold mb-4" style="color: #0f172a;">Assignment Information</h5>
                    <span class="detail-label">Assigned IT Personnel</span>
                    <div class="detail-value"><span class="badge bg-secondary"><?= e($assignedName) ?></span></div>
                    <hr class="text-muted my-2">
                    <span class="detail-label mt-3">Computer Location</span>
                    <div class="detail-value mb-0"><?= e($location) ?></div>
                </div>
            </div>
        </div>
    </main>
</div>

<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
    <a href="issue-reports.php" class="dock-item active"><i class="bi bi-clipboard-data"></i><span>Issue Reports</span></a>
    <a href="maintenance-records.php" class="dock-item"><i class="bi bi-tools"></i><span>Maintenance</span></a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>