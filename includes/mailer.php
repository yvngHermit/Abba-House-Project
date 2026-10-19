<?php
/** SMTP mailer for admin replies to contact messages. */

use PHPMailer\PHPMailer\PHPMailer;

function sendContactReply(array $contact, string $replyBody): void
{
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    $configPath = __DIR__ . '/mail_config.local.php';

    if (!is_file($autoloadPath)) {
        throw new RuntimeException('Email library is not installed. Run composer install.');
    }
    if (!is_file($configPath)) {
        throw new RuntimeException('SMTP configuration is missing. Create includes/mail_config.local.php.');
    }

    require_once $autoloadPath;
    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('SMTP configuration is invalid.');
    }

    $required = ['host', 'port', 'username', 'password', 'encryption', 'from_email', 'from_name'];
    foreach ($required as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) && !is_numeric($config[$key]) || trim((string) $config[$key]) === '') {
            throw new RuntimeException('SMTP configuration is incomplete.');
        }
    }

    $recipient = (string) ($contact['email'] ?? '');
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('The saved contact email address is invalid.');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['username'];
    $mail->Password = (string) $config['password'];
    $mail->Port = (int) $config['port'];
    $encryption = strtolower((string) $config['encryption']);
    if ($encryption === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($encryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        throw new RuntimeException('SMTP encryption must be tls or ssl.');
    }
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;

    // The sender must be an address authorized by the configured SMTP account.
    $mail->setFrom((string) $config['from_email'], (string) $config['from_name']);
    // Recipient is loaded from the stored contact row, never from a posted form field.
    $mail->addAddress($recipient, (string) ($contact['full_name'] ?? ''));

    $subject = trim(preg_replace('/[\r\n]+/', ' ', (string) ($contact['subject'] ?? 'Your enquiry')));
    $mail->Subject = 'Re: ' . ($subject !== '' ? $subject : 'Your enquiry');

    $safeName = htmlspecialchars((string) ($contact['full_name'] ?? 'there'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeReply = nl2br(htmlspecialchars($replyBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $safeFromName = htmlspecialchars((string) $config['from_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mail->isHTML(true);
    $mail->Body = '<p>Hello ' . $safeName . ',</p>'
        . '<div>' . $safeReply . '</div>'
        . '<p>Kind regards,<br>' . $safeFromName . '</p>';
    $mail->AltBody = "Hello " . (string) ($contact['full_name'] ?? 'there') . ",\n\n"
        . $replyBody . "\n\nKind regards,\n" . (string) $config['from_name'];

    $mail->send();
}
