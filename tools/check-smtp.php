<?php
// Read-only SMTP authentication check. Does NOT send messages.
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/mailer.php';
try {
    $mail = createMailer($config);
    if (!$mail->smtpConnect()) throw new RuntimeException('SMTP connection rejected');
    $mail->smtpClose();
    echo "SMTP authentication and verified TLS connection successful. No messages sent.\n";
} catch (Throwable $error) {
    // PHPMailer connection/authentication errors never include passwords with debug off.
    echo 'SMTP check failed: ' . $error->getMessage() . "\n";
    exit(1);
}
