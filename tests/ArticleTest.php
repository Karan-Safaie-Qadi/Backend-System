<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\Article;
use App\Models\ArticleSection;

class ArticleTest extends TestCase
{
    public function testArticleExtendsModel()
    {
        $this->assertEquals('App\Core\Model', (new \ReflectionClass(Article::class))->getParentClass()->getName());
    }

    public function testArticleTable()
    {
        $r = new \ReflectionClass(Article::class);
        $p = $r->getProperty('table');
        $p->setAccessible(true);
        $this->assertEquals('articles', $p->getValue(new Article()));
    }

    public function testArticleHasMethods()
    {
        foreach (['findBySlug', 'getByCategory', 'getPublished', 'getDrafts', 'publish', 'unpublish', 'getWithSections', 'getArchiveByMonth'] as $m) {
            $this->assertTrue(method_exists(Article::class, $m), "Missing: $m");
        }
    }

    public function testArticleSectionTypes()
    {
        $this->assertEquals('text', ArticleSection::TYPE_TEXT);
        $this->assertEquals('list', ArticleSection::TYPE_LIST);
        $this->assertEquals('table', ArticleSection::TYPE_TABLE);
        $this->assertEquals('image', ArticleSection::TYPE_IMAGE);
        $this->assertEquals('mixed', ArticleSection::TYPE_MIXED);
    }

    public function testArticleSectionHasMethods()
    {
        foreach (['getByArticle', 'addSection', 'getTableOfContents', 'reorder', 'deleteByArticle'] as $m) {
            $this->assertTrue(method_exists(ArticleSection::class, $m), "Missing: $m");
        }
    }
}
