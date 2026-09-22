<?php
declare(strict_types=1);

function calendarLock() {
    $lock = fopen(storagePath() . '/calendar.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Calendar unavailable');
    return $lock;
}

function defaultScheduleState(array $catalog): array {
    $serviceRows = [];
    foreach ($catalog as $id => $service) {
        $serviceRows[$id] = [
            'name' => $service['name'],
            'duration_minutes' => (int) ($service['minutes'] ?? 60),
            'session_break_minutes' => 15,
            'buffer_minutes' => 15,
            'max_booking_sessions' => 2,
            'price' => (int) $service['price'],
            'sessions' => (int) $service['sessions'],
            'active' => true,
            'custom' => false,
        ];
    }
    $weekly = [];
    for ($day = 1; $day <= 7; $day++) {
        $weekly[(string) $day] = ['enabled' => $day <= 5, 'start' => '09:00', 'end' => '20:00'];
    }
    return [
        'version' => 1,
        'settings' => ['timezone' => 'Europe/Bucharest', 'horizon_days' => 90, 'minimum_notice_minutes' => 180, 'slot_step_minutes' => 30],
        'weekly' => $weekly,
        'exceptions' => [],
        'services' => $serviceRows,
        'updated_at' => date(DATE_ATOM),
    ];
}

function scheduleState(array $catalog): array {
    $path = storagePath() . '/schedule.json';
    if (!is_file($path)) {
        $lock = calendarLock();
        try {
            if (!is_file($path)) atomicJsonWrite($path, defaultScheduleState($catalog));
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) throw new RuntimeException('Invalid schedule configuration');
    $defaults = defaultScheduleState($catalog);
    $decoded['settings'] = array_replace($defaults['settings'], is_array($decoded['settings'] ?? null) ? $decoded['settings'] : []);
    $decoded['weekly'] = array_replace($defaults['weekly'], is_array($decoded['weekly'] ?? null) ? $decoded['weekly'] : []);
    $decoded['exceptions'] = is_array($decoded['exceptions'] ?? null) ? $decoded['exceptions'] : [];
    $storedServices = is_array($decoded['services'] ?? null) ? $decoded['services'] : [];
    $normalizedServices = [];
    foreach (array_unique(array_merge(array_keys($defaults['services']), array_keys($storedServices))) as $id) {
        $fallback = $defaults['services'][$id] ?? [
            'name' => (string) $id,
            'duration_minutes' => 60,
            'session_break_minutes' => 15,
            'buffer_minutes' => 15,
            'max_booking_sessions' => 2,
            'price' => 0,
            'sessions' => 1,
            'active' => true,
            'custom' => true,
        ];
        $stored = is_array($storedServices[$id] ?? null) ? $storedServices[$id] : [];
        $normalizedServices[$id] = array_replace($fallback, $stored);
    }
    $decoded['services'] = $normalizedServices;
    return $decoded;
}

function saveScheduleState(array $state): void {
    $state['updated_at'] = date(DATE_ATOM);
    atomicJsonWrite(storagePath() . '/schedule.json', $state);
}

function bookingServices(array $catalog): array {
    $state = scheduleState($catalog);
    $result = [];
    foreach ($state['services'] as $id => $row) {
        if (empty($row['active'])) continue;
        $base = $catalog[$id] ?? [];
        $minutes = max(15, min(480, (int) ($row['duration_minutes'] ?? 60)));
        $price = max(0, (int) ($row['price'] ?? ($base['price'] ?? 0)));
        $sessions = max(1, min(20, (int) ($row['sessions'] ?? ($base['sessions'] ?? 4))));
        $result[$id] = array_replace($base, [
            'name' => (string) ($row['name'] ?? ($base['name'] ?? $id)),
            'duration' => $minutes . ' min',
            'minutes' => $minutes,
            'session_break_minutes' => max(0, min(180, (int) ($row['session_break_minutes'] ?? 15))),
            'buffer_minutes' => max(0, min(180, (int) ($row['buffer_minutes'] ?? 15))),
            'max_booking_sessions' => max(1, min(10, (int) ($row['max_booking_sessions'] ?? 2))),
            'price' => $price,
            'sessions' => $sessions,
            'package' => (int) round($price * $sessions * .9),
        ]);
    }
    return $result;
}

function bookingBlockedMinutes(array $service, int $sessions): int {
    $maxSessions = max(1, min(10, (int) ($service['max_booking_sessions'] ?? 2)));
    if ($sessions < 1 || $sessions > $maxSessions) throw new InvalidArgumentException('Alege un număr valid de sesiuni pentru serviciul selectat.');
    $duration = max(15, (int) ($service['minutes'] ?? $service['duration_minutes'] ?? 60));
    $between = max(0, (int) ($service['session_break_minutes'] ?? 15));
    $after = max(0, (int) ($service['buffer_minutes'] ?? 15));
    return $duration * $sessions + $between * max(0, $sessions - 1) + $after;
}

function bookingRecords(): array {
    $records = [];
    foreach (glob(storagePath() . '/*.json') ?: [] as $file) {
        $id = basename($file, '.json');
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) continue;
        $record = json_decode((string) file_get_contents($file), true);
        if (is_array($record)) $records[] = $record;
    }
    return $records;
}

