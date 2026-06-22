<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Model;

class ModelTest extends TestCase
{
    public function testModelIsAbstract()
    {
        $reflection = new \ReflectionClass(Model::class);
        $this->assertTrue($reflection->isAbstract());
    }

    public function testModelExtendsBaseModel()
    {
        $reflection = new \ReflectionClass(Model::class);
        $parent = $reflection->getParentClass();
        $this->assertEquals('Models\Model', $parent->getName());
    }

    public function testModelHasRequiredMethods()
    {
        $methods = [
            'find', 'all', 'create', 'updateRecord', 'deleteRecord',
            'findBy', 'where', 'count', 'exists', 'paginate',
            'search', 'pluck', 'latest', 'oldest',
        ];
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(Model::class, $method),
                "Method '$method' not found on Model"
            );
        }
    }
}
