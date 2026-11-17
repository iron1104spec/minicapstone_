<?php
// it-personnel/maintenance-records.php
require_once __DIR__ . '/maintenance-records-logic.php';

$fullName = $_SESSION['full_name'] ?? 'IT Personnel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Records | LabSync</title>

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
        .form-label { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; font-size: 14px; }
        .form-control:focus, .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25); }
        
        /* --- BUTTONS --- */
        .btn-pcare { background-color: #0f3d2e; color: white; border-radius: 8px; font-weight: 600; padding: 10px 20px; border: none; }
        .btn-pcare:hover { background-color: #1a5c47; color: white; }

        /* --- TABLE STYLES --- */
        .table-custom th {
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #64748b; font-weight: 700; background-color: #f8fafc;
            padding: 15px 24px; border-bottom: 1px solid #e2e8f0;
        }
        .table-custom td { padding: 15px 24px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .status-badge { background: #f1f5f9; color: #334155; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1;}
        .btn-view { background: #f1f5f9; color: #0f3d2e; font-size: 12px; font-weight: 600; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-view:hover { background: #e2e8f0; color: #0d2f24; }
    </style>
</head>

<body>

<!-- TOP HEADER (LabSync) -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
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
        
        <!-- HERO BANNER -->
        <section class="welcome-section">
            <div style="position: relative; z-index: 2;">
                <div class="welcome-label">LABORATORY MAINTENANCE</div>
                <h1 class="welcome-title">Maintenance Records</h1>
                <p class="welcome-description">
                    Document inspections, troubleshooting actions, and repair results for reported computer issues.
                </p>
            </div>
        </section>

        <!-- NOTIFICATIONS -->
        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;">
                <i class="bi bi-check-circle me-2"></i> <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 10px;">
                <i class="bi bi-exclamation-circle me-2"></i> <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- CREATE MAINTENANCE RECORD FORM -->
        <section class="content-card">
            <div class="card-heading">
                <h5 class="card-title"><i class="bi bi-clipboard-plus me-2"></i> Create Maintenance Record</h5>
                <span class="card-subtitle">Select an issue report and document the maintenance activity.</span>
            </div>
            <div class="p-4">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="report_id" class="form-label">Issue Report <span class="text-danger">*</span></label>
                            <select name="report_id" id="report_id" class="form-select" required>
                                <option value="">Select an issue report</option>
                                <?php foreach ($availableReports as $report): ?>
                                    <option value="<?= (int) $report['report_id'] ?>">
                                        <?= e($report['reference_no']) ?> — <?= e($report['unit_code']) ?> (<?= e($report['status']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="inspection_date" class="form-label">Inspection Date</label>
                            <input type="datetime-local" name="inspection_date" id="inspection_date" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label for="maintenance_date" class="form-label">Maintenance Date</label>
                            <input type="datetime-local" name="maintenance_date" id="maintenance_date" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label for="findings" class="form-label">Inspection Findings <span class="text-danger">*</span></label>
                            <textarea name="findings" id="findings" class="form-control" rows="3" placeholder="Describe the problem found during inspection..." required></textarea>
                        </div>

                        <div class="col-md-12">
                            <label for="action_taken" class="form-label">Actions Taken <span class="text-danger">*</span></label>
                            <textarea name="action_taken" id="action_taken" class="form-control" rows="3" placeholder="Describe the troubleshooting or repair actions performed..." required></textarea>
                        </div>

                        <div class="col-md-12">
                            <label for="repair_result" class="form-label">Repair Result <span class="text-danger">*</span></label>
                            <textarea name="repair_result" id="repair_result" class="form-control" rows="3" placeholder="Describe the result after maintenance..." required></textarea>
                        </div>

                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="submit" class="btn-pcare">
                                <i class="bi bi-save me-2"></i> Save Maintenance Record
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- MAINTENANCE RECORDS TABLE -->
        <section class="content-card">
            <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title">Saved Maintenance Records</h5>
                    <span class="card-subtitle">Maintenance activities recorded in the database.</span>
                </div>
                <span class="badge bg-secondary" style="font-size: 12px; padding: 6px 12px; border-radius: 20px;"><?= count($maintenanceRecords) ?> RECORD(S)</span>
            </div>

            <div class="table-responsive">
                <?php if (count($maintenanceRecords) > 0): ?>
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Computer Unit</th>
                                <th>Inspection Date</th>
                                <th>Maintenance Date</th>
                                <th>Personnel</th>
                                <th>Issue Status</th>
                                <th>Repair Result</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenanceRecords as $record): ?>
                                <tr>
                                    <td class="fw-bold" style="color: #0f3d2e;"><?= e($record['reference_no']) ?></td>
                                    <td><?= e($record['unit_code']) ?></td>
                                    <td>
                                        <?= $record['inspection_date'] ? e(date('M d, Y h:i A', strtotime($record['inspection_date']))) : '<span class="text-muted">Not set</span>' ?>
                                    </td>
                                    <td>
                                        <?= $record['maintenance_date'] ? e(date('M d, Y h:i A', strtotime($record['maintenance_date']))) : '<span class="text-muted">Not set</span>' ?>
                                    </td>
                                    <td><?= e($record['personnel_name']) ?></td>
                                    <td>
                                        <span class="status-badge"><?= e($record['status']) ?></span>
                                    </td>
                                    <td><?= e($record['repair_result']) ?></td>
                                    <td>
                                        <a href="maintenance-details.php?id=<?= (int) $record['maintenance_id'] ?>" class="btn-view">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard2-x text-muted" style="font-size: 40px;"></i>
                        <h5 class="mt-3">No maintenance records yet</h5>
                        <p class="text-muted mb-0">Once an IT Personnel member saves a maintenance record, it will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>
</div>

<!-- FLOATING BOTTOM DOCK -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="issue-reports.php" class="dock-item">
        <i class="bi bi-clipboard-data"></i><span>Issue Reports</span>
    </a>
    <a href="maintenance-records.php" class="dock-item active">
        <i class="bi bi-tools"></i><span>Maintenance</span>
    </a>
</nav>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>