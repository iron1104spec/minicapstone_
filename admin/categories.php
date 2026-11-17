<?php
// admin/categories.php
require_once __DIR__ . '/categories-logic.php';

// Safe fallback para sa avatar function (kung wala sa logic file)
if (!function_exists('pcare_categories_initials')) {
    function pcare_categories_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'U';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Categories | LabSync</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- LOCAL BOOTSTRAP & ICONS -->
    <link href="../assets/vendor/bootstrap.min.css?v=123" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons.min.css?v=123" rel="stylesheet">

    <!-- PREMIUM CSS -->
    <link rel="stylesheet" href="../assets/css/admin-categories.css?v=123">

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
                <?= e(pcare_categories_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
            </div>
            <div class="d-none d-sm-block">
                <div class="profile-name" style="font-size: 14px; font-weight: 600; margin: 0; line-height: 1.2;"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                <div class="profile-role" style="font-size: 12px; color: #666; margin: 0;"><?= is_super_admin() ? 'Super Administrator' : 'System Administrator' ?></div>
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
                <div class="welcome-label">SYSTEM CONFIGURATION</div>
                <h1 class="welcome-title">Issue Categories</h1>
                <p class="welcome-description">
                    Manage the categories that users can select when reporting computer issues. Keep the list organized to help IT personnel resolve problems faster.
                </p>
            </div>
            <button type="button" class="report-button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg"></i> Add Category
            </button>
        </section>

        <!-- NOTIFICATIONS -->
        <?php if (isset($notification) && $notification): ?>
            <div class="alert alert-<?= e($notification['type']) ?> alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <?= e($notification['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- STATISTICS -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-tags"></i></div>
                    </div>
                    <div class="stat-value"><?= (int) $stats['total'] ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    </div>
                    <div class="stat-value"><?= (int) $stats['active'] ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-pause-circle"></i></div>
                    </div>
                    <div class="stat-value"><?= (int) $stats['inactive'] ?></div>
                    <div class="stat-label">Inactive Categories</div>
                </div>
            </div>
        </div>

        <!-- SEARCH AND FILTERS -->
        <section class="content-card mb-4">
            <div class="card-heading">
                <h5 class="card-title"><i class="bi bi-funnel me-2"></i> Search and Filters</h5>
                <span class="card-subtitle">Search by category name or description.</span>
            </div>
            <div class="filter-body">
                <form method="GET" class="row g-3">
                    <div class="col-12 col-lg-7">
                        <label class="form-label">Search Category</label>
                        <input type="text" name="search" class="form-control" placeholder="Enter category name or description" value="<?= e($search) ?>">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn-primary-custom flex-grow-1" style="justify-content: center;"><i class="bi bi-search"></i> Apply</button>
                        <a href="categories.php" class="btn-light-custom">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- CATEGORY TABLE -->
        <section class="content-card">
            <div class="card-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title">Registered Categories</h5>
                    <span class="card-subtitle">View and manage the issue categories stored in LabSync.</span>
                </div>
                <span class="result-count"><?= count($categories) ?> RESULT(S)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="bi bi-tags"></i>
                                    <strong class="d-block text-dark">No categories found</strong>
                                    <span>Try changing your search or add a new category.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-table" style="background: var(--soft-mint); color: var(--deep-green);">
                                                <i class="bi bi-tag"></i>
                                            </div>
                                            <div>
                                                <div class="user-name"><?= e($category['category_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-description">
                                            <?= e($category['description'] ?: 'No description provided.') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= (int) $category['is_active'] === 1 ? 'active' : 'inactive' ?>">
                                            <span class="status-dot"></span>
                                            <?= (int) $category['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <!-- EDIT BUTTON -->
                                            <button type="button" class="action-button btn-edit" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= (int) $category['category_id'] ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>

                                            <!-- ACTIVATE / DEACTIVATE -->
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to <?= (int) $category['is_active'] === 1 ? 'deactivate' : 'activate' ?> this category?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= (int) $category['is_active'] === 1 ? 0 : 1 ?>">

                                                <?php if ((int) $category['is_active'] === 1): ?>
                                                    <button type="submit" class="action-button btn-deactivate"><i class="bi bi-pause-circle"></i> Deactivate</button>
                                                <?php else: ?>
                                                    <button type="submit" class="action-button btn-activate"><i class="bi bi-check-circle"></i> Activate</button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="bi bi-info-circle me-1"></i> Showing <?= count($categories) ?> category record(s). Deactivated categories are preserved in the database.
            </div>
        </section>

        <footer class="page-footer">
            LabSync · <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?> · SLSU Lucena Campus
        </footer>
    </main>
</div>

<!-- EDIT CATEGORY MODALS -->
<?php foreach ($categories as $category): ?>
    <div class="modal fade" id="editCategoryModal<?= (int) $category['category_id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header px-4 py-3">
                    <div>
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i> Edit Category</h5>
                        <div class="modal-description">Update the selected issue category.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" class="form-control" value="<?= e($category['category_name']) ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Enter category description"><?= e($category['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn-light-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary-custom"><i class="bi bi-check2-circle"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- ADD CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-4 py-3">
                <div>
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Add Issue Category</h5>
                    <div class="modal-description">Create a category for computer issue reports.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="category_name" class="form-control" placeholder="Example: Hardware" required>
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue category..."></textarea>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn-light-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Add Category</button>
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
    <a href="users.php" class="dock-item">
        <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="computer-units.php" class="dock-item">
        <i class="bi bi-pc-display"></i><span>Computers</span>
    </a>
    <a href="categories.php" class="dock-item active">
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
<script src="../assets/js/admin-categories.js"></script>

</body>
</html>