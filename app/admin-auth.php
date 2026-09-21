<?php
declare(strict_types=1);

function adminSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE && session_name() !== 'alin_admin') session_write_close();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('alin_admin');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'use_strict_mode' => true,
            'gc_maxlifetime' => 28800,
        ]);
    }
}

function adminUser(): array {
    $path = storagePath() . '/admin-user.json';
    if (!is_file($path)) {
        $lock = fopen(storagePath() . '/admin-user.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Admin storage unavailable');
        try {
            if (!is_file($path)) atomicJsonWrite($path, ['username' => 'admin', 'password_hash' => password_hash('admin', PASSWORD_DEFAULT), 'must_change_password' => false, 'updated_at' => date(DATE_ATOM)]);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    $user = json_decode((string) file_get_contents($path), true);
    if (!is_array($user) || empty($user['password_hash'])) throw new RuntimeException('Invalid admin account');
    return $user;
}

function adminSaveUser(array $user): void {
    $user['updated_at'] = date(DATE_ATOM);
    atomicJsonWrite(storagePath() . '/admin-user.json', $user);
}

function adminRememberCookieName(): string { return 'alin_admin_remember'; }

function adminRememberCookieOptions(int $expires): array {
    return [
        'expires' => $expires,
        'path' => '/admin/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

function adminClearRememberCookie(): void {
    setcookie(adminRememberCookieName(), '', adminRememberCookieOptions(time() - 3600));
    unset($_COOKIE[adminRememberCookieName()]);
}

function adminMutateRememberTokens(callable $callback): array {
    $lock = fopen(storagePath() . '/admin-remember.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Admin token storage unavailable');
    try {
        $path = storagePath() . '/admin-remember.json';
        $stored = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        $tokens = is_array($stored) ? $stored : [];
        $tokens = array_values(array_filter($tokens, static fn($item): bool => is_array($item) && (int) ($item['expires'] ?? 0) > time()));
        $tokens = $callback($tokens);
        if (!is_array($tokens)) throw new RuntimeException('Invalid admin token mutation');
        atomicJsonWrite($path, array_values($tokens));
        return $tokens;
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}

function adminRememberSelector(): string {
    $cookie = is_string($_COOKIE[adminRememberCookieName()] ?? null) ? $_COOKIE[adminRememberCookieName()] : '';
    return preg_match('/^([a-f0-9]{32})\.[a-f0-9]{64}$/D', $cookie, $match) ? $match[1] : '';
}

function adminRevokeRememberToken(): void {
    $selector = adminRememberSelector();
    if ($selector !== '') adminMutateRememberTokens(static fn(array $tokens): array => array_values(array_filter($tokens, static fn($item): bool => !hash_equals((string) ($item['selector'] ?? ''), $selector))));
    adminClearRememberCookie();
}

function adminRevokeAllRememberTokens(): void {
    adminMutateRememberTokens(static fn(array $tokens): array => []);
    adminClearRememberCookie();
}

function adminIssueRememberToken(string $username): void {
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + 60 * 60 * 24 * 90;
    $entry = [
        'selector' => $selector,
        'validator_hash' => hash('sha256', $validator),
        'username' => $username,
        'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
        'created_at' => date(DATE_ATOM),
        'expires' => $expires,
    ];
    adminMutateRememberTokens(static function(array $tokens) use ($entry): array {
        $tokens[] = $entry;
        return array_slice($tokens, -8);
    });
    setcookie(adminRememberCookieName(), $selector . '.' . $validator, adminRememberCookieOptions($expires));
    $_COOKIE[adminRememberCookieName()] = $selector . '.' . $validator;
}

function adminInitializeAuthenticatedSession(string $username): void {
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_must_change'] = false;
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

function adminRestoreRememberedLogin(): bool {
    $cookie = is_string($_COOKIE[adminRememberCookieName()] ?? null) ? $_COOKIE[adminRememberCookieName()] : '';
    if (!preg_match('/^([a-f0-9]{32})\.([a-f0-9]{64})$/D', $cookie, $match)) return false;
    [$unused, $selector, $validator] = $match;
    $matched = null;
    adminMutateRememberTokens(static function(array $tokens) use ($selector, &$matched): array {
        foreach ($tokens as $item) if (hash_equals((string) ($item['selector'] ?? ''), $selector)) { $matched = $item; break; }
        return $tokens;
    });
    $valid = is_array($matched)
        && hash_equals((string) ($matched['validator_hash'] ?? ''), hash('sha256', $validator))
        && hash_equals((string) ($matched['user_agent_hash'] ?? ''), hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''))
        && hash_equals((string) adminUser()['username'], (string) ($matched['username'] ?? ''));
    if (!$valid) { adminRevokeRememberToken(); return false; }
    adminInitializeAuthenticatedSession((string) $matched['username']);
    return true;
}

function adminLoginAllowed(string $ip): bool {
    $path = storagePath() . '/admin-login-rate.json';
    $file = fopen($path, 'c+');
    if ($file === false || !flock($file, LOCK_EX)) return false;
    try {
        $raw = stream_get_contents($file);
        $items = $raw ? json_decode($raw, true) : [];
        $now = time(); $hash = hash('sha256', $ip);
        $items = array_values(array_filter(is_array($items) ? $items : [], fn($item) => ($item['time'] ?? 0) > $now - 900));
        $attempts = count(array_filter($items, fn($item) => ($item['ip'] ?? '') === $hash));
        if ($attempts >= 8) return false;
        $items[] = ['ip' => $hash, 'time' => $now];
        rewind($file); ftruncate($file, 0); fwrite($file, json_encode($items, JSON_THROW_ON_ERROR)); fflush($file);
        return true;
    } finally { flock($file, LOCK_UN); fclose($file); }
}

function adminAttemptLogin(string $username, string $password, string $ip, bool $remember = false): bool {
    if (!adminLoginAllowed($ip)) return false;
    $user = adminUser();
    if (!hash_equals((string) $user['username'], $username) || !password_verify($password, (string) $user['password_hash'])) return false;
    adminInitializeAuthenticatedSession((string) $user['username']);
    if ($remember) adminIssueRememberToken((string) $user['username']);
    else adminRevokeRememberToken();
    return true;
}

function adminAuthenticated(): bool {
    $sessionValid = !empty($_SESSION['admin_authenticated'])
        && ($_SESSION['admin_last_activity'] ?? 0) >= time() - 28800
        && hash_equals((string) ($_SESSION['admin_user_agent'] ?? ''), hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($sessionValid) { $_SESSION['admin_last_activity'] = time(); return true; }
    unset($_SESSION['admin_authenticated'], $_SESSION['admin_username'], $_SESSION['admin_last_activity'], $_SESSION['admin_user_agent'], $_SESSION['admin_csrf']);
    return adminRestoreRememberedLogin();
}

function adminCsrf(): string {
    $_SESSION['admin_csrf'] ??= bin2hex(random_bytes(32));
    return $_SESSION['admin_csrf'];
}

function adminVerifyCsrf(string $token): void {
    if (!$token || !hash_equals((string) ($_SESSION['admin_csrf'] ?? ''), $token)) throw new RuntimeException('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.');
}

function adminLogout(): void {
    adminRevokeRememberToken();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function adminChangePassword(string $current, string $password, string $confirmation): void {
    $user = adminUser();
    if (!password_verify($current, (string) $user['password_hash'])) throw new InvalidArgumentException('Parola actuală nu este corectă.');
    if ($password !== $confirmation) throw new InvalidArgumentException('Parolele noi nu coincid.');
    if (strlen($password) < 12 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) throw new InvalidArgumentException('Alege minimum 12 caractere, cu literă mare, literă mică și cifră.');
    if (strtolower($password) === 'admin' || password_verify($password, (string) $user['password_hash'])) throw new InvalidArgumentException('Alege o parolă nouă, diferită de cea actuală.');
    $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $user['must_change_password'] = false;
    adminSaveUser($user);
    adminRevokeAllRememberTokens();
    $_SESSION['admin_must_change'] = false;
    session_regenerate_id(true);
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
