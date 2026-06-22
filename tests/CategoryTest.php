<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\Category;

class CategoryTest extends TestCase
{
    public function testCategoryExtendsModel()
    {
        $reflection = new \ReflectionClass(Category::class);
        $this->assertEquals('App\Core\Model', $reflection->getParentClass()->getName());
    }

    public function testCategoryTableName()
    {
        $reflection = new \ReflectionClass(Category::class);
        $prop = $reflection->getProperty('table');
        $prop->setAccessible(true);
        $this->assertEquals('categories', $prop->getValue(new Category()));
    }

    public function testHasRequiredMethods()
    {
        $methods = ['findBySlug', 'getByType', 'getCategoryTree', 'searchCategories'];
        foreach ($methods as $method) {
            $this->assertTrue(method_exists(Category::class, $method), "Missing: $method");
        }
    }
}
