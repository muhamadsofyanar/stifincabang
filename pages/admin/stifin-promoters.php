<?php
require_once __DIR__ . '/../../bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../../helpers/stifin-promoters.php';

header('Cache-Control: no-store');
$pageTitle = $topbarTitle = 'Promotor Cabang';
$branchCode = stifinPromoterBranchCode();
$promoters = [];
$directoryError = '';
try {
    $promoters = stifinBranchPromoters($branchCode);
} catch (RuntimeException $error) {
    $directoryError = $error->getMessage();
}
include __DIR__ . '/../../partials/head.php';
?>
<body class="bg-gray-50 dark:bg-gray-950 antialiased">
<div class="flex h-screen overflow-hidden">
  <?php include __DIR__ . '/../../partials/sidebar-admin.php'; ?>
  <div class="flex-1 flex flex-col overflow-hidden lg:ml-60">
    <?php include __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-5 lg:p-6">
      <div class="max-w-5xl space-y-4 text-gray-900 dark:text-white">
        <h1 class="text-2xl font-bold">Promotor Cabang</h1>
        <?php if ($branchCode !== ''): ?>
          <p>Kode cabang <strong><?= e($branchCode) ?></strong></p>
        <?php endif; ?>
        <?php if ($directoryError !== ''): ?>
          <p role="alert"><?= e($directoryError) ?></p>
        <?php elseif ($promoters === []): ?>
          <p>API STIFIn belum mengembalikan promotor untuk cabang ini.</p>
        <?php else: ?>
          <p><?= count($promoters) ?> promotor dari direktori STIFIn</p>
          <div class="overflow-x-auto rounded-xl border bg-white dark:bg-gray-900">
            <table class="w-full text-left text-sm">
              <thead>
                <tr>
                  <th scope="col" class="p-3">Nama</th>
                  <th scope="col" class="p-3">Kode promotor</th>
                  <th scope="col" class="p-3">Area</th>
                  <th scope="col" class="p-3">Provinsi</th>
                  <th scope="col" class="p-3">Status di STIFIn</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($promoters as $promoter): ?>
                  <tr class="border-t">
                    <td class="p-3"><?= e($promoter['name']) ?></td>
                    <td class="p-3"><?= e($promoter['code']) ?></td>
                    <td class="p-3"><?= e($promoter['area'] ?: '—') ?></td>
                    <td class="p-3"><?= e($promoter['province'] ?: '—') ?></td>
                    <td class="p-3"><?= $promoter['active'] === null ? '—' : ($promoter['active'] ? 'Aktif' : 'Nonaktif') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
</body>
</html>
