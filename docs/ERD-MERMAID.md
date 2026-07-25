# Entity Relationship Diagram (ERD) LMS Villa Merah

Diagram ini merepresentasikan tabel bisnis pada migration aplikasi hingga
`2026_07_18_000002_create_native_live_stream_signals.php`. Tabel infrastruktur
Laravel seperti `cache`, `jobs`, `sessions`, dan `password_reset_tokens` tidak
ditampilkan agar diagram tetap fokus pada domain LMS.

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        string role
        string program_type
        string delivery_mode
        json video_accesses
        string student_class
        string branch
        string academic_year
        string student_code UK
        string photo_path
        timestamp email_verified_at
        timestamp approved_at
        bigint approved_by FK
        string password
        timestamp created_at
        timestamp updated_at
    }

    CLASSROOMS {
        bigint id PK
        string program_type
        string delivery_mode
        string title
        string branch
        text description
        bigint teacher_id FK
        timestamp created_at
        timestamp updated_at
    }

    MATERIALS {
        bigint id PK
        bigint classroom_id FK
        string program_type
        string title
        text content
        string youtube_embed_url
        timestamp created_at
        timestamp updated_at
    }

    CLASSROOM_MATERIAL {
        bigint id PK
        bigint classroom_id FK
        bigint material_id FK
        timestamp created_at
        timestamp updated_at
    }

    TASKS {
        bigint id PK
        bigint material_id FK
        string task_type
        string title
        text description
        string attachment_path
        json questions
        timestamp due_at
        smallint duration_minutes
        timestamp created_at
        timestamp updated_at
    }

    CLASSROOM_TASK {
        bigint id PK
        bigint classroom_id FK
        bigint task_id FK
        timestamp created_at
        timestamp updated_at
    }

    SUBMISSIONS {
        bigint id PK
        bigint task_id FK
        bigint student_id FK
        text content
        json answers
        timestamp started_at
        timestamp submitted_at
        string attachment
        int score
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCES {
        bigint id PK
        bigint classroom_id FK
        bigint student_id FK
        date date
        date week_start
        boolean present
        timestamp created_at
        timestamp updated_at
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        string title
        text body
        boolean read
        timestamp created_at
        timestamp updated_at
    }

    LIVE_STREAM_SESSIONS {
        bigint id PK
        bigint classroom_id FK
        string title
        text meeting_url
        datetime starts_at
        datetime ends_at
        datetime started_at
        bigint started_by FK
        timestamp created_at
        timestamp updated_at
    }

    LIVE_STREAM_PARTICIPANTS {
        bigint id PK
        bigint live_stream_session_id FK
        bigint user_id FK
        timestamp created_at
        timestamp updated_at
    }

    LIVE_STREAM_SIGNALS {
        bigint id PK
        bigint live_stream_session_id FK
        bigint from_user_id FK
        bigint to_user_id FK
        string type
        json payload
        timestamp created_at
        timestamp updated_at
    }

    USERS o|--o{ USERS : "menyetujui admin"
    USERS ||--o{ CLASSROOMS : "mengajar"
    CLASSROOMS ||--o{ MATERIALS : "materi utama"
    CLASSROOMS ||--o{ CLASSROOM_MATERIAL : "memiliki"
    MATERIALS ||--o{ CLASSROOM_MATERIAL : "dibagikan ke"
    MATERIALS o|--o{ TASKS : "sumber materi"
    CLASSROOMS ||--o{ CLASSROOM_TASK : "memiliki"
    TASKS ||--o{ CLASSROOM_TASK : "ditugaskan ke"
    TASKS ||--o{ SUBMISSIONS : "dikumpulkan melalui"
    USERS ||--o{ SUBMISSIONS : "mengumpulkan"
    CLASSROOMS ||--o{ ATTENDANCES : "mencatat"
    USERS ||--o{ ATTENDANCES : "memiliki absensi"
    USERS ||--o{ NOTIFICATIONS : "menerima"
    CLASSROOMS ||--o{ LIVE_STREAM_SESSIONS : "menyelenggarakan"
    USERS o|--o{ LIVE_STREAM_SESSIONS : "memulai"
    LIVE_STREAM_SESSIONS ||--o{ LIVE_STREAM_PARTICIPANTS : "diikuti"
    USERS ||--o{ LIVE_STREAM_PARTICIPANTS : "bergabung"
    LIVE_STREAM_SESSIONS ||--o{ LIVE_STREAM_SIGNALS : "mengelola sinyal"
    USERS ||--o{ LIVE_STREAM_SIGNALS : "mengirim"
    USERS ||--o{ LIVE_STREAM_SIGNALS : "menerima"
```

## Catatan relasi

- `classroom_material` dan `classroom_task` adalah tabel pivot dengan pasangan
  foreign key yang unik.
- `materials.classroom_id` tetap menjadi relasi kelas utama, sedangkan
  `classroom_material` memungkinkan satu materi dibagikan ke beberapa kelas.
- `tasks.material_id` bersifat opsional; tugas dapat didistribusikan langsung ke
  kelas melalui `classroom_task`.
- `users.approved_by` dan `live_stream_sessions.started_by` dapat bernilai
  `NULL`.
- `attendances` memiliki kombinasi unik `student_id` dan `week_start`.

