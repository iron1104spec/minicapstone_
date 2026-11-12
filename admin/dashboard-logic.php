<?php
// admin/dashboard-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();

// ==========================================
// DASHBOARD STATISTICS
// ==========================================
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$totalUnits = (int) $pdo->query("SELECT COUNT(*) FROM computer_units")->fetchColumn();
$totalReports = (int) $pdo->query("SELECT COUNT(*) FROM issue_reports")->fetchColumn();

$issueStats = $pdo->query("
    SELECT
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status IN ('Under Inspection', 'Under Repair') THEN 1 ELSE 0 END) AS ongoing,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) AS unresolved
    FROM issue_reports
")->fetch(PDO::FETCH_ASSOC);

$pendingReports = (int) ($issueStats['pending'] ?? 0);
$ongoingReports = (int) ($issueStats['ongoing'] ?? 0);
$resolvedReports = (int) ($issueStats['resolved'] ?? 0);
$unresolvedReports = (int) ($issueStats['unresolved'] ?? 0);

$totalMaintenance = (int) $pdo->query("SELECT COUNT(*) FROM maintenance_records")->fetchColumn();

// ==========================================
// RECENT ISSUE REPORTS
// ==========================================
$stmt = $pdo->query("
    SELECT
        r.report_id, r.reference_no, r.status, r.created_at,
        reporter.full_name AS reporter_name, c.unit_code, cat.category_name
    FROM issue_reports r
    INNER JOIN users reporter ON r.reporter_id = reporter.user_id
    INNER JOIN computer_units c ON r.unit_id = c.unit_id
    INNER JOIN issue_categories cat ON r.category_id = cat.category_id
    ORDER BY r.created_at DESC, r.report_id DESC
    LIMIT 5
");

$recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// ADMINISTRATOR INFORMATION
// ==========================================
$adminName = $_SESSION['full_name'] ?? 'Administrator';

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = :user_id LIMIT 1");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $adminName = $admin['full_name'];
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================
function adminStatusClass(string $status): string {
    switch ($status) {
        case 'Pending': return 'pending';
        case 'Under Inspection': return 'inspection';
        case 'Under Repair': return 'repair';
        case 'Resolved': return 'resolved';
        case 'Unresolved': return 'unresolved';
        default: return 'default';
    }
}

function adminInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }
    return $initials ?: 'AD';
}

function statusPercentage(int $value, int $total): int {
    return $total > 0 ? (int) round(($value / $total) * 100) : 0;
}

$adminInitials = adminInitials($adminName);
$statusTotal = $pendingReports + $ongoingReports + $resolvedReports + $unresolvedReports;