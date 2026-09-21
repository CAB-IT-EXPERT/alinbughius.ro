<?php
declare(strict_types=1);
putenv('APP_MAIL_CAPTURE=1');
putenv('APP_STORAGE_DIR=' . dirname(__DIR__) . '/.runtime/test-unit-' . time());
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/booking.php';
require APP_ROOT . '/app/mailer.php';
$checks = 0;
function check(bool $condition, string $message): void { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }
$now = new DateTimeImmutable('2026-09-20 12:00');
$input = ['service' => 'terapeutic', 'plan' => 'single', 'name' => 'Test Client', 'email' => 'test@example.com', 'phone' => '0773 000 000', 'date' => '2026-09-25', 'time' => '15:30', 'zone' => 'sector-1', 'privacy' => '1', 'price' => '1'];
$result = validateBooking($input, $bookingServices, $now);
check($result['price'] === 200, 'A booking always uses the single-session price');
check($result['total'] === 230, 'Travel is charged once for one booking');
check(validateBooking(array_replace($input, ['zone' => 'sector-2']), $services, $now)['total'] === 200, 'Sector 2 travel included');
check(validateBooking(array_replace($input, ['service' => 'anticelulitic']), $services, $now)['total'] === 200, 'Anticellulite single-session price and travel');
check(validateBooking(array_replace($input, ['plan' => 'single']), $services, $now)['total'] === 230, 'Individual visit price');
check(validateBooking(array_replace($input, ['plan' => 'package']), $bookingServices, $now)['plan'] === 'single', 'Submitted package values cannot change a booking');
foreach ([['email' => ''], ['email' => "x@example.com\r\nBcc: y@example.com"], ['email' => ['test']], ['service' => 'invalid'], ['date' => '2026-02-30'], ['date' => '2026-09-01'], ['date' => '2028-01-01'], ['time' => '99:00'], ['phone' => 'abc'], ['zone' => 'remote'], ['name' => '<script>'], ['privacy' => '']] as $invalid) {
    try { validateBooking(array_replace($input, $invalid), $services, $now); throw new RuntimeException('Invalid input accepted: ' . json_encode($invalid)); } catch (InvalidArgumentException $expected) { $checks++; }
}
foreach ($services as $service) check($service['package'] === (int) round($service['price'] * $service['sessions'] * .9), 'Consistent discount for ' . $service['name']);
$record = $result + ['id' => bin2hex(random_bytes(16)), 'created_at' => date(DATE_ATOM), 'status' => 'pending', 'manage_token' => bin2hex(random_bytes(32)), 'delivery' => []];
$receipt = bookingEmail($record, 'receipt', $config); $owner = bookingEmail($record, 'owner', $config);
check(!str_contains($receipt['text'], $record['manage_token']), 'Private link is never sent to client');
check(str_contains($owner['text'], $record['manage_token']), 'Owner has confirmation link');
check(str_contains($receipt['text'], 'intervalul este reținut'), 'Receipt explains that the selected interval is held');
saveRecord($record); deliverBooking($record, $config);
check(!empty($record['delivery']['owner']) && !empty($record['delivery']['receipt']), 'Both initial mails delivered');
check(loadRecord($record['id'])['status'] === 'pending', 'New booking remains pending');
$ownerMessage = file_get_contents(storagePath() . '/' . $record['id'] . '-owner.eml');
check(str_contains($ownerMessage, 'bughius_alin@yahoo.com'), 'Correct owner destination');
check(str_contains($ownerMessage, 'contact@alinbughius.ro'), 'Correct authenticated sender');
$defaultOwnerEmail = $config['owner_email'];
saveOwnerNotificationEmail('programari-test@example.com');
check(ownerNotificationEmail($config) === 'programari-test@example.com', 'Owner notification email can be changed from private settings');
saveOwnerNotificationEmail($defaultOwnerEmail);
check(ownerNotificationEmail($config) === $defaultOwnerEmail, 'Owner notification email can be restored');
$oldTime = $record['delivery']['owner']; deliverBooking($record, $config);
check($record['delivery']['owner'] === $oldTime, 'Repeated requests do not send owner message twice');
sendBookingEmail($record, 'confirmed', $config);
check(is_file(storagePath() . '/' . $record['id'] . '-confirmed.eml'), 'Confirmation generated');
$failed = array_replace($record, ['id' => bin2hex(random_bytes(16)), 'delivery' => []]);
putenv('APP_MAIL_FAIL=receipt'); deliverBooking($failed, $config);
check(!empty($failed['delivery']['owner']) && empty($failed['delivery']['receipt']), 'Customer failure is recorded independently');
putenv('APP_MAIL_FAIL'); deliverBooking($failed, $config);
check(!empty($failed['delivery']['receipt']), 'Failed customer receipt can be retried');
$blocked = array_replace($record, ['id' => bin2hex(random_bytes(16)), 'delivery' => []]);
putenv('APP_MAIL_FAIL=owner'); deliverBooking($blocked, $config);
check(empty($blocked['delivery']['receipt']), 'No receipt when owner notification failed');
putenv('APP_MAIL_FAIL');
check(applyRateLimit('192.0.2.1', 'spam@example.com'), 'First request allowed');
check(applyRateLimit('192.0.2.1', 'spam@example.com'), 'Second request allowed');
check(applyRateLimit('192.0.2.1', 'spam@example.com'), 'Third request allowed');
check(!applyRateLimit('192.0.2.2', 'spam@example.com'), 'Email flood limited across IPs');
echo "PASS: {$checks} validation, pricing, delivery, failure and privacy checks. No external email sent.\n";
