<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require APP_ROOT . '/app/booking.php';
require APP_ROOT . '/app/mailer.php';
require APP_ROOT . '/app/admin-auth.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');
adminSession();
ob_start(static fn(string $html): string => str_replace(['/assets/admin.css?v=1', '/assets/admin.js?v=1'], ['/assets/admin.css?v=12', '/assets/admin.js?v=8'], $html));

function adminRedirect(string $view = 'dashboard'): never {
    header('Location: /admin/?view=' . rawurlencode($view), true, 303);
    exit;
}
function adminFlash(string $message, string $type = 'success'): void { $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type]; }
function adminTime(string $value): string { return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value) ? $value : ''; }
function adminMonthShort(string $date): string { $months = [1=>'ian', 'feb', 'mar', 'apr', 'mai', 'iun', 'iul', 'aug', 'sept', 'oct', 'nov', 'dec']; return $months[(int) (new DateTimeImmutable($date))->format('n')]; }
function adminTodayLabel(): string { $days = [1=>'luni','marți','miercuri','joi','vineri','sâmbătă','duminică']; $months = [1=>'ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie']; $now = new DateTimeImmutable(); return $days[(int)$now->format('N')] . ', ' . $now->format('j') . ' ' . $months[(int)$now->format('n')]; }
if (!function_exists('mb_strtolower')) { function mb_strtolower(string $value): string { return strtolower($value); } }
if (!class_exists('IntlDateFormatter')) {
    final class IntlDateFormatter {
        public const FULL = 0; public const NONE = -1; public const SHORT = 2;
        private bool $short = false;
        public function __construct(...$arguments) { $this->short = ($arguments[1] ?? null) === self::SHORT; }
        public function format(DateTimeInterface $date): string { return $this->short ? adminMonthShort($date->format('Y-m-d')) : adminTodayLabel(); }
    }
}
function adminIcon(string $name): string {
    $paths = [
        'home' => '<path d="M3 11.5 12 4l9 7.5V21H6v-9.5"/><path d="M9 21v-6h6v6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'services' => '<path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="10" cy="17" r="1"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'out' => '<path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'arrow' => '<path d="m9 18 6-6-6-6"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$loginError = '';
if (!adminAuthenticated()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']);
        if (adminAttemptLogin($username, $password, $ip, $remember)) adminRedirect('dashboard');
        $loginError = 'Datele nu sunt corecte sau au existat prea multe încercări. Verifică și încearcă din nou.';
    }
    ?>
<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Autentificare · Alin Bughius CRM</title><link rel="icon" href="/assets/logo-mark.svg"><link rel="stylesheet" href="/assets/admin.css?v=1"></head><body class="admin-login-page"><main class="login-shell"><section class="login-brand"><img src="/assets/logo-mark.svg" alt="" width="64" height="64"><span>ALIN BUGHIUS · CRM</span><h1>Programul tău.<br><em>Într-un singur loc.</em></h1><p>Gestionezi programările, disponibilitatea și serviciile simplu, de pe telefon sau calculator.</p><div class="login-promise"><span>●</span> Spațiu privat și securizat</div></section><section class="login-card"><p class="eyebrow">ADMINISTRARE</p><h2>Bine ai revenit.</h2><p>Introdu datele de acces pentru a continua.</p><?php if ($loginError): ?><div class="alert error" role="alert"><?= e($loginError) ?></div><?php endif; ?><form method="post"><input type="hidden" name="action" value="login"><label>Utilizator<input name="username" autocomplete="username" required autofocus></label><label>Parolă<input type="password" name="password" autocomplete="current-password" required></label><label class="remember-login"><input type="checkbox" name="remember" value="1"><span><strong>Ține-mă minte</strong><small>Rămâi autentificat pe acest dispozitiv, inclusiv după restart.</small></span></label><button type="submit">Intră în panoul de administrare <?= adminIcon('arrow') ?></button></form><small>Poți schimba parola oricând din secțiunea Securitate.</small></section></main></body></html><?php
    exit;
}

$allowedViews = ['dashboard', 'bookings', 'schedule', 'services', 'security'];
$view = is_string($_GET['view'] ?? null) && in_array($_GET['view'], $allowedViews, true) ? $_GET['view'] : 'dashboard';
$mustChange = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        adminVerifyCsrf(is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '');
        if ($action === 'logout') { adminLogout(); header('Location: /admin/', true, 303); exit; }
        if ($action === 'change_password') {
            adminChangePassword((string) ($_POST['current_password'] ?? ''), (string) ($_POST['new_password'] ?? ''), (string) ($_POST['confirm_password'] ?? ''));
            adminFlash('Parola a fost schimbată. Panoul este acum securizat cu parola ta nouă.'); adminRedirect('dashboard');
        }
        if ($action === 'save_notification_email') {
            saveOwnerNotificationEmail((string) ($_POST['owner_email'] ?? ''));
            adminFlash('Adresa pentru programările noi a fost actualizată.'); adminRedirect('security');
        }
        if ($action === 'save_schedule') {
            $lock = calendarLock();
            try {
                $state = scheduleState($services);
                foreach (range(1, 7) as $day) {
                    $enabled = isset($_POST['day'][$day]['enabled']);
                    $start = adminTime((string) ($_POST['day'][$day]['start'] ?? ''));
                    $end = adminTime((string) ($_POST['day'][$day]['end'] ?? ''));
                    if ($enabled && (!$start || !$end || $end <= $start)) throw new InvalidArgumentException('Verifică orele de început și sfârșit pentru fiecare zi activă.');
                    $state['weekly'][(string) $day] = ['enabled' => $enabled, 'start' => $start ?: '09:00', 'end' => $end ?: '20:00'];
                }
                $horizon = (int) ($_POST['horizon_days'] ?? 90); $notice = (int) ($_POST['minimum_notice_minutes'] ?? 180);
                $stepValue = (int) ($_POST['slot_step_value'] ?? ($_POST['slot_step_minutes'] ?? 30));
                $stepUnit = (string) ($_POST['slot_step_unit'] ?? 'minutes');
                if (!in_array($stepUnit, ['minutes', 'hours'], true)) throw new InvalidArgumentException('Alege minute sau ore pentru intervalul de pornire.');
                $step = $stepUnit === 'hours' ? $stepValue * 60 : $stepValue;
                if ($horizon < 7 || $horizon > 180 || $notice < 0 || $notice > 10080 || $stepValue < 1 || $step < 1 || $step > 480) throw new InvalidArgumentException('Setările generale nu sunt valide. Intervalul de pornire poate fi între 1 minut și 8 ore.');
                $state['settings'] = array_replace($state['settings'], ['horizon_days' => $horizon, 'minimum_notice_minutes' => $notice, 'slot_step_minutes' => $step]);
                saveScheduleState($state);
            } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash('Programul săptămânal și regulile de rezervare au fost actualizate.'); adminRedirect('schedule');
        }
        if ($action === 'save_exception') {
            $date = (string) ($_POST['exception_date'] ?? ''); $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$day || $day->format('Y-m-d') !== $date) throw new InvalidArgumentException('Alege o dată validă.');
            $closed = ($_POST['exception_type'] ?? '') === 'closed'; $start = adminTime((string) ($_POST['exception_start'] ?? '')); $end = adminTime((string) ($_POST['exception_end'] ?? ''));
            if (!$closed && (!$start || !$end || $end <= $start)) throw new InvalidArgumentException('Completează programul special corect.');
            $lock = calendarLock(); try { $state = scheduleState($services); $state['exceptions'][$date] = ['closed' => $closed, 'start' => $start ?: '09:00', 'end' => $end ?: '20:00', 'note' => substr(trim((string) ($_POST['exception_note'] ?? '')), 0, 200)]; ksort($state['exceptions']); saveScheduleState($state); } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash($closed ? 'Ziua a fost marcată ca indisponibilă.' : 'Programul special a fost salvat.'); adminRedirect('schedule');
        }
        if ($action === 'remove_exception') {
            $date = (string) ($_POST['date'] ?? ''); $lock = calendarLock(); try { $state = scheduleState($services); unset($state['exceptions'][$date]); saveScheduleState($state); } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash('Excepția a fost eliminată. Se aplică din nou programul săptămânal.'); adminRedirect('schedule');
        }
        if ($action === 'save_services') {
            $posted = is_array($_POST['service'] ?? null) ? $_POST['service'] : [];
            $lock = calendarLock();
            try {
                $state = scheduleState($services);
                foreach ($state['services'] as $id => $row) {
                    if (!isset($posted[$id]) || !is_array($posted[$id])) continue;
                    $name = trim((string) ($posted[$id]['name'] ?? '')); $duration = (int) ($posted[$id]['duration'] ?? 0); $buffer = (int) ($posted[$id]['buffer'] ?? 0); $price = (int) ($posted[$id]['price'] ?? -1);
                    if (strlen($name) < 2 || strlen($name) > 80 || $duration < 15 || $duration > 480 || $duration % 5 || $buffer < 0 || $buffer > 180 || $price < 0 || $price > 10000) throw new InvalidArgumentException('Verifică durata, pauza și prețul pentru fiecare serviciu.');
                    $state['services'][$id] = array_replace($row, ['name' => $name, 'duration_minutes' => $duration, 'buffer_minutes' => $buffer, 'price' => $price, 'active' => isset($posted[$id]['active'])]);
                }
                saveScheduleState($state);
            } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash('Serviciile și duratele au fost actualizate în calendarul public.'); adminRedirect('services');
        }
        if ($action === 'add_service') {
            $name = trim((string) ($_POST['name'] ?? '')); $duration = (int) ($_POST['duration'] ?? 60); $buffer = (int) ($_POST['buffer'] ?? 15); $price = (int) ($_POST['price'] ?? 0);
            if (strlen($name) < 2 || strlen($name) > 80 || $duration < 15 || $duration > 480 || $duration % 5 || $buffer < 0 || $buffer > 180 || $price < 0) throw new InvalidArgumentException('Datele noului serviciu nu sunt valide.');
            $lock = calendarLock(); try { $state = scheduleState($services); $id = slugForService($name, $state['services']); $state['services'][$id] = ['name' => $name, 'duration_minutes' => $duration, 'buffer_minutes' => $buffer, 'price' => $price, 'sessions' => 1, 'active' => true, 'custom' => true]; saveScheduleState($state); } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash('Serviciul nou a fost adăugat și este disponibil pentru programări.'); adminRedirect('services');
        }
        if ($action === 'add_manual_booking') {
            $bookable = bookingServices($services);
            $serviceId = trim((string) ($_POST['service_id'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $zone = trim((string) ($_POST['zone'] ?? ''));
            $date = trim((string) ($_POST['date'] ?? ''));
            $time = adminTime((string) ($_POST['time'] ?? ''));
            $status = (string) ($_POST['status'] ?? 'confirmed');
            $paymentStatus = (string) ($_POST['payment_status'] ?? 'unpaid');
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_INT);
            if (!isset($bookable[$serviceId])) throw new InvalidArgumentException('Alege un serviciu activ.');
            if (strlen($name) < 2 || strlen($name) > 100 || preg_match('/[\x00-\x1f\x7f<>]/', $name)) throw new InvalidArgumentException('Completează un nume valid pentru client.');
            if (!preg_match('/^\+?[0-9\s().-]{8,22}$/D', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 8) throw new InvalidArgumentException('Completează un număr de telefon valid.');
            if ($email !== '' && (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL))) throw new InvalidArgumentException('Adresa de e-mail nu este validă.');
            if (strlen($zone) > 140 || preg_match('/[\x00-\x1f\x7f<>]/', $zone)) throw new InvalidArgumentException('Zona sau adresa este prea lungă ori conține caractere nepermise.');
            if ($amount === false || $amount < 0 || $amount > 100000) throw new InvalidArgumentException('Completează un cost valid.');
            if (!in_array($status, ['pending', 'confirmed'], true) || !in_array($paymentStatus, ['paid', 'unpaid'], true)) throw new InvalidArgumentException('Starea programării sau a încasării nu este validă.');
            $service = $bookable[$serviceId];
            $id = bin2hex(random_bytes(16));
            $record = [
                'id' => $id, 'name' => $name, 'phone' => $phone, 'email' => $email,
                'service_id' => $serviceId, 'service' => $service['name'], 'duration' => $service['duration'],
                'duration_minutes' => (int) $service['minutes'], 'buffer_minutes' => (int) $service['buffer_minutes'],
                'plan' => 'single', 'sessions' => 1, 'price' => (int) $service['price'], 'travel_per_visit' => 0,
                'total' => $amount, 'amount' => $amount, 'payment_status' => $paymentStatus,
                'zone' => $zone !== '' ? $zone : 'București & Ilfov', 'date' => $date, 'time' => $time,
                'status' => $status, 'source' => 'manual', 'privacy_version' => 'admin-manual',
                'created_at' => date(DATE_ATOM), 'updated_at' => date(DATE_ATOM),
                'manage_token' => bin2hex(random_bytes(32)), 'delivery' => [],
                'internal_notes' => substr(trim((string) ($_POST['internal_notes'] ?? '')), 0, 4000),
            ];
            $lock = recordLock($id);
            try {
                $calendar = calendarLock();
                try { assertSlotAvailable($record, $services); saveRecord($record); }
                finally { flock($calendar, LOCK_UN); fclose($calendar); }
            } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash('Programarea manuală a fost adăugată în calendar.');
            header('Location: /admin/?view=bookings#booking-' . $id, true, 303);
            exit;
        }
        if ($action === 'update_booking') {
            $id = (string) ($_POST['id'] ?? ''); $status = (string) ($_POST['status'] ?? '');
            if (!preg_match('/^[a-f0-9]{32}$/D', $id) || !in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) throw new InvalidArgumentException('Programarea nu este validă.');
            $lock = recordLock($id); $mailKind = '';
            try {
                $record = loadRecord($id); if (!$record) throw new InvalidArgumentException('Programarea nu a fost găsită.');
                $oldStatus = $record['status']; $oldMoment = ($record['date'] ?? '') . ' ' . ($record['time'] ?? '');
                $date = (string) ($_POST['date'] ?? ''); $time = adminTime((string) ($_POST['time'] ?? ''));
                $amount = filter_var($_POST['amount'] ?? ($record['amount'] ?? $record['total'] ?? 0), FILTER_VALIDATE_INT);
                $paymentStatus = (string) ($_POST['payment_status'] ?? ($record['payment_status'] ?? 'unpaid'));
                if ($amount === false || $amount < 0 || $amount > 100000 || !in_array($paymentStatus, ['paid', 'unpaid'], true)) throw new InvalidArgumentException('Verifică suma și starea încasării.');
                $record['date'] = $date; $record['time'] = $time; $record['status'] = $status; $record['amount'] = $amount; $record['total'] = $amount; $record['payment_status'] = $paymentStatus; $record['internal_notes'] = substr(trim((string) ($_POST['internal_notes'] ?? '')), 0, 4000); $record['updated_at'] = date(DATE_ATOM);
                $currentServices = bookingServices($services);
                if (isset($currentServices[$record['service_id']])) { $record['duration'] = $currentServices[$record['service_id']]['duration']; $record['duration_minutes'] = $currentServices[$record['service_id']]['minutes']; $record['buffer_minutes'] = $currentServices[$record['service_id']]['buffer_minutes']; }
                $calendar = calendarLock(); try { if (in_array($status, ['pending', 'confirmed'], true)) assertSlotAvailable($record, $services, $id); saveRecord($record); } finally { flock($calendar, LOCK_UN); fclose($calendar); }
                $momentChanged = $oldMoment !== $date . ' ' . $time;
                if ($status === 'confirmed' && ($oldStatus !== 'confirmed' || $momentChanged)) $mailKind = 'confirmed';
                if ($status === 'cancelled' && $oldStatus !== 'cancelled') $mailKind = 'cancelled';
                if ($mailKind) {
                    $record['notification_version'] = (int) ($record['notification_version'] ?? 1) + 1;
                    sendBookingEmail($record, $mailKind, $config); $record['delivery'][$mailKind . '_' . $record['notification_version']] = date(DATE_ATOM); saveRecord($record);
                }
            } finally { flock($lock, LOCK_UN); fclose($lock); }
            adminFlash($mailKind ? 'Programarea a fost actualizată, iar clientul a primit un e-mail.' : 'Programarea și notițele interne au fost actualizate.'); adminRedirect('bookings');
        }
        throw new RuntimeException('Acțiune necunoscută.');
    } catch (Throwable $error) {
        adminFlash($error instanceof InvalidArgumentException ? $error->getMessage() : 'Operațiunea nu a putut fi finalizată. Verifică datele și încearcă din nou.', 'error');
        adminRedirect(in_array($action, ['change_password', 'save_notification_email'], true) ? 'security' : ($view ?: 'dashboard'));
    }
}

