<?php
declare(strict_types=1);

/** Small, dependency-free XLSX writer for the authenticated CRM export. */
final class XlsxZipBuilder {
    /** @var array<int,array{name:string,data:string,compressed:string,crc:int,offset:int}> */
    private array $entries = [];

    public function add(string $name, string $data): void {
        $compressed = gzdeflate($data, 6);
        if ($compressed === false) throw new RuntimeException('Raportul nu a putut fi comprimat.');
        $this->entries[] = ['name' => $name, 'data' => $data, 'compressed' => $compressed, 'crc' => crc32($data), 'offset' => 0];
    }

    public function bytes(): string {
        $local = '';
        $central = '';
        $now = getdate();
        $year = max(1980, min(2107, (int) $now['year']));
        $dosTime = ((int) $now['hours'] << 11) | ((int) $now['minutes'] << 5) | ((int) $now['seconds'] >> 1);
        $dosDate = (($year - 1980) << 9) | ((int) $now['mon'] << 5) | (int) $now['mday'];
        foreach ($this->entries as $index => $entry) {
            $name = $entry['name'];
            $data = $entry['data'];
            $compressed = $entry['compressed'];
            $crc = $entry['crc'];
            $offset = strlen($local);
            $this->entries[$index]['offset'] = $offset;
            $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 8, $dosTime, $dosDate, $crc, strlen($compressed), strlen($data), strlen($name), 0) . $name . $compressed;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 0x0314, 20, 0x0800, 8, $dosTime, $dosDate, $crc, strlen($compressed), strlen($data), strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
        }
        $count = count($this->entries);
        return $local . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);
    }
}

function xlsxEscape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
}

function xlsxColumn(int $number): string {
    $result = '';
    while ($number > 0) {
        $number--;
        $result = chr(65 + ($number % 26)) . $result;
        $number = intdiv($number, 26);
    }
    return $result;
}

function xlsxSerial(?string $value): ?float {
    if (!$value) return null;
    try {
        $date = new DateTimeImmutable($value, new DateTimeZone('Europe/Bucharest'));
        return ((float) $date->format('U')) / 86400 + 25569;
    } catch (Throwable) {
        return null;
    }
}

function xlsxCell(string $reference, mixed $value = '', int $style = 0, ?string $formula = null): string {
    $attributes = ' r="' . $reference . '"' . ($style ? ' s="' . $style . '"' : '');
    if ($formula !== null) {
        $cached = is_numeric($value) ? (string) (0 + $value) : '0';
        return '<c' . $attributes . '><f>' . xlsxEscape($formula) . '</f><v>' . $cached . '</v></c>';
    }
    if (is_int($value) || is_float($value)) return '<c' . $attributes . '><v>' . $value . '</v></c>';
    if ($value === null || $value === '') return '<c' . $attributes . ' t="inlineStr"><is><t></t></is></c>';
    return '<c' . $attributes . ' t="inlineStr"><is><t xml:space="preserve">' . xlsxEscape((string) $value) . '</t></is></c>';
}

/** @param array<int,string> $cells */
function xlsxRow(int $number, array $cells, float $height = 21): string {
    return '<row r="' . $number . '" ht="' . $height . '" customHeight="1">' . implode('', $cells) . '</row>';
}

