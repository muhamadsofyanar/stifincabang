# Pembaruan STIFIn Cabang

Patch disiapkan pada commit `779615fc08bd934b3ebe320b05f91f7e370d951f` repositori `muhamadsofyanar/stifincabang`.

## Pasang

1. Cadangkan database cabang. Di checkout repositori, jalankan pemeriksaan dan terapkan patch.

   ```bash
   git apply --check /path/to/stifin-cabang.patch
   git apply /path/to/stifin-cabang.patch
   ```

2. Commit empat file yang berubah, push melalui akun yang memiliki akses tulis, kemudian deploy ulang di Coolify.
3. Dari terminal **container app** yang baru, jalankan:

   ```bash
   php /var/www/html/scripts/setup-stifin-branch.php
   ```

   Perintah menyiapkan tabel pemetaan promotor dan lima draf produk beserta Canvas. Jika slug produk atau Canvas sudah dipakai, pasangan itu dilewati. Menjalankan ulang perintah aman untuk pasangan yang sudah ada.

## Promotor dan voucher

- Buka **Promotor Cabang**. Kolom email menampilkan alamat yang diterima dari API.
- Centang promotor aktif dengan email valid, lalu pilih **Impor akun terpilih**. Email duplikat dan akun lokal yang sudah terdaftar dilewati. Akun baru memakai peran pengguna. Promotor membuat password melalui **Lupa Password** setelah pengiriman email diuji.
- Pilih **Cek saldo** pada baris promotor untuk membaca saldo Voucher dan Free dari STIFIn pusat. Pemeriksaan ini hanya membaca saldo. Fitur stok kode voucher lokal tetap memakai menu dan konfigurasi yang sudah ada.
- Jika API memerlukan autentikasi, masukkan header dan nilainya di konfigurasi runtime layanan app pada Coolify.

## Produk ujian

| Draf produk | Harga ujian per peserta | Canvas | Checkout internal |
|---|---:|---|---|
| Workshop STIFIn Level 1 | Rp150.000 | `/ujian-wsl-1` | `/order/wsl-1` |
| Workshop STIFIn Level 2 | Rp350.000 | `/ujian-wsl-2` | `/order/wsl-2` |
| Workshop STIFIn Tematik Health | Rp300.000 | `/ujian-wst-health` | `/order/wst-health` |
| Workshop STIFIn Learning | Rp300.000 | `/ujian-wst-learning` | `/order/wst-learning` |
| Workshop STIFIn Tahfizh | Rp300.000 | `/ujian-wst-tahfizh` | `/order/wst-tahfizh` |

Canvas memakai Visual Builder dan tombol yang mengarah ke form checkout publik bawaan. Harga normal flyer, jadwal, instruktur, dan poster dapat diisi setelah datanya tersedia. File sumber memuat WSL Level 1 dan 2 serta tiga WST. WSL Level 3 tidak ada di arsip.

Periksa draf di **Produk** dan pratinjau di **Canvas**. Setelah konten siap dan metode pembayaran sudah diuji, aktifkan produk dan Canvas. Buka halaman publik dan cek tombol menuju checkout, nama produk, harga, dan pilihan pembayaran.