$state = scheduleState($services); $effectiveServices = bookingServices($services); $records = bookingRecords();
foreach ($records as &$record) {
    $record['amount'] = max(0, (int) ($record['amount'] ?? $record['total'] ?? 0));
    $record['payment_status'] = in_array(($record['payment_status'] ?? ''), ['paid', 'unpaid'], true) ? $record['payment_status'] : 'unpaid';
    $record['source'] = in_array(($record['source'] ?? ''), ['site', 'manual'], true) ? $record['source'] : (str_contains((string) ($record['internal_notes'] ?? ''), 'demonstrativă') ? 'manual' : 'site');
}
unset($record);
usort($records, fn($a, $b) => strcmp(($b['date'] ?? '') . ' ' . ($b['time'] ?? ''), ($a['date'] ?? '') . ' ' . ($a['time'] ?? '')));
$now = new DateTimeImmutable(); $today = $now->format('Y-m-d');
$upcoming = array_values(array_filter($records, fn($record) => in_array($record['status'] ?? '', ['pending', 'confirmed'], true) && (($record['date'] ?? '') . ' ' . ($record['time'] ?? '')) >= $now->format('Y-m-d H:i')));
usort($upcoming, fn($a, $b) => strcmp($a['date'] . ' ' . $a['time'], $b['date'] . ' ' . $b['time']));
$financialRecords = array_values(array_filter($records, fn($r) => ($r['status'] ?? '') !== 'cancelled'));
$stats = [
    'pending' => count(array_filter($records, fn($r) => ($r['status'] ?? '') === 'pending')),
    'today' => count(array_filter($records, fn($r) => ($r['date'] ?? '') === $today && in_array($r['status'] ?? '', ['pending', 'confirmed'], true))),
    'upcoming' => count($upcoming),
    'confirmed' => count(array_filter($records, fn($r) => ($r['status'] ?? '') === 'confirmed')),
    'paid_amount' => array_sum(array_map(fn($r) => $r['payment_status'] === 'paid' ? (int) $r['amount'] : 0, $financialRecords)),
    'unpaid_amount' => array_sum(array_map(fn($r) => $r['payment_status'] === 'unpaid' ? (int) $r['amount'] : 0, $financialRecords)),
];
$bookingFinancialData = [];
foreach ($records as $record) $bookingFinancialData[(string) $record['id']] = ['amount' => (int) $record['amount'], 'payment_status' => $record['payment_status'], 'source' => $record['source']];
$flash = $_SESSION['admin_flash'] ?? null; unset($_SESSION['admin_flash']);
$dayNames = [1 => 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];
$statusLabels = ['pending' => 'În așteptare', 'confirmed' => 'Confirmată', 'completed' => 'Finalizată', 'cancelled' => 'Anulată'];
$csrf = adminCsrf();
if ($view === 'schedule') {
    $storedStep = max(1, min(480, (int) $state['settings']['slot_step_minutes']));
    $displayUnit = $storedStep >= 60 && $storedStep % 60 === 0 ? 'hours' : 'minutes';
    $displayValue = $displayUnit === 'hours' ? intdiv($storedStep, 60) : $storedStep;
    ob_start(static function(string $html) use ($displayUnit, $displayValue): string {
        $unitOptions = '<option value="minutes"' . ($displayUnit === 'minutes' ? ' selected' : '') . '>minute</option><option value="hours"' . ($displayUnit === 'hours' ? ' selected' : '') . '>ore</option>';
        $replacement = '<label>Interval de pornire<div class="step-editor"><input type="number" name="slot_step_value" value="' . $displayValue . '" min="1" max="' . ($displayUnit === 'hours' ? '8' : '480') . '" step="1" required aria-label="Valoarea intervalului"><select name="slot_step_unit" aria-label="Unitatea intervalului">' . $unitOptions . '</select></div></label>';
        return preg_replace('~<label>Interval de pornire<select name="slot_step_minutes">.*?</select></label>~s', $replacement, $html, 1) ?? $html;
    });
}
?>
<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= e(['dashboard'=>'Panou','bookings'=>'Programări','schedule'=>'Disponibilitate','services'=>'Servicii','security'=>'Securitate'][$view]) ?> · Alin Bughius CRM</title><link rel="icon" href="/assets/logo-mark.svg"><link rel="stylesheet" href="/assets/admin.css?v=1"><script src="/assets/admin.js?v=1" defer></script></head><body class="admin-app"><div class="admin-layout"><aside class="admin-sidebar" id="admin-sidebar"><a class="admin-brand" href="/admin/"><img src="/assets/logo-mark.svg" alt="" width="48" height="48"><span><strong>Alin Bughius</strong><small>PROGRAMĂRI & CRM</small></span></a><nav aria-label="Administrare"><?php foreach ([['dashboard','home','Privire de ansamblu'],['bookings','calendar','Programări'],['schedule','clock','Program & disponibilitate'],['services','services','Servicii'],['security','lock','Securitate']] as [$id,$icon,$label]): ?><a href="/admin/?view=<?= $id ?>" class="<?= $view === $id ? 'active' : '' ?>"><?= adminIcon($icon) ?><span><?= e($label) ?></span></a><?php endforeach; ?></nav><div class="sidebar-bottom"><a href="/" target="_blank" rel="noopener">Vezi site-ul <?= adminIcon('arrow') ?></a><form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="logout"><button type="submit"><?= adminIcon('out') ?> Ieșire din cont</button></form></div></aside><button class="sidebar-backdrop" data-sidebar-close aria-label="Închide meniul"></button><main class="admin-main"><header class="admin-topbar"><button class="sidebar-toggle" data-sidebar-toggle aria-label="Deschide meniul"><?= adminIcon('menu') ?></button><div><p><?= e((new IntlDateFormatter('ro_RO', IntlDateFormatter::FULL, IntlDateFormatter::NONE))->format(new DateTime())) ?></p><strong>Bun venit, Alin</strong></div><span class="live-indicator"><i></i> Calendar activ</span></header><div class="admin-content"><?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>

