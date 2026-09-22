<?php
require dirname(__DIR__) . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['ok' => false, 'message' => 'Metodă neacceptată.'], 405);
$serviceId = is_string($_GET['service'] ?? null) ? trim($_GET['service']) : '';
if (!isset($bookingServices[$serviceId])) jsonResponse(['ok' => false, 'message' => 'Alege un serviciu disponibil.'], 422);
$service = $bookingServices[$serviceId];
$sessions = filter_var($_GET['sessions'] ?? 1, FILTER_VALIDATE_INT);
$maxSessions = max(1, min(10, (int) ($service['max_booking_sessions'] ?? 2)));
if ($sessions === false || $sessions < 1 || $sessions > $maxSessions) jsonResponse(['ok' => false, 'message' => 'Alege un număr valid de sesiuni.'], 422);
$details = ['id' => $serviceId, 'name' => $service['name'], 'duration' => $service['duration'], 'minutes' => $service['minutes'], 'max_sessions' => $maxSessions, 'session_break_minutes' => (int) $service['session_break_minutes'], 'buffer_minutes' => (int) $service['buffer_minutes']];
$date = is_string($_GET['date'] ?? null) ? trim($_GET['date']) : '';
if ($date !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) jsonResponse(['ok' => false, 'message' => 'Data nu este validă.'], 422);
    jsonResponse(['ok' => true, 'service' => $details, 'sessions' => $sessions, 'date' => $date, 'slots' => computeAvailableSlots($date, $serviceId, $services, null, null, $sessions)]);
}
jsonResponse(['ok' => true, 'service' => $details, 'sessions' => $sessions, 'days' => availableDays($serviceId, $services, 24, null, $sessions)]);
