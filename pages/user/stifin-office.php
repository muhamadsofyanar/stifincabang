<?php
require_once __DIR__ . '/../../bootstrap.php';
requireLogin();
require_once __DIR__ . '/../../helpers/stifin-promoters.php';
header('Cache-Control: no-store');
$branch = stifinPromoterBranchCode();
$code = stifinLinkedPromoterCode(getDB(), (int)$_SESSION['user_id']);
if ($code === null) { redirect('/dashboard'); }
$pageTitle = $topbarTitle = 'STIFIn Saya';
$states = ['registered'=>'Terdaftar','scheduled'=>'Terjadwal','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$rows = []; $error = '';
try {
    $stmt = getDB()->prepare('SELECT participant_name,scheduled_at,status FROM stifin_test_registrations WHERE branch_code=? AND promoter_code=? ORDER BY id DESC LIMIT 200');
    $stmt->execute([$branch,$code]); $rows = $stmt->fetchAll();
} catch (Throwable $e) { $error = 'Catatan tes belum tersedia.'; }
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased"><div class="flex h-screen overflow-hidden"><?php include __DIR__ . '/../../partials/sidebar-user.php'; ?><div class="flex-1 flex flex-col overflow-hidden lg:ml-60"><?php include __DIR__ . '/../../partials/topbar.php'; ?><main class="flex-1 overflow-y-auto p-5 lg:p-6 space-y-4">
<section class="rounded-xl p-5 text-white" style="background:#123d30"><h1 class="text-2xl font-bold">STIFIn Saya</h1><p><?= e($code) ?> · <?= e($branch) ?></p></section>
<div class="flex flex-wrap gap-3"><a class="rounded border p-3" href="<?= e(APP_URL) ?>/member/voucher-tes?cek_saldo=1">Saldo &amp; riwayat voucher</a><a class="rounded border p-3" href="<?= e(APP_URL) ?>/transactions">Transaksi saya</a><a class="rounded border p-3" href="<?= e(APP_URL) ?>/kajian">Kajian</a><a class="rounded border p-3" href="<?= e(APP_URL) ?>/katalog">Workshop</a></div>
<h2 class="font-bold">Peserta dan jadwal saya</h2><p>Catatan lokal cabang, bukan hasil tes resmi pusat.</p><?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php else: ?><div class="rounded-xl border overflow-x-auto bg-white dark:bg-gray-900"><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Peserta</th><th>Jadwal</th><th>Status</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr class="border-t"><td class="p-3"><?= e($row['participant_name']) ?></td><td><?= e($row['scheduled_at'] ?? '—') ?></td><td><?= e($states[$row['status']] ?? '—') ?></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="3" class="p-3">Belum ada catatan.</td></tr><?php endif; ?></tbody></table></div><?php endif; ?>
</main></div></div><?php include __DIR__ . '/../../partials/footer.php'; ?>
