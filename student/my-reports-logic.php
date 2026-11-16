<?php
// student/my-reports-logic.php

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
        INNER JOIN computer_units AS c ON r.unit_id = c.unit_id
        INNER JOIN issue_categories AS cat ON r.category_id = cat.category_id
        WHERE r.reporter_id = :user_id
        ORDER BY r.created_at DESC, r.report_id DESC
    ");

    $stmt->execute(['user_id' => $userId]);
    $reports = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $databaseError = true;
}

// Assign a status class based on report status.
function getStatusClass(string $status): string
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