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
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
            <span>
                <span class="brand-name">LabSync</span>
                <span class="brand-subtitle">LABORATORY MANAGEMENT</span>
            </span>
        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="users.php" class="sidebar-link"><i class="bi bi-people"></i> User Management</a>
            <a href="computer-units.php" class="sidebar-link"><i class="bi bi-pc-display"></i> Computer Units</a>
            <a href="categories.php" class="sidebar-link active"><i class="bi bi-tags"></i> Issue Categories</a>
            <a href="issue-reports.php" class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i> Issue Reports</a>
            <a href="maintenance-records.php" class="sidebar-link"><i class="bi bi-tools"></i> Maintenance Records</a>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?= e(pcare_categories_initials($_SESSION['full_name'] ?? 'Administrator')) ?>
                </div>
                <div>
                    <div class="admin-name"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></div>
                    <div class="admin-role"><?= is_super_admin() ? 'Super Administrator' : 'System Administrator' ?></div>
                </div>
            </div>
            <a href="../logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-left"></i> Sign Out</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="mobile-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="breadcrumb-text">
                    <?= is_super_admin() ? 'Super Administrator' : 'Administrator' ?> <span class="mx-2">/</span> <strong>Issue Categories</strong>
                </div>
            </div>
            <div class="topbar-right">
                <span><span class="topbar-dot"></span> <?= is_super_admin() ? 'Super Administrator Portal' : 'Administrator Portal' ?></span>
                <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i> <?= date('F d, Y') ?></span>
            </div>
        </header>

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

    <!-- LOCAL BOOTSTRAP JS -->
    <script src="../assets/vendor/bootstrap.bundle.min.js?v=123"></script>

    <!-- External JS -->
    <script src="../assets/js/admin-categories.js"></script>

</body>
</html>