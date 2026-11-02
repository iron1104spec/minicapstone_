
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();

$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Student';

// Get report ID from the URL.
$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$report = null;
$history = [];
$databaseError = false;

if (!$reportId || $reportId < 1) {
    http_response_code(404);
} else {

    try {

        // Retrieve only a report owned by the logged-in student.
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
                c.unit_code,
                c.location,
                cat.category_name

            FROM issue_reports AS r

            INNER JOIN computer_units AS c
                ON r.unit_id = c.unit_id

            INNER JOIN issue_categories AS cat
                ON r.category_id = cat.category_id

            WHERE r.report_id = :report_id
            AND r.reporter_id = :user_id

            LIMIT 1
        ");

        $stmt->execute([
            'report_id' => $reportId,
            'user_id' => $userId
        ]);

        $report = $stmt->fetch();

        // Retrieve status history only if the report belongs to the student.
        if ($report) {

            $stmt = $pdo->prepare("
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

            $stmt->execute([
                'report_id' => $reportId
            ]);

            $history = $stmt->fetchAll();

        } else {

            http_response_code(404);

        }

    } catch (PDOException $e) {

        error_log($e->getMessage());
        $databaseError = true;

    }
}


// Status badge colors.
function reportDetailsStatusClass(string $status): string
{
    switch ($status) {

        case 'Pending':
            return 'bg-warning text-dark';

        case 'Under Inspection':
            return 'bg-info text-dark';

        case 'Under Repair':
            return 'bg-primary';

        case 'Resolved':
            return 'bg-success';

        case 'Unresolved':
            return 'bg-danger';

        default:
            return 'bg-secondary';
    }
}


// Format database dates safely.
function reportDetailsDate($date): string
{
    if (empty($date)) {
        return 'Not available';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return 'Not available';
    }

    return date('F d, Y - h:i A', $timestamp);
}


// Validate the optional uploaded image path.
$photoUrl = null;

if ($report && !empty($report['photo_path'])) {

    $photoFile = basename($report['photo_path']);

    if (preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/i', $photoFile)) {
        $photoUrl = '../uploads/issues/' . $photoFile;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Details | PCare</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <!-- NAVIGATION -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">

        <div class="container">

            <a class="navbar-brand fw-bold" href="dashboard.php">
                PCare
            </a>

            <div class="ms-auto d-flex align-items-center">

                <span class="text-white me-3">
                    <?= e($fullName) ?>
                </span>

                <form action="../logout.php" method="POST">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-outline-light btn-sm"
                    >
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </nav>


    <!-- MAIN CONTENT -->
    <main class="container py-5">

        <div class="mb-4">

            <a href="my-reports.php" class="text-decoration-none">
                ← Back to My Reports
            </a>

            <h2 class="mt-3">Report Details</h2>

            <p class="text-muted">
                View your submitted computer issue and its maintenance status history.
            </p>

        </div>


        <!-- DATABASE ERROR -->
        <?php if ($databaseError): ?>

            <div class="alert alert-danger">
                Unable to retrieve report details.
                Please try again later.
            </div>


        <!-- REPORT NOT FOUND -->
        <?php elseif (!$report): ?>

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center py-5">

                    <h4>Report Not Found</h4>

                    <p class="text-muted">
                        This report does not exist or you do not have permission to view it.
                    </p>

                    <a href="my-reports.php" class="btn btn-success">
                        Return to My Reports
                    </a>

                </div>

            </div>


        <!-- REPORT INFORMATION -->
        <?php else: ?>

            <!-- REPORT HEADER -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

                        <div>

                            <p class="text-muted mb-1">
                                Reference Number
                            </p>

                            <h4 class="fw-bold">
                                <?= e($report['reference_no']) ?>
                            </h4>

                        </div>

                        <span class="badge fs-6 <?= e(reportDetailsStatusClass($report['status'])) ?>">
                            <?= e($report['status']) ?>
                        </span>

                    </div>

                </div>

            </div>


            <!-- COMPUTER INFORMATION -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="mb-3">
                        Computer Information
                    </h5>

                    <div class="row g-3">

                        <div class="col-md-6">

                            <p class="text-muted mb-1">
                                Computer Unit
                            </p>

                            <h6>
                                <?= e($report['unit_code']) ?>
                            </h6>

                        </div>

                        <div class="col-md-6">

                            <p class="text-muted mb-1">
                                Laboratory Location
                            </p>

                            <h6>
                                <?= e($report['location']) ?>
                            </h6>

                        </div>

                        <div class="col-md-6">

                            <p class="text-muted mb-1">
                                Issue Category
                            </p>

                            <h6>
                                <?= e($report['category_name']) ?>
                            </h6>

                        </div>

                        <div class="col-md-6">

                            <p class="text-muted mb-1">
                                Date Submitted
                            </p>

                            <h6>
                                <?= e(reportDetailsDate($report['created_at'])) ?>
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ISSUE DESCRIPTION -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="mb-3">
                        Problem Description
                    </h5>

                    <p class="mb-0">
                        <?= nl2br(e($report['description'])) ?>
                    </p>

                </div>

            </div>


            <!-- ATTACHED PHOTO -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="mb-3">
                        Attached Photo
                    </h5>

                    <?php if ($photoUrl): ?>

                        <a
                            href="<?= e($photoUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >

                            <img
                                src="<?= e($photoUrl) ?>"
                                alt="Uploaded computer issue photo"
                                class="img-fluid rounded"
                                style="max-height: 350px;"
                            >

                        </a>

                    <?php else: ?>

                        <p class="text-muted mb-0">
                            No photo was attached to this report.
                        </p>

                    <?php endif; ?>

                </div>

            </div>


            <!-- REPORT TIMELINE -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="mb-4">
                        Issue Status History
                    </h5>

                    <?php if (empty($history)): ?>

                        <p class="text-muted">
                            No status history is available.
                        </p>

                    <?php else: ?>

                        <div class="list-group list-group-flush">

                            <?php foreach ($history as $item): ?>

                                <div class="list-group-item px-0 py-3">

                                    <div class="d-flex justify-content-between flex-wrap gap-2">

                                        <div>

                                            <?php if ($item['old_status'] === null): ?>

                                                <strong>Initial Report Submission</strong>

                                            <?php else: ?>

                                                <strong>
                                                    <?= e($item['old_status']) ?>
                                                    →
                                                    <?= e($item['new_status']) ?>
                                                </strong>

                                            <?php endif; ?>

                                            <div class="mt-2">

                                                <span class="badge <?= e(reportDetailsStatusClass($item['new_status'])) ?>">
                                                    <?= e($item['new_status']) ?>
                                                </span>

                                            </div>

                                        </div>

                                        <small class="text-muted">
                                            <?= e(reportDetailsDate($item['changed_at'])) ?>
                                        </small>

                                    </div>

                                    <?php if (!empty($item['remarks'])): ?>

                                        <p class="text-muted mt-2 mb-1">
                                            <?= nl2br(e($item['remarks'])) ?>
                                        </p>

                                    <?php endif; ?>

                                    <small class="text-muted">
                                        Updated by:
                                        <?= e($item['changed_by_name'] ?? 'Unknown user') ?>
                                    </small>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ADDITIONAL INFORMATION -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body p-4">

                    <h5 class="mb-3">
                        Additional Information
                    </h5>

                    <p>
                        <strong>Last Updated:</strong>
                        <?= e(reportDetailsDate($report['updated_at'])) ?>
                    </p>

                    <p class="mb-0">
                        <strong>Resolved Date:</strong>
                        <?= e(reportDetailsDate($report['resolved_at'])) ?>
                    </p>

                </div>

            </div>


            <a href="my-reports.php" class="btn btn-outline-secondary">
                Back to My Reports
            </a>

        <?php endif; ?>

    </main>

</body>
</html>
