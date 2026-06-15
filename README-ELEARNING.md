E-Learning Gambar — Prototype

Quick setup and notes for the prototype implementation added to this Laravel app.

Setup
- Copy `.env.example` to `.env` and configure your database connection.
- Run composer install if needed: `composer install`
- Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed --class=InitialUsersSeeder
```

Auth
- This repository doesn't include full auth scaffolding. Install one (Laravel Breeze or UI) if you need registration and frontend auth pages:

```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run dev
php artisan migrate
```

Middleware
- A `RoleMiddleware` was added at `app/Http/Middleware/RoleMiddleware.php`.
- Register it in `app/Http/Kernel.php` under `$routeMiddleware`:

```php
'role' => \App\Http\Middleware\RoleMiddleware::class,
```

Seeded users
- admin@example.com / password
- teacher@example.com / password
- student@example.com / password

Notes
- Views and controllers are scaffolds to demonstrate features: dashboards per role, materials, tasks, and notifications UI.
- Extend controllers with CRUD, validation, file uploads, and live notifications as needed.
