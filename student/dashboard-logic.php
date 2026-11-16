<?php
// student/dashboard-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Student';

$pdo = db();

$totalReports = 0;
$pendingReports = 0;
$ongoingReports = 0;
$resolvedReports = 0;
$recentReports = [];
$databaseError = false;

try {
    // Count all reports submitted by this student.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issue_reports WHERE reporter_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $totalReports = (int) $stmt->fetchColumn();

    // Count pending reports.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issue_reports WHERE reporter_id = :user_id AND status = 'Pending'");
    $stmt->execute(['user_id' => $userId]);
    $pendingReports = (int) $stmt->fetchColumn();

    // Count ongoing reports.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issue_reports WHERE reporter_id = :user_id AND status IN ('Under Inspection', 'Under Repair')");
    $stmt->execute(['user_id' => $userId]);
    $ongoingReports = (int) $stmt->fetchColumn();

    // Count resolved reports.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issue_reports WHERE reporter_id = :user_id AND status = 'Resolved'");
    $stmt->execute(['user_id' => $userId]);
    $resolvedReports = (int) $stmt->fetchColumn();

    // Get the student's five most recent reports.
    $stmt = $pdo->prepare("
        SELECT
            r.report_id, r.reference_no, r.description, r.status, r.created_at,
            c.unit_code, cat.category_name
        FROM issue_reports AS r
        INNER JOIN computer_units AS c ON r.unit_id = c.unit_id
        INNER JOIN issue_categories AS cat ON r.category_id = cat.category_id
        WHERE r.reporter_id = :user_id
        ORDER BY r.created_at DESC, r.report_id DESC
        LIMIT 5
    ");
    $stmt->execute(['user_id' => $userId]);
    $recentReports = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $databaseError = true;
}

// Get a suitable visual style for each report status.
function student_status_class(string $status): string
{
    return match ($status) {
        'Pending' => 'status-pending',
        'Under Inspection' => 'status-inspection',
        'Under Repair' => 'status-repair',
        'Resolved' => 'status-resolved',
        'Unresolved' => 'status-unresolved',
        default => 'status-default'
    };
}