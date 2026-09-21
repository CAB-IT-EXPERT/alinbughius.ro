<?php
// Temporary, token-protected deployment diagnostic. Removed after verification.
if (!hash_equals('__DEPLOY_TOKEN_HASH__', hash('sha256', $_SERVER['HTTP_X_DEPLOY_CHECK'] ?? ''))) {
    http_response_code(404); exit;
}
require __DIR__ . '/_bootstrap.php';
require APP_ROOT . '/app/booking.php';
require APP_ROOT . '/app/mailer.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$checks = ['php' => PHP_VERSION, 'openssl' => extension_loaded('openssl'), 'storage_writable' => is_writable(storagePath()), 'private_outside_document_root' => !str_starts_with(realpath(APP_ROOT), realpath($_SERVER['DOCUMENT_ROOT']))];
try {
    $mailer = createMailer($config);
    $checks['smtp_authenticated'] = $mailer->smtpConnect();
    $mailer->smtpClose();
} catch (Throwable $error) {
    $checks['smtp_authenticated'] = false;
    // Return a category, never credentials or the original exception.
    $checks['smtp_error'] = 'TLS connection or authentication failed';
}
echo json_encode($checks);
