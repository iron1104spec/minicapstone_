
<?php

require_once __DIR__ . '/../includes/auth.php';

require_role('personnel');

$pdo = db();

// Get maintenance record ID from URL
$maintenanceId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$maintenanceId || $maintenanceId <= 0) {
    http_response_code(400);
    exit('Invalid maintenance record ID.');
}

// Retrieve complete maintenance record
$stmt = $pdo->prepare("
    SELECT
        mr.maintenance_id,
        mr.inspection_date,
        mr.maintenance_date,
        mr.findings,
        mr.action_taken,
        mr.repair_result,
        mr.created_at,

        ir.report_id,
        ir.reference_no,
        ir.description AS issue_description,
        ir.status AS issue_status,

        cu.unit_code,
        cu.location,

        personnel.full_name AS personnel_name,
        personnel.email AS personnel_email

    FROM maintenance_records mr

    INNER JOIN issue_reports ir
        ON mr.report_id = ir.report_id

    INNER JOIN computer_units cu
        ON ir.unit_id = cu.unit_id

    INNER JOIN users personnel
        ON mr.personnel_id = personnel.user_id

    WHERE mr.maintenance_id = :maintenance_id

    LIMIT 1
");

$stmt->execute([
    'maintenance_id' => $maintenanceId
]);

$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    http_response_code(404);
    exit('Maintenance record not found.');
}

// Date formatting helper
function formatDate($value): string
{
    if (empty($value)) {
        return 'Not recorded';
    }

    return date('F d, Y - h:i A', strtotime($value));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Maintenance Details | PCare</title>

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
            font-size: 16px;
            font-weight: 700;
        }

        .detail-label {
            color: #78847F;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 6px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .status-badge {
            display: inline-block;
            background: #E5F0ED;
            color: #245B54;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .text-section {
            background: #F7F8F6;
            border: 1px solid #E8ECE9;
            border-radius: 10px;
            padding: 18px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 14px;
            line-height: 1.8;
        }

        .btn-pcare {
            background: #4E968B;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            padding: 9px 16px;
            font-weight: 600;
        }

        .btn-pcare:hover {
            background: #245B54;
            color: #FFFFFF;
        }

        .btn-outline-pcare {
            border: 1px solid #4E968B;
            color: #245B54;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-pcare:hover {
            background: #E5F0ED;
            color: #245B54;
        }
    </style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container-fluid px-lg-4">

        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-pc-display me-2"></i>
            PCare
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

        <div
            class="collapse navbar-collapse"
            id="mainNavigation"
        >

            <div class="navbar-nav me-auto">

                <a class="nav-link" href="dashboard.php">
                    Dashboard
                </a>

                <a class="nav-link" href="issue-reports.php">
                    Issue Reports
                </a>

                <a
                    class="nav-link active"
                    href="maintenance-records.php"
                >
                    Maintenance Records
                </a>

            </div>

            <a
                href="../logout.php"
                class="btn btn-sm btn-outline-light"
            >
                <i class="bi bi-box-arrow-right me-1"></i>
                Logout
            </a>

        </div>

    </div>

</nav>

