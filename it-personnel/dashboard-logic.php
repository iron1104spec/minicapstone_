<?php
// it-personnel/dashboard-logic.php

// Load authentication and CSRF protection.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Only active IT Personnel accounts can access this page.
require_role('personnel');

// Get the current personnel information.
$fullName = $_SESSION['full_name'] ?? 'IT Personnel';

// Connect to the database.
$pdo = db();

// Initialize dashboard values.
$totalReports = 0;
$pendingReports = 0;
$ongoingReports = 0;
$resolvedReports = 0;
$unresolvedReports = 0;

$recentReports = [];
$databaseError = false;

try {
    // 1. Get the overall issue report statistics.
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_reports,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_reports,
            SUM(CASE WHEN status IN ('Under Inspection', 'Under Repair') THEN 1 ELSE 0 END) AS ongoing_reports,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_reports,
            SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) AS unresolved_reports
        FROM issue_reports
    ");

    $statistics = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalReports = (int) $statistics['total_reports'];
    $pendingReports = (int) $statistics['pending_reports'];
    $ongoingReports = (int) $statistics['ongoing_reports'];
    $resolvedReports = (int) $statistics['resolved_reports'];
    $unresolvedReports = (int) $statistics['unresolved_reports'];

    // 2. Get the five most recent issue reports.
    $stmt = $pdo->query("
        SELECT
            r.report_id,
            r.reference_no,
            r.description,
            r.status,
            r.created_at,
            reporter.full_name AS reporter_name,
            c.unit_code,
            cat.category_name,
            assigned.full_name AS assigned_name
        FROM issue_reports AS r
        INNER JOIN users AS reporter ON r.reporter_id = reporter.user_id
        INNER JOIN computer_units AS c ON r.unit_id = c.unit_id
        INNER JOIN issue_categories AS cat ON r.category_id = cat.category_id
        LEFT JOIN users AS assigned ON r.assigned_to = assigned.user_id
        ORDER BY r.created_at DESC, r.report_id DESC
        LIMIT 5
    ");

    $recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Record the actual error in the PHP error log.
    error_log($e->getMessage());
    // Display a friendly error message.
    $databaseError = true;
}

// Bootstrap status badge colors.
$statusClasses = [
    'Pending' => 'bg-warning text-dark',
    'Under Inspection' => 'bg-info text-dark',
    'Under Repair' => 'bg-primary',
    'Resolved' => 'bg-success',
    'Unresolved' => 'bg-danger'
];