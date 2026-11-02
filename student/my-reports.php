
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();

$userId = (int) $_SESSION['user_id'];

$reports = [];
$databaseError = false;

try {

    // Retrieve only reports submitted by the logged-in student.
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


// Assign a status class based on report status.
function getStatusClass(string $status): string
{
    switch ($status) {

        case 'Pending':
            return 'status-pending';

        case 'Under Inspection':
            return 'status-inspection';

        case 'Under Repair':
            return 'status-repair';

        case 'Resolved':
            return 'status-resolved';

        case 'Unresolved':
            return 'status-unresolved';

        default:
            return 'status-default';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Reports | LabSync</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary: #4E968B;
            --deep-green: #245B54;
            --soft-mint: #E5F0ED;
            --background: #F7F8F6;
            --text: #303C39;
            --muted: #7B8985;
            --border: #E7ECE9;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--deep-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 22px;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .page-heading h1 {
            font-size: clamp(24px, 4vw, 30px);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .page-heading p {
            color: var(--muted);
            line-height: 1.7;
            margin: 0;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--deep-green);
            color: var(--white);
            border: 1px solid var(--deep-green);
            border-radius: 9px;
            padding: 11px 16px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }

        .primary-button:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
            transform: translateY(-1px);
        }

        .summary-card,
        .reports-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(36, 91, 84, .035);
        }

        .summary-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 23px;
            margin-bottom: 22px;
        }

        .summary-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .summary-number {
            color: var(--deep-green);
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1;
        }

        .summary-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 12px;
            font-size: 20px;
        }

        .reports-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 21px 23px;
            border-bottom: 1px solid var(--border);
        }

        .reports-header h2 {
            font-size: 15px;
            font-weight: 750;
            margin: 0 0 5px;
        }

        .reports-header p {
            color: var(--muted);
            font-size: 11px;
            margin: 0;
        }

        .search-box {
            position: relative;
            width: 260px;
            max-width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
        }

        .search-box input {
            width: 100%;
            height: 39px;
            padding: 0 12px 0 35px;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            font-family: inherit;
            font-size: 11px;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78, 150, 139, .1);
        }

        .table-wrapper {
            padding: 0 20px 15px;
        }

        .table {
            margin-bottom: 0;
            min-width: 850px;
        }

        .table thead th {
            padding: 15px 12px;
            background: #FAFBFA;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            font-size: 10px;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 16px 12px;
            border-color: var(--border);
            color: var(--text);
            font-size: 11px;
            vertical-align: middle;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .reference-number {
            color: var(--deep-green);
            font-weight: 750;
            white-space: nowrap;
        }

        .unit-label {
            display: inline-block;
            background: var(--soft-mint);
            color: var(--deep-green);
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .description-cell {
            max-width: 210px;
            line-height: 1.6;
            color: #65736F !important;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 750;
            white-space: nowrap;
        }

        .status-pending {
            background: #FFF3D6;
            color: #936000;
        }

        .status-inspection {
            background: #E2F2F8;
            color: #236780;
        }

        .status-repair {
            background: #E7ECFF;
            color: #4056A5;
        }

        .status-resolved {
            background: #E4F4E9;
            color: #287342;
        }

        .status-unresolved {
            background: #FBE8E8;
            color: #A23E3E;
        }

        .status-default {
            background: #EDF0EF;
            color: #58645F;
        }

        .date-label {
            white-space: nowrap;
            color: var(--muted);
        }

        .view-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #C8DCD6;
            border-radius: 7px;
            padding: 7px 10px;
            color: var(--deep-green);
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s ease;
        }

        .view-button:hover {
            background: var(--deep-green);
            border-color: var(--deep-green);
            color: var(--white);
        }

        .empty-state {
            padding: 65px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 65px;
            height: 65px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 27px;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 750;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 20px;
        }

        .no-results {
            display: none;
            padding: 25px;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
        }

        .alert {
            border: none;
            border-radius: 10px;
            font-size: 12px;
        }

        @media (max-width: 767px) {

            .reports-header {
                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

            .summary-card {
                padding: 18px;
            }

            .table-wrapper {
                padding: 0 12px 12px;
            }

        }

    </style>

</head>

<body>

    <!-- SHARED STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>


    <main class="container py-4 py-lg-5">

        <div class="page-container">

            <!-- PAGE HEADER -->
            <div class="page-heading">

                <a href="dashboard.php" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

                    <div>

                        <h1>My Reports</h1>

                        <p>
                            View and monitor the computer issues you have submitted.
                        </p>

                    </div>

                    <a href="report-issue.php" class="primary-button">
                        <i class="bi bi-plus-lg"></i>
                        Report an Issue
                    </a>

                </div>

            </div>


            <!-- DATABASE ERROR -->
            <?php if ($databaseError): ?>

                <div class="alert alert-danger mb-4">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    Unable to retrieve your reports.
                    Please try again later.

                </div>

            <?php else: ?>


                <!-- REPORT COUNT -->
                <div class="summary-card">

                    <div>

                        <div class="summary-label">
                            Total Submitted Reports
                        </div>

                        <div class="summary-number">
                            <?= count($reports) ?>
                        </div>

                    </div>

                    <div class="summary-icon">
                        <i class="bi bi-clipboard-data"></i>
                    </div>

                </div>


                <!-- REPORT HISTORY -->
                <section class="reports-card">

                    <div class="reports-header">

                        <div>

                            <h2>Report History</h2>

                            <p>
                                A record of your submitted computer issue reports.
                            </p>

                        </div>

                        <?php if (!empty($reports)): ?>

                            <div class="search-box">

                                <i class="bi bi-search"></i>

                                <input
                                    type="search"
                                    id="reportSearch"
                                    placeholder="Search your reports..."
                                    aria-label="Search your reports"
                                >

                            </div>

                        <?php endif; ?>

                    </div>


                    <?php if (empty($reports)): ?>

                        <!-- EMPTY STATE -->
                        <div class="empty-state">

                            <div class="empty-icon">
                                <i class="bi bi-clipboard-x"></i>
                            </div>

                            <h3>No Reports Yet</h3>

                            <p>
                                You haven't submitted any computer issue reports.
                                Your submitted reports will appear here.
                            </p>

                            <a
                                href="report-issue.php"
                                class="primary-button"
                            >
                                <i class="bi bi-plus-lg"></i>
                                Submit Your First Report
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
                                                    <span class="reference-number">
                                                        <?= e($report['reference_no']) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="unit-label">
                                                        <?= e($report['unit_code']) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?= e($report['category_name']) ?>
                                                </td>

                                                <td class="description-cell">
                                                    <?= e($report['description']) ?>
                                                </td>

                                                <td>

                                                    <span class="status-badge <?= e(getStatusClass($report['status'])) ?>">
                                                        <?= e($report['status']) ?>
                                                    </span>

                                                </td>

                                                <td class="date-label">
                                                    <?= e(
                                                        date(
                                                            'M d, Y',
                                                            strtotime($report['created_at'])
                                                        )
                                                    ) ?>
                                                </td>

                                                <td>

                                                    <a
                                                        href="report-details.php?id=<?= (int) $report['report_id'] ?>"
                                                        class="view-button"
                                                    >
                                                        <i class="bi bi-eye"></i>
                                                        View Details
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

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </div>

    </main>


    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Search reports by reference, unit, category, description, or status.
        const searchInput = document.getElementById('reportSearch');
        const reportRows = document.querySelectorAll('.report-row');
        const noResults = document.getElementById('noResults');

        if (searchInput) {

            searchInput.addEventListener('input', function () {

                const searchValue = this.value.toLowerCase().trim();

                let visibleCount = 0;

                reportRows.forEach(function (row) {

                    const rowText = row.textContent.toLowerCase();

                    const matches = rowText.includes(searchValue);

                    row.style.display = matches ? '' : 'none';

                    if (matches) {
                        visibleCount++;
                    }

                });

                if (noResults) {
                    noResults.style.display =
                        visibleCount === 0 ? 'block' : 'none';
                }

            });

        }

    </script>

</body>
</html>
