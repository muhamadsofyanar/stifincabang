<?php
require_once __DIR__ . '/../../bootstrap.php';
requireAdmin();

$periods = [
    'all' => 'Semua Waktu', 'today' => 'Hari Ini', 'yesterday' => 'Kemarin',
    7 => '7 Hari', 30 => '30 Hari', 90 => '90 Hari', 365 => '1 Tahun',
];
$period = $_GET['period'] ?? 'all';
if (!is_string($period) || !array_key_exists($period, $periods)) {
    $period = 'all';
}

$paidDate = 'COALESCE(o.paid_at, o.created_at)';
$dateFilter = match ($period) {
    'all' => '',
    'today' => "AND $paidDate >= CURDATE() AND $paidDate < CURDATE() + INTERVAL 1 DAY",
    'yesterday' => "AND $paidDate >= CURDATE() - INTERVAL 1 DAY AND $paidDate < CURDATE()",
    default => "AND $paidDate >= CURDATE() - INTERVAL " . ((int) $period - 1) . " DAY
                AND $paidDate < CURDATE() + INTERVAL 1 DAY",
};
$paidFilter = "o.status = 'paid' $dateFilter";

$loadError = false;
try {
    $pdo = getDB();
    $sources = $pdo->query(
        "SELECT o.utm_source AS source, COUNT(*) AS sales, SUM(o.total) AS revenue
         FROM orders o
         WHERE $paidFilter
         GROUP BY o.utm_source
         ORDER BY revenue DESC, sales DESC, source ASC"
    )->fetchAll();
    $topProducts = $pdo->query(
        "SELECT p.name, SUM(oi.quantity) AS units
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         WHERE $paidFilter AND o.order_stage <> 'settlement'
         GROUP BY p.id, p.name
         ORDER BY units DESC, p.name ASC, p.id ASC
         LIMIT 10"
    )->fetchAll();
} catch (Throwable $e) {
    error_log('Data Akuisisi: ' . $e->getMessage());
    $loadError = true;
    $sources = $topProducts = [];
}

$totalRevenue = array_sum(array_column($sources, 'revenue'));
$totalSales = array_sum(array_column($sources, 'sales'));
$colors = ['#2563eb', '#0f766e', '#7c3aed', '#c2410c', '#be123c', '#4338ca', '#4d7c0f', '#0369a1'];
$segments = [];
$end = 0.0;
foreach ($sources as $index => &$source) {
    $source['color'] = $colors[$index % count($colors)];
    $source['share'] = $totalRevenue > 0 ? 100 * (float) $source['revenue'] / $totalRevenue : 0;
    if ($source['share'] > 0) {
        $segments[] = sprintf('%s %.8F%% %.8F%%', $source['color'], $end, $end + $source['share']);
        $end += $source['share'];
    }
}
unset($source);

$pageTitle = 'Data Akuisisi — ' . getSetting('app_name', 'STIFIn Mulia');
$topbarTitle = 'Data Akuisisi';
?>
<?php include __DIR__ . '/../../partials/head.php'; ?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased">
<?php include __DIR__ . '/../../partials/sidebar-admin.php'; ?>
<div class="lg:pl-60 flex flex-col min-h-screen">
  <?php include __DIR__ . '/../../partials/topbar.php'; ?>
  <main class="flex-1 p-4 sm:p-6 space-y-6 max-w-6xl mx-auto w-full">
    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Data Akuisisi</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tambahkan <code>utm_source=facebook</code> atau <code>utm_source=whatsapp</code> pada URL promosi.</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Periode mengikuti tanggal pembayaran.</p>
      </div>
      <form method="get" action="<?= e(APP_URL) ?>/admin/acquisition" class="flex flex-wrap items-center gap-2">
        <label for="acquisition-period" class="text-sm text-gray-600 dark:text-gray-300">Periode</label>
        <select name="period" id="acquisition-period" class="px-3 py-2 rounded-lg bg-white dark:bg-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 text-sm">
          <?php foreach ($periods as $value => $label): ?>
            <option value="<?= e((string) $value) ?>" <?= (string) $value === $period ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="px-3 py-2 rounded-lg bg-indigo-700 text-white text-sm" type="submit">Terapkan</button>
      </form>
    </header>

    <?php if ($loadError): ?>
      <p role="alert" class="rounded-lg bg-red-50 dark:bg-red-950 p-4 text-sm text-red-800 dark:text-red-200">Data Akuisisi gagal dimuat. Periksa koneksi dan migrasi basis data.</p>
    <?php else: ?>
      <dl class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4">
          <dt class="text-sm text-gray-500 dark:text-gray-400">Order lunas</dt>
          <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($totalSales, 0, ',', '.') ?></dd>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4">
          <dt class="text-sm text-gray-500 dark:text-gray-400">Total omzet</dt>
          <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= e(formatRupiah((float) $totalRevenue)) ?></dd>
        </div>
      </dl>
      <section class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 sm:p-5">
        <h2 class="font-semibold text-gray-900 dark:text-white">Penjualan dan omzet per sumber</h2>
        <?php if (!$sources): ?>
          <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Belum ada order lunas pada periode ini.</p>
        <?php else: ?>
          <div class="mt-4 grid items-start gap-6 md:grid-cols-[12rem_minmax(0,1fr)]">
            <div class="flex flex-col items-center gap-2 text-center">
              <div class="relative w-36 h-36 rounded-full bg-gray-200 dark:bg-gray-800"
                   style="<?= $segments ? 'background:conic-gradient(' . e(implode(', ', $segments)) . ')' : '' ?>" aria-hidden="true">
                <div class="absolute inset-7 rounded-full bg-white dark:bg-gray-900"></div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400"><?= $segments ? 'Pembagian omzet' : 'Belum ada omzet' ?></p>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                <thead class="border-b border-gray-200 dark:border-gray-700">
                  <tr><th scope="col" class="text-left py-2 pr-3">Sumber</th><th scope="col" class="text-right px-2">Order lunas</th><th scope="col" class="text-right px-2">Omzet</th><th scope="col" class="text-right pl-2">Porsi omzet</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                  <?php foreach ($sources as $source): ?>
                    <tr>
                      <th scope="row" class="py-2 pr-3 text-left font-medium break-all"><span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" style="background:<?= e($source['color']) ?>" aria-hidden="true"></span><?= e($source['source'] ?? 'Tanpa sumber UTM') ?></th>
                      <td class="text-right px-2"><?= number_format((int) $source['sales'], 0, ',', '.') ?></td>
                      <td class="text-right px-2 whitespace-nowrap"><?= e(formatRupiah((float) $source['revenue'])) ?></td>
                      <td class="text-right pl-2"><?= number_format($source['share'], 1, ',', '.') ?>%</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <section class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 sm:p-5">
        <h2 class="font-semibold text-gray-900 dark:text-white">Top 10 produk terlaris</h2>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Berdasarkan kuantitas item order lunas. Unit booking dihitung saat DP lunas.</p>
        <?php if (!$topProducts): ?>
          <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Belum ada produk terjual pada periode ini.</p>
        <?php else: ?>
          <table class="mt-3 w-full text-sm text-gray-700 dark:text-gray-300">
            <thead class="border-b border-gray-200 dark:border-gray-700">
              <tr><th scope="col" class="text-left py-2">Produk</th><th scope="col" class="text-right">Unit</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
              <?php foreach ($topProducts as $product): ?>
                <tr><th scope="row" class="text-left py-2 pr-3 font-medium"><?= e($product['name']) ?></th><td class="text-right"><?= number_format((int) $product['units'], 0, ',', '.') ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
