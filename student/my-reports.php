
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();

$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Student';

$reports = [];
$databaseError = false;

try {

    // Retrieve all reports submitted by the logged-in student.
    $stmt = $pdo->prepare("
        SELECT
            r.report_id,
            r.reference_no,
            r.description,
            r.status,
            r.created_at,
            c.unit_code,
            cat.category_name

        FROM issue_reports AS r

        INNER JOIN computer_units AS c
            ON r.unit_id = c.unit_id

        INNER JOIN issue_categories AS cat
            ON r.category_id = cat.category_id

        WHERE r.reporter_id = :user_id

        ORDER BY r.created_at DESC, r.report_id DESC
    ");

    $stmt->execute([
        'user_id' => $userId
    ]);

    $reports = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log($e->getMessage());
    $databaseError = true;
}


// Assign a Bootstrap badge color based on report status.
function getStatusClass(string $status): string
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Reports | PCare</title>

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

        <!-- PAGE HEADER -->
        <div class="mb-4">

            <a
                href="dashboard.php"
                class="text-decoration-none"
            >
                ← Back to Dashboard
            </a>

            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">

                <div>

                    <h2>My Reports</h2>

                    <p class="text-muted mb-0">
                        View and monitor all your submitted computer issue reports.
                    </p>

                </div>

                <a
                    href="report-issue.php"
                    class="btn btn-success mt-3 mt-md-0"
                >
                    + Report an Issue
                </a>

            </div>

        </div>


        <!-- DATABASE ERROR -->
        <?php if ($databaseError): ?>

            <div class="alert alert-danger">
                Unable to retrieve your reports.
                Please try again later.
            </div>

        <?php else: ?>


            <!-- REPORT COUNT -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Total Submitted Reports
                    </p>

                    <h3 class="fw-bold mb-0">
                        <?= count($reports) ?>
                    </h3>

                </div>

            </div>


            <!-- REPORT TABLE -->
            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <h5 class="mb-3">
                        Report History
                    </h5>


                    <?php if (empty($reports)): ?>

                        <div class="text-center py-5">

                            <h5>No Reports Found</h5>

                            <p class="text-muted">
                                You have not submitted any computer issue reports yet.
                            </p>

                            <a
                                href="report-issue.php"
                                class="btn btn-success"
                            >
                                Submit Your First Report
                            </a>

                        </div>

                    <?php else: ?>

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

                                <tbody>

                                    <?php foreach ($reports as $report): ?>

                                        <tr>

                                            <td class="fw-semibold">
                                                <?= e($report['reference_no']) ?>
                                            </td>

                                            <td>
                                                <?= e($report['unit_code']) ?>
                                            </td>

                                            <td>
                                                <?= e($report['category_name']) ?>
                                            </td>

                                            <td>
                                                <?= e($report['description']) ?>
                                            </td>

                                            <td>

                                                <span class="badge <?= e(getStatusClass($report['status'])) ?>">
                                                    <?= e($report['status']) ?>
                                                </span>

                                            </td>

                                            <td>
                                                <?= e(
                                                    date(
                                                        'M d, Y',
                                                        strtotime($report['created_at'])
                                                    )
                                                ) ?>
                                            </td>

                                            <!-- VIEW DETAILS BUTTON -->
                                            <td>

                                                <a
                                                    href="report-details.php?id=<?= (int) $report['report_id'] ?>"
                                                    class="btn btn-sm btn-outline-success"
                                                >
                                                    View Details
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </main>

</body>
</html>
