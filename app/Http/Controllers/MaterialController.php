<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    public function index()
    {
        $programTypes = User::programTypeOptions();
        $selectedProgramType = Auth::user()?->role === 'student'
            ? User::normalizeProgramType(Auth::user()?->program_type)
            : User::normalizeProgramType(request('program_type', 'gambar'));

        $hasProgramTypeColumn = Schema::hasColumn('materials', 'program_type');

        $materials = Material::with('classroom')
            ->when($hasProgramTypeColumn, fn ($query) => $query->where('program_type', $selectedProgramType))
            ->when(Auth::user()?->role === 'student', function ($query) {
                $studentClassKeys = User::studentClassLookupKeys(Auth::user()?->student_class);

                if ($studentClassKeys === []) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->whereHas('classroom', function ($classroomQuery) use ($studentClassKeys) {
                    $classroomQuery->where(function ($titleQuery) use ($studentClassKeys) {
                        foreach ($studentClassKeys as $studentClassKey) {
                            $titleQuery->orWhereRaw('LOWER(TRIM(title)) = ?', [$studentClassKey]);
                        }
                    });
                });
            })
            ->latest()
            ->get();

        return view('materials.index', compact('materials', 'programTypes', 'selectedProgramType'));
    }

    public function create()
    {
        abort_unless($this->canManageMaterials(), 403);

        $classrooms = $this->availableClassrooms();
        $programTypes = User::programTypeOptions();

        return view('materials.create', compact('classrooms', 'programTypes'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageMaterials(), 403);

        $data = $this->validatedVideoData($request);

        Material::create($data);

        return redirect()->route('materials.index')->with('success', 'Video pembelajaran berhasil dibuat');
    }

    public function edit(Material $material)
    {
        abort_unless($this->canManageMaterial($material), 403);

        $classrooms = $this->availableClassrooms();
        $programTypes = User::programTypeOptions();

        return view('materials.edit', compact('material', 'classrooms', 'programTypes'));
    }

    public function update(Request $request, Material $material)
    {
        abort_unless($this->canManageMaterial($material), 403);

        $material->update($this->validatedVideoData($request));

        return redirect()->route('materials.index')->with('success', 'Video pembelajaran berhasil diperbarui');
    }

    public function destroy(Material $material)
    {
        abort_unless($this->canManageMaterial($material), 403);

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Video pembelajaran berhasil dihapus');
    }

    private function canManageMaterials(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function canManageMaterial(Material $material): bool
    {
        if (! $this->canManageMaterials()) {
            return false;
        }

        if (in_array(Auth::user()?->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return $material->classroom?->teacher_id === Auth::id();
    }

    private function availableClassrooms()
    {
        $query = Classroom::with('teacher')->latest();

        if (Auth::user()?->role === 'teacher') {
            $query->where('teacher_id', Auth::id());
        }

        return $query->get();
    }

    private function validatedVideoData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'program_type' => ['nullable', 'string', Rule::in(array_keys(User::programTypeOptions()))],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'youtube_embed_url' => ['required', 'string', 'max:2048'],
        ]);
        $data['program_type'] = User::normalizeProgramType($data['program_type'] ?? null);

        $classroom = $this->availableClassrooms()->firstWhere('id', (int) $data['classroom_id']);

        if (! $classroom) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Pilih kelas yang tersedia untuk akun Anda.',
            ]);
        }

        if (User::normalizeProgramType($classroom->program_type) !== $data['program_type']) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Kelas harus berada di grup program yang sama dengan video.',
            ]);
        }

        $youtubeEmbedUrl = $this->normalizeYoutubeEmbedUrl($data['youtube_embed_url'] ?? null);

        if (! $youtubeEmbedUrl) {
            throw ValidationException::withMessages([
                'youtube_embed_url' => 'Masukkan link YouTube yang valid.',
            ]);
        }

        $data['youtube_embed_url'] = $youtubeEmbedUrl;

        return $data;
    }

    private function normalizeYoutubeEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/src=["\']([^"\']+)["\']/i', $url, $matches)) {
            $url = $matches[1];
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower(str_replace('www.', '', $parts['host']));
        $path = trim($parts['path'] ?? '', '/');
        $videoId = null;

        if (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str($parts['query'] ?? '', $query);
                $videoId = $query['v'] ?? null;
            } elseif (str_starts_with($path, 'embed/')) {
                $videoId = explode('/', $path)[1] ?? null;
            } elseif (str_starts_with($path, 'shorts/')) {
                $videoId = explode('/', $path)[1] ?? null;
            }
        } elseif ($host === 'youtu.be') {
            $videoId = explode('/', $path)[0] ?? null;
        }

        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$videoId;
    }
}