/** @param array<int,float> $widths @param array<int,string> $rows @param array<int,string> $merges */
function xlsxSheet(array $widths, array $rows, array $merges = [], ?string $autoFilter = null, int $freezeRows = 0, int $freezeColumns = 0, string $tabColor = '155446', bool $drawing = false): string {
    $columns = '';
    foreach ($widths as $index => $width) $columns .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
    $pane = '';
    if ($freezeRows || $freezeColumns) {
        $topLeft = xlsxColumn($freezeColumns + 1) . ($freezeRows + 1);
        $activePane = $freezeRows && $freezeColumns ? 'bottomRight' : ($freezeRows ? 'bottomLeft' : 'topRight');
        $pane = '<pane' . ($freezeColumns ? ' xSplit="' . $freezeColumns . '"' : '') . ($freezeRows ? ' ySplit="' . $freezeRows . '"' : '') . ' topLeftCell="' . $topLeft . '" activePane="' . $activePane . '" state="frozen"/>';
    }
    $mergeXml = $merges ? '<mergeCells count="' . count($merges) . '">' . implode('', array_map(static fn(string $range): string => '<mergeCell ref="' . $range . '"/>', $merges)) . '</mergeCells>' : '';
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetPr><tabColor rgb="FF' . $tabColor . '"/></sheetPr>'
        . '<sheetViews><sheetView showGridLines="0" workbookViewId="0">' . $pane . '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="21"/>'
        . '<cols>' . $columns . '</cols><sheetData>' . implode('', $rows) . '</sheetData>' . $mergeXml
        . ($autoFilter ? '<autoFilter ref="' . $autoFilter . '"/>' : '')
        . ($drawing ? '<drawing r:id="rId1"/>' : '')
        . '<pageMargins left="0.35" right="0.35" top="0.55" bottom="0.55" header="0.2" footer="0.2"/>'
        . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
        . '</worksheet>';
}

function xlsxStyles(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0 &quot;lei&quot;"/><numFmt numFmtId="165" formatCode="dd.mm.yyyy"/><numFmt numFmtId="166" formatCode="dd.mm.yyyy hh:mm"/><numFmt numFmtId="167" formatCode="0.0%"/></numFmts>'
        . '<fonts count="7">'
        . '<font><sz val="10"/><name val="Aptos"/><family val="2"/><color rgb="FF153D34"/></font>'
        . '<font><b/><sz val="10"/><name val="Aptos"/><family val="2"/><color rgb="FFFFFFFF"/></font>'
        . '<font><b/><sz val="16"/><name val="Aptos Display"/><family val="2"/><color rgb="FF153D34"/></font>'
        . '<font><i/><sz val="10"/><name val="Aptos"/><family val="2"/><color rgb="FF71847B"/></font>'
        . '<font><b/><sz val="10"/><name val="Aptos"/><family val="2"/><color rgb="FF153D34"/></font>'
        . '<font><b/><sz val="15"/><name val="Aptos Display"/><family val="2"/><color rgb="FFFFFFFF"/></font>'
        . '<font><b/><sz val="12"/><name val="Aptos"/><family val="2"/><color rgb="FF155446"/></font>'
        . '</fonts>'
        . '<fills count="10"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF155446"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF198667"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFE5F2EA"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF4D8"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFBE5E2"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF0F4F1"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFDDF3ED"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="3"><border/><border><left/><right/><top/><bottom style="thin"><color rgb="FFDCE5DD"/></bottom><diagonal/></border><border><left style="thin"><color rgb="FFBFD5C8"/></left><right style="thin"><color rgb="FFBFD5C8"/></right><top style="thin"><color rgb="FFBFD5C8"/></top><bottom style="thin"><color rgb="FFBFD5C8"/></bottom><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="28">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="165" fontId="0" fillId="7" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="165" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="166" fontId="0" fillId="7" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="166" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="164" fontId="4" fillId="7" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="164" fontId="4" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="8" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="9" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="5" fillId="2" borderId="2" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="164" fontId="5" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="164" fontId="6" fillId="5" borderId="2" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="6" fillId="9" borderId="2" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="167" fontId="4" fillId="7" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="8" borderId="2" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="164" fontId="4" fillId="8" borderId="2" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="7" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
}

