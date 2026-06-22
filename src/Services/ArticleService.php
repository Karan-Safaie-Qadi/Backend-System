<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleSection;
use App\Models\Category;
use App\Models\ActivityLog;

class ArticleService
{
    public static function createArticle(array $data, array $sections = []): array
    {
        if (empty($data['title'])) throw new \InvalidArgumentException('Article title is required.');
        $articleData = [
            'title' => $data['title'],
            'slug' => self::slug($data['title']),
            'summary' => $data['summary'] ?? null,
            'main_image' => $data['main_image'] ?? null,
            'author_id' => $data['author_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'is_published' => $data['is_published'] ?? 0,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];
        if (!empty($data['is_published'])) $articleData['published_at'] = date('Y-m-d H:i:s');
        $articleId = Article::create($articleData);
        foreach ($sections as $s) {
            ArticleSection::addSection($articleId, $s['title'] ?? '', $s['section_type'] ?? 'text', $s['content'] ?? null, $s['list_items'] ?? null, $s['table_data'] ?? null, $s['image'] ?? null, $s['sort_order'] ?? null);
        }
        ActivityLog::log($data['_actor_id'] ?? null, 'create_article', 'article', $articleId, "Article '{$data['title']}' created");
        return Article::getWithSections($articleId);
    }

    public static function updateArticle(int $id, array $data, ?array $sections = null): array
    {
        $article = Article::find($id);
        if (!$article) throw new \RuntimeException('Article not found.');
        $update = [];
        foreach (['title', 'summary', 'main_image', 'author_id', 'category_id', 'is_published', 'meta_keywords', 'meta_description'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        if (isset($data['title']) && $data['title'] !== $article['title']) $update['slug'] = self::slug($data['title'], $id);
        if (isset($data['is_published']) && $data['is_published'] && !$article['published_at']) $update['published_at'] = date('Y-m-d H:i:s');
        if ($update) Article::updateRecord($id, $update);
        if ($sections !== null) {
            ArticleSection::deleteByArticle($id);
            foreach ($sections as $s) {
                ArticleSection::addSection($id, $s['title'] ?? '', $s['section_type'] ?? 'text', $s['content'] ?? null, $s['list_items'] ?? null, $s['table_data'] ?? null, $s['image'] ?? null, $s['sort_order'] ?? null);
            }
        }
        ActivityLog::log($data['_actor_id'] ?? null, 'update_article', 'article', $id, "Article '{$article['title']}' updated");
        return Article::getWithSections($id);
    }

    public static function deleteArticle(int $id, ?int $actorId = null): void
    {
        $a = Article::find($id);
        if (!$a) throw new \RuntimeException('Article not found.');
        ArticleSection::deleteByArticle($id);
        Article::deleteRecord($id);
        ActivityLog::log($actorId, 'delete_article', 'article', $id, "Article '{$a['title']}' deleted");
    }

    public static function getArticle(int $id): array
    {
        $a = Article::getWithSections($id);
        if (!$a) throw new \RuntimeException('Article not found.');
        $a['toc'] = ArticleSection::getTableOfContents($id);
        return $a;
    }

    public static function getBySlug(string $slug): ?array
    {
        $a = Article::findBySlug($slug);
        return $a ? Article::getWithSections($a['id']) : null;
    }

    public static function getByCategory(int $categoryId, int $page = 1, int $perPage = 20): array
    {
        return Article::getByCategoryWithPagination($categoryId, $page, $perPage);
    }

    public static function getAllArticles(int $page = 1, int $perPage = 20): array { return Article::paginate($page, $perPage); }
    public static function searchArticles(string $query): array { return Article::searchArticles($query); }

    public static function publishArticle(int $id): void
    {
        Article::publish($id);
        $a = Article::find($id);
        ActivityLog::log(null, 'publish_article', 'article', $id, "Article '{$a['title']}' published");
    }

    public static function unpublishArticle(int $id): void
    {
        Article::unpublish($id);
        $a = Article::find($id);
        ActivityLog::log(null, 'unpublish_article', 'article', $id, "Article '{$a['title']}' unpublished");
    }

    public static function addSection(int $articleId, array $sd): array
    {
        if (!Article::find($articleId)) throw new \RuntimeException('Article not found.');
        return ArticleSection::find(ArticleSection::addSection($articleId, $sd['title'] ?? '', $sd['section_type'] ?? 'text', $sd['content'] ?? null, $sd['list_items'] ?? null, $sd['table_data'] ?? null, $sd['image'] ?? null, $sd['sort_order'] ?? null));
    }

    public static function updateSection(int $sectionId, array $data): array
    {
        if (!ArticleSection::find($sectionId)) throw new \RuntimeException('Section not found.');
        ArticleSection::updateSection($sectionId, $data);
        return ArticleSection::find($sectionId);
    }

    public static function deleteSection(int $sectionId): void { ArticleSection::deleteRecord($sectionId); }
    public static function reorderSections(int $articleId, array $sectionIds): void { ArticleSection::reorder($articleId, $sectionIds); }
    public static function getToc(int $articleId): array { return ArticleSection::getTableOfContents($articleId); }

    public static function getStats(): array
    {
        return [
            'total' => Article::count(),
            'published' => count(Article::getPublished()),
            'drafts' => count(Article::getDrafts()),
            'categories' => count(Category::getByType('article')),
            'archive' => Article::getArchiveByMonth(),
        ];
    }

    private static function slug(string $title, ?int $excludeId = null): string
    {
        $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower(trim($title)))), '-');
        $existing = Article::findBySlug($slug);
        if ($existing && (!$excludeId || $existing['id'] !== $excludeId)) $slug .= '-' . uniqid();
        return $slug;
    }
}
