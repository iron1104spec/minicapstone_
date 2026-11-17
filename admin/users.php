<?php
// admin/users.php
require_once __DIR__ . '/users-logic.php';

// Role-aware UI labels
$uiRoleLabel       = $isSuperAdmin ? 'Super Administrator' : 'Administrator';
$uiRolePortalLabel = $isSuperAdmin ? 'Super Administrator Portal' : 'Administrator Portal';

// UI permission helpers (mirror the backend rules)
$canEditUser = function (array $u) use ($isSuperAdmin, $currentUserId): bool {
    if ($u['role'] === 'super_admin') return false;
    if ($u['role'] === 'admin' && !$isSuperAdmin && (int) $u['user_id'] !== $currentUserId) return false;
    return true;
};
$canChangeStatusUser = function (array $u) use ($isSuperAdmin): bool {
    if ($u['role'] === 'super_admin') return false;
    if ($u['role'] === 'admin' && !$isSuperAdmin) return false;
    return true;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | LabSync</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">

    <!-- PREMIUM CSS -->
    <link rel="stylesheet" href="../assets/css/admin-users.css?v=123">

    <!-- BAGONG LAYOUT CSS (Floating Dock) -->
    <style>
        /* --- TOP HEADER --- */
        .custom-topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #ffffff; display: flex; justify-content: space-between;
            align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        .brand-area { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand-icon { font-size: 24px; color: #0d2f24; }
        .brand-name { display: block; font-weight: 800; font-size: 18px; color: #0d2f24; line-height: 1; }
        .brand-caption { display: block; font-size: 9px; letter-spacing: 1px; color: #666; font-weight: 600; }

        /* --- FLOATING DOCK --- */
        .floating-dock {
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%);
            display: flex; flex-direction: row; gap: 8px; background-color: #0d2f24;
            padding: 12px 24px; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1); z-index: 1000;
            max-width: 95%; overflow-x: auto;
        }
        .floating-dock::-webkit-scrollbar { display: none; }
        .dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #a0b8b0; text-decoration: none; padding: 10px 16px;
            border-radius: 16px; transition: all 0.3s ease; white-space: nowrap;
        }
        .dock-item i { font-size: 20px; margin-bottom: 4px; }
        .dock-item span { font-size: 11px; font-weight: 500; }
        .dock-item:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; transform: translateY(-4px); }
        .dock-item.active { background-color: #1a4a3a; color: #ffffff; }

        /* --- FIX CONTENT SPACING --- */
        .main-wrapper {
            margin-left: 0 !important;
            padding-top: 70px; 
            padding-bottom: 120px; 
            width: 100%;
        }
    </style>
</head>
<body>

<!-- BAGONG TOP HEADER (Logo & Profile) -->
<header class="custom-topbar">
    <a href="dashboard.php" class="brand-area">
        <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
        <span>
            <span class="brand-name">LabSync</span>
            <span class="brand-caption">LAB MANAGEMENT SYSTEM</span>
        </span>
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-date d-none d-md-inline text-muted" style="font-size: 14px;">
            <i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?>
        </span>
        <div class="d-flex align-items-center gap-2 border-start ps-3">
            <div class="topbar-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold; font-size: 14px;">
                <?= e(pcare_user_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2;"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;"><?= e($uiRoleLabel) ?></div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Sign Out">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    <main class="main-content">
        <!-- HERO BANNER -->
        <section class="welcome-section">
            <div>
                <div class="welcome-label">ACCOUNT ADMINISTRATION</div>
                <h1 class="welcome-title">User Management</h1>
                <p class="welcome-description">
                    Manage registered accounts, user roles, and system access. Ensure that every student, personnel, and administrator has the correct permissions.
                </p>
            </div>
            <button type="button" class="report-button" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Add New User
            </button>
        </section>

        <!-- NOTIFICATIONS -->
        <?php if ($notification): ?>
            <div class="alert alert-<?= e($notification['type']) ?> alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <?= e($notification['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- USER STATISTICS -->
        <?php
        $statCards = [
            ['Total Users', $stats['total'], 'bi-people'],
            ['Active Users', $stats['active'], 'bi-person-check'],
            ['Inactive Users', $stats['inactive'], 'bi-person-x'],
            ['Students', $stats['students'], 'bi-mortarboard'],
            ['IT Personnel', $stats['personnel'], 'bi-tools'],
            ['Administrators', $stats['administrators'], 'bi-shield-check']
        ];
        ?>

        <div class="row g-3 mb-4">
            <?php foreach ($statCards as $card): ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="bi <?= e($card[2]) ?>"></i></div>
                        </div>
                        <div class="stat-value"><?= (int) $card[1] ?></div>
                        <div class="stat-label"><?= e($card[0]) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- SEARCH AND FILTERS -->
        <section class="content-card mb-4">
            <div class="card-heading">
                <h5 class="card-title"><i class="bi bi-funnel me-2"></i> Search and Filters</h5>
                <span class="card-subtitle">Find accounts using their name, email, ID number, role, or status.</span>
            </div>
            <div class="filter-body">
                <form method="GET" class="row g-3">
                    <div class="col-12 col-lg-5">
                        <label class="form-label">Search Users</label>
                        <input type="text" name="search" class="form-control" placeholder="Enter name, email, or ID number" value="<?= e($search) ?>">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">User Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="personnel" <?= $role === 'personnel' ? 'selected' : '' ?>>IT Personnel</option>
                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                            <?php if ($isSuperAdmin): ?>
                                <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Administrator</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn-primary-custom flex-grow-1" style="justify-content: center;"><i class="bi bi-search"></i> Apply Filters</button>
                        <a href="users.php" class="btn-light-custom">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- REGISTERED USERS TABLE -->
        <section class="content-card">
            <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title">Registered Accounts</h5>
                    <span class="card-subtitle">Review user information and manage account access.</span>
                </div>
                <span class="result-count"><?= count($users) ?> RESULT(S)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User Information</th>
                            <th>ID Number</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                            <th>Account Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <strong class="d-block text-dark">No user accounts found</strong>
                                    <span>Try adjusting your search or filter settings.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-table">
                                                <?= e(pcare_user_initials($user['full_name'])) ?>
                                            </div>
                                            <div>
                                                <div class="user-name"><?= e($user['full_name']) ?></div>
                                                <div class="user-email"><?= e($user['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($user['id_number'] ?? '—') ?></td>
                                    <td><?= e($user['department'] ?? '—') ?></td>
                                    <td>
                                        <span class="role-badge <?= e($user['role']) ?>">
                                            <?= e($roleLabels[$user['role']] ?? $user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= (int) $user['is_active'] === 1 ? 'active' : 'inactive' ?>">
                                            <span class="status-dot"></span>
                                            <?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= e(date('M d, Y', strtotime($user['created_at']))) ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <!-- EDIT BUTTON -->
                                            <?php if ($canEditUser($user)): ?>
                                                <button type="button" class="action-button btn-edit" data-bs-toggle="modal" data-bs-target="#editUserModal<?= (int) $user['user_id'] ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                            <?php endif; ?>

                                            <!-- ACTIVE ACCOUNT -->
                                            <?php if ((int) $user['is_active'] === 1): ?>
                                                <?php if ((int) $user['user_id'] === $currentUserId): ?>
                                                    <span class="current-account"><i class="bi bi-shield-check me-1"></i> Current account</span>
                                                <?php elseif ($canChangeStatusUser($user)): ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate this account? The user will no longer be able to log in.');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="change_status">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                        <input type="hidden" name="new_status" value="0">
                                                        <button type="submit" class="action-button btn-deactivate"><i class="bi bi-person-x"></i> Deactivate</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="current-account" title="Super Administrator accounts cannot be managed through this module.">
                                                        <i class="bi bi-shield-lock me-1"></i> Restricted
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- INACTIVE ACCOUNT -->
                                                <?php if ($canChangeStatusUser($user)): ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to activate this account?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="change_status">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                        <input type="hidden" name="new_status" value="1">
                                                        <button type="submit" class="action-button btn-activate"><i class="bi bi-person-check"></i> Activate</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="current-account" title="Super Administrator accounts cannot be managed through this module.">
                                                        <i class="bi bi-shield-lock me-1"></i> Restricted
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="bi bi-info-circle me-1"></i> Showing <?= count($users) ?> account(s). User records are preserved when accounts are deactivated.
            </div>
        </section>

        <footer class="page-footer">
            LabSync · <?= e($uiRolePortalLabel) ?> · SLSU Lucena Campus
        </footer>
    </main>
</div>

<!-- EDIT USER MODALS -->
<?php foreach ($users as $user): ?>
    <?php if ($canEditUser($user)): ?>
    <div class="modal fade" id="editUserModal<?= (int) $user['user_id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= (int) $user['user_id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header px-4 py-3">
                    <div>
                        <h5 class="modal-title" id="editUserModalLabel<?= (int) $user['user_id'] ?>"><i class="bi bi-pencil-square me-2"></i> Edit User Information</h5>
                        <div class="modal-description">Update the selected account details.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" maxlength="150" value="<?= e($user['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" maxlength="150" value="<?= e($user['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" class="form-control" maxlength="50" value="<?= e($user['id_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" class="form-control" maxlength="100" value="<?= e($user['department'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_no" class="form-control" maxlength="30" value="<?= e($user['contact_no'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Account Role</label>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <input type="text" class="form-control" value="Administrator" disabled>
                                    <input type="hidden" name="role" value="admin">
                                    <small class="text-secondary d-block mt-2">Administrator roles cannot be changed through this form.</small>
                                <?php else: ?>
                                    <select name="role" class="form-select" required>
                                        <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="personnel" <?= $user['role'] === 'personnel' ? 'selected' : '' ?>>IT Personnel</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="password-note mt-4">
                            <i class="bi bi-shield-lock me-2"></i>
                            Passwords are not changed in this form. The user's current password will remain unchanged.
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn-light-custom px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary-custom px-4"><i class="bi bi-check2-circle"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header px-4 py-3">
                <div>
                    <h5 class="modal-title" id="addUserModalLabel"><i class="bi bi-person-plus me-2"></i> Create User Account</h5>
                    <div class="modal-description">
                        <?= $isSuperAdmin
                            ? 'Add a Student, IT Personnel, or Administrator account.'
                            : 'Add a Student or IT Personnel account.' ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" maxlength="150" placeholder="Enter complete name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" maxlength="150" placeholder="example@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID Number</label>
                            <input type="text" name="id_number" class="form-control" maxlength="50" placeholder="Enter ID number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" maxlength="100" placeholder="Enter department" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_no" class="form-control" maxlength="30" placeholder="Enter contact number" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select account role</option>
                                <option value="student">Student</option>
                                <option value="personnel">IT Personnel</option>
                                <?php if ($isSuperAdmin): ?>
                                    <option value="admin">Administrator</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-secondary d-block mt-2">
                                <?= $isSuperAdmin
                                    ? 'As a Super Administrator, you may also create Administrator accounts.'
                                    : 'Administrator accounts cannot be created through this form.' ?>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" minlength="8" placeholder="At least 8 characters" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" placeholder="Re-enter password" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="password-note mt-4">
                        <i class="bi bi-shield-lock me-2"></i>
                        New accounts created by the administrator will be active. Passwords are securely hashed before being stored.
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn-light-custom px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom px-4"><i class="bi bi-person-plus"></i> Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FLOATING BOTTOM DOCK (Menu) -->
<nav class="floating-dock">
    <a href="dashboard.php" class="dock-item">
        <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="users.php" class="dock-item active">
        <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="computer-units.php" class="dock-item">
        <i class="bi bi-pc-display"></i><span>Computers</span>
    </a>
    <a href="categories.php" class="dock-item">
        <i class="bi bi-grid"></i><span>Categories</span>
    </a>
    <a href="issue-reports.php" class="dock-item">
        <i class="bi bi-clipboard-data"></i><span>Reports</span>
    </a>
    <a href="maintenance-records.php" class="dock-item">
        <i class="bi bi-tools"></i><span>Maintenance</span>
    </a>
    <a href="analytics.php" class="dock-item">
        <i class="bi bi-bar-chart-line"></i><span>Analytics</span>
    </a>
</nav>

<!-- LOCAL BOOTSTRAP JS -->
<script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>

<!-- External JS -->
<script src="../assets/js/admin-users.js"></script>

</body>
</html>