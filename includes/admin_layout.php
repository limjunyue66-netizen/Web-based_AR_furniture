<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

/**
 * Admin panel layout.
 */

function admin_header(string $title, string $active = ''): void
{
    auth_start_session();
    $app = app_config()['app'];
    $admin = current_admin();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Admin · <?= e($app['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/styles.css')) ?>?v=20260917e">
</head>
<body class="admin-body">
<header class="admin-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(base_url('admin/index.php')) ?>">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-text"><?= e($app['name']) ?> Admin</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="admin-nav" data-nav-toggle>
            <span class="sr-only">Menu</span>
            <span></span><span></span><span></span>
        </button>
        <nav id="admin-nav" class="site-nav" data-site-nav>
            <a href="<?= e(base_url('admin/index.php')) ?>" class="<?= $active === 'products' ? 'is-active' : '' ?>">Products</a>
            <a href="<?= e(base_url('admin/categories.php')) ?>" class="<?= $active === 'categories' ? 'is-active' : '' ?>">Categories</a>
            <a href="<?= e(base_url()) ?>" target="_blank" rel="noopener">Storefront</a>
            <?php if ($admin): ?>
                <span class="nav-user"><?= e($admin['username']) ?></span>
            <?php endif; ?>
            <a href="<?= e(base_url('admin/logout.php')) ?>">Logout</a>
        </nav>
    </div>
</header>
<main class="admin-main container">
<?php
    $flash = flash_get();
    if ($flash) {
        $type = $flash['type'] === 'error' ? 'error' : 'success';
        echo '<div class="flash flash-' . e($type) . '" role="status">' . e($flash['message']) . '</div>';
    }
}

function admin_footer(): void
{
    ?>
</main>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}
