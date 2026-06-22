<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\ActivityLog;

class ProductService
{
    public static function createProduct(array $data): array
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Product name is required.');
        }

        $slug = self::generateSlug($data['name']);

        $productData = [
            'name' => $data['name'],
            'slug' => $slug,
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

        ActivityLog::log(
            $data['_actor_id'] ?? null,
            'create_product',
            'product',
            $productId,
            "Product '{$data['name']}' created"
        );

        return Product::find($productId);
    }

    public static function updateProduct(int $id, array $data): array
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \RuntimeException('Product not found.');
        }

        $updateData = [];

        $fields = ['name', 'description', 'short_description', 'price', 'sale_price',
                   'sku', 'stock_quantity', 'main_image', 'is_featured', 'is_active', 'category_id'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['name']) && $data['name'] !== $product['name']) {
            $updateData['slug'] = self::generateSlug($data['name'], $id);
        }

        if (isset($data['gallery'])) {
            $updateData['gallery'] = json_encode($data['gallery'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($data['specifications'])) {
            $updateData['specifications'] = json_encode($data['specifications'], JSON_UNESCAPED_UNICODE);
        }

        if (!empty($updateData)) {
            Product::updateRecord($id, $updateData);
        }

        ActivityLog::log(
            $data['_actor_id'] ?? null,
            'update_product',
            'product',
            $id,
            "Product '{$product['name']}' updated"
        );

        return Product::find($id);
    }

    public static function deleteProduct(int $id, ?int $actorId = null): void
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \RuntimeException('Product not found.');
        }

        Product::deleteRecord($id);

        ActivityLog::log(
            $actorId,
            'delete_product',
            'product',
            $id,
            "Product '{$product['name']}' deleted"
        );
    }

    public static function getProduct(int $id): array
    {
        $product = Product::find($id);
        if (!$product) {
            throw new \RuntimeException('Product not found.');
        }

        if ($product['gallery']) {
            $product['gallery'] = json_decode($product['gallery'], true);
        }
        if ($product['specifications']) {
            $product['specifications'] = json_decode($product['specifications'], true);
        }

        return $product;
    }

    public static function getBySlug(string $slug): ?array
    {
        return Product::findBySlug($slug);
    }

    public static function getByCategory(int $categoryId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $total = Product::selectOne(
            "SELECT COUNT(*) as count FROM products WHERE category_id = ?",
            [$categoryId]
        );
        $totalCount = (int)($total['count'] ?? 0);

        $items = Product::select(
            "SELECT * FROM products WHERE category_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$categoryId, $perPage, $offset]
        );

        return [
            'items' => $items,
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
        ];
    }

    public static function getAllProducts(int $page = 1, int $perPage = 20): array
    {
        return Product::paginate($page, $perPage);
    }

    public static function searchProducts(string $query): array
    {
        return Product::searchProducts($query);
    }

    public static function updateStock(int $id, int $quantity): void
    {
        Product::updateStock($id, $quantity);
    }

    public static function getFeatured(): array
    {
        return Product::getFeatured();
    }

    public static function getOnSale(): array
    {
        return Product::getOnSale();
    }

    public static function getLowStock(int $threshold = 10): array
    {
        return Product::getLowStock($threshold);
    }

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

    private static function generateSlug(string $name, int $excludeId = null): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower(trim($name)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $existing = Product::findBySlug($slug);
        if ($existing && (!$excludeId || $existing['id'] !== $excludeId)) {
            $slug .= '-' . uniqid();
        }

        return $slug;
    }
}
