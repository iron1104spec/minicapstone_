
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();

// Get all registered computer units
$stmt = $pdo->query("
    SELECT
        unit_id,
        unit_code,
        location,
        unit_status,
        remarks,
        created_at,
        updated_at
    FROM computer_units
    ORDER BY unit_code ASC
");

$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count units by status
$totalUnits = count($units);
$activeUnits = 0;
$maintenanceUnits = 0;
$inactiveUnits = 0;

foreach ($units as $unit) {
    if ($unit['unit_status'] === 'Active') {
        $activeUnits++;
    } elseif ($unit['unit_status'] === 'Under Maintenance') {
        $maintenanceUnits++;
    } elseif ($unit['unit_status'] === 'Inactive') {
        $inactiveUnits++;
    }
}

function unitStatusClass(string $status): string
{
    switch ($status) {
        case 'Active':
            return 'bg-success-subtle text-success';

        case 'Under Maintenance':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Inactive':
            return 'bg-secondary-subtle text-secondary';

        default:
            return 'bg-light text-dark';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Computer Units | PCare</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #F7F8F6;
            color: #303C39;
            font-family: Arial, sans-serif;
        }

        .main-content {
            padding: 35px;
        }

        .page-title {
            color: #245B54;
            font-weight: 700;
        }

        .stat-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 14px;
            padding: 22px;
            height: 100%;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .unit-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 14px;
            padding: 20px;
            height: 100%;
            transition: 0.2s ease;
        }

        .unit-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(36, 91, 84, 0.08);
        }

        .unit-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #E5F0ED;
            color: #245B54;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .section-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 14px;
            overflow: hidden;
        }

        .table th {
            color: #64736E;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            padding: 16px;
            white-space: nowrap;
        }

        .table td {
            padding: 16px;
            vertical-align: middle;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .unit-code {
            color: #245B54;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 14px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid main-content">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

        <div>
            <p class="text-secondary small mb-1">
                IT PERSONNEL / COMPUTER UNITS
            </p>

            <h2 class="page-title mb-1">
                Computer Units
            </h2>

            <p class="text-secondary mb-0">
                Monitor the registered computers in B204 Computer Laboratory.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Dashboard
        </a>

    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Total Units</p>
                        <h3 class="fw-bold mb-0"><?= $totalUnits ?></h3>
                    </div>

                    <div class="stat-icon" style="background:#E5F0ED;color:#245B54;">
                        <i class="bi bi-pc-display"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Active</p>
                        <h3 class="fw-bold mb-0 text-success">
                            <?= $activeUnits ?>
                        </h3>
                    </div>

                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Under Maintenance</p>
                        <h3 class="fw-bold mb-0 text-warning">
                            <?= $maintenanceUnits ?>
                        </h3>
                    </div>

                    <div class="stat-icon bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary small mb-1">Inactive</p>
                        <h3 class="fw-bold mb-0 text-secondary">
                            <?= $inactiveUnits ?>
                        </h3>
                    </div>

                    <div class="stat-icon bg-secondary-subtle text-secondary">
                        <i class="bi bi-pc-display-horizontal"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Computer Unit Cards -->
    <div class="mb-4">

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Unit Overview</h5>
            <p class="text-secondary small mb-0">
                Current condition of registered computer units.
            </p>
        </div>

        <div class="row g-3">

            <?php if (empty($units)): ?>

                <div class="col-12">
                    <div class="alert alert-info">
                        No computer units are currently registered.
                    </div>
                </div>

            <?php else: ?>

                <?php foreach ($units as $unit): ?>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">

                        <div class="unit-card">

                            <div class="d-flex justify-content-between align-items-start mb-3">

                                <div class="unit-icon">
                                    <i class="bi bi-pc-display"></i>
                                </div>

                                <span class="badge rounded-pill <?= unitStatusClass($unit['unit_status']) ?>">
                                    <?= e($unit['unit_status']) ?>
                                </span>

                            </div>

                            <h5 class="unit-code mb-1">
                                <?= e($unit['unit_code']) ?>
                            </h5>

                            <p class="text-secondary small mb-3">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?= e($unit['location']) ?>
                            </p>

                            <div class="border-top pt-3">

                                <p class="small text-secondary mb-1">
                                    Remarks
                                </p>

                                <p class="small mb-0">
                                    <?= !empty($unit['remarks']) ? e($unit['remarks']) : 'No remarks available.' ?>
                                </p>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

    <!-- Computer Units Table -->
    <div class="section-card">

        <div class="p-3 p-md-4 border-bottom">

            <h5 class="fw-bold mb-1">
                Registered Computer Units
            </h5>

            <p class="text-secondary small mb-0">
                Complete list of computers assigned to B204 Computer Laboratory.
            </p>

        </div>

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">
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
                            <td colspan="4" class="text-center text-secondary py-4">
                                No computer unit records found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($units as $unit): ?>

                            <tr>

                                <td class="unit-code">
                                    <?= e($unit['unit_code']) ?>
                                </td>

                                <td>
                                    <?= e($unit['location']) ?>
                                </td>

                                <td>
                                    <span class="badge rounded-pill <?= unitStatusClass($unit['unit_status']) ?>">
                                        <?= e($unit['unit_status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($unit['remarks']) ? e($unit['remarks']) : '—' ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="p-3 border-top">
            <small class="text-secondary">
                Showing <?= $totalUnits ?> registered computer unit(s).
            </small>
        </div>

    </div>

    <div class="text-center mt-4">
        <small class="text-secondary">
            PCare · B204 Computer Laboratory
        </small>
    </div>

</div>

</body>
</html>
