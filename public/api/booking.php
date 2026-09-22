<?php
require dirname(__DIR__) . '/_bootstrap.php';
require APP_ROOT . '/app/booking.php';
require APP_ROOT . '/app/mailer.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['ok' => false, 'message' => 'Metodă neacceptată.'], 405);
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) jsonResponse(['ok' => false, 'message' => 'Cererea este prea mare.'], 413);
requestSession();
$id = is_string($_POST['request_id'] ?? null) ? $_POST['request_id'] : '';
$csrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
if (!$csrf || !hash_equals($_SESSION['csrf'] ?? '', $csrf) || !isset($_SESSION['requests'][$id])) jsonResponse(['ok' => false, 'message' => 'Sesiunea formularului a expirat. Trimite din nou pentru a reîncărca formularul.'], 403);
if ($_SESSION['requests'][$id]['issued'] < time() - 7200) jsonResponse(['ok' => false, 'message' => 'Formularul a expirat. Redeschide-l și încearcă din nou.'], 403);
if (!empty($_POST['website']) || time() - $_SESSION['requests'][$id]['issued'] < 2) jsonResponse(['ok' => false, 'message' => 'Te rugăm să verifici formularul și să încerci din nou.'], 422);
session_write_close();
$lock = null;
try {
    $data = validateBooking($_POST, $bookingServices);
    $lock = recordLock($id);
    $record = loadRecord($id);
    if (!$record) {
        if (!applyRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown', $data['email'])) {
            header('Retry-After: 3600');
            jsonResponse(['ok' => false, 'message' => 'Au fost trimise mai multe cereri într-un timp scurt. Pentru ajutor, sună la 0773 919 071 sau revino peste o oră.'], 429);
        }
        $record = $data + ['id' => $id, 'created_at' => date(DATE_ATOM), 'updated_at' => date(DATE_ATOM), 'status' => 'pending', 'source' => 'site', 'amount' => (int) $data['total'], 'payment_status' => 'unpaid', 'manage_token' => bin2hex(random_bytes(32)), 'delivery' => [], 'internal_notes' => ''];
        $calendar = calendarLock();
        try {
            assertSlotAvailable($record, $services);
            saveRecord($record);
        } finally { flock($calendar, LOCK_UN); fclose($calendar); }
    } else {
        // A retry can only resend the original request, never silently replace its recipient/details.
        foreach (['name', 'email', 'phone', 'service_id', 'plan', 'sessions', 'date', 'time', 'zone'] as $key) {
            if ($record[$key] !== $data[$key]) jsonResponse(['ok' => false, 'message' => 'Această cerere a fost deja înregistrată. Pentru alte detalii, redeschide formularul sau contactează-l pe Alin.'], 409);
        }
    }
    if (empty($record['last_attempt']) || $record['last_attempt'] < time() - 60) deliverBooking($record, $config);
    if (empty($record['delivery']['owner'])) jsonResponse(['ok' => false, 'message' => 'Cererea a fost salvată, dar notificarea pe e-mail nu a putut fi trimisă încă. Reîncearcă peste un minut sau sună la 0773 919 071.'], 503);
    $message = !empty($record['delivery']['receipt']) ? 'Intervalul a fost reținut pentru tine, iar detaliile au plecat pe e-mail. Alin va confirma programarea.' : 'Intervalul a fost reținut, iar Alin a primit solicitarea. E-mailul către tine nu a putut fi trimis încă; te va contacta folosind datele completate.';
    jsonResponse(['ok' => true, 'message' => $message, 'reference' => strtoupper(substr($id, 0, 8))]);
} catch (InvalidArgumentException $error) {
    $status = str_contains($error->getMessage(), 'nu mai este disponibil') ? 409 : 422;
    jsonResponse(['ok' => false, 'message' => $error->getMessage()], $status);
} catch (Throwable $error) {
    deliveryLog(preg_match('/^[a-f0-9]{32}$/D', $id) ? $id : 'invalid', 'request processing failed');
    jsonResponse(['ok' => false, 'message' => 'Nu am putut finaliza cererea. Reîncearcă sau sună la 0773 919 071.'], 500);
} finally {
    if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
}
