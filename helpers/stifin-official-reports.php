<?php

function stifinOfficialCsvRows($stream, string $valueColumn): array
{
    $header = fgetcsv($stream, 0, ',', '"', '');
    if ($header) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    }
    if ($header !== ['referensi', 'tanggal', 'kode_promotor', $valueColumn]) {
        throw new InvalidArgumentException('Kolom CSV harus mengikuti template laporan.');
    }

    $rows = [];
    $line = 1;
    while (($raw = fgetcsv($stream, 0, ',', '"', '')) !== false) {
        $line++;
        if ($raw === [null]) {
            continue;
        }
        if (count($raw) !== 4 || count($rows) >= 5000) {
            throw new InvalidArgumentException("Baris CSV bermasalah ($line) atau melebihi 5.000 catatan.");
        }

        [$ref, $date, $promoter, $value] = array_map('trim', $raw);
        $parsed = preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $date)
            : false;
        $validValue = match ($valueColumn) {
            'hasil' => preg_match('/^[\p{L}\p{N} ._()+\/-]{1,120}$/uD', $value),
            'jumlah_tes' => preg_match('/^\d{1,9}$/D', $value),
            default => preg_match('/^[+-]?\d{1,9}$/D', $value),
        };
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.\/-]{0,99}$/D', $ref)
            || isset($rows[$ref])
            || !$parsed || $parsed->format('Y-m-d') !== $date
            || !preg_match('/^[A-Za-z0-9_-]{0,40}$/D', $promoter)
            || !$validValue) {
            throw new InvalidArgumentException("Nilai atau referensi duplikat pada catatan CSV $line.");
        }
        $rows[$ref] = [$ref, $date, strtoupper($promoter), $value];
    }

    if (!$rows) {
        throw new InvalidArgumentException('CSV belum berisi catatan.');
    }
    return $rows;
}

function stifinOfficialApiResultRows(string $body, DateTimeImmutable $start): array
{
    $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    $data = $payload['data'] ?? null;
    $total = filter_var($payload['total'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if (($payload['Pesan'] ?? null) !== 'Success' || !is_array($data) || !array_is_list($data)
        || $total === false || $total !== count($data)) {
        throw new RuntimeException('API belum mengembalikan laporan bulanan yang lengkap.');
    }

    $rows = [];
    foreach ($data as $item) {
        $ref = $item['KodeID'] ?? null;
        $value = $item['KetHasil'] ?? null;
        if (!is_string($ref) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,99}$/D', $ref)
            || isset($rows[$ref]) || !is_string($value)
            || !preg_match('/^[\p{L}\p{N} ._()+\/-]{1,120}$/uD', trim($value))) {
            throw new RuntimeException('API mengembalikan hasil tes tanpa referensi atau kode hasil yang valid.');
        }

        $date = $item['TglTes'] ?? null;
        $exact = is_string($date) && preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
            && ($parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date))
            && $parsed->format('Y-m-d') === $date && $parsed->format('Y-m') === $start->format('Y-m');
        $promoter = '';
        if (preg_match('/^([A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+)-[0-9]{4}-[0-9]{4}$/D', $ref, $parts)
            && strlen($parts[1]) <= 40) {
            $promoter = $parts[1];
        }
        $rows[$ref] = [$ref, $exact ? $date : $start->format('Y-m-d'), $promoter, trim($value), (int) !$exact];
    }
    return $rows;
}

function stifinSaveOfficialRows(PDO $db, string $branch, string $type, array $rows): void
{
    $db->beginTransaction();
    try {
        $insert = $db->prepare(
            'INSERT INTO stifin_official_report_rows
                (branch_code, report_type, source_ref, report_date, promoter_code, report_value, period_only)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                report_date = CASE WHEN VALUES(period_only) = 0 THEN VALUES(report_date)
                    WHEN period_only = 0 THEN report_date ELSE VALUES(report_date) END,
                period_only = LEAST(period_only, VALUES(period_only)),
                promoter_code = IF(VALUES(promoter_code) = '', promoter_code, VALUES(promoter_code)),
                report_value = VALUES(report_value),
                imported_at = CURRENT_TIMESTAMP'
        );
        // Use a consistent lock order when two imports contain the same references.
        ksort($rows, SORT_STRING);
        foreach ($rows as $row) {
            $insert->execute([$branch, $type, ...($row + [4 => 0])]);
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}
