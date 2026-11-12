<?php
// it-personnel/issue-details-logic.php

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
                $checkStmt->execute(['report_id' => $reportId]);
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
                        report_id, old_status, new_status, changed_by, remarks, changed_at
                    ) VALUES (
                        :report_id, :old_status, :new_status, :changed_by, :remarks, NOW()
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
                $errorMessage = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the issue status. Please try again.';
                error_log($e->getMessage());
            }
        }
    }
}

// Retrieve the selected issue report.
$stmt = $pdo->prepare("
    SELECT
        r.report_id, r.reference_no, r.description, r.photo_path, r.status, r.created_at, r.updated_at, r.resolved_at,
        reporter.full_name AS reporter_name, reporter.email AS reporter_email,
        c.unit_code, c.location AS unit_location,
        cat.category_name,
        assigned.full_name AS assigned_name
    FROM issue_reports AS r
    INNER JOIN users AS reporter ON r.reporter_id = reporter.user_id
    INNER JOIN computer_units AS c ON r.unit_id = c.unit_id
    INNER JOIN issue_categories AS cat ON r.category_id = cat.category_id
    LEFT JOIN users AS assigned ON r.assigned_to = assigned.user_id
    WHERE r.report_id = :report_id
    LIMIT 1
");

$stmt->execute(['report_id' => $reportId]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    http_response_code(404);
    exit('Issue report not found.');
}

// Retrieve status history.
$historyStmt = $pdo->prepare("
    SELECT h.old_status, h.new_status, h.remarks, h.changed_at, u.full_name AS changed_by_name
    FROM issue_status_history AS h
    LEFT JOIN users AS u ON h.changed_by = u.user_id
    WHERE h.report_id = :report_id
    ORDER BY h.changed_at ASC, h.history_id ASC
");

$historyStmt->execute(['report_id' => $reportId]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Status badge colors.
$statusClasses = [
    'Pending' => 'bg-warning text-dark',
    'Under Inspection' => 'bg-info text-dark',
    'Under Repair' => 'bg-primary',
    'Resolved' => 'bg-success',
    'Unresolved' => 'bg-danger'
];