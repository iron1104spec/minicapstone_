
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$pdo = db();

$userId = (int) $_SESSION['user_id'];

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


// Status badge classes.
function reportDetailsStatusClass(string $status): string
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

    <title>Report Details | LabSync</title>

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
            max-width: 1100px;
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
            font-weight: 650;
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

        .content-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(36, 91, 84, .035);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-content {
            padding: 24px;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 750;
            margin-bottom: 20px;
        }

        .section-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 9px;
            font-size: 15px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            padding: 25px;
        }

        .reference-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 8px;
        }

        .reference-number {
            color: var(--deep-green);
            font-size: clamp(17px, 3vw, 23px);
            font-weight: 800;
            letter-spacing: -.5px;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 13px;
            border-radius: 30px;
            font-size: 10px;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 23px 30px;
        }

        .info-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 650;
            margin-bottom: 7px;
        }

        .info-value {
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .description-box {
            padding: 17px;
            background: #FAFCFB;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #4E5D58;
            font-size: 12px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .photo-container {
            padding: 14px;
            background: #FAFCFB;
            border: 1px solid var(--border);
            border-radius: 10px;
            display: inline-block;
            max-width: 100%;
        }

        .report-photo {
            display: block;
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 7px;
        }

        .no-photo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 11px;
            padding: 16px;
            background: #FAFCFB;
            border: 1px dashed var(--border);
            border-radius: 10px;
        }

        .no-photo i {
            font-size: 19px;
            color: var(--primary);
        }

        .timeline {
            position: relative;
            padding-left: 7px;
        }

        .timeline-item {
            position: relative;
            display: flex;
            gap: 15px;
            padding-bottom: 27px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-marker-column {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }

        .timeline-marker {
            position: relative;
            z-index: 1;
            width: 29px;
            height: 29px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--soft-mint);
            color: var(--deep-green);
            font-size: 12px;
        }

        .timeline-line {
            position: absolute;
            top: 29px;
            bottom: -1px;
            width: 2px;
            background: #DDEAE5;
        }

        .timeline-item:last-child .timeline-line {
            display: none;
        }

        .timeline-content {
            flex: 1;
            min-width: 0;
            padding: 2px 0 0;
        }

        .timeline-title {
            font-size: 12px;
            font-weight: 750;
            margin-bottom: 9px;
        }

        .timeline-meta {
            color: var(--muted);
            font-size: 10px;
            line-height: 1.7;
        }

        .timeline-remarks {
            margin-top: 12px;
            padding: 12px 14px;
            background: #FAFCFB;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #5E6B66;
            font-size: 11px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .additional-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
            font-size: 11px;
        }

        .additional-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .additional-label {
            color: var(--muted);
        }

        .additional-value {
            color: var(--text);
            font-weight: 700;
            text-align: right;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #C8DCD6;
            border-radius: 9px;
            padding: 11px 16px;
            color: var(--deep-green);
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }

        .secondary-button:hover {
            background: var(--deep-green);
            border-color: var(--deep-green);
            color: var(--white);
        }

        .empty-history {
            padding: 25px;
            background: #FAFCFB;
            border: 1px dashed var(--border);
            border-radius: 10px;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
        }

        .empty-history i {
            display: block;
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .not-found-icon {
            width: 65px;
            height: 65px;
            display: grid;
            place-items: center;
            background: var(--soft-mint);
            color: var(--deep-green);
            border-radius: 18px;
            font-size: 26px;
            margin: 0 auto 18px;
        }

        .not-found-card {
            padding: 50px 25px;
            text-align: center;
        }

        .not-found-card h2 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .not-found-card p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
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
        }

        .alert {
            border: none;
            border-radius: 10px;
            font-size: 12px;
        }

        @media (max-width: 575px) {

            .card-content {
                padding: 19px;
            }

            .report-header {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .additional-row {
                flex-direction: column;
                gap: 5px;
            }

            .additional-value {
                text-align: left;
            }

        }

    </style>

</head>

<body>

    <!-- SHARED STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>


    <main class="container py-4 py-lg-5">

        <div class="page-container">

            <!-- PAGE HEADING -->
            <div class="page-heading">

                <a href="my-reports.php" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to My Reports
                </a>

                <h1>Report Details</h1>

                <p>
                    Review your submitted computer issue and monitor
                    its maintenance status history.
                </p>

            </div>


            <!-- DATABASE ERROR -->
            <?php if ($databaseError): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    Unable to retrieve report details.
                    Please try again later.

                </div>


            <!-- REPORT NOT FOUND -->
            <?php elseif (!$report): ?>

                <section class="content-card not-found-card">

                    <div class="not-found-icon">
                        <i class="bi bi-file-earmark-x"></i>
                    </div>

                    <h2>Report Not Found</h2>

                    <p>
                        This report does not exist or you do not have
                        permission to view it.
                    </p>

                    <a href="my-reports.php" class="primary-button">
                        <i class="bi bi-arrow-left"></i>
                        Return to My Reports
                    </a>

                </section>


            <!-- REPORT INFORMATION -->
            <?php else: ?>


                <!-- REPORT HEADER -->
                <section class="content-card">

                    <div class="report-header">

                        <div>

                            <div class="reference-label">
                                Reference Number
                            </div>

                            <h2 class="reference-number">
                                <?= e($report['reference_no']) ?>
                            </h2>

                        </div>

                        <span class="status-badge <?= e(reportDetailsStatusClass($report['status'])) ?>">

                            <i class="bi bi-circle-fill"
                               style="font-size: 7px;"></i>

                            <?= e($report['status']) ?>

                        </span>

                    </div>

                </section>


                <!-- COMPUTER INFORMATION -->
                <section class="content-card">

                    <div class="card-content">

                        <h2 class="section-heading">

                            <span class="section-icon">
                                <i class="bi bi-pc-display"></i>
                            </span>

                            Computer Information

                        </h2>

                        <div class="info-grid">

                            <div>

                                <div class="info-label">
                                    Computer Unit
                                </div>

                                <div class="info-value">
                                    <?= e($report['unit_code']) ?>
                                </div>

                            </div>

                            <div>

                                <div class="info-label">
                                    Laboratory Location
                                </div>

                                <div class="info-value">
                                    <?= e($report['location']) ?>
                                </div>

                            </div>

                            <div>

                                <div class="info-label">
                                    Issue Category
                                </div>

                                <div class="info-value">
                                    <?= e($report['category_name']) ?>
                                </div>

                            </div>

                            <div>

                                <div class="info-label">
                                    Date Submitted
                                </div>

                                <div class="info-value">
                                    <?= e(reportDetailsDate($report['created_at'])) ?>
                                </div>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- ISSUE DESCRIPTION -->
                <section class="content-card">

                    <div class="card-content">

                        <h2 class="section-heading">

                            <span class="section-icon">
                                <i class="bi bi-card-text"></i>
                            </span>

                            Problem Description

                        </h2>

                        <div class="description-box">
                            <?= nl2br(e($report['description'])) ?>
                        </div>

                    </div>

                </section>


                <!-- ATTACHED PHOTO -->
                <section class="content-card">

                    <div class="card-content">

                        <h2 class="section-heading">

                            <span class="section-icon">
                                <i class="bi bi-image"></i>
                            </span>

                            Attached Photo

                        </h2>

                        <?php if ($photoUrl): ?>

                            <div class="photo-container">

                                <a
                                    href="<?= e($photoUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    <img
                                        src="<?= e($photoUrl) ?>"
                                        alt="Uploaded computer issue photo"
                                        class="report-photo"
                                    >

                                </a>

                            </div>

                            <p class="text-muted mt-2 mb-0"
                               style="font-size: 10px;">
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Select the image to view it in a new tab.
                            </p>

                        <?php else: ?>

                            <div class="no-photo">

                                <i class="bi bi-image"></i>

                                No photo was attached to this report.

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


                <!-- ISSUE STATUS HISTORY -->
                <section class="content-card">

                    <div class="card-content">

                        <h2 class="section-heading">

                            <span class="section-icon">
                                <i class="bi bi-clock-history"></i>
                            </span>

                            Issue Status History

                        </h2>

                        <?php if (empty($history)): ?>

                            <div class="empty-history">

                                <i class="bi bi-clock"></i>

                                No status history is available for this report.

                            </div>

                        <?php else: ?>

                            <div class="timeline">

                                <?php foreach ($history as $item): ?>

                                    <div class="timeline-item">

                                        <div class="timeline-marker-column">

                                            <div class="timeline-marker">

                                                <i class="bi bi-check2"></i>

                                            </div>

                                            <div class="timeline-line"></div>

                                        </div>


                                        <div class="timeline-content">

                                            <?php if ($item['old_status'] === null): ?>

                                                <div class="timeline-title">
                                                    Initial Report Submission
                                                </div>

                                            <?php else: ?>

                                                <div class="timeline-title">

                                                    <?= e($item['old_status']) ?>

                                                    <i class="bi bi-arrow-right mx-1"
                                                       style="color: #4E968B;"></i>

                                                    <?= e($item['new_status']) ?>

                                                </div>

                                            <?php endif; ?>


                                            <span class="status-badge <?= e(reportDetailsStatusClass($item['new_status'])) ?>">
                                                <?= e($item['new_status']) ?>
                                            </span>


                                            <div class="timeline-meta mt-2">

                                                <div>
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?= e(reportDetailsDate($item['changed_at'])) ?>
                                                </div>

                                                <div>
                                                    <i class="bi bi-person me-1"></i>
                                                    Updated by:
                                                    <?= e($item['changed_by_name'] ?? 'Unknown user') ?>
                                                </div>

                                            </div>


                                            <?php if (!empty($item['remarks'])): ?>

                                                <div class="timeline-remarks">

                                                    <strong>Remarks</strong>

                                                    <div class="mt-1">
                                                        <?= nl2br(e($item['remarks'])) ?>
                                                    </div>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


                <!-- ADDITIONAL INFORMATION -->
                <section class="content-card">

                    <div class="card-content">

                        <h2 class="section-heading">

                            <span class="section-icon">
                                <i class="bi bi-info-circle"></i>
                            </span>

                            Additional Information

                        </h2>

                        <div class="additional-row">

                            <span class="additional-label">
                                Last Updated
                            </span>

                            <span class="additional-value">
                                <?= e(reportDetailsDate($report['updated_at'])) ?>
                            </span>

                        </div>

                        <div class="additional-row">

                            <span class="additional-label">
                                Resolved Date
                            </span>

                            <span class="additional-value">
                                <?= e(reportDetailsDate($report['resolved_at'])) ?>
                            </span>

                        </div>

                    </div>

                </section>


                <!-- BACK BUTTON -->
                <a href="my-reports.php" class="secondary-button">

                    <i class="bi bi-arrow-left"></i>

                    Back to My Reports

                </a>

            <?php endif; ?>

        </div>

    </main>


    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
