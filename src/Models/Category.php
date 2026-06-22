<?php

declare(strict_types=1);

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
        $cond = $type ? "WHERE type = ?" : "";
        $cats = self::select("SELECT * FROM categories $cond ORDER BY parent_id IS NULL DESC, parent_id, sort_order", $type ? [$type] : []);
        $tree = [];
        $children = [];
        foreach ($cats as $c) {
            if ($c['parent_id'] === null) { $c['children'] = []; $tree[$c['id']] = $c; }
            else { $children[$c['parent_id']][] = $c; }
        }
        foreach ($children as $pid => $list) {
            if (isset($tree[$pid])) $tree[$pid]['children'] = $list;
        }
        return array_values($tree);
    }

    public static function getWithProductCount(): array
    {
        return self::select(
            "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
             FROM categories c WHERE c.type = 'product' ORDER BY c.sort_order"
        );
    }

    public static function getWithArticleCount(): array
    {
        return self::select(
            "SELECT c.*, (SELECT COUNT(*) FROM articles a WHERE a.category_id = c.id) as article_count
             FROM categories c WHERE c.type = 'article' ORDER BY c.sort_order"
        );
    }

    public static function searchCategories(string $query): array
    {
        return self::search('name', $query);
    }
}
