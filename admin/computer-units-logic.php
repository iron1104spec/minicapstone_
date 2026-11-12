<?php
// admin/computer-units-logic.php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

// Retrieve computer units from the existing database.
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

// Database-driven statistics.
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(unit_status = 'Active') AS active,
        SUM(unit_status = 'Under Maintenance') AS maintenance,
        SUM(unit_status = 'Inactive') AS inactive
    FROM computer_units
")->fetch(PDO::FETCH_ASSOC);

function unitStatusClass(string $status): string
{
    return match ($status) {
        'Active' => 'active',
        'Under Maintenance' => 'maintenance',
        'Inactive' => 'inactive',
        default => 'unknown'
    };
}

function unitStatusIcon(string $status): string
{
    return match ($status) {
        'Active' => 'bi-check-circle',
        'Under Maintenance' => 'bi-tools',
        'Inactive' => 'bi-x-circle',
        default => 'bi-question-circle'
    };
}