function scheduleHoursForDate(string $date, array $state): ?array {
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Europe/Bucharest'));
    if (!$day || $day->format('Y-m-d') !== $date) return null;
    if (isset($state['exceptions'][$date])) {
        $exception = $state['exceptions'][$date];
        if (!empty($exception['closed'])) return null;
        return ['start' => $exception['start'], 'end' => $exception['end'], 'note' => $exception['note'] ?? 'Program special'];
    }
    $hours = $state['weekly'][(string) $day->format('N')] ?? null;
    if (!$hours || empty($hours['enabled'])) return null;
    return ['start' => $hours['start'], 'end' => $hours['end'], 'note' => 'Program obișnuit'];
}

function recordInterval(array $record): ?array {
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', ($record['date'] ?? '') . ' ' . ($record['time'] ?? ''), new DateTimeZone('Europe/Bucharest'));
    if (!$start) return null;
    $duration = (int) ($record['duration_minutes'] ?? 0);
    if ($duration <= 0 && preg_match('/(\d+)/', (string) ($record['duration'] ?? ''), $match)) $duration = (int) $match[1];
    $duration = $duration > 0 ? $duration : 60;
    $sessions = max(1, (int) ($record['sessions'] ?? 1));
    $between = max(0, (int) ($record['session_break_minutes'] ?? 15));
    $buffer = max(0, (int) ($record['buffer_minutes'] ?? 15));
    $blocked = $duration * $sessions + $between * max(0, $sessions - 1) + $buffer;
    return [$start, $start->modify('+' . $blocked . ' minutes')];
}

function computeAvailableSlots(string $date, string $serviceId, array $catalog, ?string $excludeId = null, ?DateTimeImmutable $now = null, int $sessions = 1): array {
    $state = scheduleState($catalog);
    $bookable = bookingServices($catalog);
    if (!isset($bookable[$serviceId])) return [];
    $hours = scheduleHoursForDate($date, $state);
    if (!$hours) return [];
    $timezone = new DateTimeZone('Europe/Bucharest');
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);
    $now ??= new DateTimeImmutable('now', $timezone);
    if (!$day) return [];
    $today = $now->setTime(0, 0);
    $horizon = $today->modify('+' . (int) $state['settings']['horizon_days'] . ' days');
    if ($day < $today || $day > $horizon) return [];
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $hours['start'], $timezone);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $hours['end'], $timezone);
    if (!$start || !$end || $end <= $start) return [];
    $service = $bookable[$serviceId];
    $blockedMinutes = bookingBlockedMinutes($service, $sessions);
    $step = max(1, min(480, (int) $state['settings']['slot_step_minutes']));
    $earliest = $now->modify('+' . (int) $state['settings']['minimum_notice_minutes'] . ' minutes');
    $blocking = [];
    foreach (bookingRecords() as $record) {
        if (($record['id'] ?? null) === $excludeId || !in_array($record['status'] ?? '', ['pending', 'confirmed'], true) || ($record['date'] ?? '') !== $date) continue;
        $interval = recordInterval($record);
        if ($interval) $blocking[] = $interval;
    }
    $slots = [];
    // The configured end represents the final time at which a booking may start.
    // Duration and buffer still block overlaps, but must not shorten the visible
    // availability range selected by the administrator.
    for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+' . $step . ' minutes')) {
        if ($cursor < $earliest) continue;
        $slotEnd = $cursor->modify('+' . $blockedMinutes . ' minutes');
        $conflict = false;
        foreach ($blocking as [$busyStart, $busyEnd]) {
            if ($cursor < $busyEnd && $slotEnd > $busyStart) { $conflict = true; break; }
        }
        if (!$conflict) $slots[] = $cursor->format('H:i');
    }
    return $slots;
}

function availableDays(string $serviceId, array $catalog, int $limit = 24, ?DateTimeImmutable $now = null, int $sessions = 1): array {
    $state = scheduleState($catalog);
    $timezone = new DateTimeZone('Europe/Bucharest');
    $now ??= new DateTimeImmutable('now', $timezone);
    $dayNames = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
    $monthNames = [1 => 'ian', 'feb', 'mar', 'apr', 'mai', 'iun', 'iul', 'aug', 'sept', 'oct', 'nov', 'dec'];
    $days = [];
    for ($offset = 0; $offset <= (int) $state['settings']['horizon_days'] && count($days) < $limit; $offset++) {
        $date = $now->setTime(0, 0)->modify('+' . $offset . ' days');
        $slots = computeAvailableSlots($date->format('Y-m-d'), $serviceId, $catalog, null, $now, $sessions);
        if (!$slots) continue;
        $days[] = ['date' => $date->format('Y-m-d'), 'weekday' => $dayNames[(int) $date->format('w')], 'label' => $date->format('j') . ' ' . $monthNames[(int) $date->format('n')], 'slots' => $slots];
    }
    return $days;
}

function assertSlotAvailable(array $record, array $catalog, ?string $excludeId = null): void {
    $slots = computeAvailableSlots($record['date'], $record['service_id'], $catalog, $excludeId, null, max(1, (int) ($record['sessions'] ?? 1)));
    if (!in_array($record['time'], $slots, true)) throw new InvalidArgumentException('Intervalul ales nu mai este disponibil. Alege o altă oră din calendar.');
}

function slugForService(string $name, array $existing): string {
    $normalized = function_exists('iconv') ? (iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name) : $name;
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $normalized), '-')) ?: 'serviciu';
    $base = $slug; $suffix = 2;
    while (isset($existing[$slug])) $slug = $base . '-' . $suffix++;
    return $slug;
}
