<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/layout.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$product = null;
$dbError = null;

try {
    if ($slug === '') {
        http_response_code(404);
    } else {
        $product = product_by_slug($slug, true);
        if (!$product) {
            http_response_code(404);
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $dbError = 'Database unavailable. Please check the database configuration.';
}

if ($dbError) {
    render_header('Error', 'catalog');
    echo '<section class="section"><div class="container"><div class="flash flash-error" role="alert">' . e($dbError) . '</div></div></section>';
    render_footer();
    exit;
}

if (!$product) {
    render_header('Product not found', 'catalog');
    echo '<section class="section"><div class="container"><h1>Product not found.</h1><p><a class="text-link" href="' . e(base_url()) . '">Back to catalog</a></p></div></section>';
    render_footer();
    exit;
}

$glbUrl = !empty($product['glb_path']) ? asset_url($product['glb_path']) : '';
$usdzUrl = !empty($product['usdz_path']) ? asset_url($product['usdz_path']) : '';
$meters = product_dimensions_meters($product);
$pageUrl = qr_page_url();

// Guard: DB path exists but file missing on disk
if (!empty($product['glb_path'])) {
    $glbFull = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $product['glb_path']);
    if (!is_file($glbFull)) {
        $glbUrl = '';
    }
}

render_header($product['name'], 'catalog');
?>

<section class="section product-detail">
    <div class="container product-layout">
        <div class="viewer-panel">
            <?php if ($glbUrl === ''): ?>
                <div class="viewer-fallback" role="alert">
                    <p>3D model failed to load. Please check the model file.</p>
                </div>
            <?php else: ?>
                <div class="viewer-shell" data-viewer-shell>
                    <div class="viewer-loading" data-viewer-loading>Loading 3D model…</div>
                    <div class="viewer-error" data-viewer-error hidden>
                        3D model failed to load. Please check the model file.
                    </div>
                    <model-viewer
                        id="product-viewer"
                        class="product-viewer"
                        src="<?= e($glbUrl) ?>"
                        <?php if ($usdzUrl !== ''): ?>ios-src="<?= e($usdzUrl) ?>"<?php endif; ?>
                        alt="3D model of <?= e($product['name']) ?>"
                        crossorigin="anonymous"
                        camera-controls
                        touch-action="pan-y"
                        ar
                        ar-modes="webxr scene-viewer quick-look"
                        ar-scale="fixed"
                        shadow-intensity="1"
                        shadow-softness="0.8"
                        environment-image="neutral"
                        exposure="1"
                        interaction-prompt="none"
                        loading="eager"
                        reveal="auto"
                        data-dim-x="<?= e((string) $meters['x']) ?>"
                        data-dim-y="<?= e((string) $meters['y']) ?>"
                        data-dim-z="<?= e((string) $meters['z']) ?>"
                    >
                        <button slot="ar-button" class="ar-button">View in your space</button>
                    </model-viewer>
                </div>
            <?php endif; ?>

            <aside class="qr-inline desktop-only" data-qr-panel>
                <div>
                    <p class="qr-label">Scan for AR on phone</p>
                    <p class="qr-url" data-qr-url-text><?= e($pageUrl) ?></p>
                    <?php if (!qr_url_is_phone_reachable()): ?>
                        <p class="muted small">Set <code>app.base_url</code> to your public cPanel URL so phones can open this page.</p>
                    <?php endif; ?>
                </div>
                <div class="qr-frame qr-frame-sm" data-qr-code data-qr-url="<?= e($pageUrl) ?>" aria-label="QR code for this product page"></div>
            </aside>
        </div>

        <div class="product-info">
            <p class="product-cat"><?= e($product['category_name'] ?? 'Furniture') ?></p>
            <h1><?= e($product['name']) ?></h1>
            <p class="product-price large"><?= e(format_price($product['price'], $product['currency'])) ?></p>
            <p class="product-desc"><?= nl2br(e((string) $product['description'])) ?></p>

            <dl class="dims">
                <div>
                    <dt>Width</dt>
                    <dd><?= e(format_dimension_cm($product['width_cm'] !== null ? (float) $product['width_cm'] : null)) ?></dd>
                </div>
                <div>
                    <dt>Height</dt>
                    <dd><?= e(format_dimension_cm($product['height_cm'] !== null ? (float) $product['height_cm'] : null)) ?></dd>
                </div>
                <div>
                    <dt>Depth</dt>
                    <dd><?= e(format_dimension_cm($product['depth_cm'] !== null ? (float) $product['depth_cm'] : null)) ?></dd>
                </div>
            </dl>

            <div class="ar-help" data-ar-help>
                <p><strong>AR tip:</strong> AR is supported on compatible mobile devices. Open this page on your phone and tap the AR button.</p>
                <p class="muted small">Supported paths include WebXR, Google Scene Viewer (Android), and Apple Quick Look (iOS) where available.</p>
            </div>

            <p><a class="text-link" href="<?= e(base_url('index.php' . (!empty($product['category_slug']) ? '?category=' . rawurlencode($product['category_slug']) : ''))) ?>">← Back to catalog</a></p>
        </div>
    </div>
</section>

<?php render_footer(); ?>
