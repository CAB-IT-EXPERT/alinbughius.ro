<?php
require dirname(__DIR__) . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['ok' => false, 'message' => 'Metodă neacceptată.'], 405);
$serviceId = is_string($_GET['service'] ?? null) ? trim($_GET['service']) : '';
if (!isset($bookingServices[$serviceId])) jsonResponse(['ok' => false, 'message' => 'Alege un serviciu disponibil.'], 422);
$service = $bookingServices[$serviceId];
$details = ['id' => $serviceId, 'name' => $service['name'], 'duration' => $service['duration'], 'minutes' => $service['minutes']];
$date = is_string($_GET['date'] ?? null) ? trim($_GET['date']) : '';
if ($date !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) jsonResponse(['ok' => false, 'message' => 'Data nu este validă.'], 422);
    jsonResponse(['ok' => true, 'service' => $details, 'date' => $date, 'slots' => computeAvailableSlots($date, $serviceId, $services)]);
}
jsonResponse(['ok' => true, 'service' => $details, 'days' => availableDays($serviceId, $services)]);
