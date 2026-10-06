<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/products.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = product_by_id($id);
if (!$product) {
    flash_set('error', 'Product not found.');
    redirect(base_url('admin/index.php'));
}

if (is_post()) {
    require_csrf();
    try {
        product_delete($id);
        flash_set('success', 'Product deleted.');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash_set('error', 'Could not delete product.');
    }
    redirect(base_url('admin/index.php'));
}

admin_header('Delete product', 'products');
?>

<div class="admin-top">
    <h1>Delete product</h1>
</div>

<div class="confirm-box">
    <p>Delete <strong><?= e($product['name']) ?></strong>? This cannot be undone.</p>
    <p class="muted small">Associated uploaded files for this product will also be removed.</p>
    <form method="post" class="inline-actions">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Yes, delete</button>
        <a class="btn btn-ghost" href="<?= e(base_url('admin/index.php')) ?>">Cancel</a>
    </form>
</div>

<?php admin_footer(); ?>
