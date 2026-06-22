<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with('material.classroom')
            ->when(Auth::user()?->role === 'student', function ($query) {
                $studentClassKeys = User::studentClassLookupKeys(Auth::user()?->student_class);
                $videoAccesses = Auth::user()?->videoAccesses() ?? [User::normalizeProgramType(Auth::user()?->program_type)];

                if ($studentClassKeys === []) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->whereHas('material', fn ($materialQuery) => $materialQuery->whereIn('program_type', $videoAccesses));
                $query->whereHas('material.classroom', function ($classroomQuery) use ($studentClassKeys) {
                    $classroomQuery->where(function ($titleQuery) use ($studentClassKeys) {
                        foreach ($studentClassKeys as $studentClassKey) {
                            $titleQuery->orWhereRaw('LOWER(TRIM(title)) = ?', [$studentClassKey]);
                        }
                    });
                });
            })
            ->latest()
            ->get();

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        abort_unless($this->canManageTasks(), 403);

        $videos = $this->availableVideos();

        return view('tasks.create', compact('videos'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageTasks(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
        ]);

        if (! $this->availableVideos()->contains('id', (int) $data['material_id'])) {
            throw ValidationException::withMessages([
                'material_id' => 'Pilih video pembelajaran yang tersedia untuk akun Anda.',
            ]);
        }

        Task::create($data);
        return redirect()->route('tasks.index')->with('success', 'Task created');
    }

    private function canManageTasks(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function availableVideos()
    {
        $query = Material::with('classroom.teacher')->latest();

        if (Auth::user()?->role === 'teacher') {
            $query->whereHas('classroom', fn ($classroomQuery) => $classroomQuery->where('teacher_id', Auth::id()));
        }

        return $query->get();
    }
}
