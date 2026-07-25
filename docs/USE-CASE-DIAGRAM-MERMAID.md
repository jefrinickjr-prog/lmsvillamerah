# Use Case Diagram — LMS Villa Merah

Use case diagram berikut menggambarkan fungsi LMS dari sudut pandang
Administrator, Guru, dan Siswa.

```mermaid
flowchart LR
    ADMIN["👤<br/>Administrator"]

    subgraph LMS["SISTEM LMS VILLA MERAH"]
        direction TB

        UC1([Login])
        UC2([Kelola dan Setujui Pengguna])
        UC3([Kelola Kelas])
        UC4([Kelola Materi])
        UC5([Kelola Tugas dan Ujian])
        UC6([Kelola Absensi])
        UC7([Kelola Nilai dan Laporan])
        UC8([Kelola Live Streaming])
        UC9([Akses Materi])
        UC10([Kerjakan Tugas dan Ujian])
        UC11([Lihat Nilai dan Laporan])
        UC12([Ikuti Live Streaming])

        UC2 -.->|"«include»"| UC1
        UC3 -.->|"«include»"| UC1
        UC4 -.->|"«include»"| UC3
        UC5 -.->|"«include»"| UC3
        UC6 -.->|"«include»"| UC3
        UC7 -.->|"«include»"| UC5
        UC8 -.->|"«include»"| UC3
        UC9 -.->|"«include»"| UC1
        UC10 -.->|"«include»"| UC9
        UC11 -.->|"«include»"| UC1
        UC12 -.->|"«include»"| UC1
    end

    GURU["🧑‍🏫<br/>Guru"]
    SISWA["🎓<br/>Siswa"]

    ADMIN --- UC1
    ADMIN --- UC2
    ADMIN --- UC3

    UC1 --- GURU
    UC3 --- GURU
    UC4 --- GURU
    UC5 --- GURU
    UC6 --- GURU
    UC7 --- GURU
    UC8 --- GURU

    UC1 --- SISWA
    UC9 --- SISWA
    UC10 --- SISWA
    UC11 --- SISWA
    UC12 --- SISWA

    classDef actor fill:#fff7ed,stroke:#c2410c,stroke-width:2px,color:#431407;
    classDef usecase fill:#dbeafe,stroke:#2563eb,stroke-width:2px,color:#172554;
    class ADMIN,GURU,SISWA actor;
    class UC1,UC2,UC3,UC4,UC5,UC6,UC7,UC8,UC9,UC10,UC11,UC12 usecase;

    style LMS fill:#f8fafc,stroke:#334155,stroke-width:2px
```

## Ringkasan aktor

| Aktor | Hak akses utama |
|---|---|
| Administrator | Login, persetujuan pengguna, pengelolaan pengguna, dan kelas |
| Guru | Kelas, materi, tugas, ujian, absensi, nilai, laporan, dan live streaming |
| Siswa | Materi, tugas atau ujian, nilai, laporan, dan live streaming |

## Keterangan relasi

- Garis penuh menunjukkan interaksi aktor dengan use case.
- Relasi `«include»` menunjukkan bahwa suatu use case selalu menggunakan fungsi
  lain sebagai bagian dari prosesnya.
- Seluruh use case berada di dalam batas **Sistem LMS Villa Merah**.

