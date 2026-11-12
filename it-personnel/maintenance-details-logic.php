<?php
// it-personnel/maintenance-details-logic.php

require_once __DIR__ . '/../includes/auth.php';

require_role('personnel');

$pdo = db();

// Get maintenance record ID from URL
$maintenanceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$maintenanceId || $maintenanceId <= 0) {
    http_response_code(400);
    exit('Invalid maintenance record ID.');
}

// Retrieve complete maintenance record
$stmt = $pdo->prepare("
    SELECT
        mr.maintenance_id,
        mr.inspection_date,
        mr.maintenance_date,
        mr.findings,
        mr.action_taken,
        mr.repair_result,
        mr.created_at,

        ir.report_id,
        ir.reference_no,
        ir.description AS issue_description,
        ir.status AS issue_status,

        cu.unit_code,
        cu.location,

        personnel.full_name AS personnel_name,
        personnel.email AS personnel_email

    FROM maintenance_records mr

    INNER JOIN issue_reports ir
        ON mr.report_id = ir.report_id

    INNER JOIN computer_units cu
        ON ir.unit_id = cu.unit_id

    INNER JOIN users personnel
        ON mr.personnel_id = personnel.user_id

    WHERE mr.maintenance_id = :maintenance_id

    LIMIT 1
");

$stmt->execute([
    'maintenance_id' => $maintenanceId
]);

$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    http_response_code(404);
    exit('Maintenance record not found.');
}

// Date formatting helper
function formatDate($value): string
{
    if (empty($value)) {
        return 'Not recorded';
    }

    return date('F d, Y - h:i A', strtotime($value));
}