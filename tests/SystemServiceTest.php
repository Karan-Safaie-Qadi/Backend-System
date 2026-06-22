<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\SystemService;

class SystemServiceTest extends TestCase
{
    public function testGenerateSlug()
    {
        $this->assertEquals('hello-world', SystemService::generateSlug('Hello World'));
        $this->assertEquals('test-slug', SystemService::generateSlug('Test Slug!'));
        $this->assertEquals('a-b-c', SystemService::generateSlug('  A  B  C  '));
    }

    public function testSanitizeInput()
    {
        $this->assertEquals('alert("xss")', SystemService::sanitizeInput('<script>alert("xss")</script>'));
        $this->assertEquals('hello', SystemService::sanitizeInput('  <b>hello</b>  '));
    }

    public function testGenerateToken()
    {
        $token = SystemService::generateToken();
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testGenerateTokenCustomLength()
    {
        $token = SystemService::generateToken(16);
        $this->assertEquals(32, strlen($token));
    }

    public function testRegisterAndCallMethod()
    {
        SystemService::registerMethod('multiply', function($a, $b) {
            return $a * $b;
        });

        $this->assertEquals(15, SystemService::callMethod('multiply', 3, 5));
        $this->assertTrue(SystemService::hasMethod('multiply'));
    }

    public function testCallNonExistentMethod()
    {
        $this->expectException(\RuntimeException::class);
        SystemService::callMethod('nonexistent');
    }

    public function testGetRegisteredMethods()
    {
        SystemService::registerMethod('test_method', function() {});
        $methods = SystemService::getRegisteredMethods();
        $this->assertContains('test_method', $methods);
    }

    public function testDateFormat()
    {
        $result = SystemService::dateFormat('2024-01-15 10:30:00', 'Y/m/d');
        $this->assertEquals('2024/01/15', $result);
    }

    public function testTimeAgo()
    {
        $result = SystemService::timeAgo(date('Y-m-d H:i:s'));
        $this->assertEquals('just now', $result);
    }
}
