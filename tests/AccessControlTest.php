<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Auth\AccessControl;

class AccessControlTest extends TestCase
{
    public function testGetLevels()
    {
        $levels = AccessControl::getLevels();
        $this->assertEquals([1 => 'user', 2 => 'admin', 3 => 'owner'], $levels);
    }

    public function testGetLevelName()
    {
        $this->assertEquals('user', AccessControl::getLevelName(1));
        $this->assertEquals('admin', AccessControl::getLevelName(2));
        $this->assertEquals('owner', AccessControl::getLevelName(3));
        $this->assertEquals('unknown', AccessControl::getLevelName(99));
    }

    public function testGetLevelValue()
    {
        $this->assertEquals(1, AccessControl::getLevelValue('user'));
        $this->assertEquals(2, AccessControl::getLevelValue('admin'));
        $this->assertEquals(3, AccessControl::getLevelValue('owner'));
        $this->assertNull(AccessControl::getLevelValue('superadmin'));
    }

    public function testHasAccess()
    {
        $this->assertTrue(AccessControl::hasAccess(2, 'user'));
        $this->assertTrue(AccessControl::hasAccess(2, 'admin'));
        $this->assertFalse(AccessControl::hasAccess(2, 'owner'));
        $this->assertTrue(AccessControl::hasAccess(3, 'owner'));
    }

    public function testIsAdmin()
    {
        $this->assertFalse(AccessControl::isAdmin(1));
        $this->assertTrue(AccessControl::isAdmin(2));
        $this->assertTrue(AccessControl::isAdmin(3));
    }

    public function testIsOwner()
    {
        $this->assertFalse(AccessControl::isOwner(1));
        $this->assertFalse(AccessControl::isOwner(2));
        $this->assertTrue(AccessControl::isOwner(3));
    }

    public function testCanManageAdmins()
    {
        $this->assertFalse(AccessControl::canManageAdmins(1));
        $this->assertFalse(AccessControl::canManageAdmins(2));
        $this->assertTrue(AccessControl::canManageAdmins(3));
    }

    public function testRequireLevelPasses()
    {
        AccessControl::requireLevel(3, 'user');
        $this->assertTrue(true);
    }

    public function testRequireLevelThrows()
    {
        $this->expectException(\RuntimeException::class);
        AccessControl::requireLevel(1, 'admin');
    }
}
