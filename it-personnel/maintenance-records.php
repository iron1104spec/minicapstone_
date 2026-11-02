
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();
$personnelId = (int) $_SESSION['user_id'];

$success = '';
$error = '';

// Process maintenance record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid form request. Please refresh the page and try again.';
    } else {

        $reportId = filter_var(
            $_POST['report_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $inspectionDate = trim($_POST['inspection_date'] ?? '');
        $maintenanceDate = trim($_POST['maintenance_date'] ?? '');
        $findings = trim($_POST['findings'] ?? '');
        $actionTaken = trim($_POST['action_taken'] ?? '');
        $repairResult = trim($_POST['repair_result'] ?? '');

        // Validate date fields
        $validateDate = function ($value) {
            if ($value === '') {
                return null;
            }

            $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value);

            if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
                return false;
            }

            return $date->format('Y-m-d H:i:s');
        };

        $inspectionDateValue = $validateDate($inspectionDate);
        $maintenanceDateValue = $validateDate($maintenanceDate);

        if (!$reportId || $reportId <= 0) {
            $error = 'Please select a valid issue report.';

        } elseif (
            $inspectionDateValue === false ||
            $maintenanceDateValue === false
        ) {
            $error = 'Please enter valid inspection and maintenance dates.';

        } elseif (
            $inspectionDateValue !== null &&
            $maintenanceDateValue !== null &&
            strtotime($maintenanceDateValue) < strtotime($inspectionDateValue)
        ) {
            $error = 'Maintenance date cannot be earlier than the inspection date.';

        } elseif (
            $findings === '' ||
            $actionTaken === '' ||
            $repairResult === ''
        ) {
            $error = 'Please complete the findings, actions taken, and repair result.';

        } else {

            try {

                // Confirm that the selected issue report exists
                $checkReport = $pdo->prepare("
                    SELECT report_id
                    FROM issue_reports
                    WHERE report_id = :report_id
                    LIMIT 1
                ");

                $checkReport->execute([
                    'report_id' => $reportId
                ]);

                if (!$checkReport->fetch()) {

                    $error = 'The selected issue report does not exist.';

                } else {

                    // Save maintenance record
                    $stmt = $pdo->prepare("
                        INSERT INTO maintenance_records (
                            report_id,
                            personnel_id,
                            inspection_date,
                            maintenance_date,
                            findings,
                            action_taken,
                            repair_result
                        ) VALUES (
                            :report_id,
                            :personnel_id,
                            :inspection_date,
                            :maintenance_date,
                            :findings,
                            :action_taken,
                            :repair_result
                        )
                    ");

                    $stmt->execute([
                        'report_id' => $reportId,
                        'personnel_id' => $personnelId,
                        'inspection_date' => $inspectionDateValue,
                        'maintenance_date' => $maintenanceDateValue,
                        'findings' => $findings,
                        'action_taken' => $actionTaken,
                        'repair_result' => $repairResult
                    ]);

                    flash_set(
                        'success',
                        'Maintenance record saved successfully.'
                    );

                    redirect('/minicapstone/it-personnel/maintenance-records.php');
                }

            } catch (PDOException $e) {

                error_log('Maintenance record error: ' . $e->getMessage());

                $error = 'Unable to save the maintenance record. Please check the database and try again.';
            }
        }
    }
}

// Retrieve success notification
$success = flash_get('success') ?? '';

// Retrieve issue reports for dropdown
$reportStmt = $pdo->query("
    SELECT
        ir.report_id,
        ir.reference_no,
        ir.status,
        cu.unit_code
    FROM issue_reports ir
    INNER JOIN computer_units cu
        ON ir.unit_id = cu.unit_id
    ORDER BY ir.created_at DESC
");

$availableReports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve existing maintenance records
$recordsStmt = $pdo->query("
    SELECT
        mr.maintenance_id,
        mr.inspection_date,
        mr.maintenance_date,
        mr.findings,
        mr.action_taken,
        mr.repair_result,
        mr.created_at,
        ir.reference_no,
        ir.status,
        cu.unit_code,
        personnel.full_name AS personnel_name
    FROM maintenance_records mr
    INNER JOIN issue_reports ir
        ON mr.report_id = ir.report_id
    INNER JOIN computer_units cu
        ON ir.unit_id = cu.unit_id
    INNER JOIN users personnel
        ON mr.personnel_id = personnel.user_id
    ORDER BY mr.created_at DESC
");

$maintenanceRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Maintenance Records | PCare</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        body {
            font-family: 'Inter', sans-serif;
            background: #F7F8F6;
            color: #303C39;
        }

        .navbar {
            background: #245B54;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .navbar .nav-link {
            color: rgba(255,255,255,.82);
        }

        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: #FFFFFF;
        }

        .page-heading {
            font-weight: 700;
            letter-spacing: -1px;
        }

        .content-card {
            background: #FFFFFF;
            border: 1px solid #E8ECE9;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(36, 91, 84, 0.04);
        }

        .section-title {
            font-weight: 700;
            font-size: 17px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #46534F;
        }

        .form-control,
        .form-select {
            border-color: #DDE5E1;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4E968B;
            box-shadow: 0 0 0 3px rgba(78, 150, 139, 0.12);
        }

        .btn-pcare {
            background: #4E968B;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
        }

        .btn-pcare:hover {
            background: #245B54;
            color: #FFFFFF;
        }

        .btn-view {
            background: #E5F0ED;
            color: #245B54;
            border: 1px solid #D5E7E1;
            border-radius: 7px;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-view:hover {
            background: #245B54;
            color: #FFFFFF;
        }

        .table thead th {
            background: #F7F8F6;
            color: #66736E;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 14px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px;
            font-size: 13px;
            vertical-align: middle;
        }

        .status-badge {
            display: inline-block;
            background: #E5F0ED;
            color: #245B54;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .empty-state {
            padding: 40px 15px;
            text-align: center;
            color: #77827D;
        }

        .empty-state i {
            font-size: 35px;
            color: #9BBDB5;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container-fluid px-lg-4">

        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-pc-display me-2"></i>PCare
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavigation"
            aria-controls="mainNavigation"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavigation">

            <div class="navbar-nav me-auto">

                <a class="nav-link" href="dashboard.php">
                    Dashboard
                </a>

                <a class="nav-link" href="issue-reports.php">
                    Issue Reports
                </a>

                <a class="nav-link active" href="maintenance-records.php">
                    Maintenance Records
                </a>

            </div>

            <a href="../logout.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i>
                Logout
            </a>

        </div>

    </div>

</nav>

<main class="container-fluid px-lg-4 py-4">

    <!-- Page Heading -->

    <div class="mb-4">

        <p class="text-muted small mb-1">
            IT Personnel / Maintenance
        </p>

        <h2 class="page-heading mb-2">
            Maintenance Records
        </h2>

        <p class="text-muted mb-0">
            Document inspections, troubleshooting actions, and repair results
            for reported computer issues.
        </p>

    </div>

    <!-- Success Notification -->

    <?php if ($success): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle me-2"></i>

            <?= e($success) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>

    <!-- Error Notification -->

    <?php if ($error): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-circle me-2"></i>

            <?= e($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- Create Maintenance Record -->

    <section class="content-card p-4 mb-4">

        <div class="mb-4">

            <h5 class="section-title mb-1">

                <i class="bi bi-clipboard-plus me-2 text-success"></i>

                Create Maintenance Record

            </h5>

            <p class="text-muted small mb-0">
                Select an issue report and document the maintenance activity.
            </p>

        </div>

        <form method="POST" action="">

            <?= csrf_field() ?>

            <div class="row g-3">

                <div class="col-md-6">

                    <label for="report_id" class="form-label">
                        Issue Report <span class="text-danger">*</span>
                    </label>

                    <select
                        name="report_id"
                        id="report_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select an issue report
                        </option>

                        <?php foreach ($availableReports as $report): ?>

                            <option value="<?= (int) $report['report_id'] ?>">

                                <?= e($report['reference_no']) ?>

                                — <?= e($report['unit_code']) ?>

                                (<?= e($report['status']) ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-3">

                    <label for="inspection_date" class="form-label">
                        Inspection Date
                    </label>

                    <input
                        type="datetime-local"
                        name="inspection_date"
                        id="inspection_date"
                        class="form-control"
                    >

                </div>


                <div class="col-md-3">

                    <label for="maintenance_date" class="form-label">
                        Maintenance Date
                    </label>

                    <input
                        type="datetime-local"
                        name="maintenance_date"
                        id="maintenance_date"
                        class="form-control"
                    >

                </div>


                <div class="col-md-12">

                    <label for="findings" class="form-label">
                        Inspection Findings <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="findings"
                        id="findings"
                        class="form-control"
                        rows="3"
                        placeholder="Describe the problem found during inspection..."
                        required
                    ></textarea>

                </div>


                <div class="col-md-12">

                    <label for="action_taken" class="form-label">
                        Actions Taken <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="action_taken"
                        id="action_taken"
                        class="form-control"
                        rows="3"
                        placeholder="Describe the troubleshooting or repair actions performed..."
                        required
                    ></textarea>

                </div>


                <div class="col-md-12">

                    <label for="repair_result" class="form-label">
                        Repair Result <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="repair_result"
                        id="repair_result"
                        class="form-control"
                        rows="3"
                        placeholder="Describe the result after maintenance..."
                        required
                    ></textarea>

                </div>


                <div class="col-12 d-flex justify-content-end">

                    <button type="submit" class="btn btn-pcare">

                        <i class="bi bi-save me-2"></i>

                        Save Maintenance Record

                    </button>

                </div>

            </div>

        </form>

    </section>


    <!-- Maintenance Records Table -->

    <section class="content-card p-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

            <div>

                <h5 class="section-title mb-1">
                    Saved Maintenance Records
                </h5>

                <p class="text-muted small mb-0">
                    Maintenance activities recorded in the database.
                </p>

            </div>

            <span class="badge rounded-pill text-bg-light border">
                <?= count($maintenanceRecords) ?> Records
            </span>

        </div>


        <?php if (count($maintenanceRecords) > 0): ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

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

                                <td class="fw-semibold text-success">

                                    <?= e($record['reference_no']) ?>

                                </td>


                                <td>

                                    <?= e($record['unit_code']) ?>

                                </td>


                                <td>

                                    <?= $record['inspection_date']
                                        ? e(date('M d, Y h:i A', strtotime($record['inspection_date'])))
                                        : '<span class="text-muted">Not set</span>' ?>

                                </td>


                                <td>

                                    <?= $record['maintenance_date']
                                        ? e(date('M d, Y h:i A', strtotime($record['maintenance_date'])))
                                        : '<span class="text-muted">Not set</span>' ?>

                                </td>


                                <td>

                                    <?= e($record['personnel_name']) ?>

                                </td>


                                <td>

                                    <span class="status-badge">

                                        <?= e($record['status']) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= e($record['repair_result']) ?>

                                </td>


                                <!-- View Details Button -->

                                <td>

                                    <a
                                        href="maintenance-details.php?id=<?= (int) $record['maintenance_id'] ?>"
                                        class="btn-view"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        View Details

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


        <?php else: ?>

            <div class="empty-state">

                <i class="bi bi-clipboard2-x d-block mb-3"></i>

                <h6 class="fw-semibold text-dark">
                    No maintenance records yet
                </h6>

                <p class="small mb-0">
                    Once an IT Personnel member saves a maintenance record,
                    it will appear here.
                </p>

            </div>

        <?php endif; ?>

    </section>

</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
