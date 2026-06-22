<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';
    protected string $primaryKey = 'id';

    public static function findBySlug(string $slug): ?array
    {
        return self::findBy('slug', $slug);
    }

    public static function findBySku(string $sku): ?array
    {
        return self::findBy('sku', $sku);
    }

    public static function getByCategory(int $categoryId): array
    {
        return self::where('category_id', $categoryId);
    }

    public static function getActive(): array
    {
        return self::where('is_active', 1);
    }

    public static function getFeatured(): array
    {
        return self::where('is_featured', 1);
    }

    public static function getInStock(): array
    {
        return self::where('stock_quantity', 0, '>');
    }

    public static function getOutOfStock(): array
    {
        return self::where('stock_quantity', 0);
    }

    public static function getOnSale(): array
    {
        return self::select(
            "SELECT * FROM products WHERE sale_price IS NOT NULL AND sale_price < price AND is_active = 1
             ORDER BY sale_price ASC"
        );
    }

    public static function getByPriceRange(float $min, float $max): array
    {
        return self::select(
            "SELECT * FROM products WHERE price >= ? AND price <= ? AND is_active = 1 ORDER BY price ASC",
            [$min, $max]
        );
    }

    public static function searchProducts(string $query): array
    {
        return self::select(
            "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? OR sku LIKE ?
             ORDER BY name ASC",
            ["%$query%", "%$query%", "%$query%"]
        );
    }

    public static function updateStock(int $id, int $quantity): void
    {
        self::updateRecord($id, ['stock_quantity' => $quantity]);
    }

    public static function decreaseStock(int $id, int $quantity): void
    {
        $product = self::find($id);
        if ($product) {
            $newStock = max(0, $product['stock_quantity'] - $quantity);
            self::updateStock($id, $newStock);
        }
    }

    public static function getLowStock(int $threshold = 10): array
    {
        return self::select(
            "SELECT * FROM products WHERE stock_quantity <= ? AND stock_quantity > 0 AND is_active = 1
             ORDER BY stock_quantity ASC",
            [$threshold]
        );
    }

    public static function getCategoryWithProducts(int $categoryId): array
    {
        return self::select(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = ? OR p.category_id IN (
                SELECT id FROM categories WHERE parent_id = ?
             )
             ORDER BY p.name ASC",
            [$categoryId, $categoryId]
        );
    }

    public static function getRelatedProducts(int $productId, int $categoryId, int $limit = 4): array
    {
        return self::select(
            "SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1
             ORDER BY RAND() LIMIT ?",
            [$categoryId, $productId, $limit]
        );
    }

    public static function toggleActive(int $id): void
    {
        $product = self::find($id);
        if ($product) {
            self::updateRecord($id, ['is_active' => $product['is_active'] ? 0 : 1]);
        }
    }

    public static function toggleFeatured(int $id): void
    {
        $product = self::find($id);
        if ($product) {
            self::updateRecord($id, ['is_featured' => $product['is_featured'] ? 0 : 1]);
        }
    }
}
