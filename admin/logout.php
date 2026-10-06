<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

auth_start_session();
admin_logout();
redirect(base_url('admin/login.php'));
