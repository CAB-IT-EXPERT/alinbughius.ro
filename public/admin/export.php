<?php
declare(strict_types=1);

require dirname(__DIR__) . '/_bootstrap.php';
require APP_ROOT . '/app/admin-auth.php';
require APP_ROOT . '/app/xlsx-report.php';

adminSession();
if (!adminAuthenticated()) {
    header('Location: /admin/', true, 302);
    exit;
}

$timezone = new DateTimeZone('Europe/Bucharest');
$today = new DateTimeImmutable('today', $timezone);
$period = (string) ($_GET['period'] ?? 'all');
$start = null;
$end = null;
$label = 'Toată perioada';

switch ($period) {
    case 'today':
        $start = $end = $today;
        $label = 'Astăzi · ' . $today->format('d.m.Y');
        break;
    case 'yesterday':
        $start = $end = $today->modify('-1 day');
        $label = 'Ieri · ' . $start->format('d.m.Y');
        break;
    case 'last7':
        $start = $today->modify('-6 days');
        $end = $today;
        $label = 'Ultimele 7 zile · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'this_month':
        $start = $today->modify('first day of this month');
        $end = $today->modify('last day of this month');
        $label = 'Luna aceasta · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'last_month':
        $start = $today->modify('first day of last month');
        $end = $today->modify('last day of last month');
        $label = 'Luna trecută · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'last3m':
        $start = $today->modify('-3 months')->modify('+1 day');
        $end = $today;
        $label = 'Ultimele 3 luni · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'last6m':
        $start = $today->modify('-6 months')->modify('+1 day');
        $end = $today;
        $label = 'Ultimele 6 luni · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'last_year':
        $start = $today->modify('-1 year')->modify('+1 day');
        $end = $today;
        $label = 'Ultimul an · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'previous_year':
        $year = (int) $today->format('Y') - 1;
        $start = new DateTimeImmutable($year . '-01-01', $timezone);
        $end = new DateTimeImmutable($year . '-12-31', $timezone);
        $label = 'Anul trecut · ' . $year;
        break;
    case 'last2y':
        $start = $today->modify('-2 years')->modify('+1 day');
        $end = $today;
        $label = 'Ultimii 2 ani · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'custom':
        $from = (string) ($_GET['from'] ?? '');
        $to = (string) ($_GET['to'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            http_response_code(422);
            exit('Selectează o dată de început și o dată de sfârșit valide.');
        }
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $from, $timezone) ?: null;
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $to, $timezone) ?: null;
        if (!$start || !$end || $start->format('Y-m-d') !== $from || $end->format('Y-m-d') !== $to || $start > $end) {
            http_response_code(422);
            exit('Intervalul personalizat nu este valid.');
        }
        $label = 'Interval personalizat · ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
        break;
    case 'all':
        break;
    default:
        http_response_code(422);
        exit('Perioada selectată nu este validă.');
}

$records = bookingRecords();
if ($start && $end) {
    $from = $start->format('Y-m-d');
    $to = $end->format('Y-m-d');
    $records = array_values(array_filter($records, static function (mixed $record) use ($from, $to): bool {
        if (!is_array($record)) return false;
        $date = (string) ($record['date'] ?? '');
        return $date >= $from && $date <= $to;
    }));
}

try {
    $content = buildAppointmentXlsx($records, $label);
} catch (Throwable $error) {
    error_log('XLSX export failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Raportul nu a putut fi generat. Încearcă din nou.');
}

session_write_close();
$range = $start && $end ? $start->format('Y-m-d') . '_' . $end->format('Y-m-d') : 'toata-perioada';
$filename = 'raport-programari_' . $range . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
echo $content;
