<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'program_type', 'video_accesses', 'student_class', 'branch', 'academic_year', 'student_code', 'photo_path', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const PROGRAM_TYPES = [
        'gambar' => 'Gambar',
        'skolastik' => 'Skolastik',
    ];

    public const VIDEO_ACCESS_OPTIONS = [
        'gambar' => 'Video Tutorial Gambar',
        'skolastik' => 'Video Pembahasan Skolastik',
    ];

    public const STUDENT_CLASSES = [
        'gambar' => [
            'SR Gold',
            'SR Minat Seni',
            'SR Silver',
            'SR Advance',
            'SR Intermediate',
            'SR SMP',
        ],
        'skolastik' => [
            'Skolastik Dasar',
            'Skolastik Lanjutan',
            'Skolastik Intensif',
        ],
    ];

    public const BRANCHES = [
        'Bandung',
        'Jakarta Pusat',
        'Jakarta Selatan',
    ];

    public static function programTypeOptions(): array
    {
        return self::PROGRAM_TYPES;
    }

    public static function videoAccessOptions(): array
    {
        return self::VIDEO_ACCESS_OPTIONS;
    }

    public static function normalizeProgramType(?string $programType): string
    {
        return array_key_exists($programType, self::PROGRAM_TYPES) ? $programType : 'gambar';
    }

    public static function programTypeLabel(?string $programType): string
    {
        return self::PROGRAM_TYPES[self::normalizeProgramType($programType)] ?? self::PROGRAM_TYPES['gambar'];
    }

    public static function videoAccessLabel(?string $programType): string
    {
        return self::VIDEO_ACCESS_OPTIONS[self::normalizeProgramType($programType)] ?? self::VIDEO_ACCESS_OPTIONS['gambar'];
    }

    public static function defaultVideoAccesses(?string $programType = null, ?string $studentClass = null): array
    {
        $studentClass = self::normalizeStudentClass($studentClass);

        if ($studentClass === 'sr gold') {
            return array_keys(self::VIDEO_ACCESS_OPTIONS);
        }

        return [self::normalizeProgramType($programType)];
    }

    public static function normalizeVideoAccesses(mixed $accesses, ?string $programType = null, ?string $studentClass = null): array
    {
        if (is_string($accesses)) {
            $decoded = json_decode($accesses, true);
            $accesses = is_array($decoded) ? $decoded : [$accesses];
        }

        if (! is_array($accesses) || $accesses === []) {
            return self::defaultVideoAccesses($programType, $studentClass);
        }

        $valid = array_values(array_unique(array_filter(
            $accesses,
            fn ($access) => is_string($access) && array_key_exists($access, self::VIDEO_ACCESS_OPTIONS)
        )));

        return $valid ?: self::defaultVideoAccesses($programType, $studentClass);
    }

    public function videoAccesses(): array
    {
        return self::normalizeVideoAccesses($this->video_accesses ?? null, $this->program_type ?? null, $this->student_class ?? null);
    }

    public static function studentClassOptions(?string $programType = null): array
    {
        if ($programType !== null) {
            return self::STUDENT_CLASSES[self::normalizeProgramType($programType)] ?? [];
        }

        return array_values(array_merge(...array_values(self::STUDENT_CLASSES)));
    }

    public static function branchOptions(): array
    {
        return self::BRANCHES;
    }

    public static function currentAcademicYear(): string
    {
        $year = (int) now(config('app.timezone'))->format('Y');
        $month = (int) now(config('app.timezone'))->format('n');
        $startYear = $month >= 7 ? $year : $year - 1;

        return $startYear.'-'.($startYear + 1);
    }

    public static function normalizeBranch(?string $branch): ?string
    {
        $branch = trim((string) $branch);

        if ($branch === '') {
            return null;
        }

        return strtolower(preg_replace('/\s+/', ' ', $branch));
    }

    public static function branchLookupKeys(?string $branch): array
    {
        $normalized = self::normalizeBranch($branch);

        return $normalized ? [$normalized] : [];
    }

    public static function normalizeStudentClass(?string $studentClass): ?string
    {
        $studentClass = trim((string) $studentClass);

        if ($studentClass === '') {
            return null;
        }

        $normalized = strtolower(preg_replace('/\s+/', ' ', $studentClass));

        return match ($normalized) {
            'sr sirver' => 'sr silver',
            default => $normalized,
        };
    }

    public static function studentClassLookupKeys(?string $studentClass): array
    {
        $normalized = self::normalizeStudentClass($studentClass);

        if (! $normalized) {
            return [];
        }

        $keys = [$normalized];

        if ($normalized === 'sr silver') {
            $keys[] = 'sr sirver';
        }

        return array_values(array_unique($keys));
    }

    public static function makeStudentCode(string $academicYear, string $branch, string $studentClass, int $sequence): string
    {
        $yearCode = substr($academicYear, 2, 2).substr($academicYear, 7, 2);
        $branchCode = collect(explode(' ', strtoupper($branch)))
            ->map(fn (string $word) => substr($word, 0, 1))
            ->implode('');
        $classCode = str_replace('SR ', '', strtoupper($studentClass));
        $classCode = preg_replace('/[^A-Z0-9]/', '', $classCode) ?: 'SR';

        return $yearCode.'-'.$branchCode.'-'.$classCode.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'video_accesses' => 'array',
        ];
    }
}
