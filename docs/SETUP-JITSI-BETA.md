# Setup Jitsi as a Service untuk Fase Beta

Fase Beta menggunakan Jitsi as a Service (8x8/JaaS) melalui IFrame API. Jadwal,
izin masuk, pemetaan program/kelas/cabang, dan batas peserta tetap dikelola
Laravel. Laravel membuat JWT RS256 sementara untuk setiap akun yang masuk.

## Environment

Tambahkan pada environment server:

```env
JITSI_DOMAIN=8x8.vc
JITSI_ROOM_PREFIX=VillaMerahBeta
JITSI_APP_ID=vpaas-magic-cookie-xxxxxxxx
JITSI_KEY_ID=vpaas-magic-cookie-xxxxxxxx/yyyyyyyy
JITSI_PRIVATE_KEY_PATH=/home/USER/secure/jaas_private.key
JITSI_PRIVATE_KEY_BASE64=
JITSI_MAX_TOKEN_HOURS=6
```

Kemudian:

```bash
php artisan optimize:clear
php artisan config:cache
```

Gunakan salah satu penyimpanan private key:

- `JITSI_PRIVATE_KEY_PATH` (direkomendasikan): path absolut ke file private key
  di luar `public_html`; atau
- `JITSI_PRIVATE_KEY_BASE64`: isi private key PEM yang telah di-Base64 menjadi
  satu baris.

Jangan mengisi keduanya dan jangan memasukkan private key ke Git. Domain LMS
wajib memakai HTTPS agar browser mengizinkan kamera, mikrofon, dan berbagi layar.

## Keamanan akses Beta

- Nama ruang dibuat otomatis dari ID sesi dan HMAC `APP_KEY`.
- Ruang berada dalam namespace `JITSI_APP_ID`.
- Token mengikuti waktu selesai sesi ditambah 30 menit, dengan batas maksimum
  `JITSI_MAX_TOKEN_HOURS`.
- Host menerima claim moderator; siswa menerima claim peserta.
- Nama ruang tidak ditampilkan sebagai input dan tidak perlu dibagikan manual.
- Halaman ruang tetap dilindungi login serta kecocokan program, kelas, cabang,
  mode belajar, dan status peserta sesi.
- Kamera dan mikrofon dimulai dalam keadaan mati.
- Jangan membagikan alamat/nama konferensi Jitsi di luar LMS.

Jika konfigurasi JaaS belum lengkap, aplikasi kembali ke `meet.jit.si` agar
halaman tidak rusak, tetapi mode tersebut hanya sesuai untuk uji singkat.
