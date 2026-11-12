<?php
// it-personnel/issue-reports-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$fullName = $_SESSION['full_name'] ?? 'IT Personnel';
$pdo = db();

// Search and filter values.
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$allowedStatuses = [
    'Pending',
    'Under Inspection',
    'Under Repair',
    'Resolved',
    'Unresolved'
];

$reports = [];
$databaseError = false;

try {
    // Build the reports query.
    $sql = "
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
        WHERE 1 = 1
    ";

    $params = [];

    // Search by reference number, reporter, or computer unit.
    if ($search !== '') {
        $sql .= "
            AND (
                r.reference_no LIKE :search
                OR reporter.full_name LIKE :search
                OR c.unit_code LIKE :search
            )
        ";
        $params['search'] = '%' . $search . '%';
    }

    // Filter by report status.
    if (in_array($statusFilter, $allowedStatuses, true)) {
        $sql .= " AND r.status = :status";
        $params['status'] = $statusFilter;
    }

    $sql .= " ORDER BY r.created_at DESC, r.report_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $databaseError = true;
}

// Status badge colors.
$statusClasses = [
    'Pending' => 'bg-warning text-dark',
    'Under Inspection' => 'bg-info text-dark',
    'Under Repair' => 'bg-primary',
    'Resolved' => 'bg-success',
    'Unresolved' => 'bg-danger'
];