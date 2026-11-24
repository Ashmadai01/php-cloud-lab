<?php
declare(strict_types=1);
namespace SdutSwap\Core;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

final class Mailer
{
    public static function send(string $to, string $subject, string $body, ?string $htmlBody = null): bool
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
            if ($htmlBody !== null) {
                $mail->isHTML(true);
                $mail->Body = $htmlBody;
                $mail->AltBody = $body;
            } else {
                $mail->isHTML(false);
            }

            return $mail->send();
        } catch (Throwable $e) {
            error_log('Mail send failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function emailTemplate(string $eyebrow, string $heading, string $name, string $message, string $buttonLabel, string $link, string $expiry, string $notice): string
    {
        $safe = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLink = $safe($link);

        return '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $safe($heading) . '</title></head>'
            . '<body style="margin:0;padding:32px 12px;background:#f4f2fb;font-family:Arial,Helvetica,sans-serif;color:#211b32;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td align="center">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e9e4f3;border-radius:18px;overflow:hidden;">'
            . '<tr><td style="padding:38px 40px 18px;">'
            . '<p style="margin:0 0 12px;color:#7650c8;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">' . $safe($eyebrow) . '</p>'
            . '<h1 style="margin:0 0 20px;color:#211b32;font-size:28px;line-height:1.25;">' . $safe($heading) . '</h1>'
            . '<p style="margin:0 0 14px;font-size:16px;line-height:1.7;">Hi ' . $safe($name) . ',</p>'
            . '<p style="margin:0 0 24px;color:#554d65;font-size:15px;line-height:1.8;">' . $safe($message) . '</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;"><tr><td bgcolor="#7045c5" style="border-radius:9px;">'
            . '<a href="' . $safeLink . '" style="display:inline-block;padding:14px 24px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;border-radius:9px;">' . $safe($buttonLabel) . '</a>'
            . '</td></tr></table>'
            . '<p style="margin:0 0 8px;color:#766f84;font-size:13px;line-height:1.7;">' . $safe($expiry) . '</p>'
            . '</td></tr><tr><td style="padding:18px 40px 32px;"><div style="height:1px;background:#eeeaf4;"></div>'
            . '<p style="margin:20px 0 0;color:#766f84;font-size:13px;line-height:1.7;">' . $safe($notice) . '</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;"><tr><td valign="middle" style="width:34px;height:34px;border-radius:9px;background:#7045c5;color:#ffffff;text-align:center;font-size:15px;font-weight:800;">CX</td><td valign="middle" style="padding-left:10px;color:#554d65;font-size:14px;line-height:1.6;">Thanks,<br><strong style="color:#211b32;">The CampusX Team</strong><br><span style="color:#7650c8;font-size:11px;font-weight:700;letter-spacing:1px;">YOUR CAMPUS. YOUR MARKETPLACE.</span></td></tr></table>'
            . '</td></tr></table><p style="margin:18px 0 0;color:#9992a8;font-size:11px;text-align:center;">CampusX · Your Campus. Your Marketplace.</p>'
            . '</td></tr></table></body></html>';
    }

    public static function verificationEmail(string $to, string $name, string $token): void
    {
        $link = absUrl('/verify-email?token=' . urlencode($token));
        $body = "Hi {$name},\n\nWelcome to CampusX! Please verify your email address within 24 hours:\n{$link}\n\nIf you did not create this account, ignore this email.\n\n— CampusX Team";
        $html = self::emailTemplate(
            'Welcome to CampusX',
            'Verify your email address',
            $name,
            'Thanks for joining CampusX. Confirm your email address to activate your account and get started with your campus marketplace.',
            'Verify Email Address',
            $link,
            'This verification link expires in 24 hours.',
            'If you did not create a CampusX account, you can safely ignore this message.'
        );
        self::send($to, 'Verify your CampusX email', $body, $html);
    }

    public static function resetEmail(string $to, string $name, string $token): void
    {
        $link = absUrl('/reset-password?token=' . urlencode($token));
        $body = "Hi {$name},\n\nWe received a request to reset your CampusX password. This link expires in 1 hour:\n{$link}\n\nIf you did not request this, ignore this email — your password will not change.\n\n— CampusX Team";
        $html = self::emailTemplate(
            'Account security',
            'Reset your password',
            $name,
            'We received a request to reset the password for your CampusX account. Use the button below to choose a new password.',
            'Reset Password',
            $link,
            'For your security, this link expires in 1 hour and can only be used once.',
            'If you did not request a password reset, ignore this email. Your password will remain unchanged.'
        );
        self::send($to, 'Reset your CampusX password', $body, $html);
    }
}
