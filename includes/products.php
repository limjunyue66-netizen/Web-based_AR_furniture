<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Product data access and formatting helpers.
 */

function products_count(?bool $activeOnly = null): int
{
    $sql = 'SELECT COUNT(*) FROM products';
    if ($activeOnly === true) {
        $sql .= ' WHERE is_active = 1';
    } elseif ($activeOnly === false) {
        $sql .= ' WHERE is_active = 0';
    }
    return (int) db()->query($sql)->fetchColumn();
}

/**
 * @param array{category_slug?:string,active_only?:bool,limit?:int} $filters
 */
function products_list(array $filters = []): array
{
    $activeOnly = $filters['active_only'] ?? true;
    $categorySlug = $filters['category_slug'] ?? null;
    $limit = isset($filters['limit']) ? (int) $filters['limit'] : null;

    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE 1=1';
    $params = [];

    if ($activeOnly) {
        $sql .= ' AND p.is_active = 1 AND (c.is_active = 1 OR p.category_id IS NULL)';
    }
    if ($categorySlug) {
        $sql .= ' AND c.slug = ?';
        $params[] = $categorySlug;
    }

    $sql .= ' ORDER BY p.sort_order ASC, p.name ASC';
    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function products_all_admin(): array
{
    $sql = 'SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            ORDER BY p.sort_order ASC, p.name ASC';
    return db()->query($sql)->fetchAll();
}

function product_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_by_slug(string $slug, bool $activeOnly = true): ?array
{
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.slug = ?';
    if ($activeOnly) {
        $sql .= ' AND p.is_active = 1';
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_slug_exists(string $slug, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $stmt = db()->prepare('SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1');
        $stmt->execute([$slug, $excludeId]);
    } else {
        $stmt = db()->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
    }
    return (bool) $stmt->fetch();
}

function product_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO products (
            category_id, slug, name, description, price, currency,
            glb_path, usdz_path, thumb_path,
            width_cm, height_cm, depth_cm,
            glb_x_m, glb_y_m, glb_z_m,
            is_active, sort_order, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, NOW(), NOW()
        )'
    );
    $stmt->execute([
        $data['category_id'],
        $data['slug'],
        $data['name'],
        $data['description'],
        $data['price'],
        $data['currency'],
        $data['glb_path'],
        $data['usdz_path'],
        $data['thumb_path'],
        $data['width_cm'],
        $data['height_cm'],
        $data['depth_cm'],
        $data['glb_x_m'],
        $data['glb_y_m'],
        $data['glb_z_m'],
        $data['is_active'],
        $data['sort_order'],
    ]);
    return (int) db()->lastInsertId();
}

function product_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE products SET
            category_id = ?, slug = ?, name = ?, description = ?, price = ?, currency = ?,
            glb_path = ?, usdz_path = ?, thumb_path = ?,
            width_cm = ?, height_cm = ?, depth_cm = ?,
            glb_x_m = ?, glb_y_m = ?, glb_z_m = ?,
            is_active = ?, sort_order = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([
        $data['category_id'],
        $data['slug'],
        $data['name'],
        $data['description'],
        $data['price'],
        $data['currency'],
        $data['glb_path'],
        $data['usdz_path'],
        $data['thumb_path'],
        $data['width_cm'],
        $data['height_cm'],
        $data['depth_cm'],
        $data['glb_x_m'],
        $data['glb_y_m'],
        $data['glb_z_m'],
        $data['is_active'],
        $data['sort_order'],
        $id,
    ]);
}

function product_delete(int $id): void
{
    $product = product_by_id($id);
    if (!$product) {
        return;
    }
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    delete_project_file($product['glb_path'] ?? null);
    delete_project_file($product['usdz_path'] ?? null);
    delete_project_file($product['thumb_path'] ?? null);
}

/**
 * Convert cm dimensions to meters for model-viewer / AR scale reference.
 */
function product_dimensions_meters(array $product): array
{
    $x = $product['glb_x_m'] !== null && $product['glb_x_m'] !== ''
        ? (float) $product['glb_x_m']
        : ((float) ($product['width_cm'] ?? 0) / 100);
    $y = $product['glb_y_m'] !== null && $product['glb_y_m'] !== ''
        ? (float) $product['glb_y_m']
        : ((float) ($product['height_cm'] ?? 0) / 100);
    $z = $product['glb_z_m'] !== null && $product['glb_z_m'] !== ''
        ? (float) $product['glb_z_m']
        : ((float) ($product['depth_cm'] ?? 0) / 100);

    return ['x' => $x, 'y' => $y, 'z' => $z];
}

function product_public_payload(array $product): array
{
    return [
        'id' => (int) $product['id'],
        'slug' => $product['slug'],
        'name' => $product['name'],
        'description' => $product['description'],
        'price' => (float) $product['price'],
        'currency' => $product['currency'],
        'price_formatted' => format_price($product['price'], $product['currency']),
        'category' => $product['category_name'] ?? null,
        'category_slug' => $product['category_slug'] ?? null,
        'thumb_url' => !empty($product['thumb_path']) ? asset_url($product['thumb_path']) : null,
        'glb_url' => !empty($product['glb_path']) ? asset_url($product['glb_path']) : null,
        'usdz_url' => !empty($product['usdz_path']) ? asset_url($product['usdz_path']) : null,
        'dimensions_cm' => [
            'width' => $product['width_cm'] !== null ? (float) $product['width_cm'] : null,
            'height' => $product['height_cm'] !== null ? (float) $product['height_cm'] : null,
            'depth' => $product['depth_cm'] !== null ? (float) $product['depth_cm'] : null,
        ],
        'url' => base_url('product.php?slug=' . rawurlencode($product['slug'])),
    ];
}
