<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/xlsx-report.php';

$records = [
    [
        'id' => '11111111111111111111111111111111', 'source' => 'site', 'created_at' => '2026-09-01T10:00:00+03:00',
        'date' => '2026-09-03', 'time' => '10:00', 'name' => 'Client Din Site', 'phone' => '0773000001',
        'email' => 'client1@example.com', 'zone' => 'Sector 2', 'service' => 'Masaj terapeutic',
        'duration_minutes' => 60, 'sessions' => 1, 'session_break_minutes' => 15, 'buffer_minutes' => 15, 'price' => 200, 'status' => 'completed', 'amount' => 200,
        'payment_status' => 'paid', 'internal_notes' => 'Preferă presiune medie.', 'updated_at' => '2026-09-03T11:15:00+03:00',
        'manage_token' => 'secret-that-must-not-be-exported',
    ],
    [
        'id' => '22222222222222222222222222222222', 'source' => 'manual', 'created_at' => '2026-09-08T09:30:00+03:00',
        'date' => '2026-09-10', 'time' => '18:30', 'name' => 'Client Manual', 'phone' => '0773000002',
        'email' => '', 'zone' => 'Pipera', 'service' => 'Masaj de relaxare',
        'duration_minutes' => 60, 'sessions' => 2, 'session_break_minutes' => 15, 'buffer_minutes' => 15, 'price' => 200, 'status' => 'confirmed', 'amount' => 400,
        'payment_status' => 'unpaid', 'internal_notes' => 'Programare telefonică.', 'updated_at' => '2026-09-08T09:30:00+03:00',
        'privacy_token' => 'another-secret',
    ],
    [
        'id' => '33333333333333333333333333333333', 'source' => 'site', 'created_at' => '2026-09-15T12:20:00+03:00',
        'date' => '2026-09-22', 'time' => '14:00', 'name' => 'Client Nou', 'phone' => '0773000003',
        'email' => 'client3@example.com', 'zone' => 'Sector 1', 'service' => 'Deep Tissue',
        'duration_minutes' => 60, 'sessions' => 1, 'session_break_minutes' => 15, 'buffer_minutes' => 15, 'price' => 250, 'status' => 'pending', 'amount' => 250,
        'payment_status' => 'unpaid', 'internal_notes' => '', 'updated_at' => '2026-09-15T12:20:00+03:00',
    ],
];

$path = $argv[1] ?? dirname(__DIR__) . '/.runtime/report-test.xlsx';
if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
$content = buildAppointmentXlsx($records, 'Septembrie 2026 · raport demonstrativ');
if (file_put_contents($path, $content) === false || strlen($content) < 12000) {
    throw new RuntimeException('Raportul XLSX nu a fost generat corect.');
}
echo "PASS: raport XLSX generat la {$path} (" . strlen($content) . " bytes).\n";
