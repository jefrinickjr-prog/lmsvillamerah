<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Submission;
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
        $tasks = Task::with(['classrooms', 'material.classroom', 'material.classrooms', 'submissions'])
            ->when(Auth::user()?->role === 'student', function ($query) {
                $studentClassKeys = User::studentClassLookupKeys(Auth::user()?->student_class);

                if ($studentClassKeys === []) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->where(function ($taskQuery) use ($studentClassKeys) {
                    $taskQuery
                        ->whereHas('classrooms', fn ($classroomQuery) => $this->classroomTitleQuery($classroomQuery, $studentClassKeys))
                        ->orWhere(function ($fallbackQuery) use ($studentClassKeys) {
                            $fallbackQuery
                                ->doesntHave('classrooms')
                                ->whereHas('material.classrooms', fn ($classroomQuery) => $this->classroomTitleQuery($classroomQuery, $studentClassKeys));
                        })
                        ->orWhere(function ($fallbackQuery) use ($studentClassKeys) {
                            $fallbackQuery
                                ->doesntHave('classrooms')
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
        $classrooms = $this->availableClassrooms();

        $taskTypes = Task::typeOptions();

        return view('tasks.create', compact('videos', 'classrooms', 'taskTypes'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageTasks(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
            'task_type' => ['nullable', 'string', Rule::in(array_keys(Task::typeOptions()))],
            'due_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'questions' => ['nullable', 'array', 'max:20'],
            'questions.*.prompt' => ['nullable', 'string'],
            'questions.*.type' => ['nullable', 'string', Rule::in(['essay', 'multiple_choice', 'questionnaire'])],
            'questions.*.options' => ['nullable', 'string'],
        ]);

        if (! empty($data['material_id']) && ! $this->availableVideos()->contains('id', (int) $data['material_id'])) {
            throw ValidationException::withMessages([
                'material_id' => 'Pilih video pembelajaran yang tersedia untuk akun Anda.',
            ]);
        }

        $classroomIds = array_values(array_unique(array_map('intval', $data['classroom_ids'] ?? [])));
        if ($classroomIds === [] && ! empty($data['material_id'])) {
            $material = Material::with('classrooms')->find($data['material_id']);
            $classroomIds = $material?->classrooms->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
            if ($classroomIds === [] && $material?->classroom_id) {
                $classroomIds = [(int) $material->classroom_id];
            }
        }
        $availableClassroomIds = $this->availableClassrooms()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($classroomIds === [] || array_diff($classroomIds, $availableClassroomIds) !== []) {
            throw ValidationException::withMessages([
                'classroom_ids' => 'Pilih kelas yang tersedia untuk akun Anda.',
            ]);
        }

        $data['task_type'] = $data['task_type'] ?? 'assignment';

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('task-attachments', 'public');
        }

        $data['questions'] = $this->normalizedQuestions($data['questions'] ?? []);
        $data['material_id'] = $data['material_id'] ?? null;
        unset($data['classroom_ids']);

        $task = Task::create($data);
        $task->classrooms()->sync($classroomIds);
        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat');
    }

    public function show(Task $task)
    {
        abort_unless($this->canViewTask($task), 403);

        $task->load(['classrooms', 'material.classroom', 'material.classrooms']);
        $submission = Auth::user()?->role === 'student'
            ? Submission::where('task_id', $task->id)->where('student_id', Auth::id())->first()
            : null;

        return view('tasks.show', compact('task', 'submission'));
    }

    public function downloadAttachment(Task $task)
    {
        abort_unless($this->canViewTask($task), 403);
        abort_unless($task->attachment_path && Storage::disk('public')->exists($task->attachment_path), 404);

        return Storage::disk('public')->download($task->attachment_path);
    }

    public function submit(Request $request, Task $task)
    {
        abort_unless(Auth::user()?->role === 'student' && $this->canViewTask($task), 403);

        $data = $request->validate([
            'answers' => ['nullable', 'array'],
            'content' => ['nullable', 'string'],
        ]);

        Submission::updateOrCreate(
            [
                'task_id' => $task->id,
                'student_id' => Auth::id(),
            ],
            [
                'answers' => $data['answers'] ?? [],
                'content' => $data['content'] ?? null,
            ]
        );

        return redirect()->route('tasks.show', $task)->with('success', 'Jawaban berhasil dikirim.');
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

        if ($studentClassKeys === []) {
            return false;
        }

        $task->loadMissing(['classrooms', 'material.classroom', 'material.classrooms']);

        if ($task->classrooms->isNotEmpty()) {
            return $task->classrooms->contains(function ($classroom) use ($studentClassKeys) {
                return in_array(User::normalizeStudentClass($classroom->title), $studentClassKeys, true);
            });
        }

        $classrooms = $task->material?->classrooms;
        if ($classrooms && $classrooms->isNotEmpty()) {
            return $classrooms->contains(function ($classroom) use ($studentClassKeys) {
                return in_array(User::normalizeStudentClass($classroom->title), $studentClassKeys, true);
            });
        }

        return in_array(User::normalizeStudentClass($task->material?->classroom?->title), $studentClassKeys, true);
    }

    private function availableClassrooms()
    {
        $query = \App\Models\Classroom::with('teacher')->latest();

        if (Auth::user()?->role === 'teacher') {
            $query->where('teacher_id', Auth::id());
        }

        return $query->get();
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
