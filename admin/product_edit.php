<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/products.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = null;
$categories = [];
$errors = [];

try {
    $categories = categories_all(false);
    if ($id > 0) {
        $product = product_by_id($id);
        if (!$product) {
            flash_set('error', 'Product not found.');
            redirect(base_url('admin/index.php'));
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash_set('error', 'Database unavailable. Please check the database configuration.');
    redirect(base_url('admin/index.php'));
}

$cfg = app_config();
$root = dirname(__DIR__);

if (is_post()) {
    // PHP discards POST/FILES entirely when body exceeds post_max_size.
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
        flash_set('error', 'Upload too large for PHP limits (post_max_size / upload_max_filesize). Reduce the GLB size or raise limits in php.ini / .user.ini.');
        redirect(base_url('admin/product_edit.php' . ($id > 0 ? '?id=' . $id : '')));
    }

    require_csrf();

    $name = post_string('name');
    $slug = post_string('slug');
    if ($slug === '' && $name !== '') {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }

    $categoryId = post_int('category_id', 0);
    $description = post_string('description');
    $price = post_float('price', 0.0) ?? 0.0;
    $currency = strtoupper(post_string('currency', $cfg['currency_default'] ?? 'RM'));
    $width = post_float('width_cm');
    $height = post_float('height_cm');
    $depth = post_float('depth_cm');
    $glbXm = post_float('glb_x_m');
    $glbYm = post_float('glb_y_m');
    $glbZm = post_float('glb_z_m');
    $sortOrder = post_int('sort_order', 0);
    $isActive = post_bool('is_active') ? 1 : 0;

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($slug === '') {
        $errors[] = 'Slug is required.';
    }
    if ($price < 0) {
        $errors[] = 'Price cannot be negative.';
    }
    if ($currency === '' || strlen($currency) > 8) {
        $errors[] = 'Currency is invalid.';
    }
    if ($categoryId > 0 && !category_by_id($categoryId)) {
        $errors[] = 'Selected category does not exist.';
    }
    if (product_slug_exists($slug, $id > 0 ? $id : null)) {
        $errors[] = 'That slug is already in use.';
    }

    // Auto-fill meter fields from cm when blank
    if ($glbXm === null && $width !== null) {
        $glbXm = $width / 100;
    }
    if ($glbYm === null && $height !== null) {
        $glbYm = $height / 100;
    }
    if ($glbZm === null && $depth !== null) {
        $glbZm = $depth / 100;
    }

    $glbPath = $product['glb_path'] ?? null;
    $usdzPath = $product['usdz_path'] ?? null;
    $thumbPath = $product['thumb_path'] ?? null;
    $oldGlb = $glbPath;
    $oldUsdz = $usdzPath;
    $oldThumb = $thumbPath;
    $newGlb = null;
    $newUsdz = null;
    $newThumb = null;

    try {
        if (!empty($_FILES['glb_file']['name'])) {
            $newGlb = store_upload(
                $_FILES['glb_file'],
                $root . '/models',
                $cfg['upload']['allowed_models'],
                (int) $cfg['upload']['max_model_bytes'],
                'models'
            );
            if ($newGlb) {
                $glbPath = $newGlb;
            }
        }
        if (!empty($_FILES['usdz_file']['name'])) {
            $newUsdz = store_upload(
                $_FILES['usdz_file'],
                $root . '/models',
                ['usdz'],
                (int) $cfg['upload']['max_model_bytes'],
                'models'
            );
            if ($newUsdz) {
                $usdzPath = $newUsdz;
            }
        }
        if (!empty($_FILES['thumb_file']['name'])) {
            $newThumb = store_upload(
                $_FILES['thumb_file'],
                $root . '/uploads/thumbs',
                $cfg['upload']['allowed_images'],
                (int) $cfg['upload']['max_image_bytes'],
                'uploads/thumbs'
            );
            if ($newThumb) {
                $thumbPath = $newThumb;
            }
        }
    } catch (InvalidArgumentException $ex) {
        $errors[] = $ex->getMessage();
        // Clean orphaned new uploads if validation failed mid-way
        if ($newGlb) {
            delete_project_file($newGlb);
            $glbPath = $oldGlb;
            $newGlb = null;
        }
        if ($newUsdz) {
            delete_project_file($newUsdz);
            $usdzPath = $oldUsdz;
            $newUsdz = null;
        }
        if ($newThumb) {
            delete_project_file($newThumb);
            $thumbPath = $oldThumb;
            $newThumb = null;
        }
    } catch (Throwable $ex) {
        error_log($ex->getMessage());
        $errors[] = 'Upload failed.';
        if ($newGlb) {
            delete_project_file($newGlb);
            $glbPath = $oldGlb;
            $newGlb = null;
        }
        if ($newUsdz) {
            delete_project_file($newUsdz);
            $usdzPath = $oldUsdz;
            $newUsdz = null;
        }
        if ($newThumb) {
            delete_project_file($newThumb);
            $thumbPath = $oldThumb;
            $newThumb = null;
        }
    }

    if (post_bool('clear_usdz') && $usdzPath) {
        // Defer delete until after successful save
        $clearUsdz = true;
        $usdzPath = null;
    } else {
        $clearUsdz = false;
    }

    if (!$errors) {
        $data = [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'currency' => $currency,
            'glb_path' => $glbPath,
            'usdz_path' => $usdzPath,
            'thumb_path' => $thumbPath,
            'width_cm' => $width,
            'height_cm' => $height,
            'depth_cm' => $depth,
            'glb_x_m' => $glbXm,
            'glb_y_m' => $glbYm,
            'glb_z_m' => $glbZm,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ];

        try {
            if ($id > 0) {
                product_update($id, $data);
                flash_set('success', 'Product updated.');
            } else {
                product_create($data);
                flash_set('success', 'Product created.');
            }

            // Only remove previous files after DB save succeeds
            if ($newGlb && $oldGlb && $oldGlb !== $newGlb) {
                delete_project_file($oldGlb);
            }
            if ($newUsdz && $oldUsdz && $oldUsdz !== $newUsdz) {
                delete_project_file($oldUsdz);
            }
            if ($clearUsdz && $oldUsdz) {
                delete_project_file($oldUsdz);
            }
            if ($newThumb && $oldThumb && $oldThumb !== $newThumb && str_starts_with((string) $oldThumb, 'uploads/')) {
                delete_project_file($oldThumb);
            }

            redirect(base_url('admin/index.php'));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Could not save product.';
            if ($newGlb) {
                delete_project_file($newGlb);
            }
            if ($newUsdz) {
                delete_project_file($newUsdz);
            }
            if ($newThumb) {
                delete_project_file($newThumb);
            }
        }
    }

    // Re-populate form after error
    $product = array_merge($product ?? [], [
        'name' => $name,
        'slug' => $slug,
        'category_id' => $categoryId,
        'description' => $description,
        'price' => $price,
        'currency' => $currency,
        'width_cm' => $width,
        'height_cm' => $height,
        'depth_cm' => $depth,
        'glb_x_m' => $glbXm,
        'glb_y_m' => $glbYm,
        'glb_z_m' => $glbZm,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
        'glb_path' => $glbPath,
        'usdz_path' => $usdzPath,
        'thumb_path' => $thumbPath,
    ]);
}

$isEdit = $id > 0;
admin_header($isEdit ? 'Edit product' : 'Add product', 'products');
?>

<div class="admin-top">
    <div>
        <h1><?= $isEdit ? 'Edit product' : 'Add product' ?></h1>
        <p class="muted">GLB is required for 3D/AR. USDZ is optional for Apple Quick Look.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(base_url('admin/index.php')) ?>">Back</a>
</div>

<?php foreach ($errors as $err): ?>
    <div class="flash flash-error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>

    <fieldset>
        <legend>Basics</legend>
        <div class="form-grid">
            <label>Name
                <input type="text" name="name" required value="<?= e((string) ($product['name'] ?? '')) ?>">
            </label>
            <label>Slug
                <input type="text" name="slug" value="<?= e((string) ($product['slug'] ?? '')) ?>" placeholder="auto-from-name">
            </label>
            <label>Category
                <select name="category_id">
                    <option value="0">— Uncategorized —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?><?= (int) $cat['is_active'] !== 1 ? ' (inactive)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sort order
                <input type="number" name="sort_order" value="<?= e((string) ($product['sort_order'] ?? '0')) ?>">
            </label>
            <label>Price
                <input type="number" name="price" step="0.01" min="0" required value="<?= e((string) ($product['price'] ?? '0')) ?>">
            </label>
            <label>Currency
                <input type="text" name="currency" maxlength="8" value="<?= e((string) ($product['currency'] ?? ($cfg['currency_default'] ?? 'RM'))) ?>">
            </label>
        </div>
        <label>Description
            <textarea name="description" rows="5"><?= e((string) ($product['description'] ?? '')) ?></textarea>
        </label>
        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1" <?= ((int) ($product['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
            Active (visible in catalog)
        </label>
    </fieldset>

    <fieldset>
        <legend>Real-world dimensions (cm)</legend>
        <p class="muted small">Select a <code>.glb</code> below and these fields fill automatically from the model bounding box (X/Y/Z → width/height/depth).</p>
        <div class="form-grid three">
            <label>Width (cm)
                <input type="number" name="width_cm" step="0.01" min="0" value="<?= e((string) ($product['width_cm'] ?? '')) ?>">
            </label>
            <label>Height (cm)
                <input type="number" name="height_cm" step="0.01" min="0" value="<?= e((string) ($product['height_cm'] ?? '')) ?>">
            </label>
            <label>Depth (cm)
                <input type="number" name="depth_cm" step="0.01" min="0" value="<?= e((string) ($product['depth_cm'] ?? '')) ?>">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Measured GLB size (meters)</legend>
        <p class="muted small">Auto-filled with the GLB. Use these if you need to correct AR scale. Leave blank only when you want the server to derive meters from cm (÷100).</p>
        <div class="form-grid three">
            <label>glb_x_m (width)
                <input type="number" name="glb_x_m" step="0.0001" min="0" value="<?= e((string) ($product['glb_x_m'] ?? '')) ?>">
            </label>
            <label>glb_y_m (height)
                <input type="number" name="glb_y_m" step="0.0001" min="0" value="<?= e((string) ($product['glb_y_m'] ?? '')) ?>">
            </label>
            <label>glb_z_m (depth)
                <input type="number" name="glb_z_m" step="0.0001" min="0" value="<?= e((string) ($product['glb_z_m'] ?? '')) ?>">
            </label>
        </div>
        <div class="glb-size-tools">
            <?php if (!empty($product['glb_path'])): ?>
                <button type="button" class="btn btn-ghost" id="glb-size-from-current"
                        data-glb-url="<?= e(asset_url($product['glb_path'])) ?>">
                    Read size from current GLB
                </button>
            <?php endif; ?>
            <p id="glb-size-status" class="glb-size-status muted small" role="status"></p>
        </div>
    </fieldset>

    <fieldset>
        <legend>Files</legend>
        <p class="muted small">After a successful save, only the <strong>Current:</strong> path is stored. Choosing a new <code>.glb</code> also auto-updates the dimension fields above.</p>
        <div class="form-grid">
            <label>GLB / GLTF model
                <input type="file" name="glb_file" accept=".glb,.gltf,model/gltf-binary,model/gltf+json">
                <?php if (!empty($product['glb_path'])): ?>
                    <span class="muted small">Current: <?= e($product['glb_path']) ?></span>
                <?php endif; ?>
            </label>
            <label>USDZ (optional, Apple)
                <input type="file" name="usdz_file" accept=".usdz,model/vnd.usdz+zip">
                <?php if (!empty($product['usdz_path'])): ?>
                    <span class="muted small">Current: <?= e($product['usdz_path']) ?></span>
                    <label class="checkbox"><input type="checkbox" name="clear_usdz" value="1"> Remove USDZ</label>
                <?php endif; ?>
            </label>
            <label>Thumbnail
                <input type="file" name="thumb_file" accept=".jpg,.jpeg,.png,.webp,image/*">
                <?php if (!empty($product['thumb_path'])): ?>
                    <span class="muted small">Current: <?= e($product['thumb_path']) ?></span>
                <?php endif; ?>
            </label>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
    </div>
</form>
<script src="<?= e(asset_url('js/admin-glb-size.js')) ?>?v=20260917c" defer></script>

<?php admin_footer(); ?>
