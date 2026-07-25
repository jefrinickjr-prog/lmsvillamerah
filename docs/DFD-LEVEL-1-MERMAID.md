# DFD Level 1 — LMS Villa Merah

```mermaid
flowchart TB
    %% Entitas eksternal
    subgraph E["ENTITAS EKSTERNAL"]
        direction LR
        A["Administrator"]
        G["Guru"]
        S["Siswa"]
    end

    %% Proses utama
    subgraph P["PROSES LMS VILLA MERAH"]
        direction LR
        P1(["P1<br/>Autentikasi &<br/>Persetujuan"])
        P2(["P2<br/>Manajemen Pengguna<br/>& Kelas"])
        P3(["P3<br/>Manajemen<br/>Materi"])
        P4(["P4<br/>Tugas &<br/>Penilaian"])
        P5(["P5<br/>Absensi &<br/>Laporan"])
        P6(["P6<br/>Live Streaming<br/>& Signaling"])
    end

    %% Penyimpanan data
    subgraph D["DATA STORE"]
        direction LR
        D1[("D1<br/>Pengguna")]
        D2[("D2<br/>Kelas")]
        D3[("D3<br/>Materi")]
        D4[("D4<br/>Tugas & Nilai")]
        D5[("D5<br/>Absensi")]
        D6[("D6<br/>Live Stream")]
    end

    %% Arus data entitas dan proses
    A -->|"login & persetujuan"| P1
    G -->|"login"| P1
    S -->|"registrasi & login"| P1
    P1 -->|"status akses"| A
    P1 -->|"status akses"| G
    P1 -->|"status akses"| S

    A -->|"data pengguna & kelas"| P2
    G -->|"data kelas"| P2
    P2 -->|"informasi kelas"| G
    P2 -->|"informasi kelas"| S

    G -->|"materi pembelajaran"| P3
    P3 -->|"materi kelas"| S

    G -->|"tugas, soal & nilai"| P4
    S -->|"jawaban tugas"| P4
    P4 -->|"hasil & nilai"| G
    P4 -->|"tugas & nilai"| S

    G -->|"data kehadiran"| P5
    S -->|"permintaan laporan"| P5
    P5 -->|"rekap absensi"| G
    P5 -->|"absensi & laporan"| S

    G -->|"jadwal, sesi & sinyal"| P6
    S -->|"partisipasi & sinyal"| P6
    P6 -->|"status sesi"| G
    P6 -->|"ruang live & sinyal"| S

    %% Arus data proses dan data store
    P1 <-->|"kredensial & persetujuan"| D1
    P2 <-->|"profil & peran"| D1
    P2 <-->|"data kelas"| D2
    P3 <-->|"materi & distribusi"| D3
    P4 <-->|"tugas, jawaban & nilai"| D4
    P5 <-->|"kehadiran"| D5
    P6 <-->|"sesi, peserta & sinyal"| D6

    %% Data pendukung antarproses
    P1 -->|"identitas terverifikasi"| P2
    P2 -->|"kelas terpilih"| P3
    P2 -->|"kelas terpilih"| P4
    P2 -->|"data siswa & kelas"| P5
    P2 -->|"data peserta & kelas"| P6
    P3 -->|"materi terkait"| P4
    P4 -->|"nilai siswa"| P5

    classDef actor fill:#e8f0fe,stroke:#2563eb,stroke-width:2px,color:#172554;
    classDef process fill:#fff7ed,stroke:#f97316,stroke-width:2px,color:#431407;
    classDef store fill:#ecfdf5,stroke:#16a34a,stroke-width:2px,color:#052e16;
    class A,G,S actor;
    class P1,P2,P3,P4,P5,P6 process;
    class D1,D2,D3,D4,D5,D6 store;

    style E fill:#f8fafc,stroke:#94a3b8,stroke-dasharray:5 5
    style P fill:#fffbeb,stroke:#f59e0b,stroke-dasharray:5 5
    style D fill:#f0fdf4,stroke:#22c55e,stroke-dasharray:5 5
```

## Pemetaan data store

| Data store | Tabel aplikasi |
|---|---|
| D1 Pengguna | `users` |
| D2 Kelas | `classrooms` |
| D3 Materi | `materials`, `classroom_material` |
| D4 Tugas dan Nilai | `tasks`, `classroom_task`, `submissions` |
| D5 Absensi | `attendances` |
| D6 Live Stream | `live_stream_sessions`, `live_stream_participants`, `live_stream_signals` |
