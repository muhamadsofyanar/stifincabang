# Data Akuisisi STIFIn

## Pemasangan

Untuk instalasi yang sudah berjalan, cadangkan basis data. Jalankan
`migrations/stifin-acquisition.sql` pada basis data aplikasi sebelum mengaktifkan
berkas PHP dari paket ini. Jangan impor ulang `stifin-install.sql` pada basis data
aktif. Instalasi baru sudah memperoleh skema ini dari pemasang.

## Atribusi penjualan

Tambahkan `?utm_source=facebook` ke URL promosi. Jika URL sudah memiliki parameter,
gunakan `&utm_source=whatsapp`. Nama sumber menerima hingga 100 karakter berupa
huruf ASCII, angka, titik, garis bawah, atau tanda hubung. Karakter pertama harus
huruf atau angka. Aplikasi menyimpan nama dalam huruf kecil.

Checkout menggunakan sumber bertanda valid terakhir dalam sesi pembeli.
Navigasi tanpa UTM dalam sesi yang sama mempertahankan sumber tersebut. Order
upsell dan pelunasan booking mewarisi sumber order asal. Order tanpa sumber,
termasuk transaksi lama, muncul sebagai **Tanpa sumber UTM** di laporan.

Buka **Data Akuisisi** di bawah **Analitik Penjualan** (Funnel Analytics).
Periode berlaku untuk ringkasan, grafik, dan Top 10. Laporan menjumlahkan total
order berstatus lunas pada tanggal pembayaran. Jika tanggal pembayaran kosong,
laporan memakai tanggal pembuatan order. Untuk booking, DP dan pelunasan masing-masing
masuk ke omzet serta jumlah order lunas. Peringkat produk menghitung kuantitas
item saat DP lunas, tanpa menghitung unit yang sama lagi saat pelunasan.

## Canvas untuk akses pembeli

Di menu **Canvas**, buat **Halaman Akses Produk** dengan Visual Builder atau
Embed HTML dan aktifkan halaman. Di editor produk, pilih **Canvas** pada
**Mode Akses Konten**, lalu pilih halaman yang akan dilihat pembeli. Pembeli
dapat membukanya dari area member setelah mendapat hak akses. Jika halaman akses
berpassword diaktifkan untuk produk tersebut, halaman juga tersedia melalui
alur password reseller.

## Pemeriksaan setelah pemasangan

1. Buka tautan promosi ber-UTM dalam sesi pembeli baru, lanjutkan ke checkout,
   lalu periksa kolom **Sumber** di **Transaksi**.
2. Lunasi order uji dan periksa sumber, jumlah order, omzet, dan peringkat pada
   periode yang memuat tanggal pembayaran. Ulangi untuk sumber kedua.
3. Uji Canvas melalui akun yang memiliki hak akses dan akun yang belum memiliki
   hak akses. Verifikasi juga akses langsung ke slug Canvas tanpa login, serta
   alur password reseller jika dipakai.
