<?php
// student/report-details-logic.php

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
                r.report_id, r.reference_no, r.description, r.photo_path, r.status, 
                r.created_at, r.updated_at, r.resolved_at,
                c.unit_code, c.location,
                cat.category_name
            FROM issue_reports AS r
            INNER JOIN computer_units AS c ON r.unit_id = c.unit_id
            INNER JOIN issue_categories AS cat ON r.category_id = cat.category_id
            WHERE r.report_id = :report_id AND r.reporter_id = :user_id
            LIMIT 1
        ");

        $stmt->execute(['report_id' => $reportId, 'user_id' => $userId]);
        $report = $stmt->fetch();

        // Retrieve status history only if the report belongs to the student.
        if ($report) {
            $stmt = $pdo->prepare("
                SELECT h.old_status, h.new_status, h.remarks, h.changed_at, u.full_name AS changed_by_name
                FROM issue_status_history AS h
                LEFT JOIN users AS u ON h.changed_by = u.user_id
                WHERE h.report_id = :report_id
                ORDER BY h.changed_at ASC, h.history_id ASC
            ");
            $stmt->execute(['report_id' => $reportId]);
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
        case 'Pending': return 'status-pending';
        case 'Under Inspection': return 'status-inspection';
        case 'Under Repair': return 'status-repair';
        case 'Resolved': return 'status-resolved';
        case 'Unresolved': return 'status-unresolved';
        default: return 'status-default';
    }
}

// Format database dates safely.
function reportDetailsDate($date): string
{
    if (empty($date)) return 'Not available';
    $timestamp = strtotime($date);
    if ($timestamp === false) return 'Not available';
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