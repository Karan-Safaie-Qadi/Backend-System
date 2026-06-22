<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Session;

class SessionTest extends TestCase
{
    public function testSetAndGet(): void
    {
        Session::set('test_key', 'test_value');
        $this->assertEquals('test_value', Session::get('test_key'));
    }

    public function testHas(): void
    {
        Session::set('exists_key', 'value');
        $this->assertTrue(Session::has('exists_key'));
        $this->assertFalse(Session::has('nonexistent'));
    }

    public function testRemove(): void
    {
        Session::set('remove_key', 'value');
        Session::remove('remove_key');
        $this->assertFalse(Session::has('remove_key'));
    }

    public function testGetDefault(): void
    {
        $this->assertNull(Session::get('nonexistent'));
        $this->assertEquals('default', Session::get('nonexistent', 'default'));
    }

    public function testFlash(): void
    {
        Session::flash('flash_key', 'flash_value');
        $this->assertEquals('flash_value', Session::flash('flash_key'));
        $this->assertNull(Session::flash('flash_key'));
    }
}
