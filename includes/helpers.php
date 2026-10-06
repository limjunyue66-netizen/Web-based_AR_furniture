<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * General helpers: escaping, CSRF, URLs, uploads, formatting.
 */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    if (!preg_match('#^(https?:)?//|^[a-z0-9_./?-]+$#i', $url) && !str_starts_with($url, '/')) {
        $url = '/';
    }
    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/**
 * Whether the current request is HTTPS (works behind cPanel / proxy).
 */
function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    return false;
}

function is_loopback_host(string $host): bool
{
    $host = strtolower((string) preg_replace('/:\d+$/', '', $host));
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

/**
 * Encode each path segment (supports spaces like "Web-Based AR Furniture").
 */
function encode_url_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $parts = explode('/', $path);
    $encoded = [];
    foreach ($parts as $part) {
        if ($part === '') {
            $encoded[] = '';
            continue;
        }
        $encoded[] = rawurlencode(rawurldecode($part));
    }
    return implode('/', $encoded);
}

/**
 * Application directory path from the current script (decoded, no trailing slash).
 * Example: /yue_project/Web-based_AR_furniture
 */
function app_web_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname($script);
    if (preg_match('#/(admin|api)$#', $dir)) {
        $dir = dirname($dir);
    }
    $dir = rtrim($dir, '/');
    if ($dir === '.' || $dir === '\\') {
        return '';
    }
    return $dir;
}

/**
 * Best-effort LAN IPv4 for same-WiFi phone testing on XAMPP.
 */
function detect_lan_ipv4(): ?string
{
    $configured = trim((string) (app_config()['app']['lan_ip'] ?? ''));
    if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $configured;
    }

    // Discover the IP used for outbound traffic (usually the real Wi‑Fi/Ethernet NIC).
    if (function_exists('socket_create')) {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket !== false) {
            @socket_connect($socket, '8.8.8.8', 53);
            $localIp = '';
            @socket_getsockname($socket, $localIp);
            @socket_close($socket);
            if (is_string($localIp) && filter_var($localIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !is_loopback_host($localIp)) {
                return $localIp;
            }
        }
    }

    $candidates = [];
    if (!empty($_SERVER['SERVER_ADDR']) && filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (!is_loopback_host((string) $_SERVER['SERVER_ADDR'])) {
            $candidates[] = (string) $_SERVER['SERVER_ADDR'];
        }
    }
    $hostname = gethostname();
    if (is_string($hostname) && $hostname !== '') {
        $resolved = gethostbyname($hostname);
        if (is_string($resolved) && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !is_loopback_host($resolved)) {
            $candidates[] = $resolved;
        }
    }
    foreach ($candidates as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }
    return null;
}

/**
 * Site root URL for assets/links.
 * On cPanel: set app.base_url to your public HTTPS URL (recommended).
 */
function base_url(string $path = ''): string
{
    $cfg = app_config();
    $configured = trim((string) ($cfg['app']['base_url'] ?? ''));

    if ($configured !== '') {
        $base = rtrim($configured, '/');
    } else {
        $scheme = request_is_https() ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $dir = encode_url_path(app_web_path());
        $base = $scheme . '://' . $host . $dir;
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    if ($path === '') {
        return $base . '/';
    }
    // Keep query string intact if present
    $qPos = strpos($path, '?');
    if ($qPos === false) {
        return $base . '/' . ltrim(encode_url_path($path), '/');
    }
    $file = substr($path, 0, $qPos);
    $query = substr($path, $qPos);
    return $base . '/' . ltrim(encode_url_path($file), '/') . $query;
}

/**
 * Phone-scannable public root (QR codes).
 * Priority: app.public_url → app.base_url → auto-detect (LAN IP if localhost).
 */
function public_base_url(): string
{
    $cfg = app_config()['app'] ?? [];
    foreach (['public_url', 'base_url'] as $key) {
        $value = trim((string) ($cfg[$key] ?? ''));
        if ($value !== '') {
            return rtrim($value, '/');
        }
    }

    $scheme = request_is_https() ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $dir = encode_url_path(app_web_path());

    if (is_loopback_host($host)) {
        $lan = detect_lan_ipv4();
        if ($lan !== null) {
            // Phones cannot open localhost — use LAN IP on the same Wi‑Fi.
            $host = $lan;
            $scheme = 'http';
        }
    }

    return $scheme . '://' . $host . $dir;
}

/**
 * Absolute phone-scannable URL for the current storefront page (path + query).
 */
function qr_page_url(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');
    $query = parse_url($uri, PHP_URL_QUERY);

    $pathDecoded = rawurldecode($path);
    $prefix = app_web_path();
    if ($prefix !== '' && str_starts_with($pathDecoded, $prefix)) {
        $pathDecoded = substr($pathDecoded, strlen($prefix)) ?: '/';
    }
    $pathDecoded = ltrim($pathDecoded, '/');
    if ($pathDecoded === '') {
        $pathDecoded = 'index.php';
    }

    $url = public_base_url() . '/' . ltrim(encode_url_path($pathDecoded), '/');
    if (is_string($query) && $query !== '') {
        $url .= '?' . $query;
    }
    return $url;
}

function qr_url_is_phone_reachable(): bool
{
    $host = (string) (parse_url(public_base_url(), PHP_URL_HOST) ?? '');
    return $host !== '' && !is_loopback_host($host);
}

function asset_url(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    // Root-relative URL (no host) so localhost / LAN IP / cPanel domain all work.
    $prefix = encode_url_path(app_web_path());
    if ($prefix === '' || $prefix === '/') {
        return '/' . $relative;
    }
    return rtrim($prefix, '/') . '/' . $relative;
}

function current_url(): string
{
    return qr_page_url();
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . bin2hex(random_bytes(3));
}

function format_price(float|string|null $price, ?string $currency = null): string
{
    $cfg = app_config();
    $currency = $currency ?: ($cfg['currency_default'] ?? 'RM');
    $amount = number_format((float) $price, 2, '.', ',');
    return trim($currency . ' ' . $amount);
}

function format_dimension_cm(?float $value): string
{
    if ($value === null) {
        return '—';
    }
    return number_format($value, 1, '.', '') . ' cm';
}

function flash_set(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $session = $_SESSION['_csrf'] ?? '';
    return is_string($token) && $session !== '' && hash_equals($session, $token);
}

function require_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!csrf_verify(is_string($token) ? $token : null)) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

