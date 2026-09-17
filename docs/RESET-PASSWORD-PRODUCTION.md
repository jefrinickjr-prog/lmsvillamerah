# Konfigurasi Lupa Password Production

Fitur lupa password menggunakan password broker Laravel. Aplikasi membuat token
di tabel `password_reset_tokens`, lalu mengirim link ke email pengguna.

## 1. Isi `.env` di server

Salin `.env.example` menjadi `.env` jika file tersebut belum ada. Jangan commit
file `.env` dan jangan menaruh password SMTP di repository.

Contoh menggunakan Gmail atau Google Workspace:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms.example.com

MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akun-pengirim@example.com
MAIL_PASSWORD=16-karakter-app-password
MAIL_FROM_ADDRESS=akun-pengirim@example.com
MAIL_FROM_NAME="E-Learning Villa Merah"
```

`MAIL_PASSWORD` harus berupa **App Password**, bukan password login Gmail
biasa. App Password hanya tersedia jika verifikasi dua langkah aktif. Untuk
provider SMTP lain, gunakan host, port, dan scheme TLS dari provider tersebut.

`APP_URL` harus berupa URL publik yang dapat dibuka pengguna, termasuk
`https://`. Nilai ini dipakai untuk membuat link reset di dalam email.

## 2. Siapkan aplikasi setelah upload

Jalankan dari folder project di server:

```bash
php artisan key:generate --show
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Jalankan `key:generate --show` hanya saat `APP_KEY` belum tersedia, lalu simpan
hasilnya ke `.env` sebagai `APP_KEY`. Jangan mengganti `APP_KEY` pada aplikasi
yang sudah memiliki data terenkripsi.

Pastikan migrasi berikut sudah berstatus `Ran`:

```bash
php artisan migrate:status
```

## 3. Verifikasi pengiriman email

1. Buka `https://lms.example.com/forgot-password`.
2. Masukkan email pengguna yang sudah ada di tabel `users`.
3. Klik **Kirim link reset**.
4. Periksa inbox, spam, dan log aplikasi jika email belum diterima.

Jika gagal, periksa log:

```bash
tail -n 100 storage/logs/laravel.log
```

Pastikan folder log dapat ditulis oleh user PHP-FPM/web server. Setelah setiap
perubahan `.env` jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 4. Queue worker

Pengiriman reset password Laravel berjalan langsung ketika form dikirim. Queue
worker tidak diperlukan khusus untuk link reset, tetapi tetap wajib dijalankan
untuk job aplikasi lain karena project menggunakan `QUEUE_CONNECTION=database`.

Contoh Supervisor:

```ini
[program:elearning-queue]
command=php /home/USER/domains/lms.example.com/public_html/artisan queue:work database --sleep=3 --tries=3 --timeout=90
directory=/home/USER/domains/lms.example.com/public_html
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=USER
redirect_stderr=true
stdout_logfile=/home/USER/domains/lms.example.com/logs/queue.log
stopwaitsecs=3600
```

Setelah deploy atau perubahan kode:

```bash
php artisan queue:restart
```

## Catatan keamanan

- Gunakan HTTPS pada domain production.
- Jangan mengaktifkan `APP_DEBUG=true` di server publik.
- Jangan memasukkan `MAIL_PASSWORD`, `APP_KEY`, atau file `.env` ke Git.
- Gunakan alamat `MAIL_FROM_ADDRESS` yang diizinkan oleh provider SMTP.