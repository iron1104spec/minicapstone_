<?php
// it-personnel/maintenance-records-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('personnel');

$pdo = db();
$personnelId = (int) $_SESSION['user_id'];

$success = '';
$error = '';

// Process maintenance record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid form request. Please refresh the page and try again.';
    } else {
        $reportId = filter_var($_POST['report_id'] ?? null, FILTER_VALIDATE_INT);
        $inspectionDate = trim($_POST['inspection_date'] ?? '');
        $maintenanceDate = trim($_POST['maintenance_date'] ?? '');
        $findings = trim($_POST['findings'] ?? '');
        $actionTaken = trim($_POST['action_taken'] ?? '');
        $repairResult = trim($_POST['repair_result'] ?? '');

        // Validate date fields
        $validateDate = function ($value) {
            if ($value === '') return null;
            $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
            if (!$date || $date->format('Y-m-d\TH:i') !== $value) return false;
            return $date->format('Y-m-d H:i:s');
        };

        $inspectionDateValue = $validateDate($inspectionDate);
        $maintenanceDateValue = $validateDate($maintenanceDate);

        if (!$reportId || $reportId <= 0) {
            $error = 'Please select a valid issue report.';
        } elseif ($inspectionDateValue === false || $maintenanceDateValue === false) {
            $error = 'Please enter valid inspection and maintenance dates.';
        } elseif ($inspectionDateValue !== null && $maintenanceDateValue !== null && strtotime($maintenanceDateValue) < strtotime($inspectionDateValue)) {
            $error = 'Maintenance date cannot be earlier than the inspection date.';
        } elseif ($findings === '' || $actionTaken === '' || $repairResult === '') {
            $error = 'Please complete the findings, actions taken, and repair result.';
        } else {
            try {
                // Confirm that the selected issue report exists
                $checkReport = $pdo->prepare("SELECT report_id FROM issue_reports WHERE report_id = :report_id LIMIT 1");
                $checkReport->execute(['report_id' => $reportId]);

                if (!$checkReport->fetch()) {
                    $error = 'The selected issue report does not exist.';
                } else {
                    // Save maintenance record
                    $stmt = $pdo->prepare("
                        INSERT INTO maintenance_records (
                            report_id, personnel_id, inspection_date, maintenance_date, findings, action_taken, repair_result
                        ) VALUES (
                            :report_id, :personnel_id, :inspection_date, :maintenance_date, :findings, :action_taken, :repair_result
                        )
                    ");

                    $stmt->execute([
                        'report_id' => $reportId,
                        'personnel_id' => $personnelId,
                        'inspection_date' => $inspectionDateValue,
                        'maintenance_date' => $maintenanceDateValue,
                        'findings' => $findings,
                        'action_taken' => $actionTaken,
                        'repair_result' => $repairResult
                    ]);

                    flash_set('success', 'Maintenance record saved successfully.');
                    redirect('/minicapstone/it-personnel/maintenance-records.php');
                }
            } catch (PDOException $e) {
                error_log('Maintenance record error: ' . $e->getMessage());
                $error = 'Unable to save the maintenance record. Please check the database and try again.';
            }
        }
    }
}

// Retrieve success notification
$success = flash_get('success') ?? '';

// Retrieve issue reports for dropdown
$reportStmt = $pdo->query("
    SELECT ir.report_id, ir.reference_no, ir.status, cu.unit_code
    FROM issue_reports ir
    INNER JOIN computer_units cu ON ir.unit_id = cu.unit_id
    ORDER BY ir.created_at DESC
");
$availableReports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve existing maintenance records
$recordsStmt = $pdo->query("
    SELECT
        mr.maintenance_id, mr.inspection_date, mr.maintenance_date, mr.findings, mr.action_taken, mr.repair_result, mr.created_at,
        ir.reference_no, ir.status, cu.unit_code, personnel.full_name AS personnel_name
    FROM maintenance_records mr
    INNER JOIN issue_reports ir ON mr.report_id = ir.report_id
    INNER JOIN computer_units cu ON ir.unit_id = cu.unit_id
    INNER JOIN users personnel ON mr.personnel_id = personnel.user_id
    ORDER BY mr.created_at DESC
");
$maintenanceRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);