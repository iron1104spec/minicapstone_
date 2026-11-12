<?php
// admin/analytics-logic.php

require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = db();

// HELPER FUNCTION
if (!function_exists('pcare_analytics_initials')) {
    function pcare_analytics_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        return $initials ?: 'A';
    }
}

// DEFAULT VALUES
$dbError = false;
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'unresolved' => 0,
    'maintenance' => 0
];

$monthlyLabels = [];
$monthlyData = [];
$categoryLabels = [];
$categoryData = [];
$unitLabels = [];
$unitData = [];
$statusLabels = [];
$statusData = [];

// DATABASE ANALYTICS
try {
    // ISSUE REPORT STATISTICS
    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(status = 'Pending'), 0) AS pending,
            COALESCE(SUM(status IN ('Under Inspection', 'Under Repair')), 0) AS in_progress,
            COALESCE(SUM(status = 'Resolved'), 0) AS resolved,
            COALESCE(SUM(status = 'Unresolved'), 0) AS unresolved
        FROM issue_reports
    ");

    $reportStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $stats['total'] = (int) $reportStats['total'];
    $stats['pending'] = (int) $reportStats['pending'];
    $stats['in_progress'] = (int) $reportStats['in_progress'];
    $stats['resolved'] = (int) $reportStats['resolved'];
    $stats['unresolved'] = (int) $reportStats['unresolved'];

    // MAINTENANCE RECORD COUNT
    $maintenanceStmt = $pdo->query("SELECT COUNT(*) FROM maintenance_records");
    $stats['maintenance'] = (int) $maintenanceStmt->fetchColumn();

    // MONTHLY REPORTS - LAST 12 MONTHS
    $startMonth = new DateTimeImmutable(date('Y-m-01', strtotime('-11 months')));
    $startDate = $startMonth->format('Y-m-d');

    $monthlyStmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
        FROM issue_reports
        WHERE created_at >= :start_date
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month_key ASC
    ");

    $monthlyStmt->execute(['start_date' => $startDate]);
    $monthlyRows = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    $monthlyMap = [];
    foreach ($monthlyRows as $row) {
        $monthlyMap[$row['month_key']] = (int) $row['total'];
    }

    $monthCursor = $startMonth;
    for ($i = 0; $i < 12; $i++) {
        $monthKey = $monthCursor->format('Y-m');
        $monthlyLabels[] = $monthCursor->format('M Y');
        $monthlyData[] = $monthlyMap[$monthKey] ?? 0;
        $monthCursor = $monthCursor->modify('+1 month');
    }

    // REPORTS BY CATEGORY
    $categoryStmt = $pdo->query("
        SELECT COALESCE(ic.category_name, 'Uncategorized') AS category_name, COUNT(ir.report_id) AS total
        FROM issue_reports ir
        LEFT JOIN issue_categories ic ON ir.category_id = ic.category_id
        GROUP BY ic.category_id, ic.category_name
        ORDER BY total DESC
    ");

    foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $categoryLabels[] = $row['category_name'];
        $categoryData[] = (int) $row['total'];
    }

    // REPORTS BY COMPUTER UNIT
    $unitStmt = $pdo->query("
        SELECT COALESCE(cu.unit_code, 'Unknown Unit') AS unit_code, COUNT(ir.report_id) AS total
        FROM issue_reports ir
        LEFT JOIN computer_units cu ON ir.unit_id = cu.unit_id
        GROUP BY cu.unit_id, cu.unit_code
        ORDER BY total DESC, unit_code ASC
    ");

    foreach ($unitStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $unitLabels[] = $row['unit_code'];
        $unitData[] = (int) $row['total'];
    }

    // REPORTS BY STATUS
    $statusStmt = $pdo->query("
        SELECT status, COUNT(*) AS total
        FROM issue_reports
        GROUP BY status
        ORDER BY total DESC
    ");

    foreach ($statusStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $statusLabels[] = $row['status'] ?: 'Unknown';
        $statusData[] = (int) $row['total'];
    }

} catch (PDOException $e) {
    error_log('PCare Analytics Error: ' . $e->getMessage());
    $dbError = true;
}

// CALCULATE RESOLUTION RATE
$resolutionRate = $stats['total'] > 0 ? round(($stats['resolved'] / $stats['total']) * 100, 1) : 0;

$startLabel = date('M Y', strtotime('-11 months'));
$endLabel = date('M Y');