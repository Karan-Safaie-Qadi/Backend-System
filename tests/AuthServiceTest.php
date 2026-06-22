<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;

class AuthServiceTest extends TestCase
{
    public function testRegisterValidationEmptyUsername()
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthService::register(['username' => '']);
    }

    public function testRegisterValidationShortPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthService::register(['username' => 'test', 'password' => '123']);
    }

    public function testRegisterValidationNoEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthService::register(['username' => 'test', 'password' => '12345678']);
    }

    public function testLoginWithInvalidCredentials()
    {
        $this->expectException(\RuntimeException::class);
        AuthService::login('nonexistent_user', 'wrong_password');
    }
}