<?php if ($view === 'dashboard'): ?>
<section class="page-heading"><div><p class="eyebrow">CENTRUL TĂU DE COMANDĂ</p><h1>Tot ce urmează, <em>dintr-o privire.</em></h1><p>Programările noi, ziua de astăzi și următoarele întâlniri sunt mereu la îndemână.</p></div><a class="primary-action" href="/admin/?view=schedule">Configurează programul <?= adminIcon('arrow') ?></a></section>
<section class="stats-grid"><article><span>De confirmat</span><strong><?= $stats['pending'] ?></strong><small>Cereri care așteaptă răspuns</small></article><article><span>Astăzi</span><strong><?= $stats['today'] ?></strong><small>Întâlniri active azi</small></article><article><span>Urmează</span><strong><?= $stats['upcoming'] ?></strong><small>Programări viitoare</small></article><article><span>Confirmate</span><strong><?= $stats['confirmed'] ?></strong><small>În tot calendarul</small></article><article class="financial paid"><span>Încasați</span><strong><?= number_format($stats['paid_amount'], 0, ',', '.') ?><i> lei</i></strong><small>Total marcat ca încasat</small></article><article class="financial unpaid"><span>De încasat</span><strong><?= number_format($stats['unpaid_amount'], 0, ',', '.') ?><i> lei</i></strong><small>Programări active neîncasate</small></article></section>
<section class="panel"><div class="panel-heading"><div><p class="eyebrow">AGENDA URMĂTOARE</p><h2>Următoarele programări</h2></div><a href="/admin/?view=bookings">Vezi toate <?= adminIcon('arrow') ?></a></div><?php if (!$upcoming): ?><div class="empty-state"><span>○</span><h3>Agenda este liberă.</h3><p>Când intră prima programare, o vei vedea aici.</p></div><?php else: ?><div class="appointment-list"><?php foreach (array_slice($upcoming, 0, 6) as $record): ?><article><time><strong><?= e((new DateTimeImmutable($record['date']))->format('d')) ?></strong><span><?= e((new IntlDateFormatter('ro_RO', IntlDateFormatter::SHORT, IntlDateFormatter::NONE, 'Europe/Bucharest', null, 'MMM'))->format(new DateTime($record['date']))) ?></span></time><div><h3><?= e($record['name']) ?></h3><p><?= e($record['service']) ?> · <?= e($record['time']) ?> · <?= e($record['duration']) ?></p></div><span class="status <?= e($record['status']) ?>"><?= e($statusLabels[$record['status']] ?? $record['status']) ?></span><a href="/admin/?view=bookings#booking-<?= e($record['id']) ?>" aria-label="Deschide programarea"><?= adminIcon('arrow') ?></a></article><?php endforeach; ?></div><?php endif; ?></section>

