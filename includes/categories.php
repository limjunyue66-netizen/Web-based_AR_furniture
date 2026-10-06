<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Category data access.
 */

function categories_all(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM categories';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    return db()->query($sql)->fetchAll();
}

function category_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function category_product_count(int $categoryId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $stmt->execute([$categoryId]);
    return (int) $stmt->fetchColumn();
}

function category_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO categories (slug, name, sort_order, is_active, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $data['slug'],
        $data['name'],
        (int) $data['sort_order'],
        (int) $data['is_active'],
    ]);
    return (int) db()->lastInsertId();
}

function category_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE categories SET slug = ?, name = ?, sort_order = ?, is_active = ? WHERE id = ?'
    );
    $stmt->execute([
        $data['slug'],
        $data['name'],
        (int) $data['sort_order'],
        (int) $data['is_active'],
        $id,
    ]);
}

function category_delete(int $id): void
{
    $count = category_product_count($id);
    if ($count > 0) {
        throw new RuntimeException('Cannot delete this category because it still contains products. Move or delete those products first.');
    }
    $stmt = db()->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
}

function categories_count(bool $activeOnly = false): int
{
    $sql = 'SELECT COUNT(*) FROM categories';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    return (int) db()->query($sql)->fetchColumn();
}
