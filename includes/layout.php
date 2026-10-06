<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Public storefront layout helpers.
 */

function render_header(string $title, string $active = ''): void
{
    $app = app_config()['app'];
    $pageTitle = $title !== '' ? $title . ' · ' . $app['name'] : $app['name'];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Browse furniture and preview pieces in your room with augmented reality.">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/styles.css')) ?>?v=20260917b">
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js"></script>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(base_url()) ?>">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-text"><?= e($app['name']) ?></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span class="sr-only">Menu</span>
            <span></span><span></span><span></span>
        </button>
        <nav id="site-nav" class="site-nav" data-site-nav>
            <a href="<?= e(base_url()) ?>" class="<?= $active === 'catalog' ? 'is-active' : '' ?>">Catalog</a>
            <a href="<?= e(base_url('admin/login.php')) ?>" class="<?= $active === 'admin' ? 'is-active' : '' ?>">Admin</a>
        </nav>
    </div>
</header>
<main>
<?php
}

function render_footer(): void
{
    $app = app_config()['app'];
    ?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <p><strong><?= e($app['name']) ?></strong> — <?= e($app['tagline'] ?? 'See it before you buy') ?></p>
        <p class="muted">Web-based AR furniture catalog for desktop and mobile browsers.</p>
    </div>
</footer>
<script src="<?= e(asset_url('js/qr.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

function render_flash(): void
{
    $flash = flash_get();
    if (!$flash) {
        return;
    }
    $type = $flash['type'] === 'error' ? 'error' : 'success';
    echo '<div class="flash flash-' . e($type) . '" role="status">' . e($flash['message']) . '</div>';
}