/**
 * Safe file upload. Returns relative path (from project root) or null on failure.
 *
 * @param array $file Single $_FILES entry
 * @param string $destDir Absolute destination directory
 * @param array $allowedExt Allowed extensions
 * @param int $maxBytes Max size
 * @param string $relativePrefix Path prefix stored in DB (e.g. uploads/thumbs)
 */
function store_upload(
    array $file,
    string $destDir,
    array $allowedExt,
    int $maxBytes,
    string $relativePrefix
): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the form size limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a PHP extension.',
        ];
        throw new InvalidArgumentException($messages[$uploadError] ?? 'Upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
        throw new InvalidArgumentException('File exceeds the maximum allowed size (' . round($maxBytes / 1048576, 1) . ' MB).');
    }

    $original = (string) ($file['name'] ?? '');
    // Prefer last extension only (handles names like photo.png.jpg)
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new InvalidArgumentException('File type not allowed: .' . $ext . '. Allowed: ' . implode(', ', $allowedExt));
    }

    // Reject executable-looking names
    if (preg_match('/\.(php|phtml|phar|cgi|pl|exe|sh|bat|cmd)(\.|$)/i', $original)) {
        throw new InvalidArgumentException('Executable uploads are not allowed.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new InvalidArgumentException('Invalid upload.');
    }

    // MIME checks where possible
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $imageExts = ['jpg', 'jpeg', 'png', 'webp'];
    $modelMimes = [
        'glb' => ['model/gltf-binary', 'application/octet-stream', 'model/gltf+json'],
        'gltf' => ['model/gltf+json', 'application/json', 'text/plain', 'application/octet-stream'],
        'usdz' => ['model/vnd.usdz+zip', 'application/zip', 'application/octet-stream'],
    ];
    if (in_array($ext, $imageExts, true)) {
        // Accept common image MIME mismatches from Windows / phone cameras.
        if ($mime !== '' && !str_starts_with($mime, 'image/')) {
            throw new InvalidArgumentException('Thumbnail must be an image file (JPG/PNG/WEBP). Detected: ' . $mime);
        }
    }
    // 3D formats are often reported as octet-stream / empty on shared hosts — trust extension + size.
    if (isset($modelMimes[$ext]) && $mime !== '' && !in_array($mime, $modelMimes[$ext], true)) {
        $loose = ['application/octet-stream', 'application/zip', 'application/xml', 'text/xml', 'text/plain', 'application/x-zip-compressed'];
        if (!in_array($mime, $loose, true)) {
            throw new InvalidArgumentException('3D model MIME type not accepted (' . $mime . '). Try exporting a binary .glb again.');
        }
    }

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        throw new RuntimeException('Cannot create upload directory.');
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $target = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        throw new InvalidArgumentException('Could not save uploaded file.');
    }
    @chmod($target, 0644);

    return rtrim(str_replace('\\', '/', $relativePrefix), '/') . '/' . $filename;
}

function delete_project_file(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    $relativePath = str_replace('\\', '/', $relativePath);
    // Only allow deleting from known asset folders
    if (!preg_match('#^(uploads|models)/#', $relativePath)) {
        return;
    }
    if (str_contains($relativePath, '..')) {
        return;
    }
    $root = dirname(__DIR__);
    $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $realRoot = realpath($root);
    $realFile = realpath($full);
    if ($realRoot && $realFile && str_starts_with($realFile, $realRoot) && is_file($realFile)) {
        @unlink($realFile);
    }
}

function post_string(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_float(string $key, ?float $default = null): ?float
{
    $v = $_POST[$key] ?? null;
    if ($v === null || $v === '') {
        return $default;
    }
    return is_numeric($v) ? (float) $v : $default;
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $default;
    return is_numeric($v) ? (int) $v : $default;
}

function post_bool(string $key): bool
{
    return isset($_POST[$key]) && (string) $_POST[$key] !== '0' && (string) $_POST[$key] !== '';
}
