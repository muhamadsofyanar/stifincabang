<?php
require_once __DIR__ . '/../../bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../../helpers/stifin-promoters.php';
header('Cache-Control: no-store');
$pageTitle = $topbarTitle = 'Kantor Virtual Cabang';
$branch = stifinPromoterBranchCode();
$cards = [
    ['Promotor tertaut', '/admin/promotor-cabang', 'SELECT COUNT(*) FROM stifin_promoter_accounts WHERE branch_code = ?', [$branch]],
    ['Pendaftaran tes', '/admin/peserta-tes', 'SELECT COUNT(*) FROM stifin_test_registrations WHERE branch_code = ? AND status = ?', [$branch, 'registered']],
    ['Tes terjadwal', '/admin/peserta-tes', 'SELECT COUNT(*) FROM stifin_test_registrations WHERE branch_code = ? AND status = ?', [$branch, 'scheduled']],
    ['Tes selesai (catatan lokal)', '/admin/peserta-tes', 'SELECT COUNT(*) FROM stifin_test_registrations WHERE branch_code = ? AND status = ?', [$branch, 'completed']],
    ['Stok voucher lokal', '/admin/voucher-tes', 'SELECT COUNT(*) FROM stifin_voucher_stock WHERE queue_id IS NULL', []],
    ['Transaksi lunas (seluruh produk)', '/admin/transactions', "SELECT COUNT(*) FROM orders WHERE status = 'paid'", []],
    ['Kajian terbit', '/admin/kajian', 'SELECT COUNT(*) FROM darmutiara_kajian WHERE published = 1', []],
];
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased"><div class="flex h-screen overflow-hidden">
<?php include __DIR__ . '/../../partials/sidebar-admin.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden lg:ml-60"><?php include __DIR__ . '/../../partials/topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-5 lg:p-6 space-y-5">
<section class="rounded-xl p-6 text-white" style="background:#123d30"><h1 class="text-2xl font-bold">Kantor Virtual Cabang</h1><p class="mt-2">Kode cabang: <?= e($branch ?: 'Belum dikonfigurasi') ?></p><p>Ringkasan operasional dari database STIFIn Mulia.</p></section>
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
<?php foreach ($cards as [$label,$url,$sql,$params]): $count = null; try { if (!$params || $branch !== '') { $stmt = getDB()->prepare($sql); $stmt->execute($params); $count = (int)$stmt->fetchColumn(); } } catch (Throwable $error) { error_log('STIFIn office metric unavailable'); } ?>
<a class="rounded-xl border bg-white dark:bg-gray-900 p-5" href="<?= e(APP_URL . $url) ?>"><p class="text-sm"><?= e($label) ?></p><p class="text-2xl font-bold mt-2"><?= $count === null ? 'Belum tersedia' : number_format($count,0,',','.') ?></p><p class="text-sm mt-2">Buka modul →</p></a>
<?php endforeach; ?>
</div>
<section class="rounded-xl border bg-white dark:bg-gray-900 p-5 space-y-3"><h2 class="font-bold">Akses operasional</h2><p>Data peserta di kantor virtual adalah catatan lokal, bukan hasil resmi STIFIn pusat.</p><div class="flex flex-wrap gap-4 underline"><a href="<?= e(APP_URL) ?>/admin/products">Workshop &amp; produk</a><a href="<?= e(APP_URL) ?>/admin/users">Pengguna</a><a href="<?= e(APP_URL) ?>/admin/transactions">Keuangan dan transaksi</a></div></section>
</main></div></div><?php include __DIR__ . '/../../partials/footer.php'; ?>
