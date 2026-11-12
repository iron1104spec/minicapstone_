<?php
// it-personnel/issue-history-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();

// Search and filter values
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$allowedStatuses = [
    'Pending',
    'Under Inspection',
    'Under Repair',
    'Resolved',
    'Unresolved'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

// Build query
$sql = "
    SELECT
        h.history_id,
        h.old_status,
        h.new_status,
        h.remarks,
        h.changed_at,

        r.report_id,
        r.reference_no,
        u.unit_code,

        changer.full_name AS changed_by_name

    FROM issue_status_history h

    INNER JOIN issue_reports r
        ON h.report_id = r.report_id

    INNER JOIN computer_units u
        ON r.unit_id = u.unit_id

    LEFT JOIN users changer
        ON h.changed_by = changer.user_id

    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            r.reference_no LIKE :search
            OR u.unit_code LIKE :search
            OR changer.full_name LIKE :search
        )
    ";

    $params['search'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
    $sql .= " AND h.new_status = :status";
    $params['status'] = $statusFilter;
}

$sql .= " ORDER BY h.changed_at DESC, h.history_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total history records
$totalHistory = count($histories);

// Status badge styling
function historyStatusClass(string $status): string
{
    switch ($status) {
        case 'Pending':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Under Inspection':
            return 'bg-info-subtle text-info-emphasis';

        case 'Under Repair':
            return 'bg-primary-subtle text-primary';

        case 'Resolved':
            return 'bg-success-subtle text-success';

        case 'Unresolved':
            return 'bg-danger-subtle text-danger';

        default:
            return 'bg-secondary-subtle text-secondary';
    }
}