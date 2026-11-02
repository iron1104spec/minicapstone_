<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$fullName = $_SESSION['full_name'] ?? 'IT Personnel';
$pdo = db();

$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$reportId || $reportId <= 0) {
    http_response_code(400);
    exit('Invalid report ID.');
}

// Allowed issue statuses.
$allowedStatuses = [
    'Pending',
    'Under Inspection',
    'Under Repair',
    'Resolved',
    'Unresolved'
];

$errorMessage = '';
$successMessage = '';

// Handle status update.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!csrf_verify($submittedToken)) {

        $errorMessage = 'Invalid security token. Please refresh the page and try again.';

    } else {

        $newStatus = trim($_POST['status'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if (!in_array($newStatus, $allowedStatuses, true)) {

            $errorMessage = 'Please select a valid issue status.';

        } elseif (strlen($remarks) > 1000) {

            $errorMessage = 'Remarks must not exceed 1000 characters.';

        } else {

            try {

                $pdo->beginTransaction();

                // Lock the report while updating.
                $checkStmt = $pdo->prepare("
                    SELECT report_id, status
                    FROM issue_reports
                    WHERE report_id = :report_id
                    FOR UPDATE
                ");

                $checkStmt->execute([
                    'report_id' => $reportId
                ]);

                $currentReport = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$currentReport) {

                    throw new RuntimeException('Issue report not found.');

                }

                $oldStatus = $currentReport['status'];

                if ($oldStatus === $newStatus) {

                    throw new RuntimeException('The selected status is already current.');

                }

                // Update issue report status.
                $updateStmt = $pdo->prepare("
                    UPDATE issue_reports
                    SET
                        status = :status,
                        updated_at = NOW(),
                        resolved_at = CASE
                            WHEN :resolved_status = 'Resolved' THEN NOW()
                            WHEN :old_status = 'Resolved' THEN NULL
                            ELSE resolved_at
                        END
                    WHERE report_id = :report_id
                ");

                $updateStmt->execute([
                    'status' => $newStatus,
                    'resolved_status' => $newStatus,
                    'old_status' => $oldStatus,
                    'report_id' => $reportId
                ]);

                // Record the status change in history.
                $historyInsert = $pdo->prepare("
                    INSERT INTO issue_status_history (
                        report_id,
                        old_status,
                        new_status,
                        changed_by,
                        remarks,
                        changed_at
                    ) VALUES (
                        :report_id,
                        :old_status,
                        :new_status,
                        :changed_by,
                        :remarks,
                        NOW()
                    )
                ");

                $historyInsert->execute([
                    'report_id' => $reportId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => $_SESSION['user_id'],
                    'remarks' => $remarks !== '' ? $remarks : null
                ]);

                $pdo->commit();

                $successMessage = 'Issue status successfully updated.';

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errorMessage = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Unable to update the issue status. Please try again.';

                error_log($e->getMessage());
            }
        }
    }
}

// Retrieve the selected issue report.
$stmt = $pdo->prepare("
    SELECT
        r.report_id,
        r.reference_no,
        r.description,
        r.photo_path,
        r.status,
        r.created_at,
        r.updated_at,
        r.resolved_at,

        reporter.full_name AS reporter_name,
        reporter.email AS reporter_email,

        c.unit_code,
        c.location AS unit_location,

        cat.category_name,

        assigned.full_name AS assigned_name

    FROM issue_reports AS r

    INNER JOIN users AS reporter
        ON r.reporter_id = reporter.user_id

    INNER JOIN computer_units AS c
        ON r.unit_id = c.unit_id

    INNER JOIN issue_categories AS cat
        ON r.category_id = cat.category_id

    LEFT JOIN users AS assigned
        ON r.assigned_to = assigned.user_id

    WHERE r.report_id = :report_id
    LIMIT 1
");

$stmt->execute([
    'report_id' => $reportId
]);

$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    http_response_code(404);
    exit('Issue report not found.');
}

// Retrieve status history.
$historyStmt = $pdo->prepare("
    SELECT
        h.old_status,
        h.new_status,
        h.remarks,
        h.changed_at,
        u.full_name AS changed_by_name

    FROM issue_status_history AS h

    LEFT JOIN users AS u
        ON h.changed_by = u.user_id

    WHERE h.report_id = :report_id

    ORDER BY h.changed_at ASC, h.history_id ASC
");

$historyStmt->execute([
    'report_id' => $reportId
]);

$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Status badge colors.
$statusClasses = [
    'Pending' => 'bg-warning text-dark',
    'Under Inspection' => 'bg-info text-dark',
    'Under Repair' => 'bg-primary',
    'Resolved' => 'bg-success',
    'Unresolved' => 'bg-danger'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($report['reference_no']) ?> | PCare</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <!-- NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark"
         style="background-color: #245B54;">

        <div class="container">

            <a class="navbar-brand fw-bold" href="dashboard.php">
                PCare | IT Personnel
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#personnelNavbar"
                aria-controls="personnelNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="personnelNavbar">

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="issue-reports.php">
                            Issue Reports
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="maintenance-records.php">
                            Maintenance Records
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>


    <!-- MAIN CONTENT -->
    <main class="container py-5">

        <div class="mb-4">

            <a href="issue-reports.php"
               class="btn btn-outline-secondary btn-sm">

                &larr; Back to Issue Reports

            </a>

        </div>


        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

            <div>

                <h2 class="fw-bold mb-1">
                    Issue Details
                </h2>

                <p class="text-muted mb-0">
                    Review the submitted computer issue report.
                </p>

            </div>

            <span class="badge rounded-pill fs-6 <?= e(
                $statusClasses[$report['status']] ?? 'bg-secondary'
            ) ?>">

                <?= e($report['status']) ?>

            </span>

        </div>


        <!-- NOTIFICATIONS -->
        <?php if ($successMessage): ?>

            <div class="alert alert-success alert-dismissible fade show">
                <?= e($successMessage) ?>

                <button type="button" class="btn-close"
                        data-bs-dismiss="alert"></button>
            </div>

        <?php endif; ?>


        <?php if ($errorMessage): ?>

            <div class="alert alert-danger alert-dismissible fade show">
                <?= e($errorMessage) ?>

                <button type="button" class="btn-close"
                        data-bs-dismiss="alert"></button>
            </div>

        <?php endif; ?>


        <div class="row g-4">

            <!-- REPORT INFORMATION -->
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4">
                            Report Information
                        </h5>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <small class="text-muted">Reference Number</small>
                                <p class="fw-semibold">
                                    <?= e($report['reference_no']) ?>
                                </p>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Date Submitted</small>
                                <p class="fw-semibold">
                                    <?= e(date(
                                        'M d, Y h:i A',
                                        strtotime($report['created_at'])
                                    )) ?>
                                </p>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Computer Unit</small>
                                <p class="fw-semibold">
                                    <?= e($report['unit_code']) ?>
                                </p>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Issue Category</small>
                                <p class="fw-semibold">
                                    <?= e($report['category_name']) ?>
                                </p>
                            </div>

                        </div>

                        <hr>

                        <h6 class="fw-bold">Issue Description</h6>

                        <p class="text-muted" style="white-space: pre-wrap;"><?= e($report['description']) ?></p>


                        <?php if (!empty($report['photo_path'])): ?>

                            <hr>

                            <h6 class="fw-bold mb-3">Attached Photo</h6>

                            <img
                                src="../<?= e(ltrim($report['photo_path'], '/\\')) ?>"
                                alt="Issue attachment"
                                class="img-fluid rounded border"
                                style="max-height: 350px;"
                            >

                        <?php endif; ?>

                    </div>

                </div>


                <!-- STATUS MANAGEMENT -->
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-2">
                            Manage Issue Status
                        </h5>

                        <p class="text-muted mb-4">
                            Update the progress of this reported computer issue.
                        </p>

                        <form method="POST">

                            <?= csrf_field() ?>

                            <div class="mb-3">

                                <label for="status" class="form-label fw-semibold">
                                    New Status
                                </label>

                                <select
                                    name="status"
                                    id="status"
                                    class="form-select"
                                    required
                                >

                                    <?php foreach ($allowedStatuses as $status): ?>

                                        <option
                                            value="<?= e($status) ?>"
                                            <?= $report['status'] === $status ? 'selected' : '' ?>
                                        >
                                            <?= e($status) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="mb-3">

                                <label for="remarks" class="form-label fw-semibold">
                                    Remarks
                                    <span class="text-muted fw-normal">(Optional)</span>
                                </label>

                                <textarea
                                    name="remarks"
                                    id="remarks"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Enter inspection findings, repair progress, or reason for updating the status..."
                                ></textarea>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Save Status Update
                            </button>

                        </form>

                    </div>

                </div>


                <!-- STATUS HISTORY -->
                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4">
                            Status History
                        </h5>

                        <?php if (empty($history)): ?>

                            <p class="text-muted mb-0">
                                No status history available.
                            </p>

                        <?php else: ?>

                            <div class="table-responsive">

                                <table class="table table-hover align-middle">

                                    <thead class="table-light">

                                        <tr>
                                            <th>Previous Status</th>
                                            <th>New Status</th>
                                            <th>Changed By</th>
                                            <th>Date</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($history as $item): ?>

                                            <tr>

                                                <td>
                                                    <?= e($item['old_status'] ?? 'Initial Report') ?>
                                                </td>

                                                <td class="fw-semibold">
                                                    <?= e($item['new_status']) ?>
                                                </td>

                                                <td>
                                                    <?= e($item['changed_by_name'] ?? 'System') ?>
                                                </td>

                                                <td>
                                                    <?= e(date(
                                                        'M d, Y h:i A',
                                                        strtotime($item['changed_at'])
                                                    )) ?>
                                                </td>

                                            </tr>

                                            <?php if (!empty($item['remarks'])): ?>

                                                <tr>
                                                    <td colspan="4">
                                                        <small class="text-muted">
                                                            Remarks:
                                                            <?= e($item['remarks']) ?>
                                                        </small>
                                                    </td>
                                                </tr>

                                            <?php endif; ?>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- REPORTER AND ASSIGNMENT INFORMATION -->
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4">
                            Reporter Information
                        </h5>

                        <p class="text-muted mb-1">Full Name</p>

                        <p class="fw-semibold">
                            <?= e($report['reporter_name']) ?>
                        </p>

                        <p class="text-muted mb-1">Email Address</p>

                        <p class="fw-semibold">
                            <?= e($report['reporter_email']) ?>
                        </p>

                    </div>

                </div>


                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4">
                            Assignment Information
                        </h5>

                        <p class="text-muted mb-1">
                            Assigned IT Personnel
                        </p>

                        <p class="fw-semibold">
                            <?= e($report['assigned_name'] ?? 'Not Assigned') ?>
                        </p>

                        <hr>

                        <p class="text-muted mb-1">
                            Computer Location
                        </p>

                        <p class="fw-semibold mb-0">
                            <?= e($report['unit_location'] ?? 'Not specified') ?>
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </main>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

</body>
</html>