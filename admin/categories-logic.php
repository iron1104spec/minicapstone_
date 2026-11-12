<?php
// admin/categories-logic.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();

// HELPER FUNCTION
if (!function_exists('pcare_categories_initials')) {
    function pcare_categories_initials(string $name): string {
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

// HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    // ADD CATEGORY
    if ($action === 'add_category') {
        $categoryName = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($categoryName === '') {
            header('Location: categories.php?notice=required');
            exit;
        }

        $duplicateStmt = $pdo->prepare("SELECT category_id FROM issue_categories WHERE LOWER(category_name) = LOWER(:category_name) LIMIT 1");
        $duplicateStmt->execute(['category_name' => $categoryName]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: categories.php?notice=duplicate');
            exit;
        }

        try {
            $insertStmt = $pdo->prepare("INSERT INTO issue_categories (category_name, description, is_active) VALUES (:category_name, :description, 1)");
            $insertStmt->execute(['category_name' => $categoryName, 'description' => $description]);
            header('Location: categories.php?notice=created');
            exit;
        } catch (PDOException $e) {
            error_log('PCare Add Category Error: ' . $e->getMessage());
            header('Location: categories.php?notice=createerror');
            exit;
        }
    }

    // EDIT CATEGORY
    if ($action === 'edit_category') {
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $categoryName = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$categoryId || $categoryName === '') {
            header('Location: categories.php?notice=invalid');
            exit;
        }

        $checkStmt = $pdo->prepare("SELECT category_id FROM issue_categories WHERE category_id = :category_id LIMIT 1");
        $checkStmt->execute(['category_id' => $categoryId]);

        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: categories.php?notice=notfound');
            exit;
        }

        $duplicateStmt = $pdo->prepare("SELECT category_id FROM issue_categories WHERE LOWER(category_name) = LOWER(:category_name) AND category_id <> :category_id LIMIT 1");
        $duplicateStmt->execute(['category_name' => $categoryName, 'category_id' => $categoryId]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: categories.php?notice=duplicate');
            exit;
        }

        try {
            $updateStmt = $pdo->prepare("UPDATE issue_categories SET category_name = :category_name, description = :description WHERE category_id = :category_id");
            $updateStmt->execute(['category_name' => $categoryName, 'description' => $description, 'category_id' => $categoryId]);
            header('Location: categories.php?notice=updated');
            exit;
        } catch (PDOException $e) {
            error_log('PCare Edit Category Error: ' . $e->getMessage());
            header('Location: categories.php?notice=editerror');
            exit;
        }
    }

    // ACTIVATE / DEACTIVATE CATEGORY
    if ($action === 'change_status') {
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $newStatus = isset($_POST['new_status']) ? (int) $_POST['new_status'] : -1;

        if (!$categoryId || !in_array($newStatus, [0, 1], true)) {
            header('Location: categories.php?notice=invalid');
            exit;
        }

        $checkStmt = $pdo->prepare("SELECT category_id, is_active FROM issue_categories WHERE category_id = :category_id LIMIT 1");
        $checkStmt->execute(['category_id' => $categoryId]);
        $category = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            header('Location: categories.php?notice=notfound');
            exit;
        }

        if ((int) $category['is_active'] === $newStatus) {
            header('Location: categories.php?notice=unchanged');
            exit;
        }

        $updateStmt = $pdo->prepare("UPDATE issue_categories SET is_active = :status WHERE category_id = :category_id");
        $updateStmt->execute(['status' => $newStatus, 'category_id' => $categoryId]);
        
        header('Location: categories.php?notice=' . ($newStatus === 1 ? 'activated' : 'deactivated'));
        exit;
    }

    header('Location: categories.php?notice=invalid');
    exit;
}

// SEARCH AND FILTER
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT category_id, category_name, description, is_active FROM issue_categories WHERE 1 = 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (category_name LIKE :search_name OR description LIKE :search_description)";
    $term = '%' . $search . '%';
    $params['search_name'] = $term;
    $params['search_description'] = $term;
}

if ($status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($status === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY category_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CATEGORY STATISTICS
$stats = $pdo->query("
    SELECT COUNT(*) AS total, COALESCE(SUM(is_active = 1), 0) AS active, COALESCE(SUM(is_active = 0), 0) AS inactive
    FROM issue_categories
")->fetch(PDO::FETCH_ASSOC);

// NOTIFICATIONS
$notices = [
    'created' => ['type' => 'success', 'message' => 'The issue category was created successfully.'],
    'updated' => ['type' => 'success', 'message' => 'The issue category was updated successfully.'],
    'activated' => ['type' => 'success', 'message' => 'The issue category was activated successfully.'],
    'deactivated' => ['type' => 'success', 'message' => 'The issue category was deactivated successfully.'],
    'required' => ['type' => 'danger', 'message' => 'Please enter a category name.'],
    'duplicate' => ['type' => 'warning', 'message' => 'This category name already exists.'],
    'invalid' => ['type' => 'danger', 'message' => 'Invalid category action. Please try again.'],
    'notfound' => ['type' => 'danger', 'message' => 'The selected category could not be found.'],
    'createerror' => ['type' => 'danger', 'message' => 'Unable to create the category. Please check the server error log.'],
    'editerror' => ['type' => 'danger', 'message' => 'Unable to update the category. Please check the server error log.'],
    'unchanged' => ['type' => 'info', 'message' => 'The category already has the selected status.']
];

$notice = $_GET['notice'] ?? '';
$notification = $notices[$notice] ?? null;