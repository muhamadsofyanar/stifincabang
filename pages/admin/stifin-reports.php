<?php
require_once __DIR__ . '/../../bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../../helpers/stifin-promoters.php';
header('Cache-Control: no-store');
$pageTitle = $topbarTitle = 'Laporan Cabang';
$branch = stifinPromoterBranchCode();
$month = is_string($_GET['month'] ?? null) ? $_GET['month'] : date('Y-m');
$start = DateTimeImmutable::createFromFormat('!Y-m', $month);
if (!$start || $start->format('Y-m') !== $month) { $start = new DateTimeImmutable('first day of this month midnight'); $month = $start->format('Y-m'); }
$range = [$start->format('Y-m-d'),$start->modify('+1 month')->format('Y-m-d')];
$participants = $orders = null;
try { if ($branch !== '') { $q = getDB()->prepare('SELECT status,COUNT(*) AS total FROM stifin_test_registrations WHERE branch_code=? AND created_at>=? AND created_at<? GROUP BY status'); $q->execute([$branch,...$range]); $participants = $q->fetchAll(); } } catch (Throwable $e) { error_log('STIFIn participant report unavailable'); }
try { $q=getDB()->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(total),0) AS revenue FROM orders WHERE status='paid' AND paid_at>=? AND paid_at<?"); $q->execute($range); $orders=$q->fetch(); } catch (Throwable $e) { error_log('STIFIn order report unavailable'); }
$states = ['registered'=>'Terdaftar','scheduled'=>'Terjadwal','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased"><div class="flex h-screen overflow-hidden"><?php include __DIR__ . '/../../partials/sidebar-admin.php'; ?><div class="flex-1 flex flex-col overflow-hidden lg:ml-60"><?php include __DIR__ . '/../../partials/topbar.php'; ?><main class="flex-1 overflow-y-auto p-5 lg:p-6 space-y-4"><h1 class="text-2xl font-bold">Laporan Cabang</h1><form method="get" class="flex flex-wrap items-end gap-3"><label>Periode<input type="month" name="month" value="<?= e($month) ?>" class="block border rounded p-2 dark:bg-gray-950"></label><button class="border rounded p-2">Tampilkan</button></form>
<section class="rounded-xl border p-5 bg-white dark:bg-gray-900"><h2 class="font-bold">Pendaftaran peserta lokal · <?= e($branch ?: 'Belum dikonfigurasi') ?></h2><?php if ($participants === null): ?><p>Data belum tersedia.</p><?php else: $counts=array_column($participants,'total','status'); ?><div class="grid grid-cols-2 gap-3 mt-3"><?php foreach ($states as $key=>$label): ?><div class="border rounded p-3"><p><?= e($label) ?></p><strong><?= (int)($counts[$key] ?? 0) ?></strong></div><?php endforeach; ?></div><?php endif; ?></section>
<section class="rounded-xl border p-5 bg-white dark:bg-gray-900"><h2 class="font-bold">Pembayaran lunas STIFIn Mulia (semua produk)</h2><?php if ($orders): ?><p><?= (int)$orders['total'] ?> pembayaran · Rp<?= number_format((float)$orders['revenue'],0,',','.') ?></p><?php else: ?><p>Data belum tersedia.</p><?php endif; ?><p class="text-sm">Berdasarkan tanggal lunas; bukan saldo bank atau laporan pusat.</p><a class="underline" href="<?= e(APP_URL) ?>/admin/transactions">Detail transaksi</a></section></main></div></div><?php include __DIR__ . '/../../partials/footer.php'; ?>
