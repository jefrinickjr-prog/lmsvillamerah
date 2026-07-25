# Data Flow Diagram (DFD) Level 0 LMS Villa Merah

DFD Level 0 berikut menampilkan aplikasi sebagai satu proses utama dan
memperlihatkan pertukaran data dengan setiap entitas eksternal. Penyimpanan data
internal belum diuraikan pada level ini.

```mermaid
flowchart LR
    A[Administrator]
    G[Guru]
    S[Siswa]
    LMS([0. Sistem LMS<br/>Villa Merah])

    A -->|Data akun dan persetujuan admin<br/>Data siswa, kelas, dan pengaturan| LMS
    LMS -->|Status persetujuan<br/>Informasi akun dan laporan sistem| A

    G -->|Data kelas dan materi<br/>Tugas atau ujian<br/>Absensi, nilai, dan sesi live| LMS
    LMS -->|Data siswa dan pengumpulan tugas<br/>Rekap absensi<br/>Jadwal dan peserta kelas| G

    S -->|Data registrasi dan profil<br/>Jawaban atau pengumpulan tugas<br/>Kehadiran dan partisipasi live| LMS
    LMS -->|Materi pembelajaran<br/>Tugas, nilai, dan laporan<br/>Notifikasi dan jadwal live| S

    classDef external fill:#eff6ff,stroke:#2563eb,stroke-width:2px,color:#172554;
    classDef process fill:#fff7ed,stroke:#ea580c,stroke-width:3px,color:#431407;
    class A,G,S external;
    class LMS process;
```

## Elemen diagram

| Kode | Jenis | Nama |
|---|---|---|
| 0 | Proses utama | Sistem LMS Villa Merah |
| A | Entitas eksternal | Administrator |
| G | Entitas eksternal | Guru |
| S | Entitas eksternal | Siswa |

## Batasan level

- Diagram hanya menunjukkan batas sistem dan arus data dengan pengguna.
- Database dan proses internal tidak ditampilkan pada DFD Level 0.
- Pemecahan proses seperti autentikasi, pengelolaan pembelajaran, penilaian,
  absensi, dan live streaming dapat dijabarkan pada DFD Level 1.

