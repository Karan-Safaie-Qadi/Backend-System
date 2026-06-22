<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table = 'categories';
    protected string $primaryKey = 'id';

    public static function findBySlug(string $slug): ?array
    {
        return self::findBy('slug', $slug);
    }

    public static function getByType(string $type): array
    {
        return self::where('type', $type);
    }

    public static function getParentCategories(): array
    {
        return self::where('parent_id', null, 'IS');
    }

    public static function getChildren(int $parentId): array
    {
        return self::where('parent_id', $parentId);
    }

    public static function getCategoryTree(string $type = null): array
    {
        $condition = $type ? "WHERE type = ?" : "";
        $params = $type ? [$type] : [];

        $categories = self::select(
            "SELECT * FROM categories $condition ORDER BY parent_id IS NULL DESC, parent_id, sort_order",
            $params
        );

        $tree = [];
        $children = [];

        foreach ($categories as $cat) {
            if ($cat['parent_id'] === null) {
                $cat['children'] = [];
                $tree[$cat['id']] = $cat;
            } else {
                $children[$cat['parent_id']][] = $cat;
            }
        }

        foreach ($children as $parentId => $childList) {
            if (isset($tree[$parentId])) {
                $tree[$parentId]['children'] = $childList;
            }
        }

        return array_values($tree);
    }

    public static function getWithProductCount(): array
    {
        return self::select(
            "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
             FROM categories c
             WHERE c.type = 'product'
             ORDER BY c.sort_order"
        );
    }

    public static function getWithArticleCount(): array
    {
        return self::select(
            "SELECT c.*, (SELECT COUNT(*) FROM articles a WHERE a.category_id = c.id) as article_count
             FROM categories c
             WHERE c.type = 'article'
             ORDER BY c.sort_order"
        );
    }

    public static function searchCategories(string $query): array
    {
        return self::search('name', $query);
    }
}
