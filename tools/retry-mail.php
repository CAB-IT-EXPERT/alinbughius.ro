<?php
// Cron, e.g. every 5 minutes: php /private/path/tools/retry-mail.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/booking.php';
require APP_ROOT . '/app/mailer.php';
$pending = 0; $processed = 0;
foreach (glob(storagePath() . '/*.json') as $file) {
    $id = basename($file, '.json');
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) continue;
    $lock = recordLock($id);
    try {
        $record = loadRecord($id);
        if (!$record) continue;
        $retentionStart = max(strtotime($record['created_at']), strtotime($record['date'] ?? $record['created_at']));
        if ($retentionStart < time() - 90 * 86400) { unlink($file); continue; }
        if (strtotime($record['created_at']) < time() - 86400 || !empty($record['delivery']['owner']) && !empty($record['delivery']['receipt'])) continue;
        deliverBooking($record, $config); $processed++;
        if (empty($record['delivery']['owner']) || empty($record['delivery']['receipt'])) $pending++;
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}
echo "Processed: {$processed}; still pending: {$pending}\n";
exit($pending ? 1 : 0);
