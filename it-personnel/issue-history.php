
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();

// Search and filter values
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$allowedStatuses = [
    'Pending',
    'Under Inspection',
    'Under Repair',
    'Resolved',
    'Unresolved'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

// Build query
$sql = "
    SELECT
        h.history_id,
        h.old_status,
        h.new_status,
        h.remarks,
        h.changed_at,

        r.report_id,
        r.reference_no,
        u.unit_code,

        changer.full_name AS changed_by_name

    FROM issue_status_history h

    INNER JOIN issue_reports r
        ON h.report_id = r.report_id

    INNER JOIN computer_units u
        ON r.unit_id = u.unit_id

    LEFT JOIN users changer
        ON h.changed_by = changer.user_id

    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            r.reference_no LIKE :search
            OR u.unit_code LIKE :search
            OR changer.full_name LIKE :search
        )
    ";

    $params['search'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
    $sql .= " AND h.new_status = :status";
    $params['status'] = $statusFilter;
}

$sql .= " ORDER BY h.changed_at DESC, h.history_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total history records
$totalHistory = count($histories);

// Status badge styling
function historyStatusClass(string $status): string
{
    switch ($status) {
        case 'Pending':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Under Inspection':
            return 'bg-info-subtle text-info-emphasis';

        case 'Under Repair':
            return 'bg-primary-subtle text-primary';

        case 'Resolved':
            return 'bg-success-subtle text-success';

        case 'Unresolved':
            return 'bg-danger-subtle text-danger';

        default:
            return 'bg-secondary-subtle text-secondary';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue History | PCare</title>

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

        .history-card {
            background: #FFFFFF;
            border: 1px solid #E7ECE9;
            border-radius: 14px;
        }

        .history-count {
            background: #E5F0ED;
            color: #245B54;
            padding: 20px;
            border-radius: 14px;
        }

        .table th {
            color: #64736E;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            white-space: nowrap;
        }

        .table td {
            padding: 16px;
            vertical-align: middle;
        }

        .reference-link {
            color: #245B54;
            font-weight: 700;
            text-decoration: none;
        }

        .reference-link:hover {
            text-decoration: underline;
        }

        .filter-input {
            border: 1px solid #DEE5E1;
            border-radius: 9px;
            padding: 10px 13px;
        }

        .filter-input:focus {
            border-color: #4E968B;
            box-shadow: 0 0 0 0.2rem rgba(78, 150, 139, 0.12);
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <p class="text-secondary small mb-1">
                IT PERSONNEL / ISSUE HISTORY
            </p>

            <h2 class="page-title mb-1">
                Issue History
            </h2>

            <p class="text-secondary mb-0">
                Review the recorded status changes of reported computer issues.
            </p>

        </div>

        <a href="dashboard.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Dashboard
        </a>

    </div>

    <!-- History Summary -->
    <div class="row mb-4">

        <div class="col-12 col-md-4">

            <div class="history-count">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <p class="small mb-1">
                            History Records
                        </p>

                        <h3 class="fw-bold mb-0">
                            <?= $totalHistory ?>
                        </h3>

                    </div>

                    <i class="bi bi-clock-history fs-1"></i>

                </div>

            </div>

        </div>

    </div>

    <!-- History Table -->
    <div class="history-card">

        <!-- Search and Filters -->
        <div class="p-3 p-md-4 border-bottom">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

                <div>

                    <h5 class="fw-bold mb-1">
                        Status Change Records
                    </h5>

                    <p class="text-secondary small mb-0">
                        Complete record of issue status updates.
                    </p>

                </div>

            </div>

            <form method="GET" class="row g-2">

                <div class="col-12 col-md-6">

                    <input
                        type="text"
                        name="search"
                        class="form-control filter-input"
                        placeholder="Search reference number, unit, or personnel..."
                        value="<?= e($search) ?>"
                    >

                </div>

                <div class="col-12 col-md-4">

                    <select
                        name="status"
                        class="form-select filter-input"
                    >

                        <option value="">All Statuses</option>

                        <?php foreach ($allowedStatuses as $status): ?>

                            <option
                                value="<?= e($status) ?>"
                                <?= $statusFilter === $status ? 'selected' : '' ?>
                            >
                                <?= e($status) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-6 col-md-1">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                        title="Search"
                    >
                        <i class="bi bi-search"></i>
                    </button>

                </div>

                <div class="col-6 col-md-1">

                    <a
                        href="issue-history.php"
                        class="btn btn-outline-secondary w-100"
                        title="Reset filters"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>

                </div>

            </form>

        </div>

        <!-- Records Table -->
        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">

                    <tr>

                        <th>Reference No.</th>
                        <th>Computer Unit</th>
                        <th>Previous Status</th>
                        <th>New Status</th>
                        <th>Changed By</th>
                        <th>Date and Time</th>
                        <th>Remarks</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($histories)): ?>

                        <tr>

                            <td colspan="7" class="text-center py-5">

                                <i class="bi bi-clock-history fs-1 text-secondary"></i>

                                <p class="fw-semibold mt-2 mb-1">
                                    No history records found.
                                </p>

                                <small class="text-secondary">
                                    Status changes will appear here once recorded.
                                </small>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($histories as $history): ?>

                            <tr>

                                <td>

                                    <a
                                        href="issue-details.php?id=<?= (int) $history['report_id'] ?>"
                                        class="reference-link"
                                        title="View issue details"
                                    >
                                        <?= e($history['reference_no']) ?>
                                    </a>

                                </td>

                                <td class="fw-semibold">
                                    <?= e($history['unit_code']) ?>
                                </td>

                                <td>

                                    <?php if ($history['old_status']): ?>

                                        <span class="badge rounded-pill <?= historyStatusClass($history['old_status']) ?>">
                                            <?= e($history['old_status']) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-secondary small">
                                            Initial Report
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span class="badge rounded-pill <?= historyStatusClass($history['new_status']) ?>">
                                        <?= e($history['new_status']) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($history['changed_by_name'] ?? 'System / Unknown') ?>
                                </td>

                                <td class="text-nowrap">

                                    <?= $history['changed_at']
                                        ? e(date('M d, Y h:i A', strtotime($history['changed_at'])))
                                        : '—'
                                    ?>

                                </td>

                                <td>
                                    <?= !empty($history['remarks']) ? e($history['remarks']) : '—' ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- Table Footer -->
        <div class="p-3 border-top">

            <small class="text-secondary">
                Showing <?= $totalHistory ?> history record(s).
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
