<?php
declare(strict_types=1);
namespace SdutSwap\Core;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

        if (!Config::bool('MAIL_ENABLED')) {
            $log = Config::basePath() . '/storage/logs/mail.log';
            @mkdir(dirname($log), 0775, true);
            file_put_contents($log, date('c') . " TO: {$to} SUBJECT: {$subject}\n{$body}\n---\n", FILE_APPEND | LOCK_EX);
            return true;
        }

        $host = Config::get('MAIL_HOST');
        $port = (int) Config::get('MAIL_PORT', '587');
        $user = Config::get('MAIL_USERNAME');
        $pass = Config::get('MAIL_PASSWORD');
        $from = Config::get('MAIL_FROM');
        $fromName = Config::get('MAIL_FROM_NAME', 'SDUT Swap');

        if ($host === '' || $user === '' || $pass === '' || $from === '') {
            error_log('Mail send failed: SMTP configuration is incomplete.');
            return false;
        }

        if (!class_exists(PHPMailer::class)) {
            error_log('Mail send failed: PHPMailer is not installed. Run composer install.');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = ($port === 465)
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            return $mail->send();
        } catch (Throwable $e) {
            error_log('Mail send failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function verificationEmail(string $to, string $name, string $token): void
    {
        $link = absUrl('/verify-email?token=' . urlencode($token));
        $body = "Hi {$name},\n\nWelcome to SDUT Swap! Please verify your email address within 24 hours:\n{$link}\n\nIf you did not create this account, ignore this email.\n\n— SDUT Swap";
        self::send($to, 'Verify your SDUT Swap email', $body);
    }

    public static function resetEmail(string $to, string $name, string $token): void
    {
        $link = absUrl('/reset-password?token=' . urlencode($token));
        $body = "Hi {$name},\n\nWe received a request to reset your SDUT Swap password. This link expires in 1 hour:\n{$link}\n\nIf you did not request this, ignore this email — your password will not change.\n\n— SDUT Swap";
        self::send($to, 'Reset your SDUT Swap password', $body);
    }
}
