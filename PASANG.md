# Perbaikan tampilan STIFIn Cabang

Patch dibuat terhadap commit d519778. Ekstrak stifin-tampilan.patch ke folder repo stifincabang di Windows. Buka Git Bash dari folder tersebut, lalu jalankan:

```bash
git pull --ff-only origin main &&
git apply --check stifin-tampilan.patch &&
git apply stifin-tampilan.patch &&
git add helpers/landing-builder.php pages/admin/stifin-promoters.php pages/public/products.php scripts/setup-stifin-branch.php &&
git commit -m "Rapikan katalog ujian, Canvas, dan promotor" &&
git push origin main
```

Di Coolify, deploy commit baru jika auto-deploy belum berjalan. Perubahan berlaku pada lima Canvas ujian yang sudah dibuat. Tidak perlu menjalankan skrip setup lagi.

Periksa katalog produk, halaman /ujian-wst-health/, dan menu Promotor Cabang. Katalog memakai sampul tipografi bila poster belum ada. Badge Terlaris hanya tampil bila ada penjualan lunas. Canvas ujian mendapat lebar desktop dan menyembunyikan teks contoh bawaan yang masih persis sama. Tombol impor muncul di atas tabel dengan warna brand. Uji tautan checkout dengan produk yang aktif.
