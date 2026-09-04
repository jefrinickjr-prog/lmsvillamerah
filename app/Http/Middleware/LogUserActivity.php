<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();
        $routeName = $request->route()?->getName();

        $ignoredRoutes = ['attempts.autosave', 'login.post', 'logout'];
        if ($user && ! $request->isMethodSafe() && $routeName && ! in_array($routeName, $ignoredRoutes, true)
            && $response->getStatusCode() < 400 && Schema::hasTable('activity_logs')) {
            $module = $this->module($routeName);
            $event = $this->event($request->method(), $routeName);
            ActivityLog::create([
                'user_id' => $user->id,
                'event' => $event,
                'module' => $module,
                'description' => $this->description($user->name, $event, $module),
                'route_name' => $routeName,
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'metadata' => ['parameters' => collect($request->route()?->parameters() ?? [])->map(fn ($value) => is_object($value) ? $value->getKey() : $value)->all()],
            ]);
        }

        return $response;
    }

    private function module(string $route): string
    {
        return match (true) {
            str_starts_with($route, 'classrooms.') => 'Kelas',
            str_starts_with($route, 'academic-settings.') => 'Akademik',
            str_starts_with($route, 'meeting-') => 'Tugas Pertemuan',
            str_starts_with($route, 'live-streams.') => 'Live Streaming',
            str_starts_with($route, 'questions.') => 'Bank Soal',
            str_starts_with($route, 'exams.'), str_starts_with($route, 'attempts.') => 'Ujian',
            str_starts_with($route, 'attendances.') => 'Absensi',
            str_starts_with($route, 'materials.') => 'Video',
            str_starts_with($route, 'profile.') => 'Profil',
            $route === 'register.post', str_starts_with($route, 'students.') => 'Siswa',
            default => 'Sistem',
        };
    }

    private function event(string $method, string $route): string
    {
        if (str_contains($route, 'submit')) return 'mengumpulkan';
        if (str_contains($route, 'start')) return 'memulai';
        if (str_contains($route, 'end')) return 'mengakhiri';
        if ($method === 'DELETE') return 'menghapus';
        if (in_array($method, ['PUT', 'PATCH'], true)) return 'memperbarui';
        return 'menambahkan';
    }

    private function description(string $name, string $event, string $module): string
    {
        return "{$name} {$event} data {$module}.";
    }
}
