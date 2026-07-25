# Dokumentasi Arsitektur Teknologi
## E-Learning Bimbel Gambar Villa Merah

**Versi:** 1.0  
**Tanggal:** 25 Juli 2026  
**Status:** Production

---

## 📋 Daftar Isi

1. [Ringkasan Eksekutif](#ringkasan-eksekutif)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Stack Teknologi](#stack-teknologi)
4. [Struktur Aplikasi](#struktur-aplikasi)
5. [Database Schema](#database-schema)
6. [Module dan Features](#module-dan-features)
7. [Infrastruktur dan Deployment](#infrastruktur-dan-deployment)
8. [Keamanan](#keamanan)
9. [Performance dan Scalability](#performance-dan-scalability)

---

## Ringkasan Eksekutif

E-Learning Bimbel Gambar Villa Merah adalah aplikasi pembelajaran online (Learning Management System) yang dirancang khusus untuk mendukung pembelajaran menggambar dan pelajaran akademis (Skolastik) secara terintegrasi.

### Karakteristik Utama:
- **Monolithic Architecture** berbasis Laravel 13.8
- **Multi-user System** dengan role-based access control (Admin, Teacher, Student)
- **Video Streaming** dengan adaptive bitrate
- **Live Session/Meeting** menggunakan Whereby API
- **Task Management** dengan submission dan grading
- **Attendance Tracking** real-time
- **Responsive UI** menggunakan Tailwind CSS

---

## Arsitektur Sistem

### Diagram Arsitektur Keseluruhan

```
┌─────────────────────────────────────────────────────────────┐
│                      CLIENT LAYER                           │
│  ┌──────────────┐  ┌─────────────┐  ┌──────────────┐      │
│  │ Web Browser  │  │ Mobile      │  │ Desktop      │      │
│  │ (Chrome,     │  │ Browser     │  │ App (Future) │      │
│  │ Firefox, etc)│  └─────────────┘  └──────────────┘      │
│  └──────────────┘                                          │
└────────────┬────────────────────────────────────────────────┘
             │ HTTPS/HTTP
┌────────────▼────────────────────────────────────────────────┐
│                   PRESENTATION LAYER                        │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  Blade Templates + Tailwind CSS + Alpine JS         │ │
│  │  - Dashboard (Admin, Teacher, Student)              │ │
│  │  - Classroom Management                              │ │
│  │  - Material Viewer & Video Player                   │ │
│  │  - Task Management & Submission                      │ │
│  │  - Live Stream Interface                             │ │
│  │  - Attendance Tracking                               │ │
│  └──────────────────────────────────────────────────────┘ │
└────────────┬────────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────────┐
│                    APPLICATION LAYER                        │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  Laravel 13.8 Framework                             │ │
│  │  ┌────────────────────────────────────────────────┐ │ │
│  │  │ Controllers (HTTP Request Handling)            │ │ │
│  │  │ - AdminController                             │ │ │
│  │  │ - TeacherController                           │ │ │
│  │  │ - StudentController                           │ │ │
│  │  │ - ClassroomController                         │ │ │
│  │  │ - MaterialController                          │ │ │
│  │  │ - TaskController                              │ │ │
│  │  │ - LiveStreamController                        │ │ │
│  │  │ - AttendanceController                        │ │ │
│  │  └────────────────────────────────────────────────┘ │ │
│  │  ┌────────────────────────────────────────────────┐ │ │
│  │  │ Services (Business Logic)                      │ │ │
│  │  │ - WherebyMeetingService (Video Conference)    │ │ │
│  │  │ - AuthenticationService                        │ │ │
│  │  │ - NotificationService                          │ │ │
│  │  │ - FileUploadService                            │ │ │
│  │  │ - GradingService                               │ │ │
│  │  └────────────────────────────────────────────────┘ │ │
│  │  ┌────────────────────────────────────────────────┐ │ │
│  │  │ Middleware (Request/Response Processing)      │ │ │
│  │  │ - Authentication Middleware                   │ │ │
│  │  │ - Authorization Middleware                    │ │ │
│  │  │ - CORS Middleware                             │ │ │
│  │  │ - Rate Limiting Middleware                    │ │ │
│  │  └────────────────────────────────────────────────┘ │ │
│  └──────────────────────────────────────────────────────┘ │
└────────────┬────────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────────┐
│                     BUSINESS LOGIC LAYER                    │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  Eloquent ORM Models                               │ │
│  │  - User (Student, Teacher, Admin)                  │ │
│  │  - Classroom (Kelas)                               │ │
│  │  - Material (Konten Pembelajaran)                  │ │
│  │  - Task (Tugas Pembelajaran)                       │ │
│  │  - Submission (Pengumpulan Tugas)                  │ │
│  │  - Attendance (Presensi)                           │ │
│  │  - LiveStreamSession (Kelas Langsung)              │ │
│  │  - UserNotification (Notifikasi)                   │ │
│  └──────────────────────────────────────────────────────┘ │
└────────────┬────────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────────┐
│                      DATA LAYER                             │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  Database (MySQL/MariaDB)                          │ │
│  │  - User Management Tables                          │ │
│  │  - Classroom & Course Data                         │ │
│  │  - Learning Materials                              │ │
│  │  - Tasks & Submissions                             │ │
│  │  - Attendance Records                              │ │
│  │  - Session & Notification Data                     │ │
│  └──────────────────────────────────────────────────────┘ │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  File Storage                                       │ │
│  │  - Profile Photos (/storage/app/public)            │ │
│  │  - Video Files                                      │ │
│  │  - Assignment Submissions                          │ │
│  │  - Documents                                        │ │
│  └──────────────────────────────────────────────────────┘ │
└────────────┬────────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────────┐
│                EXTERNAL INTEGRATIONS                        │
│  ┌──────────────────────────────────────────────────────┐ │
│  │  - Whereby API (Video Conference & Recording)      │ │
│  │  - Email Service (Notifications & Alerts)          │ │
│  │  - Video CDN (Storage & Streaming)                 │ │
│  │  - File Storage Service (AWS S3 optional)          │ │
│  └──────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────┘
```

---

## Stack Teknologi

### Backend
| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Framework | Laravel | 13.8 |
| PHP | PHP | ^8.3 |
| Database | MySQL / MariaDB | 5.7+ |
| Web Server | Apache / Nginx | Latest |
| Package Manager | Composer | ^2.0 |
| ORM | Eloquent | Built-in |

### Frontend
| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| CSS Framework | Tailwind CSS | 3.4.19 |
| CSS Preprocessor | PostCSS | 8.5.15 |
| Build Tool | Vite | 8.0.0 |
| JavaScript | Vanilla JS + Alpine.js | Latest |
| Task Runner | NPM | 8+ |
| Font | Instrument Sans (Bunny) | Custom |

### Development
| Komponen | Teknologi | Tujuan |
|----------|-----------|--------|
| Testing | PHPUnit | Unit & Feature Tests |
| Faker | FakerPHP | Database Seeding |
| Code Quality | Laravel Pint | Code Formatting |
| Dev Tool | Laravel Pail | Logging Viewer |
| Dev Server | Concurrently | Multiple servers |

### External Services
| Layanan | Fungsi | URL |
|---------|--------|-----|
| Whereby | Video Conference & Meeting Recording | api.whereby.com |
| Email Service | Transactional Emails | MAIL_DRIVER |
| Storage | File Upload & Download | S3 (optional) |

---

## Struktur Aplikasi

### Directory Structure

```
elearning-02/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php
│   │   │   ├── TeacherController.php
│   │   │   ├── StudentController.php
│   │   │   ├── StudentPageController.php
│   │   │   ├── StudentManagementController.php
│   │   │   ├── ClassroomController.php
│   │   │   ├── MaterialController.php
│   │   │   ├── TaskController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── LiveStreamController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   └── RegisterController.php
│   │   │   └── AdminUserController.php
│   │   └── Middleware/
│   │       ├── Authenticate.php
│   │       ├── RedirectIfAuthenticated.php
│   │       ├── RoleMiddleware.php
│   │       └── [Custom Middlewares]
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Classroom.php
│   │   ├── Material.php
│   │   ├── Task.php
│   │   ├── Submission.php
│   │   ├── Attendance.php
│   │   ├── LiveStreamSession.php
│   │   └── UserNotification.php
│   │
│   ├── Services/
│   │   └── WherebyMeetingService.php
│   │
│   └── Providers/
│       └── AppServiceProvider.php
│
├── routes/
│   ├── web.php
│   ├── console.php
│   └── [API routes]
│
├── database/
│   ├── migrations/
│   │   ├── [Migration files]
│   │   └── [Schema definitions]
│   ├── seeders/
│   └── factories/
│       └── UserFactory.php
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php (Main layout)
│   │   │   └── [Component layouts]
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   ├── dashboard/
│   │   │   ├── admin.blade.php
│   │   │   ├── teacher.blade.php
│   │   │   └── student.blade.php
│   │   ├── classroom/
│   │   ├── materials/
│   │   ├── tasks/
│   │   ├── attendance/
│   │   ├── live-stream/
│   │   └── welcome.blade.php
│   ├── css/
│   │   └── app.css (Tailwind styles)
│   └── js/
│       └── app.js
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── session.php
│   ├── cache.php
│   ├── services.php
│   └── queue.php
│
├── storage/
│   ├── app/
│   │   └── public/ (User uploads)
│   ├── framework/
│   │   └── views/ (Compiled blade views)
│   └── logs/ (Application logs)
│
├── public/
│   ├── index.php (Entry point)
│   ├── build/ (Vite compiled assets)
│   ├── images/ (Logo & static images)
│   └── storage -> ../storage/app/public
│
├── bootstrap/
│   ├── app.php
│   └── providers.php
│
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── TestCase.php
│
├── vendor/ (Composer dependencies)
├── node_modules/ (NPM dependencies)
├── .env (Environment variables)
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
├── phpunit.xml
├── artisan (Artisan CLI)
└── README.md
```

---

## Database Schema

### Core Tables

#### Users Table
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'teacher', 'student'),
    program_type ENUM('gambar', 'skolastik'),
    delivery_mode VARCHAR(255),
    video_accesses JSON,
    student_class VARCHAR(255),
    branch VARCHAR(255),
    academic_year VARCHAR(10),
    student_code VARCHAR(50),
    photo_path VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    approved_by BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Classrooms Table
```sql
CREATE TABLE classrooms (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    description TEXT,
    teacher_id BIGINT,
    academic_year VARCHAR(10),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);
```

#### Materials Table
```sql
CREATE TABLE materials (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    classroom_id BIGINT,
    title VARCHAR(255),
    description TEXT,
    content TEXT,
    video_url VARCHAR(255),
    file_path VARCHAR(255),
    order INT,
    is_published BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
);
```

#### Tasks Table
```sql
CREATE TABLE tasks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    classroom_id BIGINT,
    title VARCHAR(255),
    description TEXT,
    due_date DATETIME,
    max_score INT,
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### Submissions Table
```sql
CREATE TABLE submissions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT,
    student_id BIGINT,
    file_path VARCHAR(255),
    submitted_at DATETIME,
    score INT NULL,
    feedback TEXT NULL,
    graded_by BIGINT NULL,
    graded_at DATETIME NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (graded_by) REFERENCES users(id)
);
```

#### Attendance Table
```sql
CREATE TABLE attendance (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    classroom_id BIGINT,
    student_id BIGINT,
    session_date DATE,
    status ENUM('present', 'absent', 'late'),
    checked_at DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);
```

#### Live Stream Sessions Table
```sql
CREATE TABLE live_stream_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    classroom_id BIGINT,
    title VARCHAR(255),
    whereby_room_id VARCHAR(255),
    started_at DATETIME,
    ended_at DATETIME NULL,
    recording_url VARCHAR(255) NULL,
    status ENUM('scheduled', 'ongoing', 'completed'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
);
```

#### User Notifications Table
```sql
CREATE TABLE user_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    type VARCHAR(255),
    title VARCHAR(255),
    message TEXT,
    data JSON,
    read_at DATETIME NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## Module dan Features

### 1. Authentication & Authorization
- **Login/Register** dengan email validation
- **Role-based Access Control** (Admin, Teacher, Student)
- **Session Management** menggunakan Laravel Session
- **Password Hashing** dengan bcrypt

**File Utama:**
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Middleware/Authenticate.php`
- `routes/web.php`

### 2. User Management
- **Admin Dashboard** untuk manage users
- **User Approval System** untuk student registration
- **Profile Management** dengan photo upload
- **Role Assignment** (Admin, Teacher, Student)

**File Utama:**
- `app/Http/Controllers/AdminUserController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Models/User.php`

### 3. Classroom Management
- **Create Classroom** oleh teacher
- **Assign Students** ke classroom
- **Class Schedule** management
- **Student Enrollment** tracking

**File Utama:**
- `app/Http/Controllers/ClassroomController.php`
- `app/Models/Classroom.php`

### 4. Learning Materials
- **Upload Materials** (video, PDF, documents)
- **Video Streaming** dengan adaptive quality
- **Material Organization** per classroom
- **Download Materials** for offline learning

**File Utama:**
- `app/Http/Controllers/MaterialController.php`
- `app/Models/Material.php`

### 5. Task Management
- **Create Tasks/Assignments** oleh teacher
- **Student Submission** dengan file upload
- **Grading System** dengan scoring
- **Feedback Provision** untuk students

**File Utama:**
- `app/Http/Controllers/TaskController.php`
- `app/Models/Task.php`
- `app/Models/Submission.php`

### 6. Attendance Tracking
- **Mark Attendance** real-time
- **Attendance Report** per student
- **Attendance History** tracking
- **Late/Absent** recording

**File Utama:**
- `app/Http/Controllers/AttendanceController.php`
- `app/Models/Attendance.php`

### 7. Live Streaming / Video Conference
- **Schedule Live Session** untuk kelas
- **Whereby Integration** untuk meeting
- **Session Recording** automatic
- **Participants Tracking** real-time

**File Utama:**
- `app/Http/Controllers/LiveStreamController.php`
- `app/Services/WherebyMeetingService.php`
- `app/Models/LiveStreamSession.php`

### 8. Notifications
- **Email Notifications** untuk events
- **In-app Notifications** dashboard
- **Assignment Reminders** untuk students
- **Grade Notifications** when graded

**File Utama:**
- `app/Models/UserNotification.php`
- `config/mail.php`

### 9. Reporting & Analytics
- **Student Performance Report**
- **Class Statistics**
- **Attendance Report**
- **Assignment Submission Report**

---

## Infrastruktur dan Deployment

### Hosting Environment
```
┌─────────────────────────────────────┐
│   Hostinger Shared Hosting          │
├─────────────────────────────────────┤
│ Server: id-dci-web1981              │
│ SSH Port: 65002                     │
│ Control Panel: hPanel               │
│ OS: Linux (cPanel/WHM)              │
│ PHP Version: 8.3+                   │
│ Database: MySQL 8.0                 │
└─────────────────────────────────────┘
```

### Directory Structure on Server
```
/home/u930607946/
├── domains/
│   └── lmsvillamerah.sivmi.id/
│       └── public_html/              # Application root
│           ├── app/
│           ├── bootstrap/
│           ├── config/
│           ├── database/
│           ├── public/
│           ├── resources/
│           ├── routes/
│           ├── storage/
│           ├── .env                  # Environment config
│           ├── composer.json
│           └── [Laravel files]
└── .composer/                        # Composer cache
```

### Deployment Process

#### 1. Version Control
```bash
# Push perubahan ke GitHub
git add .
git commit -m "Commit message"
git push origin main

# Pull di server
git pull origin main
```

#### 2. Dependency Installation
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install
npm run build
```

#### 3. Laravel Setup
```bash
# Clear cache
php artisan config:cache
php artisan cache:clear
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed
```

#### 4. File Permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 .env
```

### CI/CD Pipeline
```
GitHub Repository
        ↓
   Webhook Trigger
        ↓
   SSH to Server
        ↓
   Git Pull
        ↓
   Composer Install
        ↓
   Cache Clear
        ↓
   Application Ready
```

---

## Keamanan

### Authentication Security
- Password hashing dengan bcrypt
- Email verification untuk registration
- Session timeout configuration
- CSRF token protection untuk forms

### Authorization Security
- Role-based access control (RBAC)
- Middleware protection per route
- Policy-based authorization
- Resource owner verification

### Data Protection
- Encrypted database fields (password)
- HTTPS/SSL encryption in transit
- Prepared statements untuk prevent SQL injection
- Input validation dan sanitization

### File Security
- File upload validation (size, type)
- Stored outside public directory
- Symlink untuk storage access
- Antivirus scanning for uploads

### API Security
- Rate limiting per IP
- CORS configuration
- Request throttling
- API token validation

### Monitoring
- Application logging
- Error tracking (Laravel Pail)
- Access logging
- Security audit trails

---

## Performance dan Scalability

### Optimization Strategies

#### 1. Caching
```php
// Config caching
php artisan config:cache

// Route caching
php artisan route:cache

// View caching
php artisan view:cache

// Database query caching
// Implemented in services
```

#### 2. Database Optimization
- Indexed columns untuk frequent queries
- Lazy loading dengan pagination
- Query optimization
- Connection pooling

#### 3. Frontend Optimization
- CSS minification via Vite
- JavaScript bundling & splitting
- Image optimization
- Lazy loading images

#### 4. Codebase
- Dependency injection pattern
- Service layer pattern
- Repository pattern
- Model scoping

### Scalability Considerations

#### Horizontal Scaling
- Stateless application design
- Session storage di database
- File storage di centralized location
- Load balancer ready

#### Vertical Scaling
- Database optimization
- Query caching
- View caching
- Asset optimization

### Load Testing Guidelines
- Peak concurrent users: Expected 500+ users
- Database connection pooling
- CDN integration untuk static assets
- Video streaming dengan adaptive bitrate

### Monitoring & Observability

#### Logging
```
Location: storage/logs/laravel.log
Format: Single file with rotation
Level: debug, info, warning, error, critical
```

#### Key Metrics
- HTTP request response time
- Database query performance
- Memory usage
- Disk space
- Error rate

#### Tools
- Laravel Pail untuk development logging
- Application error tracking
- User session monitoring
- API request logging

---

## Technology Decision Matrix

| Decision | Choice | Justification |
|----------|--------|---------------|
| Framework | Laravel | Mature, extensive ecosystem, built-in security |
| Frontend | Blade + Tailwind | Server-side rendering, rapid UI development |
| Build Tool | Vite | Fast development, quick production build |
| Database | MySQL | Reliable, widespread support, cost-effective |
| ORM | Eloquent | Integrated with Laravel, intuitive API |
| Video Conference | Whereby | Reliable recording, API support, scalable |
| Hosting | Shared Hosting | Cost-effective, managed infrastructure |
| Version Control | Git/GitHub | Industry standard, collaboration features |

---

## Roadmap & Future Enhancements

### Phase 1 (Current)
- ✅ Core LMS features
- ✅ User management
- ✅ Classroom management
- ✅ Material & task management
- ✅ Live streaming integration

### Phase 2 (Planned)
- [ ] Mobile app (React Native / Flutter)
- [ ] Advanced analytics & reporting
- [ ] AI-powered grading assistant
- [ ] Discussion forum
- [ ] Peer review system

### Phase 3 (Future)
- [ ] Microservices architecture
- [ ] GraphQL API
- [ ] Real-time notifications (WebSocket)
- [ ] Advanced learning analytics
- [ ] Integration dengan external LMS

---

## Support & Maintenance

### Regular Maintenance Tasks
- Update Laravel & dependencies (quarterly)
- Database maintenance & optimization
- Security patches & updates
- Log rotation & cleanup
- Backup verification

### Monitoring Checklist
- [ ] Application health check
- [ ] Database performance
- [ ] Disk space availability
- [ ] Error log monitoring
- [ ] User feedback tracking

### Contact Information
- **Development:** jefrinickjr-prog (GitHub)
- **Repository:** https://github.com/jefrinickjr-prog/lmsvillamerah
- **Hosting:** Hostinger
- **Domain:** lmsvillamerah.sivmi.id

---

## Appendix: Key Technologies

### Laravel Framework
- **Version:** 13.8 (Latest LTS-compatible)
- **Components:** Routing, ORM, Auth, Mail, Cache
- **Documentation:** https://laravel.com/docs

### Tailwind CSS
- **Version:** 3.4.19
- **Features:** Utility-first CSS, responsive design, dark mode
- **Documentation:** https://tailwindcss.com

### Whereby API
- **Integration:** Video conferencing & recording
- **Authentication:** API key-based
- **Features:** Room management, recording, participants
- **Documentation:** https://whereby.readme.io

### Vite Build Tool
- **Version:** 8.0.0
- **Features:** Hot module replacement, optimized builds
- **Performance:** Near-instant HMR
- **Documentation:** https://vitejs.dev

---

**END OF DOCUMENT**

*Document Version: 1.0*  
*Last Updated: 25 Juli 2026*  
*Next Review: 25 Oktober 2026*
