<?php
require_once __DIR__ . '/../../bootstrap.php';
requireLogin();
header('Cache-Control: no-store');

if (stifinVoucherProductId() === 0) {
    redirect('/dashboard');
}
$pageTitle = $topbarTitle = 'Voucher Tes';
$orders = [];
$unavailable = false;
try {
    $stmt = getDB()->prepare("SELECT o.order_number, q.quantity, q.fulfilled_at, s.code
        FROM stifin_voucher_queue q
        JOIN orders o ON o.id = q.order_id AND o.user_id = q.user_id
        LEFT JOIN stifin_voucher_stock s ON s.queue_id = q.id
        WHERE q.user_id = ? AND o.status = 'paid' ORDER BY q.id DESC, s.id");
    $stmt->execute([(int) $_SESSION['user_id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $number = $row['order_number'];
        $orders[$number]['quantity'] = $row['quantity'];
        $orders[$number]['codes'] ??= [];
        if ($row['fulfilled_at'] !== null && $row['code'] !== null) {
            $orders[$number]['codes'][] = $row['code'];
        }
    }
} catch (Throwable $error) {
    $unavailable = true;
    error_log('[stifin-voucher] Riwayat member tidak tersedia.');
}
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased">
<div class="flex h-screen overflow-hidden">
  <?php include __DIR__ . '/../../partials/sidebar-user.php'; ?>
  <div class="flex-1 flex flex-col overflow-hidden lg:ml-60">
    <?php include __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-5 lg:p-6">
      <div class="max-w-4xl space-y-4">
        <div class="p-5 rounded-xl bg-gray-900 text-white">
          <h1 class="text-2xl font-bold">Voucher Tes STIFIn</h1>
          <p class="mt-2">Lihat kode voucher dari pesanan yang sudah lunas.</p>
        </div>
        <?php if ($unavailable): ?>
          <p role="alert">Data voucher sementara tidak tersedia. Silakan coba lagi.</p>
        <?php elseif (!$orders): ?>
          <p>Belum ada pesanan voucher yang lunas.</p>
          <a class="underline" href="<?= e(APP_URL) ?>/katalog">Buka katalog</a>
        <?php endif; ?>
        <?php foreach ($orders as $number => $order): ?>
          <section class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">
            <h2 class="font-semibold">Pesanan <?= e((string) $number) ?></h2>
            <p class="mt-1"><?= (int) $order['quantity'] ?> voucher</p>
            <?php if (!$order['codes']): ?>
              <p class="mt-2">Lunas · Menunggu alokasi voucher.</p>
            <?php else: ?>
              <ul class="mt-3 space-y-2">
                <?php foreach ($order['codes'] as $code): ?>
                  <li class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800"><code class="break-all"><?= e($code) ?></code></li>
                <?php endforeach; ?>
              </ul>
              <p class="mt-3 text-sm">Gunakan voucher sesuai prosedur tes resmi cabang.</p>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
