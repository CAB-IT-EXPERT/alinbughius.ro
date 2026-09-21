<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Bucharest');
define('APP_ROOT', dirname(__DIR__));
$config = require APP_ROOT . '/config/example.php';
if (is_file(APP_ROOT . '/config/local.php')) {
    $config = array_replace($config, require APP_ROOT . '/config/local.php');
}
$services = require __DIR__ . '/catalog.php';

function storagePath(): string {
    $local = in_array(PHP_SAPI, ['cli', 'cli-server'], true);
    $path = ($local && getenv('APP_STORAGE_DIR')) ? (string) getenv('APP_STORAGE_DIR') : APP_ROOT . '/storage';
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) throw new RuntimeException('Storage unavailable');
    return $path;
}

function atomicJsonWrite(string $path, array $data): void {
    $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (file_put_contents($temporary, $json, LOCK_EX) === false) throw new RuntimeException('Cannot write private data');
    chmod($temporary, 0600);
    if (!rename($temporary, $path)) throw new RuntimeException('Cannot commit private data');
}

function ownerNotificationEmail(array $config): string {
    $fallback = (string) ($config['owner_email'] ?? '');
    $path = storagePath() . '/notification-settings.json';
    if (!is_file($path)) return $fallback;
    try {
        $settings = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        $email = is_array($settings) ? trim((string) ($settings['owner_email'] ?? '')) : '';
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : $fallback;
    } catch (Throwable) {
        return $fallback;
    }
}

function saveOwnerNotificationEmail(string $email): void {
    $email = trim($email);
    if (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Introdu o adresă de e-mail validă.');
    $lock = fopen(storagePath() . '/notification-settings.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Setările de notificare nu pot fi salvate momentan.');
    try {
        atomicJsonWrite(storagePath() . '/notification-settings.json', ['owner_email' => $email, 'updated_at' => date(DATE_ATOM)]);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function requestSession(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('alin_booking');
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'use_strict_mode' => true]);
    }
}
function jsonResponse(array $body, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function securityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // Only the public landing page can load the inherited Google Ads tag.
    // Private booking/confirmation pages never permit third-party scripts.
    $ads = defined('PUBLIC_ADS_MEASUREMENT') && PUBLIC_ADS_MEASUREMENT;
    $googleScripts = $ads ? ' https://www.googletagmanager.com https://www.googleadservices.com https://www.google.com' : '';
    $googleRequests = $ads ? ' https://www.googletagmanager.com https://www.googleadservices.com https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://www.google.com https://www.google.ro https://ad.doubleclick.net' : '';
    $frames = $ads ? 'https://www.googletagmanager.com' : "'none'";
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:$googleRequests; style-src 'self'; font-src 'self'; script-src 'self'$googleScripts; connect-src 'self'$googleRequests; frame-src $frames; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
}
securityHeaders();
$config['owner_email'] = ownerNotificationEmail($config);
require_once __DIR__ . '/scheduler.php';
$bookingServices = bookingServices($services);