function normalizedReportRecords(array $records): array {
    $result = [];
    foreach ($records as $record) {
        if (!is_array($record) || empty($record['id'])) continue;
        $record['amount'] = max(0, (int) ($record['amount'] ?? $record['total'] ?? 0));
        $record['payment_status'] = in_array(($record['payment_status'] ?? ''), ['paid', 'unpaid'], true) ? $record['payment_status'] : 'unpaid';
        $record['source'] = in_array(($record['source'] ?? ''), ['site', 'manual'], true) ? $record['source'] : (str_contains((string) ($record['internal_notes'] ?? ''), 'demonstrativă') ? 'manual' : 'site');
        $record['sessions'] = max(1, (int) ($record['sessions'] ?? 1));
        $record['session_break_minutes'] = max(0, (int) ($record['session_break_minutes'] ?? 0));
        $record['buffer_minutes'] = max(0, (int) ($record['buffer_minutes'] ?? 0));
        $result[] = $record;
    }
    usort($result, static fn(array $a, array $b): int => strcmp(($b['date'] ?? '') . ' ' . ($b['time'] ?? ''), ($a['date'] ?? '') . ' ' . ($a['time'] ?? '')));
    return $result;
}

function buildAppointmentXlsx(array $inputRecords, ?string $selectedPeriodLabel = null): string {
    $records = normalizedReportRecords($inputRecords);
    $statusLabels = ['pending' => 'În așteptare', 'confirmed' => 'Confirmată', 'completed' => 'Finalizată', 'cancelled' => 'Anulată'];
    $sourceLabels = ['manual' => 'Manual', 'site' => 'Din site'];
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $financial = array_values(array_filter($records, static fn(array $r): bool => ($r['status'] ?? '') !== 'cancelled'));
    $total = count($records);
    $paid = array_sum(array_map(static fn(array $r): int => $r['payment_status'] === 'paid' ? $r['amount'] : 0, $financial));
    $unpaid = array_sum(array_map(static fn(array $r): int => $r['payment_status'] === 'unpaid' ? $r['amount'] : 0, $financial));
    $upcoming = count(array_filter($records, static fn(array $r): bool => in_array(($r['status'] ?? ''), ['pending', 'confirmed'], true) && ($r['date'] ?? '') >= $today));
    $activeValue = $paid + $unpaid;

    $statusCounts = array_fill_keys(array_keys($statusLabels), 0);
    $sourceCounts = array_fill_keys(array_keys($sourceLabels), 0);
    $serviceStats = [];
    $monthStats = [];
    foreach ($records as $record) {
        $status = (string) ($record['status'] ?? 'pending');
        $source = (string) $record['source'];
        if (isset($statusCounts[$status])) $statusCounts[$status]++;
        if (isset($sourceCounts[$source])) $sourceCounts[$source]++;
        $service = trim((string) ($record['service'] ?? 'Serviciu necunoscut')) ?: 'Serviciu necunoscut';
        $serviceStats[$service] ??= ['count' => 0, 'manual' => 0, 'site' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0, 'value' => 0, 'paid' => 0, 'unpaid' => 0];
        $serviceStats[$service]['count']++;
        $serviceStats[$service][$source]++;
        if ($status === 'completed') $serviceStats[$service]['completed']++;
        if ($status === 'cancelled') $serviceStats[$service]['cancelled']++;
        if (in_array($status, ['pending', 'confirmed'], true)) $serviceStats[$service]['active']++;
        if ($status !== 'cancelled') {
            $serviceStats[$service]['value'] += $record['amount'];
            $serviceStats[$service][$record['payment_status']] += $record['amount'];
        }
        $month = preg_match('/^\d{4}-\d{2}/', (string) ($record['date'] ?? ''), $match) ? $match[0] : 'Fără dată';
        $monthStats[$month] ??= ['count' => 0, 'manual' => 0, 'site' => 0, 'value' => 0, 'paid' => 0, 'unpaid' => 0];
        $monthStats[$month]['count']++;
        $monthStats[$month][$source]++;
        if ($status !== 'cancelled') {
            $monthStats[$month]['value'] += $record['amount'];
            $monthStats[$month][$record['payment_status']] += $record['amount'];
        }
    }
    uasort($serviceStats, static fn(array $a, array $b): int => $b['value'] <=> $a['value']);
    ksort($monthStats);

    $firstDate = $records ? min(array_filter(array_column($records, 'date')) ?: [$today]) : $today;
    $lastDate = $records ? max(array_filter(array_column($records, 'date')) ?: [$today]) : $today;
    $generated = new DateTimeImmutable();
    $periodLabel = trim((string) $selectedPeriodLabel);
    if ($periodLabel === '') $periodLabel = $records ? (new DateTimeImmutable($firstDate))->format('d.m.Y') . ' – ' . (new DateTimeImmutable($lastDate))->format('d.m.Y') : 'fără programări';

    $summaryRows = [];
    $summaryRows[] = xlsxRow(1, [], 12);
    $summaryRows[] = xlsxRow(2, [xlsxCell('D2', 'Raport programări', 1)], 27);
    $summaryRows[] = xlsxRow(3, [xlsxCell('D3', 'Alin Bughiuș · generat la ' . $generated->format('d.m.Y H:i') . ' · perioadă: ' . $periodLabel, 2)], 21);
    $summaryRows[] = xlsxRow(4, [], 8);
    $cardColumns = [['A', 'C'], ['D', 'F'], ['G', 'I'], ['J', 'L']];
    $labels = ['PROGRAMĂRI', 'ÎNCASAT', 'DE ÎNCASAT', 'URMEAZĂ'];
    $values = [$total, $paid, $unpaid, $upcoming];
    $valueStyles = [20, 21, 22, 23];
    $labelCells = []; $valueCells = [];
    foreach ($cardColumns as $index => [$start, $end]) {
        for ($column = ord($start); $column <= ord($end); $column++) {
            $refLabel = chr($column) . '6'; $refValue = chr($column) . '7';
            $labelCells[] = xlsxCell($refLabel, $column === ord($start) ? $labels[$index] : '', 19);
            $formula = null;
            if ($column === ord($start)) {
                $lastRaw = max(5, 4 + $total);
                $formula = match ($index) {
                    0 => "COUNTA(Programari!\$A\$5:\$A\$$lastRaw)",
                    1 => "SUMIFS(Programari!\$N\$5:\$N\$$lastRaw,Programari!\$O\$5:\$O\$$lastRaw,\"Încasat\",Programari!\$M\$5:\$M\$$lastRaw,\"<>Anulată\")",
                    2 => "SUMIFS(Programari!\$N\$5:\$N\$$lastRaw,Programari!\$O\$5:\$O\$$lastRaw,\"Neîncasat\",Programari!\$M\$5:\$M\$$lastRaw,\"<>Anulată\")",
                    default => "COUNTIFS(Programari!\$D\$5:\$D\$$lastRaw,\">=\"&TODAY(),Programari!\$M\$5:\$M\$$lastRaw,\"În așteptare\")+COUNTIFS(Programari!\$D\$5:\$D\$$lastRaw,\">=\"&TODAY(),Programari!\$M\$5:\$M\$$lastRaw,\"Confirmată\")",
                };
            }
            $valueCells[] = xlsxCell($refValue, $column === ord($start) ? $values[$index] : '', $valueStyles[$index], $formula);
        }
    }
    $summaryRows[] = xlsxRow(5, [], 5);
    $summaryRows[] = xlsxRow(6, $labelCells, 22);
    $summaryRows[] = xlsxRow(7, $valueCells, 34);
    $summaryRows[] = xlsxRow(8, [], 13);
    $summaryRows[] = xlsxRow(9, [xlsxCell('A9', 'Starea programărilor', 3), xlsxCell('E9', 'Sursa programărilor', 3), xlsxCell('I9', 'Servicii după valoare', 3)], 24);
    $summaryRows[] = xlsxRow(10, [xlsxCell('A10', 'Stare', 4), xlsxCell('B10', 'Număr', 4), xlsxCell('C10', 'Pondere', 4), xlsxCell('E10', 'Sursă', 4), xlsxCell('F10', 'Număr', 4), xlsxCell('G10', 'Pondere', 4), xlsxCell('I10', 'Serviciu', 4), xlsxCell('J10', 'Programări', 4), xlsxCell('K10', 'Valoare', 4), xlsxCell('L10', 'Încasat', 4)], 28);
    $serviceTop = array_slice($serviceStats, 0, 4, true);
    $statusKeys = array_keys($statusLabels);
    $sourceKeys = array_keys($sourceLabels);
    for ($i = 0; $i < 4; $i++) {
        $row = 11 + $i;
        $statusKey = $statusKeys[$i];
        $style = [13, 14, 15, 16][$i];
        $cells = [xlsxCell('A' . $row, $statusLabels[$statusKey], $style), xlsxCell('B' . $row, $statusCounts[$statusKey], 27), xlsxCell('C' . $row, $total ? $statusCounts[$statusKey] / $total : 0, 24)];
        if ($i < 2) {
            $sourceKey = $sourceKeys[$i];
            $cells[] = xlsxCell('E' . $row, $sourceLabels[$sourceKey], $sourceKey === 'manual' ? 17 : 18);
            $cells[] = xlsxCell('F' . $row, $sourceCounts[$sourceKey], 27);
            $cells[] = xlsxCell('G' . $row, $total ? $sourceCounts[$sourceKey] / $total : 0, 24);
        }
        if ($i < count($serviceTop)) {
            $serviceName = array_keys($serviceTop)[$i]; $stat = array_values($serviceTop)[$i];
            $cells[] = xlsxCell('I' . $row, $serviceName, 5);
            $cells[] = xlsxCell('J' . $row, $stat['count'], 27);
            $cells[] = xlsxCell('K' . $row, $stat['value'], 11);
            $cells[] = xlsxCell('L' . $row, $stat['paid'], 11);
        }
        $summaryRows[] = xlsxRow($row, $cells, 24);
    }
    $summaryRows[] = xlsxRow(15, [], 11);
    $summaryRows[] = xlsxRow(16, [xlsxCell('A16', 'Observații automate', 3)], 24);
    $pending = $statusCounts['pending'];
    $topService = $serviceStats ? (string) array_key_first($serviceStats) : '—';
    $topValue = $serviceStats ? (int) $serviceStats[$topService]['value'] : 0;
    $collectionRate = $activeValue > 0 ? $paid / $activeValue : 0;
    $manualShare = $total > 0 ? $sourceCounts['manual'] / $total : 0;
    $insights = [
        $pending . ' din ' . $total . ' programări sunt în așteptarea confirmării.',
        $topService === '—' ? 'Nu există încă suficiente date pentru analiza serviciilor.' : $topService . ' are cea mai mare valoare programată: ' . number_format($topValue, 0, ',', '.') . ' lei.',
        'Programările manuale reprezintă ' . number_format($manualShare * 100, 1, ',', '.') . '% din total.',
        'Gradul de încasare pentru programările active este ' . number_format($collectionRate * 100, 1, ',', '.') . '%.',
    ];
    foreach ($insights as $index => $insight) $summaryRows[] = xlsxRow(17 + $index, [xlsxCell('A' . (17 + $index), ($index + 1) . '. ' . $insight, 25)], 24);

    $summaryMerges = ['D2:L2', 'D3:L3', 'A6:C6', 'D6:F6', 'G6:I6', 'J6:L6', 'A7:C7', 'D7:F7', 'G7:I7', 'J7:L7', 'A9:C9', 'E9:G9', 'I9:L9', 'A16:L16', 'A17:L17', 'A18:L18', 'A19:L19', 'A20:L20'];
    $summaryXml = xlsxSheet([17, 11, 11, 17, 11, 11, 17, 11, 11, 24, 15, 15], $summaryRows, $summaryMerges, null, 0, 0, '155446', true);

    $analysisRows = [];
    $analysisRows[] = xlsxRow(1, [], 12);
    $analysisRows[] = xlsxRow(2, [xlsxCell('A2', 'Analiza programărilor', 1)], 27);
    $analysisRows[] = xlsxRow(3, [xlsxCell('A3', 'Centralizare automată pe servicii și luni. Programările anulate nu intră în valorile financiare.', 2)], 21);
    $analysisRows[] = xlsxRow(4, [xlsxCell('A4', 'Serviciu', 4), xlsxCell('B4', 'Total', 4), xlsxCell('C4', 'Manual', 4), xlsxCell('D4', 'Din site', 4), xlsxCell('E4', 'Active', 4), xlsxCell('F4', 'Finalizate', 4), xlsxCell('G4', 'Anulate', 4), xlsxCell('H4', 'Valoare', 4), xlsxCell('I4', 'Încasat', 4), xlsxCell('J4', 'De încasat', 4)], 30);
    $analysisRow = 5;
    foreach ($serviceStats as $service => $stat) {
        $alt = $analysisRow % 2 === 0;
        $normal = $alt ? 6 : 5; $money = $alt ? 12 : 11;
        $ref = '$A' . $analysisRow;
        $lastRaw = max(5, 4 + $total);
        $analysisRows[] = xlsxRow($analysisRow, [
            xlsxCell('A' . $analysisRow, $service, $normal),
            xlsxCell('B' . $analysisRow, $stat['count'], 27, "COUNTIF(Programari!\$J\$5:\$J\$$lastRaw,$ref)"),
            xlsxCell('C' . $analysisRow, $stat['manual'], 27, "COUNTIFS(Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$B\$5:\$B\$$lastRaw,\"Manual\")"),
            xlsxCell('D' . $analysisRow, $stat['site'], 27, "COUNTIFS(Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$B\$5:\$B\$$lastRaw,\"Din site\")"),
            xlsxCell('E' . $analysisRow, $stat['active'], 27, "COUNTIFS(Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$M\$5:\$M\$$lastRaw,\"În așteptare\")+COUNTIFS(Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$M\$5:\$M\$$lastRaw,\"Confirmată\")"),
            xlsxCell('F' . $analysisRow, $stat['completed'], 27, "COUNTIFS(Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$M\$5:\$M\$$lastRaw,\"Finalizată\")"),
            xlsxCell('G' . $analysisRow, $stat['cancelled'], 27, "COUNTIFS(Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$M\$5:\$M\$$lastRaw,\"Anulată\")"),
            xlsxCell('H' . $analysisRow, $stat['value'], $money, "SUMIFS(Programari!\$N\$5:\$N\$$lastRaw,Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$M\$5:\$M\$$lastRaw,\"<>Anulată\")"),
            xlsxCell('I' . $analysisRow, $stat['paid'], $money, "SUMIFS(Programari!\$N\$5:\$N\$$lastRaw,Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$O\$5:\$O\$$lastRaw,\"Încasat\",Programari!\$M\$5:\$M\$$lastRaw,\"<>Anulată\")"),
            xlsxCell('J' . $analysisRow, $stat['unpaid'], $money, "SUMIFS(Programari!\$N\$5:\$N\$$lastRaw,Programari!\$J\$5:\$J\$$lastRaw,$ref,Programari!\$O\$5:\$O\$$lastRaw,\"Neîncasat\",Programari!\$M\$5:\$M\$$lastRaw,\"<>Anulată\")"),
        ], 24);
        $analysisRow++;
    }
    if (!$serviceStats) $analysisRows[] = xlsxRow($analysisRow++, [xlsxCell('A' . ($analysisRow - 1), 'Nu există încă date.', 25)], 24);
    $analysisRow += 2;
    $analysisRows[] = xlsxRow($analysisRow, [xlsxCell('A' . $analysisRow, 'Evoluție lunară', 3)], 24);
    $analysisMerges = ['A2:J2', 'A3:J3', 'A' . $analysisRow . ':J' . $analysisRow];
    $analysisRow++;
    $analysisRows[] = xlsxRow($analysisRow, [xlsxCell('A' . $analysisRow, 'Lună', 4), xlsxCell('B' . $analysisRow, 'Total', 4), xlsxCell('C' . $analysisRow, 'Manual', 4), xlsxCell('D' . $analysisRow, 'Din site', 4), xlsxCell('E' . $analysisRow, 'Valoare', 4), xlsxCell('F' . $analysisRow, 'Încasat', 4), xlsxCell('G' . $analysisRow, 'De încasat', 4)], 30);
    foreach ($monthStats as $month => $stat) {
        $analysisRow++; $alt = $analysisRow % 2 === 0; $normal = $alt ? 6 : 5; $money = $alt ? 12 : 11;
        $monthLabel = $month === 'Fără dată' ? $month : (new DateTimeImmutable($month . '-01'))->format('m.Y');
        $analysisRows[] = xlsxRow($analysisRow, [xlsxCell('A' . $analysisRow, $monthLabel, $normal), xlsxCell('B' . $analysisRow, $stat['count'], 27), xlsxCell('C' . $analysisRow, $stat['manual'], 27), xlsxCell('D' . $analysisRow, $stat['site'], 27), xlsxCell('E' . $analysisRow, $stat['value'], $money), xlsxCell('F' . $analysisRow, $stat['paid'], $money), xlsxCell('G' . $analysisRow, $stat['unpaid'], $money)], 24);
    }
    $analysisXml = xlsxSheet([26, 11, 11, 11, 11, 12, 11, 15, 15, 15], $analysisRows, $analysisMerges, null, 4, 1, '198667');

    $dataRows = [];
    $dataRows[] = xlsxRow(1, [], 12);
    $dataRows[] = xlsxRow(2, [xlsxCell('A2', 'Toate programările', 1)], 27);
    $dataRows[] = xlsxRow(3, [xlsxCell('A3', 'Export complet din CRM · ' . $generated->format('d.m.Y H:i'), 2)], 21);
    $headers = ['Referință', 'Sursă', 'Creată la', 'Data', 'Ora', 'Client', 'Telefon', 'E-mail', 'Zonă / adresă', 'Serviciu', 'Durată / sesiune (min)', 'Pauză finală (min)', 'Stare', 'Cost (lei)', 'Încasare', 'Notițe interne', 'Actualizată la', 'ID intern', 'Sesiuni', 'Pauză între sesiuni (min)', 'Timp blocat total (min)', 'Tarif / sesiune (lei)'];
    $headerCells = [];
    foreach ($headers as $index => $header) $headerCells[] = xlsxCell(xlsxColumn($index + 1) . '4', $header, 4);
    $dataRows[] = xlsxRow(4, $headerCells, 34);
    foreach ($records as $index => $record) {
        $row = 5 + $index; $alt = $index % 2 === 1; $normal = $alt ? 6 : 5; $dateStyle = $alt ? 8 : 7; $dateTimeStyle = $alt ? 10 : 9; $moneyStyle = $alt ? 12 : 11;
        $status = (string) ($record['status'] ?? 'pending');
        $source = (string) $record['source'];
        $statusStyle = ['pending' => 13, 'confirmed' => 14, 'completed' => 15, 'cancelled' => 16][$status] ?? $normal;
        $sourceStyle = $source === 'manual' ? 17 : 18;
        $created = xlsxSerial((string) ($record['created_at'] ?? ''));
        $date = xlsxSerial((string) ($record['date'] ?? ''));
        $updated = xlsxSerial((string) ($record['updated_at'] ?? ''));
        $cells = [
            xlsxCell('A' . $row, '#' . strtoupper(substr((string) $record['id'], 0, 8)), $normal),
            xlsxCell('B' . $row, $sourceLabels[$source] ?? $source, $sourceStyle),
            xlsxCell('C' . $row, $created, $dateTimeStyle),
            xlsxCell('D' . $row, $date, $dateStyle),
            xlsxCell('E' . $row, (string) ($record['time'] ?? ''), $normal),
            xlsxCell('F' . $row, (string) ($record['name'] ?? ''), $normal),
            xlsxCell('G' . $row, (string) ($record['phone'] ?? ''), $normal),
            xlsxCell('H' . $row, (string) ($record['email'] ?? ''), $normal),
            xlsxCell('I' . $row, (string) ($record['zone'] ?? ''), $normal),
            xlsxCell('J' . $row, (string) ($record['service'] ?? ''), $normal),
            xlsxCell('K' . $row, (int) ($record['duration_minutes'] ?? 0), 27),
            xlsxCell('L' . $row, (int) ($record['buffer_minutes'] ?? 0), 27),
            xlsxCell('M' . $row, $statusLabels[$status] ?? $status, $statusStyle),
            xlsxCell('N' . $row, (int) $record['amount'], $moneyStyle),
            xlsxCell('O' . $row, $record['payment_status'] === 'paid' ? 'Încasat' : 'Neîncasat', $record['payment_status'] === 'paid' ? 14 : 13),
            xlsxCell('P' . $row, (string) ($record['internal_notes'] ?? ''), 26),
            xlsxCell('Q' . $row, $updated, $dateTimeStyle),
            xlsxCell('R' . $row, (string) $record['id'], $normal),
            xlsxCell('S' . $row, (int) $record['sessions'], 27),
            xlsxCell('T' . $row, (int) $record['session_break_minutes'], 27),
            xlsxCell('U' . $row, (int) ($record['duration_minutes'] ?? 0) * (int) $record['sessions'] + (int) $record['session_break_minutes'] * max(0, (int) $record['sessions'] - 1) + (int) $record['buffer_minutes'], 27),
            xlsxCell('V' . $row, (int) ($record['price'] ?? 0), $moneyStyle),
        ];
        $dataRows[] = xlsxRow($row, $cells, 31);
    }
    $dataLast = max(5, 4 + count($records));
    $dataXml = xlsxSheet([14, 13, 19, 13, 10, 24, 16, 28, 25, 23, 21, 18, 17, 15, 16, 38, 19, 35, 11, 22, 20, 19], $dataRows, ['A2:V2', 'A3:V3'], 'A4:V' . $dataLast, 4, 2, '71847B');

    $zip = new XlsxZipBuilder();
    $zip->add('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
    $zip->add('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
    $zip->add('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Raport programări Alin Bughiuș</dc:title><dc:creator>Alin Bughiuș CRM</dc:creator><cp:lastModifiedBy>Alin Bughiuș CRM</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $generated->format('c') . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $generated->format('c') . '</dcterms:modified></cp:coreProperties>');
    $zip->add('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Alin Bughiuș CRM</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>3</vt:i4></vt:variant></vt:vector></HeadingPairs><TitlesOfParts><vt:vector size="3" baseType="lpstr"><vt:lpstr>Rezumat</vt:lpstr><vt:lpstr>Analize</vt:lpstr><vt:lpstr>Programari</vt:lpstr></vt:vector></TitlesOfParts><Company>Alin Bughiuș</Company><AppVersion>1.0</AppVersion></Properties>');
    $zip->add('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="15000" activeTab="0"/></bookViews><sheets><sheet name="Rezumat" sheetId="1" r:id="rId1"/><sheet name="Analize" sheetId="2" r:id="rId2"/><sheet name="Programari" sheetId="3" r:id="rId3"/></sheets><calcPr calcId="191029" calcMode="auto" fullCalcOnLoad="1" forceFullCalc="1"/></workbook>');
    $zip->add('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->add('xl/styles.xml', xlsxStyles());
    $logoPath = __DIR__ . '/report-logo.png';
    if (!is_file($logoPath)) throw new RuntimeException('Logo-ul raportului lipsește.');
    $zip->add('xl/worksheets/_rels/sheet1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
    $zip->add('xl/drawings/drawing1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>0</xdr:col><xdr:colOff>80000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>35000</xdr:rowOff></xdr:from><xdr:to><xdr:col>2</xdr:col><xdr:colOff>250000</xdr:colOff><xdr:row>3</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo Alin Bughiuș" descr="Sigla Alin Bughiuș"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1" cy="1"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor></xdr:wsDr>');
    $zip->add('xl/drawings/_rels/drawing1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.png"/></Relationships>');
    $zip->add('xl/media/image1.png', (string) file_get_contents($logoPath));
    $zip->add('xl/worksheets/sheet1.xml', $summaryXml);
    $zip->add('xl/worksheets/sheet2.xml', $analysisXml);
    $zip->add('xl/worksheets/sheet3.xml', $dataXml);
    return $zip->bytes();
}