<?php elseif ($view === 'bookings'): ?>
<section class="page-heading"><div><p class="eyebrow">CRM PROGRAMĂRI</p><h1>Oameni, momente, <em>totul în ordine.</em></h1><p>Caută, confirmă, reprogramează, notează și finalizează fiecare întâlnire.</p></div></section><div class="booking-toolbar"><label>Caută<input type="search" id="booking-search" placeholder="Nume, telefon, serviciu…"></label><label>Stare<select id="booking-filter"><option value="">Toate programările</option><?php foreach ($statusLabels as $key=>$label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label><span><strong><?= count($records) ?></strong> înregistrări</span></div><section class="crm-list" id="crm-list"><?php if (!$records): ?><div class="panel empty-state"><span>○</span><h3>Nu există încă programări.</h3><p>Prima cerere online va apărea automat aici.</p></div><?php endif; ?><?php foreach ($records as $record): ?><details class="booking-card" id="booking-<?= e($record['id']) ?>" data-status="<?= e($record['status']) ?>" data-search="<?= e(mb_strtolower(($record['name'] ?? '').' '.($record['phone'] ?? '').' '.($record['email'] ?? '').' '.($record['service'] ?? ''))) ?>"><summary><time><strong><?= e((new DateTimeImmutable($record['date']))->format('d')) ?></strong><span><?= e((new DateTimeImmutable($record['date']))->format('m.Y')) ?></span></time><div class="booking-person"><h2><?= e($record['name']) ?></h2><p><?= e($record['service']) ?> · <?= e($record['time']) ?></p></div><span class="status <?= e($record['status']) ?>"><?= e($statusLabels[$record['status']] ?? $record['status']) ?></span><span class="card-chevron"><?= adminIcon('arrow') ?></span></summary><div class="booking-detail"><div class="client-grid"><div><small>Telefon</small><a href="tel:<?= e(preg_replace('/[^+0-9]/','',$record['phone'])) ?>"><?= e($record['phone']) ?></a></div><div><small>E-mail</small><a href="mailto:<?= e($record['email']) ?>"><?= e($record['email']) ?></a></div><div><small>Zonă</small><strong><?= e($record['zone']) ?></strong></div><div><small>Cost programare</small><strong><?= e($record['amount']) ?> lei</strong></div><div><small>Variantă</small><strong>O ședință</strong></div><div><small>Referință</small><strong>#<?= e(strtoupper(substr($record['id'],0,8))) ?></strong></div></div><form method="post" class="booking-edit"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="update_booking"><input type="hidden" name="id" value="<?= e($record['id']) ?>"><label>Data<input type="date" name="date" value="<?= e($record['date']) ?>" required></label><label>Ora<input type="time" name="time" value="<?= e($record['time']) ?>" step="300" required></label><label>Stare<select name="status"><?php foreach ($statusLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $record['status']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label class="notes">Notițe interne<textarea name="internal_notes" rows="3" maxlength="2000" placeholder="Adresă, preferințe sau detalii discutate…"><?= e($record['internal_notes'] ?? '') ?></textarea><small>Vizibile numai aici, niciodată clientului.</small></label><button class="primary-action" type="submit">Salvează modificările</button></form></div></details><?php endforeach; ?></section>

<dialog class="manual-booking-dialog" id="manual-booking-dialog" aria-labelledby="manual-booking-title">
<form method="post" class="manual-booking-form">
<input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="add_manual_booking">
<header><div class="manual-booking-mark">＋</div><div><p class="eyebrow">INTRARE MANUALĂ · CRM</p><h2 id="manual-booking-title">Adaugă o programare</h2><p>Rezervările introduse aici primesc automat eticheta „Manual”.</p></div><button type="button" class="manual-booking-close" data-manual-close aria-label="Închide">×</button></header>
<div class="manual-booking-body">
<section class="manual-form-panel"><div class="manual-panel-title"><span>01</span><div><p>CLIENT</p><h3>Cine vine la masaj?</h3></div></div><div class="manual-fields client-fields"><label class="wide">Nume client<input name="name" maxlength="100" autocomplete="name" placeholder="Nume și prenume" required></label><label>Telefon<input type="tel" name="phone" maxlength="22" autocomplete="tel" placeholder="07xx xxx xxx" required></label><label>E-mail <small>opțional</small><input type="email" name="email" maxlength="180" autocomplete="email" placeholder="client@email.ro"></label><label class="wide">Zonă sau adresă <small>opțional</small><input name="zone" maxlength="140" placeholder="ex. Sector 2, București"></label></div></section>
<section class="manual-form-panel"><div class="manual-panel-title"><span>02</span><div><p>PROGRAMARE</p><h3>Când și pentru ce?</h3></div></div><div class="manual-fields booking-fields"><label class="wide">Serviciu<select name="service_id" id="manual-service" required><option value="">Alege masajul</option><?php foreach ($effectiveServices as $id=>$service): ?><option value="<?= e($id) ?>" data-price="<?= (int) $service['price'] ?>"><?= e($service['name']) ?> · <?= e($service['duration']) ?></option><?php endforeach; ?></select></label><label>Data<input type="date" name="date" id="manual-date" min="<?= e($today) ?>" max="<?= e((new DateTimeImmutable($today))->modify('+180 days')->format('Y-m-d')) ?>" required></label><label>Ora<select name="time" id="manual-time" required disabled><option value="">Alege serviciul și data</option></select><small class="manual-slot-help" id="manual-slot-help">Sunt afișate numai orele libere.</small></label></div></section>
<section class="manual-form-panel"><div class="manual-panel-title"><span>03</span><div><p>FINANCIAR & STARE</p><h3>Ultimele detalii</h3></div></div><div class="manual-fields finance-fields"><label>Cost<input type="number" name="amount" id="manual-amount" min="0" max="100000" step="1" value="200" required><small>lei</small></label><label>Încasare<select name="payment_status"><option value="unpaid">Neîncasat</option><option value="paid">Încasat</option></select></label><label>Starea programării<select name="status"><option value="confirmed">Confirmată</option><option value="pending">În așteptare</option></select></label><label class="wide">Notițe interne <small>opțional</small><textarea name="internal_notes" rows="3" maxlength="4000" placeholder="Preferințe, adresă completă sau ce ați discutat…"></textarea></label></div></section>
</div>
<footer><p><span></span> Intervalul devine ocupat imediat după salvare.</p><div><button type="button" class="secondary-action" data-manual-close>Renunță</button><button type="submit" class="primary-action">Adaugă în calendar</button></div></footer>
</form>
</dialog>

<?php elseif ($view === 'schedule'): ?>
<section class="page-heading"><div><p class="eyebrow">DISPONIBILITATE INTELIGENTĂ</p><h1>Tu stabilești <em>ritmul.</em></h1><p>Orele ocupate dispar automat din calendar. Prima și ultima oră salvate sunt ore de pornire disponibile pentru clienți.</p></div></section><form method="post" class="schedule-layout"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="save_schedule"><section class="panel"><div class="panel-heading"><div><p class="eyebrow">PROGRAM RECURENT</p><h2>Săptămâna obișnuită</h2></div></div><div class="week-list"><?php foreach ($dayNames as $number=>$name): $row=$state['weekly'][(string)$number]; ?><div class="week-row"><label class="day-toggle"><input type="checkbox" name="day[<?= $number ?>][enabled]" <?= !empty($row['enabled'])?'checked':'' ?>><span></span><strong><?= e($name) ?></strong></label><div class="hours"><label>Prima oră<input type="time" name="day[<?= $number ?>][start]" value="<?= e($row['start']) ?>" step="900"></label><i>—</i><label>Ultima oră<input type="time" name="day[<?= $number ?>][end]" value="<?= e($row['end']) ?>" step="900"></label></div></div><?php endforeach; ?></div></section><aside class="panel settings-panel"><div class="panel-heading"><div><p class="eyebrow">REGULI CALENDAR</p><h2>Cum se fac rezervările</h2></div></div><label>Programări vizibile în avans<select name="horizon_days"><?php foreach ([30,60,90,120,180] as $value): ?><option value="<?= $value ?>" <?= (int)$state['settings']['horizon_days']===$value?'selected':'' ?>><?= $value ?> zile</option><?php endforeach; ?></select></label><label>Timp minim înainte de programare<select name="minimum_notice_minutes"><?php foreach ([0=>'Fără limită',60=>'1 oră',180=>'3 ore',360=>'6 ore',720=>'12 ore',1440=>'24 ore',2880=>'48 ore'] as $value=>$label): ?><option value="<?= $value ?>" <?= (int)$state['settings']['minimum_notice_minutes']===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Interval de pornire<select name="slot_step_minutes"><?php foreach ([15,30,60] as $value): ?><option value="<?= $value ?>" <?= (int)$state['settings']['slot_step_minutes']===$value?'selected':'' ?>>Din <?= $value ?> în <?= $value ?> minute</option><?php endforeach; ?></select></label><button class="primary-action" type="submit">Salvează programul</button><p class="helper">Durata și pauza blochează suprapunerile, fără să micșoreze ultima oră de pornire aleasă.</p></aside></form><section class="panel exceptions"><div class="panel-heading"><div><p class="eyebrow">ZILE SPECIALE</p><h2>Concedii și program diferit</h2></div></div><form method="post" class="exception-form"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="save_exception"><label>Data<input type="date" name="exception_date" min="<?= e($today) ?>" required></label><label>Tip<select name="exception_type" id="exception-type"><option value="closed">Zi liberă / indisponibil</option><option value="custom">Program special</option></select></label><label class="exception-hours" hidden>Prima oră<input type="time" name="exception_start" value="10:00"></label><label class="exception-hours" hidden>Ultima oră<input type="time" name="exception_end" value="16:00"></label><label>Notă<input name="exception_note" maxlength="100" placeholder="ex. Concediu"></label><button type="submit" class="secondary-action">Adaugă excepția</button></form><?php if ($state['exceptions']): ?><div class="exception-list"><?php foreach ($state['exceptions'] as $date=>$exception): ?><article><time><?= e((new DateTimeImmutable($date))->format('d.m.Y')) ?></time><div><strong><?= !empty($exception['closed'])?'Indisponibil':'Porniri '.$exception['start'].'–'.$exception['end'] ?></strong><small><?= e($exception['note'] ?: 'Fără notă') ?></small></div><form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="remove_exception"><input type="hidden" name="date" value="<?= e($date) ?>"><button type="submit">Elimină</button></form></article><?php endforeach; ?></div><?php endif; ?></section>

<?php elseif ($view === 'services'): ?>
<section class="page-heading"><div><p class="eyebrow">SERVICII & TIMPI</p><h1>Fiecare masaj, <em>exact cât trebuie.</em></h1><p>Durata și pauza de după sunt folosite automat pentru calculul orelor libere.</p></div></section><form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="save_services"><section class="service-admin-grid"><?php foreach ($state['services'] as $id=>$row): ?><article class="service-admin-card"><div class="service-card-top"><label class="switch"><input type="checkbox" name="service[<?= e($id) ?>][active]" <?= !empty($row['active'])?'checked':'' ?>><span></span></label><small><?= !empty($row['custom'])?'SERVICIU PERSONALIZAT':'DIN SITE' ?></small></div><label>Denumire<input name="service[<?= e($id) ?>][name]" value="<?= e($row['name']) ?>" maxlength="80" required></label><div class="service-fields"><label>Durată<input type="number" name="service[<?= e($id) ?>][duration]" value="<?= (int)$row['duration_minutes'] ?>" min="15" max="480" step="5"><small>minute</small></label><label>Pauză după<input type="number" name="service[<?= e($id) ?>][buffer]" value="<?= (int)$row['buffer_minutes'] ?>" min="0" max="180" step="5"><small>minute</small></label><label>Preț<input type="number" name="service[<?= e($id) ?>][price]" value="<?= (int)$row['price'] ?>" min="0" max="10000"><small>lei</small></label><label>Abonament<input type="number" name="service[<?= e($id) ?>][sessions]" value="<?= (int)$row['sessions'] ?>" min="1" max="20"><small>ședințe</small></label></div><p><strong><?= (int)$row['duration_minutes']+(int)$row['buffer_minutes'] ?> min</strong> blocate în calendar pentru fiecare rezervare.</p></article><?php endforeach; ?></section><button class="primary-action save-services" type="submit">Salvează toate serviciile</button></form><section class="panel add-service"><div><p class="eyebrow">SERVICIU NOU</p><h2>Adaugă un tip de masaj</h2><p>Va apărea imediat în formularul public de programare.</p></div><form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="add_service"><label>Denumire<input name="name" maxlength="80" required placeholder="ex. Masaj sportiv"></label><label>Durată<input type="number" name="duration" value="60" min="15" max="480" step="5"></label><label>Pauză<input type="number" name="buffer" value="15" min="0" max="180" step="5"></label><label>Preț<input type="number" name="price" value="200" min="0" max="10000"></label><label>Ședințe / abonament<input type="number" name="sessions" value="4" min="1" max="20"></label><button class="secondary-action" type="submit">Adaugă serviciul</button></form></section>

<?php else: ?>
<section class="page-heading"><div><p class="eyebrow">CONTUL TĂU</p><h1>Securitate, <em>fără compromisuri.</em></h1><p>Parola protejează datele clienților și întregul calendar.</p></div></section><?php if ($mustChange): ?><div class="security-notice"><strong>Înainte de toate, alege parola ta.</strong><p>Datele inițiale sunt doar pentru prima intrare. După salvare, vei avea acces la întregul CRM.</p></div><?php endif; ?><section class="security-grid"><form method="post" class="panel password-form"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="change_password"><div class="panel-heading"><div><p class="eyebrow">SCHIMBĂ PAROLA</p><h2>O parolă doar a ta</h2></div></div><label>Parola actuală<input type="password" name="current_password" autocomplete="current-password" required></label><label>Parola nouă<input type="password" name="new_password" autocomplete="new-password" minlength="12" required><small>Minimum 12 caractere, cu literă mare, literă mică și cifră.</small></label><label>Confirmă parola nouă<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required></label><button class="primary-action" type="submit">Schimbă parola</button></form><aside class="panel security-info"><span><?= adminIcon('lock') ?></span><h2>Ce protejăm</h2><ul><li>Datele de contact ale clienților</li><li>Agenda și orele disponibile</li><li>Notițele interne</li><li>Configurarea serviciilor</li></ul><p>Sesiunea obișnuită se închide după 8 ore. Opțiunea „Ține-mă minte” păstrează accesul pe dispozitiv până la 90 de zile și este revocată la ieșire sau la schimbarea parolei.</p></aside></section>
<section class="panel notification-settings"><div><p class="eyebrow">NOTIFICĂRI PROGRAMĂRI</p><h2>Unde primești cererile noi</h2><p>Aici ajung detaliile și butonul privat de confirmare pentru fiecare programare făcută pe site.</p></div><form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="save_notification_email"><label>Adresa de e-mail<input type="email" name="owner_email" value="<?= e($config['owner_email']) ?>" maxlength="180" autocomplete="email" required></label><button class="primary-action" type="submit">Salvează adresa</button></form></section>
<?php endif; ?><?php if ($view === 'bookings'): ?><template id="booking-financial-data"><?= json_encode($bookingFinancialData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></template><?php endif; ?></div></main></div></body></html>
