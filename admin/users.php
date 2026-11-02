
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');

$pdo = db();

// Search and filter
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedRoles = ['student', 'personnel', 'admin'];

$sql = "
    SELECT
        user_id,
        full_name,
        email,
        role,
        id_number,
        department,
        contact_no,
        is_active,
        created_at
    FROM users
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            full_name LIKE :search_name
            OR email LIKE :search_email
            OR id_number LIKE :search_id
        )
    ";

    $term = '%' . $search . '%';

    $params['search_name'] = $term;
    $params['search_email'] = $term;
    $params['search_id'] = $term;
}

if (in_array($role, $allowedRoles, true)) {
    $sql .= " AND role = :role";
    $params['role'] = $role;
}

if ($status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($status === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// User statistics
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_active = 1) AS active,
        SUM(is_active = 0) AS inactive,
        SUM(role = 'student') AS students,
        SUM(role = 'personnel') AS personnel,
        SUM(role = 'admin') AS administrators
    FROM users
")->fetch(PDO::FETCH_ASSOC);

function userStatusClass($active): string
{
    return (int) $active === 1 ? 'success' : 'secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Management | PCare Administrator</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<nav class="navbar navbar-dark px-4" style="background:#245B54;">
    <a class="navbar-brand fw-bold" href="dashboard.php">
        PCARE / ADMINISTRATOR
    </a>

    <div class="d-flex align-items-center gap-3">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-grid"></i> Dashboard
        </a>

        <a href="../logout.php" class="btn btn-light btn-sm">
            Logout
        </a>
    </div>
</nav>

<main class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">User Management</h2>
            <p class="text-muted mb-0">
                View and monitor registered PCare accounts.
            </p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Total Users</small>
                    <h3 class="fw-bold"><?= (int) $stats['total'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Active</small>
                    <h3 class="fw-bold text-success">
                        <?= (int) $stats['active'] ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Inactive</small>
                    <h3 class="fw-bold text-secondary">
                        <?= (int) $stats['inactive'] ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Students</small>
                    <h3 class="fw-bold">
                        <?= (int) $stats['students'] ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">IT Personnel</small>
                    <h3 class="fw-bold">
                        <?= (int) $stats['personnel'] ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Administrators</small>
                    <h3 class="fw-bold">
                        <?= (int) $stats['administrators'] ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <!-- Search and filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" class="row g-3">

                <div class="col-md-5">
                    <label class="form-label">Search users</label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Name, email, or ID number"
                        value="<?= e($search) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Role</label>

                    <select name="role" class="form-select">
                        <option value="">All Roles</option>

                        <option value="student"
                            <?= $role === 'student' ? 'selected' : '' ?>>
                            Student
                        </option>

                        <option value="personnel"
                            <?= $role === 'personnel' ? 'selected' : '' ?>>
                            IT Personnel
                        </option>

                        <option value="admin"
                            <?= $role === 'admin' ? 'selected' : '' ?>>
                            Administrator
                        </option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Account Status</label>

                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>

                        <option value="active"
                            <?= $status === 'active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="inactive"
                            <?= $status === 'inactive' ? 'selected' : '' ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-success w-100" type="submit">
                        <i class="bi bi-search"></i> Search
                    </button>

                    <a href="users.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>

    <!-- Users table -->
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Registered Accounts</h5>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>ID Number</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($users)): ?>

                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No user accounts found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($users as $user): ?>

                            <tr>
                                <td class="fw-semibold">
                                    <?= e($user['full_name']) ?>
                                </td>

                                <td><?= e($user['email']) ?></td>

                                <td><?= e($user['id_number'] ?? '—') ?></td>

                                <td><?= e($user['department'] ?? '—') ?></td>

                                <td>
                                    <?php
                                    $roleLabels = [
                                        'student' => 'Student',
                                        'personnel' => 'IT Personnel',
                                        'admin' => 'Administrator'
                                    ];
                                    ?>

                                    <span class="badge text-bg-light border">
                                        <?= e($roleLabels[$user['role']] ?? $user['role']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge text-bg-<?= userStatusClass($user['is_active']) ?>">
                                        <?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(date('M d, Y', strtotime($user['created_at']))) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer bg-white text-muted">
            Showing <?= count($users) ?> account(s).
        </div>

    </div>

</main>

</body>
</html>
