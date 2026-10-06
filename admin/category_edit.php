<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$category = null;
$errors = [];

try {
    if ($id > 0) {
        $category = category_by_id($id);
        if (!$category) {
            flash_set('error', 'Category not found.');
            redirect(base_url('admin/categories.php'));
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash_set('error', 'Database unavailable. Please check the database configuration.');
    redirect(base_url('admin/categories.php'));
}

if (is_post()) {
    require_csrf();
    $name = post_string('name');
    $slug = post_string('slug');
    if ($slug === '' && $name !== '') {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }
    $sortOrder = post_int('sort_order', 0);
    $isActive = post_bool('is_active') ? 1 : 0;

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($slug === '') {
        $errors[] = 'Slug is required.';
    }

    $existing = category_by_slug($slug);
    if ($existing && (int) $existing['id'] !== $id) {
        $errors[] = 'That category slug is already in use.';
    }

    if (!$errors) {
        $data = [
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ];
        try {
            if ($id > 0) {
                category_update($id, $data);
                flash_set('success', 'Category updated.');
            } else {
                category_create($data);
                flash_set('success', 'Category created.');
            }
            redirect(base_url('admin/categories.php'));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Could not save category.';
        }
    }

    $category = [
        'name' => $name,
        'slug' => $slug,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
    ];
}

$isEdit = $id > 0;
admin_header($isEdit ? 'Edit category' : 'Add category', 'categories');
?>

<div class="admin-top">
    <h1><?= $isEdit ? 'Edit category' : 'Add category' ?></h1>
    <a class="btn btn-ghost" href="<?= e(base_url('admin/categories.php')) ?>">Back</a>
</div>

<?php foreach ($errors as $err): ?>
    <div class="flash flash-error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-grid">
        <label>Name
            <input type="text" name="name" required value="<?= e((string) ($category['name'] ?? '')) ?>">
        </label>
        <label>Slug
            <input type="text" name="slug" value="<?= e((string) ($category['slug'] ?? '')) ?>" placeholder="auto-from-name">
        </label>
        <label>Sort order
            <input type="number" name="sort_order" value="<?= e((string) ($category['sort_order'] ?? '0')) ?>">
        </label>
    </div>
    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" <?= ((int) ($category['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
        Active
    </label>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create category' ?></button>
    </div>
</form>

<?php admin_footer(); ?>
