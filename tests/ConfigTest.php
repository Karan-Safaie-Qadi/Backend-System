<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Config;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::load(__DIR__ . '/../config');
    }

    public function testGetExistingKey()
    {
        $this->assertEquals('Backend System', Config::get('app.name'));
    }

    public function testGetRegistrationMode()
    {
        $this->assertEquals('email', Config::get('auth.registration_mode'));
    }

    public function testGetDefaultForMissingKey()
    {
        $this->assertNull(Config::get('nonexistent.key'));
        $this->assertEquals('default', Config::get('nonexistent.key', 'default'));
    }

    public function testSetAndGet()
    {
        Config::set('test.key', 'value');
        $this->assertEquals('value', Config::get('test.key'));
    }

    public function testAll()
    {
        $all = Config::all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('auth', $all);
    }
}
