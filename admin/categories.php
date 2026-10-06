<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_admin();

$error = null;
$categories = [];

try {
    $categories = categories_all(false);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Database unavailable. Please check the database configuration.';
}

admin_header('Categories', 'categories');
?>

<div class="admin-top">
    <div>
        <h1>Categories</h1>
        <p class="muted">Organize the public catalog filters.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('admin/category_edit.php')) ?>">Add category</a>
</div>

<?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table categories-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Sort</th>
                <th>Products</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$categories): ?>
                <tr><td colspan="6">No categories yet.</td></tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <?php $count = category_product_count((int) $cat['id']); ?>
                    <tr>
                        <td><strong><?= e($cat['name']) ?></strong></td>
                        <td class="muted"><?= e($cat['slug']) ?></td>
                        <td class="cell-num"><?= (int) $cat['sort_order'] ?></td>
                        <td class="cell-num"><?= (int) $count ?></td>
                        <td>
                            <span class="badge <?= (int) $cat['is_active'] === 1 ? 'badge-ok' : 'badge-off' ?>">
                                <?= (int) $cat['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= e(base_url('admin/category_edit.php?id=' . (int) $cat['id'])) ?>">Edit</a>
                                <a class="danger" href="<?= e(base_url('admin/category_delete.php?id=' . (int) $cat['id'])) ?>">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php admin_footer(); ?>
