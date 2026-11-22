<?php
require_once __DIR__ . '/../../bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../../helpers/stifin-promoters.php';
require_once __DIR__ . '/../../helpers/stifin-official-reports.php';

header('Cache-Control: no-store');
$pageTitle = $topbarTitle = 'Data Resmi App Cabang';
$source = 'https://cabang.stifin.or.id';
$reports = [
    'hasil' => ['Hasil tes resmi', '/RepTes', 'hasil'],
    'rekap' => ['Rekap tes resmi', '/RekTes', 'jumlah_tes'],
    'voucher-pusat' => ['Transaksi voucher Pusat', '/TranVoucher', 'jumlah_voucher'],
    'voucher-cabang' => ['Transaksi voucher Cabang', '/TranVoucherCab', 'jumlah_voucher'],
];
$type = $_GET['laporan'] ?? 'hasil';
if (!is_string($type) || !isset($reports[$type])) {
    $type = 'hasil';
}
[$label, $path, $column] = $reports[$type];
$base = APP_URL . '/admin/data-resmi-cabang?laporan=' . $type;
$branch = stifinPromoterBranchCode();
if (!preg_match('/\A[A-Z0-9]{2,12}-CAB-[A-Z0-9]{1,8}\z/', $branch) || $branch === 'XXX-CAB-00') {
    $branch = '';
}
$month = $_GET['bulan'] ?? date('Y-m');
if (!is_string($month) || !preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/D', $month)) {
    $month = date('Y-m');
}
$page = filter_var($_GET['halaman'] ?? 1, FILTER_VALIDATE_INT, ['options' => [
    'min_range' => 1, 'max_range' => 1000000,
]]) ?: 1;
$csrf = generateCSRF();

if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template-' . $type . '.csv"');
    echo "referensi,tanggal,kode_promotor,$column\n";
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || !validateCSRF($token) || $branch === '') {
            throw new InvalidArgumentException('Sesi atau konfigurasi cabang belum siap.');
        }
        $file = $_FILES['csv'] ?? [];
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024
            || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Pilih CSV dengan ukuran maksimal 2 MB.');
        }
        $stream = fopen($file['tmp_name'], 'rb');
        if ($stream === false) {
            throw new RuntimeException('Upload could not be opened.');
        }
        try {
            $rows = stifinOfficialCsvRows($stream, $column);
        } finally {
            fclose($stream);
        }
        stifinSaveOfficialRows(getDB(), $branch, $type, $rows);
        $firstMonth = substr(min(array_column($rows, 1)), 0, 7);
        $lastMonth = substr(max(array_column($rows, 1)), 0, 7);
        if ($firstMonth === $lastMonth) {
            $month = $firstMonth;
        }
        header('Location: ' . $base . '&bulan=' . $month . '&impor=' . count($rows), true, 303);
        exit;
    } catch (InvalidArgumentException $error) {
        http_response_code(422);
        $message = $error->getMessage();
    } catch (Throwable $error) {
        $message = 'Impor gagal. Periksa konfigurasi database dan status migrasi.';
        error_log('STIFIn official CSV import failed');
    }
}

