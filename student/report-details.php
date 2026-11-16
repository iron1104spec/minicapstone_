<?php
// student/report-details.php
require_once __DIR__ . '/report-details-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details | LabSync</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/student-report-details.css">
</head>

<body>

    <!-- SHARED STUDENT NAVIGATION -->
    <?php require_once __DIR__ . '/../includes/student-nav.php'; ?>

    <main class="container py-4 py-lg-5">
        <div class="page-container">

            <!-- PAGE HEADING -->
            <div class="page-heading">
                <a href="my-reports.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Back to My Reports
                </a>
                <h1>Report Details</h1>
                <p>Review your submitted computer issue and monitor its maintenance status history.</p>
            </div>

            <!-- DATABASE ERROR -->
            <?php if ($databaseError): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Unable to retrieve report details. Please try again later.
                </div>

            <!-- REPORT NOT FOUND -->
            <?php elseif (!$report): ?>
                <section class="content-card not-found-card">
                    <div class="not-found-icon">
                        <i class="bi bi-file-earmark-x"></i>
                    </div>
                    <h2>Report Not Found</h2>
                    <p>This report does not exist or you do not have permission to view it.</p>
                    <a href="my-reports.php" class="primary-button">
                        <i class="bi bi-arrow-left"></i> Return to My Reports
                    </a>
                </section>

            <!-- REPORT INFORMATION -->
            <?php else: ?>

                <!-- REPORT HEADER -->
                <section class="content-card">
                    <div class="report-header">
                        <div>
                            <div class="reference-label">Reference Number</div>
                            <h2 class="reference-number"><?= e($report['reference_no']) ?></h2>
                        </div>
                        <span class="status-badge <?= e(reportDetailsStatusClass($report['status'])) ?>">
                            <i class="bi bi-circle-fill dot-small"></i>
                            <?= e($report['status']) ?>
                        </span>
                    </div>
                </section>

                <!-- COMPUTER INFORMATION -->
                <section class="content-card">
                    <div class="card-content">
                        <h2 class="section-heading">
                            <span class="section-icon"><i class="bi bi-pc-display"></i></span>
                            Computer Information
                        </h2>
                        <div class="info-grid">
                            <div>
                                <div class="info-label">Computer Unit</div>
                                <div class="info-value"><?= e($report['unit_code']) ?></div>
                            </div>
                            <div>
                                <div class="info-label">Laboratory Location</div>
                                <div class="info-value"><?= e($report['location']) ?></div>
                            </div>
                            <div>
                                <div class="info-label">Issue Category</div>
                                <div class="info-value"><?= e($report['category_name']) ?></div>
                            </div>
                            <div>
                                <div class="info-label">Date Submitted</div>
                                <div class="info-value"><?= e(reportDetailsDate($report['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ISSUE DESCRIPTION -->
                <section class="content-card">
                    <div class="card-content">
                        <h2 class="section-heading">
                            <span class="section-icon"><i class="bi bi-card-text"></i></span>
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
                            <span class="section-icon"><i class="bi bi-image"></i></span>
                            Attached Photo
                        </h2>
                        <?php if ($photoUrl): ?>
                            <div class="photo-container">
                                <a href="<?= e($photoUrl) ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?= e($photoUrl) ?>" alt="Uploaded computer issue photo" class="report-photo">
                                </a>
                            </div>
                            <p class="text-muted mt-2 mb-0 text-tiny">
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
                            <span class="section-icon"><i class="bi bi-clock-history"></i></span>
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
                                                <div class="timeline-title">Initial Report Submission</div>
                                            <?php else: ?>
                                                <div class="timeline-title">
                                                    <?= e($item['old_status']) ?>
                                                    <i class="bi bi-arrow-right mx-1 arrow-icon-color"></i>
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
                                                    Updated by: <?= e($item['changed_by_name'] ?? 'Unknown user') ?>
                                                </div>
                                            </div>

                                            <?php if (!empty($item['remarks'])): ?>
                                                <div class="timeline-remarks">
                                                    <strong>Remarks</strong>
                                                    <div class="mt-1"><?= nl2br(e($item['remarks'])) ?></div>
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
                            <span class="section-icon"><i class="bi bi-info-circle"></i></span>
                            Additional Information
                        </h2>
                        <div class="additional-row">
                            <span class="additional-label">Last Updated</span>
                            <span class="additional-value"><?= e(reportDetailsDate($report['updated_at'])) ?></span>
                        </div>
                        <div class="additional-row">
                            <span class="additional-label">Resolved Date</span>
                            <span class="additional-value"><?= e(reportDetailsDate($report['resolved_at'])) ?></span>
                        </div>
                    </div>
                </section>

                <!-- BACK BUTTON -->
                <a href="my-reports.php" class="secondary-button">
                    <i class="bi bi-arrow-left"></i> Back to My Reports
                </a>

            <?php endif; ?>
        </div>
    </main>

    <!-- BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>