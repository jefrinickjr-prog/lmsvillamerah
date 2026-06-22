<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['material.classroom', 'material.classrooms'])
            ->when(Auth::user()?->role === 'student', function ($query) {
                $studentClassKeys = User::studentClassLookupKeys(Auth::user()?->student_class);
                $videoAccesses = Auth::user()?->videoAccesses() ?? [User::normalizeProgramType(Auth::user()?->program_type)];

                if ($studentClassKeys === []) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->whereHas('material', fn ($materialQuery) => $materialQuery->whereIn('program_type', $videoAccesses));
                $query->where(function ($taskQuery) use ($studentClassKeys) {
                    $taskQuery
                        ->whereHas('material.classrooms', fn ($classroomQuery) => $this->classroomTitleQuery($classroomQuery, $studentClassKeys))
                        ->orWhere(function ($fallbackQuery) use ($studentClassKeys) {
                            $fallbackQuery
                                ->whereDoesntHave('material.classrooms')
                                ->whereHas('material.classroom', fn ($classroomQuery) => $this->classroomTitleQuery($classroomQuery, $studentClassKeys));
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

        $taskTypes = Task::typeOptions();

        return view('tasks.create', compact('videos', 'taskTypes'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageTasks(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
            'task_type' => ['nullable', 'string', Rule::in(array_keys(Task::typeOptions()))],
            'due_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'questions' => ['nullable', 'array'],
            'questions.*.prompt' => ['nullable', 'string'],
            'questions.*.type' => ['nullable', 'string', Rule::in(['essay', 'multiple_choice', 'questionnaire'])],
            'questions.*.options' => ['nullable', 'string'],
        ]);

        if (! $this->availableVideos()->contains('id', (int) $data['material_id'])) {
            throw ValidationException::withMessages([
                'material_id' => 'Pilih video pembelajaran yang tersedia untuk akun Anda.',
            ]);
        }

        $data['task_type'] = $data['task_type'] ?? 'assignment';

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('task-attachments', 'public');
        }

        $data['questions'] = $this->normalizedQuestions($data['questions'] ?? []);

        Task::create($data);
        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat');
    }

    public function show(Task $task)
    {
        abort_unless($this->canViewTask($task), 403);

        $task->load(['material.classroom', 'material.classrooms']);

        return view('tasks.show', compact('task'));
    }

    private function canManageTasks(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function canViewTask(Task $task): bool
    {
        if ($this->canManageTasks()) {
            return true;
        }

        if (Auth::user()?->role !== 'student') {
            return false;
        }

        $studentClassKeys = User::studentClassLookupKeys(Auth::user()?->student_class);
        $videoAccesses = Auth::user()?->videoAccesses() ?? [User::normalizeProgramType(Auth::user()?->program_type)];

        if ($studentClassKeys === [] || ! in_array($task->material?->program_type, $videoAccesses, true)) {
            return false;
        }

        $task->loadMissing(['material.classroom', 'material.classrooms']);

        $classrooms = $task->material?->classrooms;
        if ($classrooms && $classrooms->isNotEmpty()) {
            return $classrooms->contains(function ($classroom) use ($studentClassKeys) {
                return in_array(User::normalizeStudentClass($classroom->title), $studentClassKeys, true);
            });
        }

        return in_array(User::normalizeStudentClass($task->material?->classroom?->title), $studentClassKeys, true);
    }

    private function availableVideos()
    {
        $query = Material::with(['classroom.teacher', 'classrooms.teacher'])->latest();

        if (Auth::user()?->role === 'teacher') {
            $query->where(function ($materialQuery) {
                $materialQuery
                    ->whereHas('classrooms', fn ($classroomQuery) => $classroomQuery->where('teacher_id', Auth::id()))
                    ->orWhereHas('classroom', fn ($classroomQuery) => $classroomQuery->where('teacher_id', Auth::id()));
            });
        }

        return $query->get();
    }

    private function classroomTitleQuery($query, array $studentClassKeys): void
    {
        $query->where(function ($titleQuery) use ($studentClassKeys) {
            foreach ($studentClassKeys as $studentClassKey) {
                $titleQuery->orWhereRaw('LOWER(TRIM(title)) = ?', [$studentClassKey]);
            }
        });
    }

    private function normalizedQuestions(array $questions): array
    {
        return collect($questions)
            ->map(function (array $question) {
                $prompt = trim((string) ($question['prompt'] ?? ''));

                if ($prompt === '') {
                    return null;
                }

                $type = $question['type'] ?? 'essay';
                $type = in_array($type, ['essay', 'multiple_choice', 'questionnaire'], true) ? $type : 'essay';
                $options = preg_split('/\r\n|\r|\n/', (string) ($question['options'] ?? ''));
                $options = array_values(array_filter(array_map('trim', $options)));

                return [
                    'type' => $type,
                    'prompt' => $prompt,
                    'options' => $type === 'multiple_choice' ? $options : [],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