<main class="container-fluid px-lg-4 py-4">

    <!-- Page Heading -->

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <p class="text-muted small mb-1">
                IT Personnel / Maintenance Records / Details
            </p>

            <h2 class="page-heading mb-1">
                Maintenance Details
            </h2>

            <p class="text-muted mb-0">
                Complete documentation of the recorded maintenance activity.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="maintenance-records.php"
                class="btn btn-outline-pcare"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Back to Records
            </a>

            <button
                onclick="window.print()"
                class="btn btn-pcare"
                type="button"
            >
                <i class="bi bi-printer me-1"></i>
                Print
            </button>

        </div>

    </div>


    <!-- Maintenance Overview -->

    <section class="content-card p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

            <div>

                <p class="detail-label">
                    Maintenance Record
                </p>

                <h4 class="fw-bold mb-2">
                    MR-<?= str_pad(
                        (string) $record['maintenance_id'],
                        4,
                        '0',
                        STR_PAD_LEFT
                    ) ?>
                </h4>

                <p class="text-muted small mb-0">
                    Created <?= e(formatDate($record['created_at'])) ?>
                </p>

            </div>

            <div class="text-md-end">

                <p class="detail-label">
                    Related Issue Status
                </p>

                <span class="status-badge">
                    <?= e($record['issue_status']) ?>
                </span>

            </div>

        </div>

    </section>


    <!-- Linked Issue Information -->

    <section class="content-card p-4 mb-4">

        <h5 class="section-title mb-4">
            <i class="bi bi-clipboard-data me-2 text-success"></i>
            Related Issue Report
        </h5>

        <div class="row g-4">

            <div class="col-md-4">

                <p class="detail-label">
                    Reference Number
                </p>

                <p class="detail-value text-success">
                    <?= e($record['reference_no']) ?>
                </p>

            </div>

            <div class="col-md-4">

                <p class="detail-label">
                    Computer Unit
                </p>

                <p class="detail-value">
                    <?= e($record['unit_code']) ?>
                </p>

            </div>

            <div class="col-md-4">

                <p class="detail-label">
                    Location
                </p>

                <p class="detail-value">
                    <?= e($record['location'] ?? 'Not specified') ?>
                </p>

            </div>

            <div class="col-12">

                <p class="detail-label">
                    Reported Issue
                </p>

                <div class="text-section">
                    <?= e($record['issue_description']) ?>
                </div>

            </div>

        </div>

    </section>


    <!-- Maintenance Schedule -->

    <section class="content-card p-4 mb-4">

        <h5 class="section-title mb-4">
            <i class="bi bi-calendar-check me-2 text-success"></i>
            Maintenance Schedule
        </h5>

        <div class="row g-4">

            <div class="col-md-6">

                <p class="detail-label">
                    Inspection Date
                </p>

                <p class="detail-value">
                    <?= e(formatDate($record['inspection_date'])) ?>
                </p>

            </div>

            <div class="col-md-6">

                <p class="detail-label">
                    Maintenance Date
                </p>

                <p class="detail-value">
                    <?= e(formatDate($record['maintenance_date'])) ?>
                </p>

            </div>

        </div>

    </section>


    <!-- Inspection Findings -->

    <section class="content-card p-4 mb-4">

        <h5 class="section-title mb-3">
            <i class="bi bi-search me-2 text-success"></i>
            Inspection Findings
        </h5>

        <div class="text-section"><?= e($record['findings']) ?></div>

    </section>


    <!-- Actions Taken -->

    <section class="content-card p-4 mb-4">

        <h5 class="section-title mb-3">
            <i class="bi bi-tools me-2 text-success"></i>
            Actions Taken
        </h5>

        <div class="text-section"><?= e($record['action_taken']) ?></div>

    </section>


    <!-- Repair Result -->

    <section class="content-card p-4 mb-4">

        <h5 class="section-title mb-3">
            <i class="bi bi-check-circle me-2 text-success"></i>
            Repair Result
        </h5>

        <div class="text-section"><?= e($record['repair_result']) ?></div>

    </section>


    <!-- Responsible Personnel -->

    <section class="content-card p-4 mb-4">

        <h5 class="section-title mb-4">
            <i class="bi bi-person-badge me-2 text-success"></i>
            Responsible IT Personnel
        </h5>

        <div class="row g-4">

            <div class="col-md-6">

                <p class="detail-label">
                    Personnel Name
                </p>

                <p class="detail-value">
                    <?= e($record['personnel_name']) ?>
                </p>

            </div>

            <div class="col-md-6">

                <p class="detail-label">
                    Email Address
                </p>

                <p class="detail-value">
                    <?= e($record['personnel_email'] ?? 'Not available') ?>
                </p>

            </div>

        </div>

    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
