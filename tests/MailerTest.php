<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Mailer;

class MailerTest extends TestCase
{
    public function testMailerClassExists(): void
    {
        $this->assertTrue(class_exists(Mailer::class));
    }

    public function testMailerHasSendMethod(): void
    {
        $this->assertTrue(method_exists(Mailer::class, 'send'));
    }

    public function testMailerHasSendWithTemplateMethod(): void
    {
        $this->assertTrue(method_exists(Mailer::class, 'sendWithTemplate'));
    }

    public function testSendWithInvalidEmailThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        Mailer::send('', 'Test', 'Body');
    }

    public function testSendWithTemplateInvalidTemplate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Mailer::sendWithTemplate('test@test.com', 'Test', 'nonexistent_template');
    }
}
