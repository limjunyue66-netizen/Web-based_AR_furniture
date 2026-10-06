<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin_layout.php';

auth_start_session();

$error = '';

if (admin_logged_in()) {
    redirect(base_url('admin/index.php'));
}

if (is_post()) {
    require_csrf();
    $username = post_string('username');
    $password = (string) ($_POST['password'] ?? '');
    try {
        if (admin_login($username, $password)) {
            redirect(base_url('admin/index.php'));
        }
        $error = 'Invalid username or password.';
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Database unavailable. Please check the database configuration.';
    }
}

$app = app_config()['app'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login · <?= e($app['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/styles.css')) ?>?v=20260917g">
</head>
<body class="login-body">
<main class="login-card">
    <a class="brand" href="<?= e(base_url()) ?>">
        <span class="brand-mark" aria-hidden="true"></span>
        <span class="brand-text"><?= e($app['name']) ?></span>
    </a>
    <h1>Administrator login</h1>
    <p class="muted">Manage products, categories, and 3D models.</p>
    <?php if ($error): ?>
        <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- autocomplete off: keep fields empty until Auto fill is clicked -->
    <form method="post" action="" class="stack-form" id="admin-login-form" autocomplete="off">
        <?= csrf_field() ?>
        <label>
            Username
            <input type="text"
                   name="username"
                   id="login-username"
                   required
                   autofocus
                   value=""
                   autocomplete="off"
                   autocapitalize="off"
                   spellcheck="false"
                   data-lpignore="true"
                   data-1p-ignore="true">
        </label>
        <label>
            Password
            <input type="password"
                   name="password"
                   id="login-password"
                   required
                   value=""
                   autocomplete="new-password"
                   data-lpignore="true"
                   data-1p-ignore="true">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>

    <div class="login-auto-info">
        <button type="button" class="btn btn-ghost btn-block" id="auto-info-btn">
            Auto fill demo login
        </button>
        <div class="login-info-box" id="login-info-box" hidden>
            <p><strong>Demo admin</strong></p>
            <p>Username: <code>admin</code></p>
            <p>Password: <code>admin1234</code></p>
            <p class="muted small">Shown only after you click the button. For local / student demo only.</p>
        </div>
    </div>

    <p class="muted small"><a class="text-link" href="<?= e(base_url()) ?>">← Back to storefront</a></p>
</main>
<script>
(function () {
  var btn = document.getElementById('auto-info-btn');
  var box = document.getElementById('login-info-box');
  var user = document.getElementById('login-username');
  var pass = document.getElementById('login-password');
  if (!btn || !user || !pass) return;

  // Always start empty (clears browser autofill if it ran).
  function clearFields() {
    user.value = '';
    pass.value = '';
    if (box) box.hidden = true;
  }

  clearFields();
  // Some browsers autofill after load — clear again shortly after.
  window.setTimeout(clearFields, 50);
  window.setTimeout(clearFields, 300);

  btn.addEventListener('click', function () {
    user.value = 'admin';
    pass.value = 'admin1234';
    if (box) box.hidden = false;
    pass.focus();
  });
})();
</script>
</body>
</html>
