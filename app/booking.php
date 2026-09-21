<?php
declare(strict_types=1);

function saveRecord(array $record): void {
    $path = storagePath() . '/' . $record['id'] . '.json';
    $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $json = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($temporary, $json, LOCK_EX) === false) throw new RuntimeException('Cannot save booking');
    chmod($temporary, 0600);
    if (!rename($temporary, $path)) throw new RuntimeException('Cannot commit booking');
}

function loadRecord(string $id): ?array {
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) return null;
    $file = storagePath() . '/' . $id . '.json';
    return is_file($file) ? json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) : null;
}

function recordLock(string $id) {
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) throw new InvalidArgumentException('Invalid ID');
    $lock = fopen(storagePath() . '/' . $id . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock booking');
    return $lock;
}

function validateBooking(array $input, array $services, ?DateTimeImmutable $now = null): array {
    $now ??= new DateTimeImmutable('now');
    $read = static function(string $key) use ($input): string {
        if (isset($input[$key]) && !is_string($input[$key])) throw new InvalidArgumentException('Verifică datele completate în formular.');
        return trim($input[$key] ?? '');
    };
    $serviceId = $read('service'); $plan = 'single';
    if (!isset($services[$serviceId])) throw new InvalidArgumentException('Alege un serviciu din listă.');
    $name = $read('name'); $email = $read('email'); $phone = $read('phone');
    if (strlen($name) < 2 || strlen($name) > 100 || preg_match('/[\x00-\x1f\x7f<>]/', $name)) throw new InvalidArgumentException('Completează numele tău, între 2 și 100 de caractere.');
    if (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Adresa de e-mail este obligatorie și trebuie să fie validă.');
    if (!preg_match('/^\+?[0-9\s().-]{8,22}$/D', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 8) throw new InvalidArgumentException('Completează un număr de telefon valid.');
    $date = $read('date'); $time = $read('time');
    $appointment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, new DateTimeZone('Europe/Bucharest'));
    if (!$appointment || $appointment->format('Y-m-d H:i') !== $date . ' ' . $time || !preg_match('/^\d{2}:\d{2}$/D', $time)) throw new InvalidArgumentException('Alege o dată și o oră disponibile din calendar.');
    if ($appointment <= $now || $appointment > $now->modify('+180 days')->setTime(23, 59)) throw new InvalidArgumentException('Alege un moment din viitor, în următoarele 180 de zile.');
    $zone = $read('zone');
    $zones = ['sector-1' => 'București, Sector 1', 'sector-2' => 'București, Sector 2', 'sector-3' => 'București, Sector 3', 'sector-4' => 'București, Sector 4', 'sector-5' => 'București, Sector 5', 'sector-6' => 'București, Sector 6', 'ilfov' => 'Ilfov'];
    if (!isset($zones[$zone])) throw new InvalidArgumentException('Alege zona în care are loc ședința.');
    if ($read('privacy') !== '1') throw new InvalidArgumentException('Te rugăm să citești informarea privind datele personale și să bifezi acordul de contact.');
    $service = $services[$serviceId];
    $sessions = 1;
    $price = $service['price'];
    $travel = in_array($zone, ['sector-2', 'sector-3'], true) ? 0 : 30;
    return ['name' => $name, 'email' => $email, 'phone' => $phone, 'service_id' => $serviceId, 'service' => $service['name'], 'duration' => $service['duration'], 'duration_minutes' => (int) ($service['minutes'] ?? 60), 'buffer_minutes' => (int) ($service['buffer_minutes'] ?? 15), 'plan' => $plan, 'sessions' => $sessions, 'price' => $price, 'travel_per_visit' => $travel, 'total' => $price + $travel, 'zone' => $zones[$zone], 'date' => $date, 'time' => $time, 'privacy_version' => '2026-09-21'];
}

function applyRateLimit(string $ip, string $email): bool {
    $path = storagePath() . '/rate-limits.json';
    $file = fopen($path, 'c+');
    if ($file === false || !flock($file, LOCK_EX)) throw new RuntimeException('Rate limit storage unavailable');
    try {
        $raw = stream_get_contents($file); $items = $raw ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : [];
        $now = time();
        $items = array_values(array_filter($items, fn($item) => $item['time'] > $now - 3600));
        $ipHash = hash('sha256', $ip); $emailHash = hash('sha256', strtolower($email));
        $byIp = count(array_filter($items, fn($item) => $item['ip'] === $ipHash));
        $byEmail = count(array_filter($items, fn($item) => $item['email'] === $emailHash));
        if ($byIp >= 5 || $byEmail >= 3 || count($items) >= 200) return false;
        $items[] = ['ip' => $ipHash, 'email' => $emailHash, 'time' => $now];
        rewind($file); ftruncate($file, 0);
        if (fwrite($file, json_encode($items, JSON_THROW_ON_ERROR)) === false) throw new RuntimeException('Cannot write rate limit');
        fflush($file);
        return true;
    } finally { flock($file, LOCK_UN); fclose($file); }
}

function deliveryLog(string $id, string $event): void {
    // No credentials, message body, customer names, email addresses or tokens in logs.
    error_log('Alin booking ' . $id . ': ' . $event);
}
