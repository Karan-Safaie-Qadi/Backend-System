<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\Product;

class ProductTest extends TestCase
{
    public function testProductExtendsModel()
    {
        $this->assertEquals('App\Core\Model', (new \ReflectionClass(Product::class))->getParentClass()->getName());
    }

    public function testProductTable()
    {
        $r = new \ReflectionClass(Product::class);
        $p = $r->getProperty('table');
        $p->setAccessible(true);
        $this->assertEquals('products', $p->getValue(new Product()));
    }

    public function testHasCoreMethods()
    {
        foreach (['findBySlug', 'findBySku', 'getByCategory', 'getActive', 'getFeatured', 'getOnSale', 'getLowStock', 'searchProducts', 'updateStock'] as $m) {
            $this->assertTrue(method_exists(Product::class, $m), "Missing: $m");
        }
    }
}
