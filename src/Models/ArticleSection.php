<?php

namespace App\Models;

use App\Core\Model;

class ArticleSection extends Model
{
    protected string $table = 'article_sections';
    protected string $primaryKey = 'id';
    protected bool $useTimestamps = true;

    const TYPE_TEXT = 'text';
    const TYPE_LIST = 'list';
    const TYPE_TABLE = 'table';
    const TYPE_IMAGE = 'image';
    const TYPE_MIXED = 'mixed';

    public static function getByArticle(int $articleId): array
    {
        return self::select(
            "SELECT * FROM article_sections WHERE article_id = ? ORDER BY sort_order ASC",
            [$articleId]
        );
    }

    public static function addSection(int $articleId, string $title, string $type = self::TYPE_TEXT,
                                      ?string $content = null, ?array $listItems = null,
                                      ?array $tableData = null, ?string $image = null,
                                      ?int $sortOrder = null): int|string
    {
        if ($sortOrder === null) {
            $max = self::selectOne(
                "SELECT MAX(sort_order) as max_sort FROM article_sections WHERE article_id = ?",
                [$articleId]
            );
            $sortOrder = ((int)($max['max_sort'] ?? -1)) + 1;
        }

        $data = [
            'article_id' => $articleId,
            'title' => $title,
            'section_type' => $type,
            'sort_order' => $sortOrder,
        ];

        if ($content !== null) $data['content'] = $content;
        if ($listItems !== null) $data['list_items'] = json_encode($listItems, JSON_UNESCAPED_UNICODE);
        if ($tableData !== null) $data['table_data'] = json_encode($tableData, JSON_UNESCAPED_UNICODE);
        if ($image !== null) $data['image'] = $image;

        return self::create($data);
    }

    public static function updateSection(int $sectionId, array $data): int
    {
        if (isset($data['list_items']) && is_array($data['list_items'])) {
            $data['list_items'] = json_encode($data['list_items'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($data['table_data']) && is_array($data['table_data'])) {
            $data['table_data'] = json_encode($data['table_data'], JSON_UNESCAPED_UNICODE);
        }

        return self::updateRecord($sectionId, $data);
    }

    public static function reorder(int $articleId, array $sectionIds): void
    {
        foreach ($sectionIds as $index => $sectionId) {
            self::updateRecord($sectionId, [
                'article_id' => $articleId,
                'sort_order' => $index,
            ]);
        }
    }

    public static function deleteByArticle(int $articleId): void
    {
        self::deleteWhere(['article_id' => $articleId]);
    }

    public static function duplicateSections(int $sourceArticleId, int $targetArticleId): void
    {
        $sections = self::getByArticle($sourceArticleId);
        foreach ($sections as $section) {
            self::addSection(
                $targetArticleId,
                $section['title'],
                $section['section_type'],
                $section['content'],
                $section['list_items'] ? json_decode($section['list_items'], true) : null,
                $section['table_data'] ? json_decode($section['table_data'], true) : null,
                $section['image'],
                $section['sort_order']
            );
        }
    }

    public static function getTableOfContents(int $articleId): array
    {
        return self::select(
            "SELECT id, title, section_type, sort_order
             FROM article_sections
             WHERE article_id = ?
             ORDER BY sort_order ASC",
            [$articleId]
        );
    }
}
