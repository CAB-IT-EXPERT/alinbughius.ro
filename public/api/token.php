<?php
require dirname(__DIR__) . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['ok' => false], 405);
requestSession();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$_SESSION['requests'] = array_filter($_SESSION['requests'] ?? [], fn($item) => $item['issued'] > time() - 7200);
$id = bin2hex(random_bytes(16));
$_SESSION['requests'][$id] = ['issued' => time()];
// Bound session growth without invalidating the most recent form.
$_SESSION['requests'] = array_slice($_SESSION['requests'], -12, null, true);
jsonResponse(['csrf' => $_SESSION['csrf'], 'request_id' => $id, 'today' => date('Y-m-d'), 'latest' => date('Y-m-d', strtotime('+180 days'))]);
