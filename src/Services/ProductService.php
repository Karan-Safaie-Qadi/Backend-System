<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\ActivityLog;

class ProductService
{
    public static function createProduct(array $data): array
    {
        if (empty($data['name'])) throw new \InvalidArgumentException('Product name is required.');
        $productData = [
            'name' => $data['name'],
            'slug' => self::slug($data['name']),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'sku' => $data['sku'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'main_image' => $data['main_image'] ?? null,
            'gallery' => isset($data['gallery']) ? json_encode($data['gallery'], JSON_UNESCAPED_UNICODE) : null,
            'specifications' => isset($data['specifications']) ? json_encode($data['specifications'], JSON_UNESCAPED_UNICODE) : null,
            'is_featured' => $data['is_featured'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'category_id' => $data['category_id'] ?? null,
        ];
        $productId = Product::create($productData);
        ActivityLog::log($data['_actor_id'] ?? null, 'create_product', 'product', $productId, "Product '{$data['name']}' created");
        return Product::find($productId);
    }

    public static function updateProduct(int $id, array $data): array
    {
        $product = Product::find($id);
        if (!$product) throw new \RuntimeException('Product not found.');
        $update = [];
        foreach (['name', 'description', 'short_description', 'price', 'sale_price', 'sku', 'stock_quantity', 'main_image', 'is_featured', 'is_active', 'category_id'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        if (isset($data['name']) && $data['name'] !== $product['name']) $update['slug'] = self::slug($data['name'], $id);
        if (isset($data['gallery'])) $update['gallery'] = json_encode($data['gallery'], JSON_UNESCAPED_UNICODE);
        if (isset($data['specifications'])) $update['specifications'] = json_encode($data['specifications'], JSON_UNESCAPED_UNICODE);
        if ($update) Product::updateRecord($id, $update);
        ActivityLog::log($data['_actor_id'] ?? null, 'update_product', 'product', $id, "Product '{$product['name']}' updated");
        return Product::find($id);
    }

    public static function deleteProduct(int $id, ?int $actorId = null): void
    {
        $p = Product::find($id);
        if (!$p) throw new \RuntimeException('Product not found.');
        Product::deleteRecord($id);
        ActivityLog::log($actorId, 'delete_product', 'product', $id, "Product '{$p['name']}' deleted");
    }

    public static function getProduct(int $id): array
    {
        $p = Product::find($id);
        if (!$p) throw new \RuntimeException('Product not found.');
        if ($p['gallery']) $p['gallery'] = json_decode($p['gallery'], true);
        if ($p['specifications']) $p['specifications'] = json_decode($p['specifications'], true);
        return $p;
    }

    public static function getBySlug(string $slug): ?array: ?array { return Product::findBySlug($slug); }

    public static function getByCategory(int $categoryId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $total = Product::selectOne("SELECT COUNT(*) as c FROM products WHERE category_id = ?", [$categoryId]);
        $items = Product::select("SELECT * FROM products WHERE category_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?", [$categoryId, $perPage, $offset]);
        return ['items' => $items, 'total' => (int)($total['c'] ?? 0), 'page' => $page, 'per_page' => $perPage, 'total_pages' => ceil(($total['c'] ?? 0) / $perPage)];
    }

    public static function getAllProducts(int $page = 1, int $perPage = 20): array { return Product::paginate($page, $perPage); }
    public static function searchProducts(string $query): array { return Product::searchProducts($query); }
    public static function updateStock(int $id, int $quantity): void { Product::updateStock($id, $quantity); }
    public static function getFeatured(): array { return Product::getFeatured(); }
    public static function getOnSale(): array { return Product::getOnSale(); }
    public static function getLowStock(int $threshold = 10): array { return Product::getLowStock($threshold); }

    public static function getStats(): array
    {
        return [
            'total' => Product::count(),
            'active' => count(Product::getActive()),
            'out_of_stock' => count(Product::getOutOfStock()),
            'on_sale' => count(Product::getOnSale()),
            'categories' => count(Category::getByType('product')),
        ];
    }

    private static function slug(string $name, ?int $excludeId = null): string
    {
        $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower(trim($name)))), '-');
        $existing = Product::findBySlug($slug);
        if ($existing && (!$excludeId || $existing['id'] !== $excludeId)) $slug .= '-' . uniqid();
        return $slug;
    }
}