$rows = null;
$derivedRecap = [];
if ($branch !== '') {
    try {
        $start = new DateTimeImmutable($month . '-01');
        $query = getDB()->prepare('SELECT source_ref, report_date, promoter_code, report_value, imported_at
            FROM stifin_official_report_rows WHERE branch_code = ? AND report_type = ?
            AND report_date >= ? AND report_date < ? ORDER BY report_date DESC, source_ref LIMIT 101 OFFSET ?');
        $query->bindValue(1, $branch);
        $query->bindValue(2, $type);
        $query->bindValue(3, $start->format('Y-m-d'));
        $query->bindValue(4, $start->modify('+1 month')->format('Y-m-d'));
        $query->bindValue(5, ($page - 1) * 100, PDO::PARAM_INT);
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        if ($type === 'rekap' && $page === 1 && !$rows) {
            $summary = getDB()->prepare(
                "SELECT promoter_code, COUNT(*) AS test_count FROM stifin_official_report_rows
                 WHERE branch_code = ? AND report_type = 'hasil' AND report_date >= ? AND report_date < ?
                 GROUP BY promoter_code ORDER BY promoter_code"
            );
            $summary->execute([$branch, $start->format('Y-m-d'), $start->modify('+1 month')->format('Y-m-d')]);
            $derivedRecap = $summary->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $error) {
        error_log('STIFIn official report query failed');
    }
}
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../../partials/sidebar-admin.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden lg:ml-60">
        <?php include __DIR__ . '/../../partials/topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-5 lg:p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold">Data Resmi App Cabang</h1>
                <a class="rounded-lg border px-3 py-2 text-sm" href="<?= e(APP_URL) ?>/admin/kantor-cabang">Kantor Virtual Cabang</a>
            </div>
            <nav class="flex flex-wrap gap-2" aria-label="Jenis laporan">
                <?php foreach ($reports as $key => [$name]): ?>
                    <a class="rounded-lg border px-3 py-2 text-sm font-semibold <?= $key === $type
                           ? 'bg-emerald-900 text-white' : 'bg-white dark:bg-gray-900' ?>" <?= $key === $type ? 'aria-current="page"' : '' ?>
                       href="<?= e(APP_URL . '/admin/data-resmi-cabang?laporan=' . $key . '&bulan=' . $month) ?>"><?= e($name) ?></a>
                <?php endforeach; ?>
            </nav>
            <section class="rounded-xl border p-5 bg-white dark:bg-gray-900 space-y-3">
                <h2 class="font-bold"><?= e($label) ?> · <?= e($branch ?: 'Cabang belum dikonfigurasi') ?></h2>
                <p>Data diperbarui melalui impor CSV admin.</p>
                <div class="flex flex-wrap gap-4">
                    <a class="underline" href="<?= e($source . $path) ?>" target="_blank" rel="noopener noreferrer">Buka laporan di App Cabang</a>
                    <a class="underline" href="<?= e($base . '&template=1') ?>">Unduh template CSV</a>
                </div>
                <?php if ($branch !== ''): ?>
                    <form method="post" enctype="multipart/form-data" action="<?= e($base . '&bulan=' . $month) ?>" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <label>File CSV<input class="block" type="file" name="csv" accept=".csv,text/csv" required></label>
                        <button class="border rounded p-2">Impor CSV</button>
                    </form>
                    <p class="text-sm">Gunakan referensi resmi yang stabil dan tanggal YYYY-MM-DD. Referensi yang sama memperbarui catatan sebelumnya. Untuk hasil tes, masukkan kode hasil tanpa nama peserta.</p>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <p role="alert"><?= e($message) ?></p>
                <?php elseif (is_string($_GET['impor'] ?? null) && ctype_digit($_GET['impor'])): ?>
                    <p role="status"><?= (int) $_GET['impor'] ?> catatan diproses. Pilih bulan untuk melihat data yang diimpor.</p>
                <?php endif; ?>
            </section>
            <section class="rounded-xl border p-5 bg-white dark:bg-gray-900 overflow-x-auto space-y-3">
                <form method="get" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="laporan" value="<?= e($type) ?>">
                    <label>Bulan <input class="border rounded p-2 dark:bg-gray-950" type="month" name="bulan" value="<?= e($month) ?>"></label>
                    <button class="border rounded p-2">Tampilkan</button>
                </form>
                <?php if ($rows === null): ?>
                    <p>Laporan belum tersedia. Periksa konfigurasi cabang dan status migrasi.</p>
                <?php elseif ($derivedRecap): ?>
                    <h2 class="font-bold">Ringkasan dari hasil tes yang diimpor</h2>
                    <p><?= array_sum(array_column($derivedRecap, 'test_count')) ?> tes tercatat pada <?= e($month) ?>. Cakupan mengikuti hasil yang sudah diimpor.</p>
                    <table class="w-full text-left">
                        <thead><tr><th>Promotor</th><th>Jumlah tes</th></tr></thead>
                        <tbody>
                        <?php foreach ($derivedRecap as $summaryRow): ?>
                            <tr class="border-t">
                                <td><?= e($summaryRow['promoter_code'] ?: 'Tidak tercantum') ?></td>
                                <td><?= (int) $summaryRow['test_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif (!$rows): ?>
                    <?php if ($page > 1): ?>
                        <p>Tidak ada catatan pada halaman ini.</p>
                    <?php else: ?>
                        <p>Belum ada data impor untuk <?= e($month) ?>. Pilih bulan sesuai periode laporan di App Cabang atau unggah CSV.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <table class="w-full text-left">
                        <thead><tr><th>Referensi</th><th>Tanggal</th><th>Promotor</th><th><?= e(ucfirst(str_replace('_', ' ', $column))) ?></th><th>Diimpor</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($rows, 0, 100) as $row): ?>
                            <tr class="border-t">
                                <?php foreach ($row as $value): ?><td><?= e($value) ?></td><?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <?php if ($rows !== null && ($page > 1 || count($rows) > 100)): ?>
                    <nav class="flex gap-4" aria-label="Halaman laporan">
                        <?php if ($page > 1): ?>
                            <a class="underline" href="<?= e($base . '&bulan=' . $month . '&halaman=' . ($page - 1)) ?>">Sebelumnya</a>
                        <?php endif; ?>
                        <span>Halaman <?= $page ?></span>
                        <?php if (count($rows) > 100 && $page < 1000000): ?>
                            <a class="underline" href="<?= e($base . '&bulan=' . $month . '&halaman=' . ($page + 1)) ?>">Berikutnya</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
            <section class="rounded-xl border p-5 bg-white dark:bg-gray-900 space-y-2">
                <h2 class="font-bold">Akun resmi</h2>
                <p>Pengaturan akun cabang dan password promotor menggunakan sesi App Cabang.</p>
                <div class="flex flex-wrap gap-4">
                    <a class="underline" href="<?= e($source . '/EditPass') ?>" target="_blank" rel="noopener noreferrer">Ubah password akun Cabang</a>
                    <a class="underline" href="<?= e($source . '/UserPro') ?>" target="_blank" rel="noopener noreferrer">Pengelolaan password promotor</a>
                </div>
            </section>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
