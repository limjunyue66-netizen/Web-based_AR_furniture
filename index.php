<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/categories.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/layout.php';

$app = app_config()['app'];
$dbError = null;
$categories = [];
$products = [];
$activeSlug = isset($_GET['category']) ? trim((string) $_GET['category']) : '';

try {
    $categories = categories_all(true);
    $filters = ['active_only' => true];
    if ($activeSlug !== '' && $activeSlug !== 'all') {
        $filters['category_slug'] = $activeSlug;
    } else {
        $activeSlug = 'all';
    }
    $products = products_list($filters);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $dbError = 'Database unavailable. Please check the database configuration.';
    $activeSlug = 'all';
}

render_header($app['name'] . ' — Furniture Catalog', 'catalog');
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Augmented reality catalog</p>
            <h1 class="hero-brand"><?= e($app['name']) ?></h1>
            <p class="hero-lead"><?= e($app['tagline'] ?? 'See it before you buy') ?>. Browse furniture, inspect true-to-scale 3D models, then place pieces in your room with your phone camera.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#catalog">Browse catalog</a>
                <a class="btn btn-ghost" href="#how-ar">How AR works</a>
            </div>
        </div>
        <aside class="hero-qr desktop-only" data-qr-panel>
            <p class="qr-label">Scan to open on phone</p>
            <div class="qr-frame" data-qr-code data-qr-url="<?= e(qr_page_url()) ?>" aria-label="QR code to open this site on mobile"></div>
            <p class="qr-url" data-qr-url-text><?= e(qr_page_url()) ?></p>
            <?php if (qr_url_is_phone_reachable()): ?>
                <p class="muted small">Opens this storefront on your mobile browser for AR.</p>
            <?php else: ?>
                <p class="flash flash-error small">QR still points to localhost. Set <code>app.base_url</code> in <code>config/config.php</code> to your cPanel HTTPS URL, or open the site via your PC LAN IP.</p>
            <?php endif; ?>
        </aside>
    </div>
    <div class="hero-visual" aria-hidden="true"></div>
</section>

<section class="section" id="how-ar">
    <div class="container steps">
        <article>
            <span>01</span>
            <h2>Browse</h2>
            <p>Filter by category and open any product.</p>
        </article>
        <article>
            <span>02</span>
            <h2>Inspect in 3D</h2>
            <p>Rotate and zoom the model on desktop or mobile.</p>
        </article>
        <article>
            <span>03</span>
            <h2>Place with AR</h2>
            <p>Launch AR on a compatible phone and see real-world scale.</p>
        </article>
    </div>
</section>

<section class="section catalog-section" id="catalog">
    <div class="container">
        <div class="section-head">
            <h2>Catalog</h2>
            <p class="muted">Select a category to refine the collection.</p>
        </div>

        <?php if ($dbError): ?>
            <div class="flash flash-error" role="alert"><?= e($dbError) ?></div>
        <?php else: ?>
            <div class="category-bar" role="tablist" aria-label="Product categories">
                <a class="chip <?= $activeSlug === 'all' ? 'is-active' : '' ?>" href="<?= e(base_url('index.php')) ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a class="chip <?= $activeSlug === $cat['slug'] ? 'is-active' : '' ?>"
                       href="<?= e(base_url('index.php?category=' . rawurlencode($cat['slug']))) ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!$products): ?>
                <p class="empty-state">No products found in this category.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $thumb = !empty($product['thumb_path'])
                            ? asset_url($product['thumb_path'])
                            : asset_url('assets/placeholders/generic.svg');
                        ?>
                        <article class="product-card">
                            <a class="product-card-media" href="<?= e(base_url('product.php?slug=' . rawurlencode($product['slug']))) ?>">
                                <img src="<?= e($thumb) ?>" alt="<?= e($product['name']) ?>" loading="lazy" width="640" height="480">
                            </a>
                            <div class="product-card-body">
                                <p class="product-cat"><?= e($product['category_name'] ?? 'Uncategorized') ?></p>
                                <h3><a href="<?= e(base_url('product.php?slug=' . rawurlencode($product['slug']))) ?>"><?= e($product['name']) ?></a></h3>
                                <p class="product-price"><?= e(format_price($product['price'], $product['currency'])) ?></p>
                                <a class="text-link" href="<?= e(base_url('product.php?slug=' . rawurlencode($product['slug']))) ?>">View in your space →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php render_footer(); ?>
