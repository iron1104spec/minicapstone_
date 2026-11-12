<?php
// it-personnel/issue-history.php
require_once __DIR__ . '/issue-history-logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue History | PCare</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/personnel-issue-history.css">
</head>

<body>

<div class="container-fluid main-content">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="text-secondary small mb-1">IT PERSONNEL / ISSUE HISTORY</p>
            <h2 class="page-title mb-1">Issue History</h2>
            <p class="text-secondary mb-0">
                Review the recorded status changes of reported computer issues.
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- History Summary -->
    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="history-count">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small mb-1">History Records</p>
                        <h3 class="fw-bold mb-0"><?= $totalHistory ?></h3>
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
                    <h5 class="fw-bold mb-1">Status Change Records</h5>
                    <p class="text-secondary small mb-0">Complete record of issue status updates.</p>
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-12 col-md-6">
                    <input type="text" name="search" class="form-control filter-input" placeholder="Search reference number, unit, or personnel..." value="<?= e($search) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <select name="status" class="form-select filter-input">
                        <option value="">All Statuses</option>
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                <?= e($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <button type="submit" class="btn btn-success w-100" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="col-6 col-md-1">
                    <a href="issue-history.php" class="btn btn-outline-secondary w-100" title="Reset filters">
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
                                <p class="fw-semibold mt-2 mb-1">No history records found.</p>
                                <small class="text-secondary">Status changes will appear here once recorded.</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($histories as $history): ?>
                            <tr>
                                <td>
                                    <a href="issue-details.php?id=<?= (int) $history['report_id'] ?>" class="reference-link" title="View issue details">
                                        <?= e($history['reference_no']) ?>
                                    </a>
                                </td>
                                <td class="fw-semibold"><?= e($history['unit_code']) ?></td>
                                <td>
                                    <?php if ($history['old_status']): ?>
                                        <span class="badge rounded-pill <?= historyStatusClass($history['old_status']) ?>">
                                            <?= e($history['old_status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-secondary small">Initial Report</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= historyStatusClass($history['new_status']) ?>">
                                        <?= e($history['new_status']) ?>
                                    </span>
                                </td>
                                <td><?= e($history['changed_by_name'] ?? 'System / Unknown') ?></td>
                                <td class="text-nowrap">
                                    <?= $history['changed_at'] ? e(date('M d, Y h:i A', strtotime($history['changed_at']))) : '—' ?>
                                </td>
                                <td><?= !empty($history['remarks']) ? e($history['remarks']) : '—' ?></td>
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
        <small class="text-secondary">PCare · B204 Computer Laboratory</small>
    </div>

</div>

</body>
</html>