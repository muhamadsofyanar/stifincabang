<?php
require_once __DIR__ . '/../../bootstrap.php';
requireAdmin();
header('Cache-Control: no-store');
$pageTitle = $topbarTitle = 'Stok Voucher Tes';
$productId = stifinVoucherProductId();
$stock = $assigned = $pending = null;
$product = false;
$ready = false;
try {
    if ($productId > 0) {
        $db = getDB();
        $stmt = $db->prepare('SELECT name FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetchColumn();
        $stock = $db->query('SELECT COUNT(*) FROM stifin_voucher_stock WHERE queue_id IS NULL')->fetchColumn();
        $assigned = $db->query('SELECT COUNT(*) FROM stifin_voucher_stock WHERE queue_id IS NOT NULL')->fetchColumn();
        $pending = $db->query("SELECT COUNT(*) FROM stifin_voucher_queue q
            JOIN orders o ON o.id = q.order_id AND o.user_id = q.user_id
            WHERE q.fulfilled_at IS NULL AND o.status = 'paid'")->fetchColumn();
        $ready = $product !== false;
    }
} catch (Throwable $error) {
    error_log('[stifin-voucher] Stok tidak tersedia.');
}
generateCSRF();
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased">
<div class="flex h-screen overflow-hidden">
  <?php include __DIR__ . '/../../partials/sidebar-admin.php'; ?>
  <div class="flex-1 flex flex-col overflow-hidden lg:ml-60">
    <?php include __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-5 lg:p-6">
      <?php include __DIR__ . '/../../partials/flash-message.php'; ?>
      <div class="max-w-4xl space-y-4 text-gray-900 dark:text-white">
        <h1 class="text-2xl font-bold">Stok Voucher Tes</h1>
        <?php if (!$ready): ?>
          <p role="alert">Pastikan konfigurasi produk voucher dan migrasi database sudah dipasang.</p>
        <?php else: ?>
          <p>Produk checkout <strong><?= e((string) $product) ?></strong> · ID <?= $productId ?></p>
          <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white dark:bg-gray-900 border rounded-lg p-4"><dt>Tersedia</dt><dd class="text-xl font-bold"><?= (int) $stock ?></dd></div>
            <div class="bg-white dark:bg-gray-900 border rounded-lg p-4"><dt>Sudah dialokasikan</dt><dd class="text-xl font-bold"><?= (int) $assigned ?></dd></div>
            <div class="bg-white dark:bg-gray-900 border rounded-lg p-4"><dt>Pesanan menunggu</dt><dd class="text-xl font-bold"><?= (int) $pending ?></dd></div>
          </dl>
          <form method="post" action="<?= e(APP_URL) ?>/actions/admin/stifin-vouchers" class="bg-white dark:bg-gray-900 border rounded-xl p-5 space-y-3">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="import">
            <label class="block font-semibold" for="codes">Impor kode resmi cabang</label>
            <p id="codes-help">Satu kode per baris, maksimal 500 kode per impor. Kode yang sudah ada dilewati.</p>
            <textarea class="block w-full p-3 border rounded-lg dark:bg-gray-950 font-mono" id="codes" name="codes" rows="7" maxlength="65000" aria-describedby="codes-help" spellcheck="false" required></textarea>
            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white dark:bg-gray-700" type="submit">Simpan dan distribusikan</button>
          </form>
          <form method="post" action="<?= e(APP_URL) ?>/actions/admin/stifin-vouchers">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <button class="px-4 py-2 border rounded-lg" name="action" value="distribute" type="submit">Periksa antrean dan distribusikan</button>
          </form>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
