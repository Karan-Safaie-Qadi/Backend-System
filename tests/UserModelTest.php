<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserModelTest extends TestCase
{
    public function testUserExtendsModel()
    {
        $this->assertEquals('App\Core\Model', (new \ReflectionClass(User::class))->getParentClass()->getName());
    }

    public function testUserTable()
    {
        $r = new \ReflectionClass(User::class);
        $p = $r->getProperty('table');
        $p->setAccessible(true);
        $this->assertEquals('users', $p->getValue(new User()));
    }

    public function testHasFinderMethods()
    {
        $methods = ['findByUsername', 'findByEmail', 'findByPhone', 'findByRememberToken', 'findByPasswordResetToken'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(User::class, $m), "Missing: $m");
        }
    }

    public function testHasAuthHelperMethods()
    {
        $methods = ['updatePassword', 'updateLastLogin', 'setRememberToken', 'clearRememberToken',
                     'verifyEmail', 'verifyPhone', 'isEmailVerified', 'isPhoneVerified', 'isActive'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(User::class, $m), "Missing: $m");
        }
    }

    public function testHasAdminMethods()
    {
        $methods = ['getAdmins', 'getByAccessLevel', 'getRegularUsers', 'countByAccessLevel'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(User::class, $m), "Missing: $m");
        }
    }
}
