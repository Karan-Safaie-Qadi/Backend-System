<?php

namespace App\Models;

use App\Core\Model;

class Article extends Model
{
    protected string $table = 'articles';
    protected string $primaryKey = 'id';

    public static function findBySlug(string $slug): ?array
    {
        return self::findBy('slug', $slug);
    }

    public static function getByCategory(int $categoryId): array
    {
        return self::where('category_id', $categoryId);
    }

    public static function getByAuthor(int $authorId): array
    {
        return self::where('author_id', $authorId);
    }

    public static function getPublished(): array
    {
        return self::where('is_published', 1);
    }

    public static function getDrafts(): array
    {
        return self::where('is_published', 0);
    }

    public static function searchArticles(string $query): array
    {
        return self::select(
            "SELECT * FROM articles WHERE title LIKE ? OR summary LIKE ? OR meta_keywords LIKE ? ORDER BY created_at DESC",
            array_fill(0, 3, "%$query%")
        );
    }

    public static function publish(int $id): void
    {
        self::updateRecord($id, ['is_published' => 1, 'published_at' => date('Y-m-d H:i:s')]);
    }

    public static function unpublish(int $id): void
    {
        self::updateRecord($id, ['is_published' => 0]);
    }

    public static function getWithSections(int $id): ?array
    {
        $article = self::find($id);
        if (!$article) return null;
        $article['sections'] = ArticleSection::getByArticle($id);
        if ($article['category_id']) $article['category'] = Category::find($article['category_id']);
        return $article;
    }

    public static function getRecent(int $limit = 10): array
    {
        return self::select("SELECT * FROM articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT ?", [$limit]);
    }

    public static function getPopular(int $limit = 10): array
    {
        return self::select(
            "SELECT a.*, COUNT(al.id) as view_count FROM articles a
             LEFT JOIN activity_logs al ON al.entity_type = 'article' AND al.entity_id = a.id AND al.action = 'view'
             WHERE a.is_published = 1 GROUP BY a.id ORDER BY view_count DESC LIMIT ?",
            [$limit]
        );
    }

    public static function getByCategoryWithPagination(int $categoryId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $total = self::selectOne("SELECT COUNT(*) as c FROM articles WHERE category_id = ? AND is_published = 1", [$categoryId]);
        $items = self::select(
            "SELECT * FROM articles WHERE category_id = ? AND is_published = 1 ORDER BY published_at DESC LIMIT ? OFFSET ?",
            [$categoryId, $perPage, $offset]
        );
        return ['items' => $items, 'total' => (int)($total['c'] ?? 0), 'page' => $page, 'per_page' => $perPage, 'total_pages' => ceil(($total['c'] ?? 0) / $perPage)];
    }

    public static function getArchiveByMonth(): array
    {
        return self::select(
            "SELECT DATE_FORMAT(published_at, '%Y-%m') as month, DATE_FORMAT(published_at, '%M %Y') as month_name, COUNT(*) as count
             FROM articles WHERE is_published = 1 GROUP BY month, month_name ORDER BY month DESC"
        );
    }

    public static function getRelatedArticles(int $articleId, int $categoryId, int $limit = 4): array
    {
        return self::select(
            "SELECT * FROM articles WHERE category_id = ? AND id != ? AND is_published = 1 ORDER BY RAND() LIMIT ?",
            [$categoryId, $articleId, $limit]
        );
    }
}
