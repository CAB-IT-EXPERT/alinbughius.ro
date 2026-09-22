<?php
declare(strict_types=1);
$testStorage = dirname(__DIR__) . '/.runtime/test-scheduler-' . time();
putenv('APP_STORAGE_DIR=' . $testStorage);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/booking.php';
$checks = 0;
function verify(bool $condition, string $message): void { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }
$now = new DateTimeImmutable('2026-09-21 08:00', new DateTimeZone('Europe/Bucharest'));
$slots = computeAvailableSlots('2026-09-21', 'terapeutic', $services, null, $now);
verify($slots[0] === '11:00', 'Minimum three-hour notice applies');
verify(in_array('18:30', $slots, true), 'A 60-minute service plus buffer fits before closing');
verify(in_array('19:00', $slots, true), 'Configured availability is not shortened by service duration');
verify(in_array('20:00', $slots, true), 'Configured end time is the final selectable start time');
$record = ['id'=>bin2hex(random_bytes(16)), 'date'=>'2026-09-21', 'time'=>'12:00', 'duration_minutes'=>60, 'buffer_minutes'=>15, 'status'=>'pending'];
saveRecord($record);
$blocked = computeAvailableSlots('2026-09-21', 'terapeutic', $services, null, $now);
verify(!in_array('11:30', $blocked, true) && !in_array('12:00', $blocked, true) && !in_array('12:30', $blocked, true), 'Overlapping starts are removed');
verify(in_array('13:30', $blocked, true), 'Next valid interval remains available');
$multiRecord = ['id'=>bin2hex(random_bytes(16)), 'date'=>'2026-09-24', 'time'=>'10:00', 'duration_minutes'=>60, 'sessions'=>2, 'session_break_minutes'=>15, 'buffer_minutes'=>15, 'status'=>'pending'];
[$multiStart, $multiEnd] = recordInterval($multiRecord);
verify($multiStart->format('H:i') === '10:00' && $multiEnd->format('H:i') === '12:30', 'Two sessions block both durations, the between-session pause and one final pause');
saveRecord($multiRecord);
$multiBlocked = computeAvailableSlots('2026-09-24', 'terapeutic', $services, null, $now);
verify(!in_array('12:00', $multiBlocked, true) && in_array('12:30', $multiBlocked, true), 'Calendar protects the complete multi-session interval');
$state = scheduleState($services); $state['weekly']['3'] = ['enabled'=>true,'start'=>'10:00','end'=>'18:00']; saveScheduleState($state);
$longServiceSlots = computeAvailableSlots('2026-09-23', 'lomi-lomi', $services, null, $now);
verify(end($longServiceSlots) === '18:00', 'Long services keep the configured final start time');
$state = scheduleState($services); $state['exceptions']['2026-09-22'] = ['closed'=>true,'start'=>'09:00','end'=>'20:00','note'=>'Concediu']; saveScheduleState($state);
verify(computeAvailableSlots('2026-09-22', 'terapeutic', $services, null, $now) === [], 'Closed date exception removes all slots');
$state = scheduleState($services); $state['services']['test-masaj'] = ['name'=>'Masaj test','duration_minutes'=>45,'session_break_minutes'=>10,'buffer_minutes'=>15,'max_booking_sessions'=>3,'price'=>100,'sessions'=>4,'active'=>true,'custom'=>true]; saveScheduleState($state);
$custom = bookingServices($services);
verify(isset($custom['test-masaj']) && $custom['test-masaj']['package'] === 360, 'Custom service is bookable with computed subscription discount');
verify(bookingBlockedMinutes($custom['test-masaj'], 3) === 170, 'Custom session limit and pauses are applied');
try { bookingBlockedMinutes($custom['test-masaj'], 4); throw new RuntimeException('Session limit accepted'); } catch (InvalidArgumentException $expected) { $checks++; }
try { assertSlotAvailable(['date'=>'2026-09-21','time'=>'12:30','service_id'=>'terapeutic'], $services); throw new RuntimeException('Conflict accepted'); } catch (InvalidArgumentException $expected) { $checks++; }
echo "PASS: {$checks} calendar, exceptions, services and overlap checks.\n";
