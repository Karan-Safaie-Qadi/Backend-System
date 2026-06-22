<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Mailer;

class MailerTest extends TestCase
{
    public function testMailerClassExists()
    {
        $this->assertTrue(class_exists(Mailer::class));
    }

    public function testMailerHasSendMethod()
    {
        $this->assertTrue(method_exists(Mailer::class, 'send'));
    }

    public function testMailerHasSendWithTemplateMethod()
    {
        $this->assertTrue(method_exists(Mailer::class, 'sendWithTemplate'));
    }

    public function testSendWithInvalidEmailThrows()
    {
        $this->expectException(\RuntimeException::class);
        Mailer::send('', 'Test', 'Body');
    }

    public function testSendWithTemplateInvalidTemplate()
    {
        $this->expectException(\InvalidArgumentException::class);
        Mailer::sendWithTemplate('test@test.com', 'Test', 'nonexistent_template');
    }
}
