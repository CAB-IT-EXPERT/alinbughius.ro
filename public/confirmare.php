<?php
require __DIR__ . '/_bootstrap.php';
require APP_ROOT . '/app/booking.php';
require APP_ROOT . '/app/mailer.php';
header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
requestSession();
$_SESSION['manage_csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['manage_csrf'];
$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
$record = loadRecord($id);
if (!$record || !$token || !hash_equals($record['manage_token'], $token) || strtotime($record['created_at']) < time() - 30 * 86400) {
    http_response_code(404); exit('Linkul este invalid sau a expirat. Folosește linkul din e-mailul de programare, valabil 30 de zile.');
}
$message = $record['status'] === 'confirmed' ? 'Programarea este deja confirmată. Nu mai este necesară nicio altă acțiune.' : ''; $error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) { http_response_code(403); exit('Sesiune expirată. Reîncarcă pagina.'); }
    session_write_close();
    $lock = recordLock($id);
    try {
        $record = loadRecord($id);
        if (strtotime($record['date'] . ' ' . $record['time']) <= time()) throw new RuntimeException('past');
        if ($record['status'] === 'confirmed' || !empty($record['delivery']['confirmed'])) {
            $message = 'Programarea este deja confirmată. Nu a fost trimis un alt e-mail clientului.';
        } else {
            $calendar = calendarLock();
            try { assertSlotAvailable($record, $services, $id); } finally { flock($calendar, LOCK_UN); fclose($calendar); }
            sendBookingEmail($record, 'confirmed', $config);
            $record['delivery']['confirmed'] = date(DATE_ATOM);
            $record['status'] = 'confirmed';
            saveRecord($record);
            $message = 'Programarea este confirmată. Clientul a primit un e-mail cu data și ora.';
        }
    } catch (Throwable $failure) {
        $error = true;
        $message = strtotime($record['date'] . ' ' . $record['time']) <= time() ? 'Intervalul solicitat a trecut. Contactează clientul pentru o dată nouă.' : 'E-mailul de confirmare nu a putut fi trimis. Reîncearcă sau contactează clientul direct; confirmarea nu a fost înregistrată.';
        deliveryLog($id, 'confirmation failed');
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}
?>
<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Confirmă programarea · Alin Bughiuș</title><link rel="stylesheet" href="/assets/style.css?v=1"><link rel="stylesheet" href="/assets/botanical-palette.css?v=3"><link rel="icon" href="/assets/logo-mark.svg"></head><body><main class="manage-card"><a href="/" class="brand"><img src="/assets/logo-mark.svg" alt="" width="44" height="44"><span>Alin Bughiuș</span></a><h1>Cerere de programare</h1><span class="status-pill"><?= $record['status'] === 'confirmed' ? 'Confirmată' : 'În așteptarea confirmării' ?></span><p>Verifică disponibilitatea și discută adresa cu clientul înainte de confirmare.</p><dl><?php $sessionCount = max(1, (int) ($record['sessions'] ?? 1)); foreach (['Client' => $record['name'], 'Telefon' => $record['phone'], 'E-mail' => $record['email'], 'Serviciu' => $record['service'], 'Sesiuni' => $sessionCount === 1 ? '1 sesiune' : $sessionCount . ' sesiuni', 'Data și ora' => (new DateTimeImmutable($record['date']))->format('d.m.Y') . ' · ' . $record['time'], 'Zona' => $record['zone'], 'Total estimat' => $record['total'] . ' lei'] as $label => $value): ?><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd><?php endforeach; ?></dl><?php if ($message): ?><p role="status"><?= e($message) ?></p><?php endif; ?><?php if ($record['status'] !== 'confirmed'): ?><form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="button button-dark" type="submit">Confirmă și trimite e-mail clientului</button></form><?php endif; ?><p><a href="mailto:<?= e($record['email']) ?>">Scrie-i clientului</a> · <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $record['phone'])) ?>">Sună clientul</a></p></main></body></html>
