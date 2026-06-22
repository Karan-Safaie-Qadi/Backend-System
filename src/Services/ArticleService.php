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
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Article title is required.');
        }

        $slug = self::generateSlug($data['title']);

        $articleData = [
            'title' => $data['title'],
            'slug' => $slug,
            'summary' => $data['summary'] ?? null,
            'main_image' => $data['main_image'] ?? null,
            'author_id' => $data['author_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'is_published' => $data['is_published'] ?? 0,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];

        if (!empty($data['is_published'])) {
            $articleData['published_at'] = date('Y-m-d H:i:s');
        }

        $articleId = Article::create($articleData);

        foreach ($sections as $section) {
            ArticleSection::addSection(
                $articleId,
                $section['title'] ?? '',
                $section['section_type'] ?? ArticleSection::TYPE_TEXT,
                $section['content'] ?? null,
                $section['list_items'] ?? null,
                $section['table_data'] ?? null,
                $section['image'] ?? null,
                $section['sort_order'] ?? null
            );
        }

        ActivityLog::log(
            $data['_actor_id'] ?? null,
            'create_article',
            'article',
            $articleId,
            "Article '{$data['title']}' created"
        );

        return Article::getWithSections($articleId);
    }

    public static function updateArticle(int $id, array $data, array $sections = null): array
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \RuntimeException('Article not found.');
        }

        $updateData = [];

        $fields = ['title', 'summary', 'main_image', 'author_id', 'category_id',
                   'is_published', 'meta_keywords', 'meta_description'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['title']) && $data['title'] !== $article['title']) {
            $updateData['slug'] = self::generateSlug($data['title'], $id);
        }

        if (isset($data['is_published']) && $data['is_published'] && !$article['published_at']) {
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }

        if (!empty($updateData)) {
            Article::updateRecord($id, $updateData);
        }

        if ($sections !== null) {
            ArticleSection::deleteByArticle($id);
            foreach ($sections as $section) {
                ArticleSection::addSection(
                    $id,
                    $section['title'] ?? '',
                    $section['section_type'] ?? ArticleSection::TYPE_TEXT,
                    $section['content'] ?? null,
                    $section['list_items'] ?? null,
                    $section['table_data'] ?? null,
                    $section['image'] ?? null,
                    $section['sort_order'] ?? null
                );
            }
        }

        ActivityLog::log(
            $data['_actor_id'] ?? null,
            'update_article',
            'article',
            $id,
            "Article '{$article['title']}' updated"
        );

        return Article::getWithSections($id);
    }

    public static function deleteArticle(int $id, ?int $actorId = null): void
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \RuntimeException('Article not found.');
        }

        ArticleSection::deleteByArticle($id);
        Article::deleteRecord($id);

        ActivityLog::log(
            $actorId,
            'delete_article',
            'article',
            $id,
            "Article '{$article['title']}' deleted"
        );
    }

    public static function getArticle(int $id): array
    {
        $article = Article::getWithSections($id);
        if (!$article) {
            throw new \RuntimeException('Article not found.');
        }

        $article['toc'] = ArticleSection::getTableOfContents($id);

        return $article;
    }

    public static function getBySlug(string $slug): ?array
    {
        $article = Article::findBySlug($slug);
        if (!$article) {
            return null;
        }
        return Article::getWithSections($article['id']);
    }

    public static function getByCategory(int $categoryId, int $page = 1, int $perPage = 20): array
    {
        return Article::getByCategoryWithPagination($categoryId, $page, $perPage);
    }

    public static function getAllArticles(int $page = 1, int $perPage = 20): array
    {
        return Article::paginate($page, $perPage);
    }

    public static function searchArticles(string $query): array
    {
        return Article::searchArticles($query);
    }

    public static function publishArticle(int $id): void
    {
        Article::publish($id);

        $article = Article::find($id);
        ActivityLog::log(null, 'publish_article', 'article', $id, "Article '{$article['title']}' published");
    }

    public static function unpublishArticle(int $id): void
    {
        Article::unpublish($id);

        $article = Article::find($id);
        ActivityLog::log(null, 'unpublish_article', 'article', $id, "Article '{$article['title']}' unpublished");
    }

    public static function addSection(int $articleId, array $sectionData): array
    {
        $article = Article::find($articleId);
        if (!$article) {
            throw new \RuntimeException('Article not found.');
        }

        $sectionId = ArticleSection::addSection(
            $articleId,
            $sectionData['title'] ?? '',
            $sectionData['section_type'] ?? ArticleSection::TYPE_TEXT,
            $sectionData['content'] ?? null,
            $sectionData['list_items'] ?? null,
            $sectionData['table_data'] ?? null,
            $sectionData['image'] ?? null,
            $sectionData['sort_order'] ?? null
        );

        return ArticleSection::find($sectionId);
    }

    public static function updateSection(int $sectionId, array $data): array
    {
        $section = ArticleSection::find($sectionId);
        if (!$section) {
            throw new \RuntimeException('Section not found.');
        }

        ArticleSection::updateSection($sectionId, $data);
        return ArticleSection::find($sectionId);
    }

    public static function deleteSection(int $sectionId): void
    {
        ArticleSection::deleteRecord($sectionId);
    }

    public static function reorderSections(int $articleId, array $sectionIds): void
    {
        ArticleSection::reorder($articleId, $sectionIds);
    }

    public static function getToc(int $articleId): array
    {
        return ArticleSection::getTableOfContents($articleId);
    }

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

    private static function generateSlug(string $title, int $excludeId = null): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower(trim($title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $existing = Article::findBySlug($slug);
        if ($existing && (!$excludeId || $existing['id'] !== $excludeId)) {
            $slug .= '-' . uniqid();
        }

        return $slug;
    }
}
