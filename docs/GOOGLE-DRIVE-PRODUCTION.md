# Google Workspace Shared Drive untuk Karya Pertemuan

Integrasi ini berjalan dari server produksi Hostinger. Kredensial tidak disimpan di Git.

## Persiapan Google Workspace

1. Buat project di Google Cloud dan aktifkan Google Drive API.
2. Buat service account dan unduh credential JSON.
3. Buat Shared Drive untuk arsip LMS.
4. Tambahkan email service account sebagai anggota Shared Drive dengan peran **Content manager**.
5. Di dalam Shared Drive, buat folder induk, misalnya `Karya Siswa LMS`.
6. Salin Shared Drive ID dan ID folder induk dari URL Google Drive.

## Penempatan credential di Hostinger

Simpan JSON di luar `public_html`, misalnya:

```text
/home/USER/domains/lmsvillamerah.sivmi.id/private/google-workspace-service-account.json
```

Permission yang disarankan:

```bash
chmod 600 /home/USER/domains/lmsvillamerah.sivmi.id/private/google-workspace-service-account.json
```

Tambahkan ke `.env` produksi:

```dotenv
GOOGLE_DRIVE_ENABLED=true
GOOGLE_DRIVE_SHARED_DRIVE_ID=ID_SHARED_DRIVE
GOOGLE_DRIVE_ROOT_FOLDER_ID=ID_FOLDER_KARYA_SISWA_LMS
GOOGLE_DRIVE_SERVICE_ACCOUNT_PATH=/home/USER/domains/lmsvillamerah.sivmi.id/private/google-workspace-service-account.json
GOOGLE_DRIVE_DELETE_LOCAL_AFTER_SYNC=false
QUEUE_CONNECTION=database
```

Lalu jalankan:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan google-drive:check
php artisan meeting-submissions:sync-pending
php artisan queue:work --stop-when-empty --tries=5 --timeout=180
```

## Cron Hostinger

Jalankan setiap menit. Sesuaikan path PHP dan direktori akun Hostinger:

```cron
* * * * * cd /home/USER/domains/lmsvillamerah.sivmi.id/public_html && php artisan queue:work --stop-when-empty --tries=5 --timeout=180 >> /dev/null 2>&1
```

Folder Google Drive otomatis disusun:

```text
Tahun Ajaran / Kelas - Cabang / Kode Siswa - Nama / Tanggal - Pertemuan / File karya
```

File lokal dipertahankan secara default sebagai cadangan. Aktifkan `GOOGLE_DRIVE_DELETE_LOCAL_AFTER_SYNC=true` hanya setelah integrasi stabil dan backup Shared Drive sudah dipastikan.
