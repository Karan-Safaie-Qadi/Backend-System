<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\ActivityLog;

class ActivityLogTest extends TestCase
{
    public function testLogExtendsModel()
    {
        $this->assertEquals('App\Core\Model', (new \ReflectionClass(ActivityLog::class))->getParentClass()->getName());
    }

    public function testTimestampsDisabled()
    {
        $r = new \ReflectionClass(ActivityLog::class);
        $p = $r->getProperty('useTimestamps');
        $p->setAccessible(true);
        $this->assertFalse($p->getValue(new ActivityLog()));
    }

    public function testHasMethods()
    {
        foreach (['log', 'getByUser', 'getByEntity', 'getRecent', 'countToday', 'cleanOld'] as $m) {
            $this->assertTrue(method_exists(ActivityLog::class, $m), "Missing: $m");
        }
    }
}
