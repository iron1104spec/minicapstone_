<?php
// it-personnel/computer-units-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();

$stmt = $pdo->query("
    SELECT
        unit_id,
        unit_code,
        location,
        unit_status,
        remarks,
        created_at,
        updated_at
    FROM computer_units
    ORDER BY unit_code ASC
");

$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count units by status
$totalUnits = count($units);
$activeUnits = 0;
$maintenanceUnits = 0;
$inactiveUnits = 0;

foreach ($units as $unit) {
    if ($unit['unit_status'] === 'Active') {
        $activeUnits++;
    } elseif ($unit['unit_status'] === 'Under Maintenance') {
        $maintenanceUnits++;
    } elseif ($unit['unit_status'] === 'Inactive') {
        $inactiveUnits++;
    }
}

// Helper function for status classes
function unitStatusClass(string $status): string
{
    switch ($status) {
        case 'Active':
            return 'bg-success-subtle text-success';

        case 'Under Maintenance':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Inactive':
            return 'bg-secondary-subtle text-secondary';

        default:
            return 'bg-light text-dark';
    }
}