<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once APP_ROOT . '/vendor/PHPMailer/src/Exception.php';
require_once APP_ROOT . '/vendor/PHPMailer/src/PHPMailer.php';
require_once APP_ROOT . '/vendor/PHPMailer/src/SMTP.php';

function createMailer(array $config): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->Port = $config['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->Timeout = 15;
    $mail->getSMTPInstance()->Timelimit = 20;
    $mail->SMTPDebug = 0;
    $mail->SMTPOptions = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], 'Alin Bughius');
    return $mail;
}

function bookingEmail(array $record, string $kind, array $config): array {
    $owner = $kind === 'owner'; $confirmed = $kind === 'confirmed'; $cancelled = $kind === 'cancelled';
    $reference = strtoupper(substr($record['id'], 0, 8));
    $date = (new DateTimeImmutable($record['date']))->format('d.m.Y');
    $subject = $owner ? "Cerere nouă de programare #{$reference}" : ($cancelled ? "Actualizare programare #{$reference}" : ($confirmed ? "Programarea ta a fost confirmată · {$date}, {$record['time']}" : "Am primit cererea ta de programare #{$reference}"));
    $heading = $owner ? 'O nouă cerere de programare' : ($cancelled ? 'Programarea a fost anulată.' : ($confirmed ? 'Ne vedem în curând!' : 'Am primit cererea ta.'));
    $intro = $owner ? 'Verifică detaliile de mai jos, contactează clientul pentru adresă și confirmă disponibilitatea.' : ($cancelled ? 'Programarea de mai jos nu mai este activă. Pentru a alege un alt moment, răspunde acestui e-mail, sună sau trimite un mesaj pe WhatsApp.' : ($confirmed ? 'Alin a confirmat data și ora de mai jos. Pentru adresa exactă sau orice modificare, răspunde acestui e-mail ori sună la 0773 919 071.' : 'Îți mulțumesc pentru încredere! Cererea ta este înregistrată, iar intervalul este reținut în calendar. Alin verifică detaliile și îți va trimite confirmarea finală.'));
    $rows = ['Referință' => $reference, 'Nume' => $record['name'], 'Masaj' => $record['service'], 'Durată' => $record['duration'], 'Data' => $date, 'Ora (România)' => $record['time'], 'Zona' => $record['zone'], 'Telefon' => $record['phone'], 'E-mail client' => $record['email'], 'Tarif masaj' => $record['price'] . ' lei', 'Deplasare' => $record['travel_per_visit'] ? '30 lei' : 'Inclusă', 'Total estimat' => $record['total'] . ' lei'];
    $table = ''; $plainRows = '';
    foreach ($rows as $label => $value) {
        $table .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e3e9e2;color:#65736d;font-size:14px;vertical-align:top;width:43%">' . e($label) . '</td><td style="padding:10px 0;border-bottom:1px solid #e3e9e2;color:#163d34;font-size:14px">' . e($value) . '</td></tr>';
        $plainRows .= $label . ': ' . $value . "\n";
    }
    $link = rtrim($config['site_url'], '/') . '/confirmare.php?id=' . $record['id'] . '&token=' . $record['manage_token'];
    $action = $owner ? '<p style="margin:28px 0"><a href="' . e($link) . '" style="background:#164d40;color:white;text-decoration:none;padding:14px 22px;border-radius:6px;display:inline-block">Verifică și confirmă programarea</a></p><p style="font-size:12px;color:#65736d">Link privat, destinat doar ție. Nu îl redirecționa clientului. Simpla deschidere nu confirmă programarea.</p>' : '<p style="margin:25px 0"><a href="https://wa.me/40773919071" style="color:#008779">Scrie-mi pe WhatsApp</a> · <a href="tel:+40773919071" style="color:#008779">0773 919 071</a></p>';
    $notice = 'Plata și adresa exactă se stabilesc direct cu Alin. Nu se efectuează nicio plată online.';
    $html = '<!doctype html><html lang="ro"><head><meta charset="utf-8"></head><body style="margin:0;background:#f0f7f3;font-family:Arial,sans-serif"><table role="presentation" style="width:100%;border-collapse:collapse"><tr><td style="padding:30px 15px"><table role="presentation" style="width:100%;max-width:590px;margin:auto;background:#fff;border-radius:12px;border-collapse:collapse"><tr><td style="background:#164d40;color:#fff;padding:26px 30px;font-size:26px">Alin Bughius<span style="display:block;font-size:10px;letter-spacing:2px;margin-top:8px;color:#d6eee4">MASAJ & STARE DE BINE</span></td></tr><tr><td style="padding:30px"><h1 style="font-size:26px;line-height:1.3;color:#163d34;margin:0 0 15px">' . e($heading) . '</h1><p style="font-size:15px;line-height:1.8;color:#65736d">' . e($intro) . '</p><table style="width:100%;border-collapse:collapse;margin:22px 0">' . $table . '</table><p style="font-size:13px;line-height:1.8;color:#65736d">' . e($notice) . '</p>' . $action . '<p style="font-size:12px;color:#849086;margin-top:30px">Acest e-mail se referă doar la solicitarea de programare pe alinbughius.ro.</p></td></tr></table></td></tr></table></body></html>';
    return ['subject' => $subject, 'html' => $html, 'text' => $heading . "\n\n" . $intro . "\n\n" . $plainRows . "\n" . $notice . "\n\n" . ($owner ? "Confirmare (link privat): " . $link : 'Contact: 0773 919 071 · https://wa.me/40773919071')];
}

function sendBookingEmail(array $record, string $kind, array $config): void {
    $content = bookingEmail($record, $kind, $config);
    $mail = createMailer($config);
    $owner = $kind === 'owner';
    $mail->addAddress($owner ? $config['owner_email'] : $record['email'], $owner ? 'Alin Bughius' : $record['name']);
    $mail->addReplyTo($owner ? $record['email'] : $config['owner_email'], $owner ? $record['name'] : 'Alin Bughius');
    $mail->isHTML(true); $mail->Subject = $content['subject']; $mail->Body = $content['html']; $mail->AltBody = $content['text'];
    $mail->MessageID = '<' . $record['id'] . '.' . $kind . '.' . (int) ($record['notification_version'] ?? 1) . '@alinbughius.ro>';
    // Test capture is possible only in a CLI process, never via the production web SAPI.
    if (in_array(PHP_SAPI, ['cli', 'cli-server'], true) && getenv('APP_MAIL_CAPTURE') === '1') {
        if (getenv('APP_MAIL_FAIL') === $kind) throw new RuntimeException('Simulated test failure');
        $mail->preSend();
        $suffix = (int) ($record['notification_version'] ?? 1) > 1 ? '-' . (int) $record['notification_version'] : '';
        if (file_put_contents(storagePath() . '/' . $record['id'] . '-' . $kind . $suffix . '.eml', $mail->getSentMIMEMessage(), LOCK_EX) === false) throw new RuntimeException('Cannot capture test mail');
        return;
    }
    if (!$config['smtp_password']) throw new RuntimeException('SMTP is not configured');
    $mail->send();
}

function deliverBooking(array &$record, array $config): void {
    foreach (['owner', 'receipt'] as $kind) {
        if (!empty($record['delivery'][$kind])) continue;
        try {
            sendBookingEmail($record, $kind, $config);
            $record['delivery'][$kind] = date(DATE_ATOM);
            saveRecord($record);
        } catch (Throwable $error) {
            $record['last_attempt'] = time();
            saveRecord($record);
            deliveryLog($record['id'], $kind . ' mail failed');
            if ($kind === 'owner') break;
        }
    }
}
