<?php
// admin/maintenance-records-logic.php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

// HELPER FUNCTIONS
if (!function_exists('pcare_maintenance_date')) {
    function pcare_maintenance_date(?string $date): string {
        if (empty($date)) return '—';
        $timestamp = strtotime($date);
        return $timestamp ? date('M d, Y h:i A', $timestamp) : '—';
    }
}

if (!function_exists('pcare_maintenance_initials')) {
    function pcare_maintenance_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'A';
    }
}

if (!function_exists('pcare_maintenance_status_class')) {
    function pcare_maintenance_status_class(string $status): string {
        $classes = [
            'Pending' => 'pending',
            'Under Inspection' => 'inspection',
            'Under Repair' => 'repair',
            'Resolved' => 'resolved',
            'Unresolved' => 'unresolved'
        ];
        return $classes[$status] ?? 'default';
    }
}

// GET FILTER VALUES
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$unitFilter = filter_input(INPUT_GET, 'unit', FILTER_VALIDATE_INT);

$allowedStatuses = ['Pending', 'Under Inspection', 'Under Repair', 'Resolved', 'Unresolved'];

// CATEGORY OPTIONS
$categoryStmt = $pdo->query("SELECT category_id, category_name FROM issue_categories ORDER BY category_name ASC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// COMPUTER UNIT OPTIONS
$unitStmt = $pdo->query("SELECT unit_id, unit_code FROM computer_units ORDER BY unit_code ASC");
$units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH MAINTENANCE RECORDS
$sql = "
    SELECT mr.maintenance_id, mr.report_id, mr.personnel_id, mr.inspection_date, mr.maintenance_date, mr.findings, mr.action_taken, mr.repair_result, mr.created_at,
           ir.reference_no, ir.description AS issue_description, ir.status AS issue_status,
           cu.unit_code, ic.category_name,
           personnel.full_name AS personnel_name, personnel.email AS personnel_email
    FROM maintenance_records mr
    LEFT JOIN issue_reports ir ON mr.report_id = ir.report_id
    LEFT JOIN computer_units cu ON ir.unit_id = cu.unit_id
    LEFT JOIN issue_categories ic ON ir.category_id = ic.category_id
    LEFT JOIN users personnel ON mr.personnel_id = personnel.user_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (ir.reference_no LIKE :reference_search OR personnel.full_name LIKE :personnel_search OR personnel.email LIKE :email_search OR cu.unit_code LIKE :unit_search OR ic.category_name LIKE :category_search OR mr.findings LIKE :findings_search OR mr.action_taken LIKE :actions_search OR mr.repair_result LIKE :result_search)";
    $searchTerm = '%' . $search . '%';
    $params['reference_search'] = $searchTerm;
    $params['personnel_search'] = $searchTerm;
    $params['email_search'] = $searchTerm;
    $params['unit_search'] = $searchTerm;
    $params['category_search'] = $searchTerm;
    $params['findings_search'] = $searchTerm;
    $params['actions_search'] = $searchTerm;
    $params['result_search'] = $searchTerm;
}

if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= " AND ir.status = :status";
    $params['status'] = $statusFilter;
}

if ($categoryFilter !== false && $categoryFilter !== null && $categoryFilter > 0) {
    $sql .= " AND ir.category_id = :category_id";
    $params['category_id'] = $categoryFilter;
}

if ($unitFilter !== false && $unitFilter !== null && $unitFilter > 0) {
    $sql .= " AND ir.unit_id = :unit_id";
    $params['unit_id'] = $unitFilter;
}

$sql .= " ORDER BY COALESCE(mr.maintenance_date, mr.inspection_date, mr.created_at) DESC, mr.maintenance_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$maintenanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// MAINTENANCE STATISTICS
$stats = $pdo->query("
    SELECT COUNT(*) AS total, COUNT(DISTINCT personnel_id) AS personnel_count, COALESCE(SUM(inspection_date IS NOT NULL), 0) AS inspected, COALESCE(SUM(maintenance_date IS NOT NULL), 0) AS maintained
    FROM maintenance_records
")->fetch(PDO::FETCH_ASSOC);

// ISSUE STATUS LABELS
$statusLabels = [
    'Pending' => 'Pending',
    'Under Inspection' => 'Under Inspection',
    'Under Repair' => 'Under Repair',
    'Resolved' => 'Resolved',
    'Unresolved' => 'Unresolved'
];