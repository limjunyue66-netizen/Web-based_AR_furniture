<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/products.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_admin();

$error = null;
$products = [];
$total = $active = $cats = 0;

try {
    $products = products_all_admin();
    $total = products_count(null);
    $active = products_count(true);
    $cats = categories_count(false);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Database unavailable. Please check the database configuration.';
}

admin_header('Products', 'products');
?>

<div class="admin-top">
    <div>
        <h1>Products</h1>
        <p class="muted">Add furniture, upload GLB models, and control catalog visibility.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('admin/product_edit.php')) ?>">Add product</a>
</div>

<?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
<?php else: ?>
    <div class="stat-row">
        <div class="stat"><span><?= (int) $total ?></span> Products</div>
        <div class="stat"><span><?= (int) $active ?></span> Active</div>
        <div class="stat"><span><?= (int) $cats ?></span> Categories</div>
    </div>

    <div class="table-wrap">
        <table class="data-table products-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Sort</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$products): ?>
                <tr><td colspan="6">No products yet.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="cell-stack">
                                <strong><?= e($p['name']) ?></strong>
                                <span class="muted small"><?= e($p['slug']) ?></span>
                            </div>
                        </td>
                        <td><?= e($p['category_name'] ?? '—') ?></td>
                        <td class="cell-num"><?= e(format_price($p['price'], $p['currency'])) ?></td>
                        <td>
                            <span class="badge <?= (int) $p['is_active'] === 1 ? 'badge-ok' : 'badge-off' ?>">
                                <?= (int) $p['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="cell-num"><?= (int) $p['sort_order'] ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= e(base_url('product.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank" rel="noopener">View</a>
                                <a href="<?= e(base_url('admin/product_edit.php?id=' . (int) $p['id'])) ?>">Edit</a>
                                <a class="danger" href="<?= e(base_url('admin/product_delete.php?id=' . (int) $p['id'])) ?>">Delete</a>
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
