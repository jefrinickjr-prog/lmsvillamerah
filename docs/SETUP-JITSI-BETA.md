# Setup Jitsi Meet untuk Fase Beta

Fase Beta menggunakan Jitsi Meet melalui IFrame API. Jadwal, izin masuk, pemetaan
program/kelas/cabang, dan batas peserta tetap dikelola Laravel. Audio dan video
diproses oleh infrastruktur Jitsi.

## Environment

Tambahkan pada environment server:

```env
JITSI_DOMAIN=meet.jit.si
JITSI_ROOM_PREFIX=VillaMerahBeta
```

Kemudian:

```bash
php artisan optimize:clear
php artisan config:cache
```

Domain LMS wajib memakai HTTPS agar browser mengizinkan kamera, mikrofon, dan
berbagi layar.

## Keamanan akses Beta

- Nama ruang dibuat otomatis dari ID sesi dan HMAC `APP_KEY`.
- Nama ruang tidak ditampilkan sebagai input dan tidak perlu dibagikan manual.
- Halaman ruang tetap dilindungi login serta kecocokan program, kelas, cabang,
  mode belajar, dan status peserta sesi.
- Kamera dan mikrofon dimulai dalam keadaan mati.
- Jangan membagikan alamat/nama konferensi Jitsi di luar LMS.

`meet.jit.si` cocok untuk pengujian integrasi, tetapi Jitsi menyatakan instance
publik tersebut bukan layanan production aplikasi. Setelah fase Beta, arahkan
`JITSI_DOMAIN` ke instalasi Jitsi milik sendiri atau lanjutkan arsitektur meeting
internal dengan VPS/SFU/TURN.
