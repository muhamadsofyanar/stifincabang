<?php

function stifinPromoterBranchCode(): string
{
    return strtoupper(trim((string) getenv('STIFIN_BRANCH_CODE')));
}

function stifinBranchPromoters(string $branchCode): array
{
    if (!preg_match('/\A[A-Z0-9]{2,12}-CAB-[A-Z0-9]{1,8}\z/', $branchCode) || $branchCode === 'XXX-CAB-00') {
        throw new RuntimeException('Isi STIFIN_BRANCH_CODE dengan kode cabang resmi.');
    }

    $baseUrl = rtrim(trim((string) getenv('STIFIN_API_BASE')), '/') ?: 'https://apro.stifin.id/api';
    $urlParts = parse_url($baseUrl);
    if (($urlParts['scheme'] ?? '') !== 'https' || empty($urlParts['host'])
        || isset($urlParts['user']) || isset($urlParts['pass'])
        || isset($urlParts['query']) || isset($urlParts['fragment'])) {
        throw new RuntimeException('STIFIN_API_BASE harus berupa URL dasar HTTPS.');
    }

    $headers = ['Accept: application/json'];
    $authHeader = trim((string) getenv('STIFIN_API_AUTH_HEADER'));
    $authValue = trim((string) getenv('STIFIN_API_AUTH_VALUE'));
    if ($authHeader !== '' || $authValue !== '') {
        if (!preg_match('/\A[A-Za-z0-9-]+\z/', $authHeader)
            || $authValue === '' || preg_match('/[\x00-\x1F\x7F]/', $authValue)) {
            throw new RuntimeException('Periksa konfigurasi autentikasi API STIFIn.');
        }
        $headers[] = $authHeader . ': ' . $authValue;
    }

    $body = '';
    $curl = curl_init($baseUrl . '/proGetCab/pro/' . rawurlencode($branchCode));
    curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) + strlen($chunk) > 12_000_000) {
                return 0;
            }
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    $received = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($received === false) {
        throw new RuntimeException('API STIFIn tidak dapat dihubungi atau respons melebihi batas ukuran.');
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('API STIFIn mengembalikan HTTP ' . $status . '.');
    }

    return stifinPromoterDirectory($body, $branchCode);
}

function stifinPromoterDirectory(string $body, string $branchCode): array
{
    $payload = json_decode($body, true);
    $rows = $payload['data'] ?? null;
    if (!is_array($rows) || !array_is_list($rows)) {
        throw new RuntimeException('Format respons daftar promotor STIFIn tidak dikenali.');
    }

    $promoters = [];
    foreach (array_slice($rows, 0, 10_000) as $row) {
        if (!is_array($row)) {
            continue;
        }

        $code = strtoupper(stifinPromoterText($row['KodeID'] ?? $row['kode'] ?? $row['code'] ?? '', 80));
        $name = stifinPromoterText($row['Nama'] ?? $row['nama'] ?? $row['name'] ?? '', 160);
        $rowBranch = strtoupper(stifinPromoterText($row['Sub'] ?? $row['KodeCabang'] ?? $row['branchCode'] ?? '', 40));
        if ($code === '' || $name === '' || ($rowBranch !== '' && $rowBranch !== $branchCode) || isset($promoters[$code])) {
            continue;
        }

        $status = $row['Aktif'] ?? $row['active'] ?? null;
        $promoters[$code] = [
            'code' => $code,
            'name' => $name,
            'area' => stifinPromoterText($row['Area'] ?? $row['area'] ?? '', 120),
            'province' => stifinPromoterText($row['Propinsi'] ?? $row['province'] ?? '', 120),
            'active' => $status === null ? null : in_array(
                strtolower(stifinPromoterText($status, 16)), ['1', 'true', 'ya', 'yes', 'aktif'], true
            ),
        ];
    }
    if ($rows !== [] && $promoters === []) {
        throw new RuntimeException('Respons API tidak memuat data yang cocok dengan kode cabang ini.');
    }

    return array_values($promoters);
}

function stifinPromoterText(mixed $value, int $maxLength): string
{
    return is_scalar($value) ? mb_substr(trim((string) $value), 0, $maxLength) : '';
}
