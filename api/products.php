<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/products.php';

try {
    $filters = ['active_only' => true];
    if (!empty($_GET['category'])) {
        $filters['category_slug'] = trim((string) $_GET['category']);
    }
    if (!empty($_GET['slug'])) {
        $product = product_by_slug(trim((string) $_GET['slug']), true);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found.'], JSON_UNESCAPED_SLASHES);
            exit;
        }
        echo json_encode([
            'success' => true,
            'product' => product_public_payload($product),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $products = products_list($filters);
    $payload = array_map('product_public_payload', $products);

    echo json_encode([
        'success' => true,
        'count' => count($payload),
        'products' => $payload,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database unavailable. Please check the database configuration.',
    ], JSON_UNESCAPED_SLASHES);
}
