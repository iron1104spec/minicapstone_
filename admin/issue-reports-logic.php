<?php
// admin/issue-reports-logic.php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

// HELPER FUNCTIONS
if (!function_exists('pcare_report_status_class')) {
    function pcare_report_status_class(string $status): string {
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

if (!function_exists('pcare_report_date')) {
    function pcare_report_date(?string $date): string {
        if (empty($date)) return '—';
        $timestamp = strtotime($date);
        return $timestamp ? date('M d, Y h:i A', $timestamp) : '—';
    }
}

if (!function_exists('pcare_report_initials')) {
    function pcare_report_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'U';
    }
}

// GET FILTER VALUES
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$unitFilter = filter_input(INPUT_GET, 'unit', FILTER_VALIDATE_INT);

$allowedStatuses = ['Pending', 'Under Inspection', 'Under Repair', 'Resolved', 'Unresolved'];

// GET CATEGORY OPTIONS
$categoryStmt = $pdo->query("SELECT category_id, category_name FROM issue_categories ORDER BY category_name ASC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// GET COMPUTER UNIT OPTIONS
$unitStmt = $pdo->query("SELECT unit_id, unit_code FROM computer_units ORDER BY unit_code ASC");
$units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH ISSUE REPORTS
$sql = "
    SELECT ir.report_id, ir.reference_no, ir.reporter_id, ir.unit_id, ir.category_id, ir.description, ir.photo_path, ir.status, ir.assigned_to, ir.created_at, ir.updated_at, ir.resolved_at,
           reporter.full_name AS reporter_name, reporter.email AS reporter_email,
           cu.unit_code, ic.category_name, assigned.full_name AS assigned_personnel
    FROM issue_reports ir
    LEFT JOIN users reporter ON ir.reporter_id = reporter.user_id
    LEFT JOIN computer_units cu ON ir.unit_id = cu.unit_id
    LEFT JOIN issue_categories ic ON ir.category_id = ic.category_id
    LEFT JOIN users assigned ON ir.assigned_to = assigned.user_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (ir.reference_no LIKE :reference_search OR reporter.full_name LIKE :name_search OR reporter.email LIKE :email_search OR cu.unit_code LIKE :unit_search OR ic.category_name LIKE :category_search OR ir.description LIKE :description_search)";
    $searchTerm = '%' . $search . '%';
    $params['reference_search'] = $searchTerm;
    $params['name_search'] = $searchTerm;
    $params['email_search'] = $searchTerm;
    $params['unit_search'] = $searchTerm;
    $params['category_search'] = $searchTerm;
    $params['description_search'] = $searchTerm;
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

$sql .= " ORDER BY ir.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ISSUE REPORT STATISTICS
$stats = $pdo->query("
    SELECT COUNT(*) AS total, COALESCE(SUM(status = 'Pending'), 0) AS pending, COALESCE(SUM(status = 'Under Inspection'), 0) AS inspection, COALESCE(SUM(status = 'Under Repair'), 0) AS repair, COALESCE(SUM(status = 'Resolved'), 0) AS resolved, COALESCE(SUM(status = 'Unresolved'), 0) AS unresolved
    FROM issue_reports
")->fetch(PDO::FETCH_ASSOC);

// FETCH ISSUE STATUS HISTORY
$historyStmt = $pdo->query("
    SELECT h.history_id, h.report_id, h.old_status, h.new_status, h.changed_by, h.remarks, h.changed_at, changer.full_name AS changed_by_name
    FROM issue_status_history h
    LEFT JOIN users changer ON h.changed_by = changer.user_id
    ORDER BY h.changed_at DESC
");

$allHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
$reportHistory = [];

foreach ($allHistory as $history) {
    $reportId = (int) $history['report_id'];
    if (!isset($reportHistory[$reportId])) {
        $reportHistory[$reportId] = [];
    }
    $reportHistory[$reportId][] = $history;
}

// STATUS LABELS
$statusLabels = [
    'Pending' => 'Pending',
    'Under Inspection' => 'Under Inspection',
    'Under Repair' => 'Under Repair',
    'Resolved' => 'Resolved',
    'Unresolved' => 'Unresolved'
];