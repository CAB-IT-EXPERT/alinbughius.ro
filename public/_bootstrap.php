<?php
declare(strict_types=1);
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}
// Hosting uses a dedicated sibling folder, outside the public document root.
$privateRoot = dirname(__DIR__) . '/alinbughius-private';
if (!is_file($privateRoot . '/app/bootstrap.php')) $privateRoot = dirname(__DIR__);
require $privateRoot . '/app/bootstrap.php';
unset($privateRoot);
