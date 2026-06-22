<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Config;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::load(__DIR__ . '/../config');
    }

    public function testGetExistingKey(): void
    {
        $this->assertEquals('Backend System', Config::get('app.name'));
    }

    public function testGetRegistrationMode(): void
    {
        $this->assertEquals('email', Config::get('auth.registration_mode'));
    }

    public function testGetDefaultForMissingKey(): void
    {
        $this->assertNull(Config::get('nonexistent.key'));
        $this->assertEquals('default', Config::get('nonexistent.key', 'default'));
    }

    public function testSetAndGet(): void
    {
        Config::set('test.key', 'value');
        $this->assertEquals('value', Config::get('test.key'));
    }

    public function testAll(): void
    {
        $all = Config::all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('auth', $all);
    }
}
