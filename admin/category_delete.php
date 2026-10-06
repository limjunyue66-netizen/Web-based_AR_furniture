<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$category = category_by_id($id);
if (!$category) {
    flash_set('error', 'Category not found.');
    redirect(base_url('admin/categories.php'));
}

$count = category_product_count($id);

if (is_post()) {
    require_csrf();
    try {
        category_delete($id);
        flash_set('success', 'Category deleted.');
        redirect(base_url('admin/categories.php'));
    } catch (RuntimeException $e) {
        flash_set('error', $e->getMessage());
        redirect(base_url('admin/categories.php'));
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash_set('error', 'Could not delete category.');
        redirect(base_url('admin/categories.php'));
    }
}

admin_header('Delete category', 'categories');
?>

<div class="admin-top">
    <h1>Delete category</h1>
</div>

<div class="confirm-box">
    <?php if ($count > 0): ?>
        <p>Cannot delete <strong><?= e($category['name']) ?></strong> because it contains <?= (int) $count ?> product(s).</p>
        <p class="muted">Move or delete those products first, then try again.</p>
        <a class="btn btn-ghost" href="<?= e(base_url('admin/categories.php')) ?>">Back</a>
    <?php else: ?>
        <p>Delete category <strong><?= e($category['name']) ?></strong>?</p>
        <form method="post" class="inline-actions">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">Yes, delete</button>
            <a class="btn btn-ghost" href="<?= e(base_url('admin/categories.php')) ?>">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>
