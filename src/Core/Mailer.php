<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    private static ?PHPMailer $mailer = null;

    private static function getInstance(): PHPMailer
    {
        if (self::$mailer === null) {
            self::$mailer = new PHPMailer(true);

            $host = Config::get('mail.host', '');
            if (!empty($host)) {
                self::$mailer->isSMTP();
                self::$mailer->Host = $host;
                self::$mailer->Port = Config::get('mail.port', 587);
                self::$mailer->SMTPAuth = true;
                self::$mailer->Username = Config::get('mail.username', '');
                self::$mailer->Password = Config::get('mail.password', '');
                self::$mailer->SMTPSecure = Config::get('mail.encryption', 'tls');
            }

            self::$mailer->CharSet = 'UTF-8';
            self::$mailer->setLanguage('fa');

            $fromAddress = Config::get('mail.from_address', 'noreply@localhost');
            $fromName = Config::get('mail.from_name', 'Backend System');
            self::$mailer->setFrom($fromAddress, $fromName);
        }

        return self::$mailer;
    }

    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        try {
            $mail = self::getInstance();
            $mail->clearAddresses();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML($isHtml);

            if (!$isHtml) {
                $mail->AltBody = strip_tags($body);
            }

            return $mail->send();
        } catch (PHPMailerException $e) {
            $errorInfo = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            throw new \RuntimeException("Mail error: " . $errorInfo);
        }
    }

    public static function sendWithTemplate(string $to, string $subject, string $template, array $data = []): bool
    {
        $body = self::renderTemplate($template, $data);
        return self::send($to, $subject, $body);
    }

    private static function renderTemplate(string $template, array $data): string
    {
        $path = __DIR__ . '/../../templates/email/' . $template . '.php';
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Email template '$template' not found");
        }

        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
