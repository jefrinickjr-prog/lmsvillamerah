<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\MeetingAssignment;
use App\Models\MeetingSubmission;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeetingAssignmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $manager = $this->isManager();
        $classrooms = $manager
            ? Classroom::when($user->role === 'teacher', fn ($query) => $query->where('teacher_id', $user->id))->orderBy('title')->get()
            : collect();

        $assignments = MeetingAssignment::with(['classroom.teacher', 'submissions' => fn ($query) => $query->where('student_id', $user->id)])
            ->withCount('submissions')
            ->when($user->role === 'teacher', fn ($query) => $query->whereHas('classroom', fn ($classroom) => $classroom->where('teacher_id', $user->id)))
            ->when($user->role === 'student', fn ($query) => $this->studentAssignmentQuery($query, $user))
            ->latest('meeting_date')
            ->paginate(15);

        return view('meeting-assignments.index', compact('assignments', 'classrooms', 'manager'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isManager(), 403);
        $classroomIds = $this->manageableClassrooms()->pluck('id')->all();
        $data = $request->validate([
            'classroom_id' => ['required', Rule::in($classroomIds)],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'meeting_date' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:meeting_date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $assignment = MeetingAssignment::create($data + ['created_by' => Auth::id()]);
            $weekStart = $assignment->meeting_date->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();
            foreach ($this->studentsForClassroom($assignment->classroom) as $student) {
                Attendance::firstOrCreate([
                    'classroom_id' => $assignment->classroom_id,
                    'student_id' => $student->id,
                    'week_start' => $weekStart,
                ], [
                    'date' => $assignment->meeting_date->toDateString(),
                    'present' => false,
                ]);
            }
        });

        return redirect()->route('meeting-assignments.index')->with('success', 'Tugas pertemuan dibuat dan absensi awal siswa sudah disiapkan.');
    }

    public function show(MeetingAssignment $meetingAssignment)
    {
        $this->authorizeAssignment($meetingAssignment);
        $meetingAssignment->load(['classroom.teacher', 'submissions.student', 'submissions.grader']);
        $submission = Auth::user()->role === 'student'
            ? $meetingAssignment->submissions->firstWhere('student_id', Auth::id())
            : null;

        return view('meeting-assignments.show', compact('meetingAssignment', 'submission'));
    }

    public function submit(Request $request, MeetingAssignment $meetingAssignment)
    {
        abort_unless(Auth::user()?->role === 'student' && $this->studentCanAccess($meetingAssignment->classroom, Auth::user()), 403);
        if (now()->gt($meetingAssignment->due_at)) {
            throw ValidationException::withMessages(['work' => 'Batas pengumpulan tugas pertemuan telah berakhir.']);
        }
        $data = $request->validate([
            'work' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = $meetingAssignment->submissions()->where('student_id', Auth::id())->first();
        $path = $request->file('work')->store('meeting-works', 'local');

        DB::transaction(function () use ($meetingAssignment, $data, $existing, $path): void {
            $submission = MeetingSubmission::updateOrCreate([
                'meeting_assignment_id' => $meetingAssignment->id,
                'student_id' => Auth::id(),
            ], [
                'work_path' => $path,
                'note' => $data['note'] ?? null,
                'submitted_at' => now(),
                'score' => null,
                'feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]);

            Attendance::updateOrCreate([
                'classroom_id' => $meetingAssignment->classroom_id,
                'student_id' => Auth::id(),
                'week_start' => $meetingAssignment->meeting_date->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
            ], [
                'date' => $meetingAssignment->meeting_date->toDateString(),
                'present' => true,
            ]);
        });

        if ($existing && $existing->work_path !== $path) Storage::disk('local')->delete($existing->work_path);

        return back()->with('success', 'Karya berhasil dikumpulkan dan kehadiran Anda tercatat otomatis.');
    }

    public function grade(Request $request, MeetingSubmission $meetingSubmission)
    {
        $assignment = $meetingSubmission->assignment()->with('classroom')->firstOrFail();
        abort_unless($this->canManage($assignment->classroom), 403);
        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:'.$assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        $meetingSubmission->update($data + ['graded_by' => Auth::id(), 'graded_at' => now()]);

        return back()->with('success', 'Nilai karya siswa berhasil disimpan.');
    }

    public function file(MeetingSubmission $meetingSubmission)
    {
        $assignment = $meetingSubmission->assignment()->with('classroom')->firstOrFail();
        $allowed = (Auth::id() === $meetingSubmission->student_id) || $this->canManage($assignment->classroom);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($meetingSubmission->work_path), 404);

        return Storage::disk('local')->response($meetingSubmission->work_path);
    }

    public function destroy(MeetingAssignment $meetingAssignment)
    {
        abort_unless($this->canManage($meetingAssignment->classroom), 403);
        foreach ($meetingAssignment->submissions as $submission) Storage::disk('local')->delete($submission->work_path);
        $meetingAssignment->delete();

        return redirect()->route('meeting-assignments.index')->with('success', 'Tugas pertemuan berhasil dihapus. Rekap absensi manual tetap dipertahankan.');
    }

    private function isManager(): bool { return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true); }
    private function canManage(Classroom $classroom): bool { return $this->isManager() && (Auth::user()->role !== 'teacher' || $classroom->teacher_id === Auth::id()); }
    private function manageableClassrooms() { return Classroom::when(Auth::user()->role === 'teacher', fn ($query) => $query->where('teacher_id', Auth::id()))->get(); }
    private function authorizeAssignment(MeetingAssignment $assignment): void { abort_unless($this->canManage($assignment->classroom) || (Auth::user()->role === 'student' && $this->studentCanAccess($assignment->classroom, Auth::user())), 403); }

    private function studentCanAccess(Classroom $classroom, User $student): bool
    {
        return $classroom->program_type === User::normalizeProgramType($student->program_type)
            && in_array(User::normalizeStudentClass($classroom->title), User::studentClassLookupKeys($student->student_class), true)
            && in_array(User::normalizeBranch($classroom->branch), User::branchLookupKeys($student->branch), true);
    }

    private function studentAssignmentQuery($query, User $student)
    {
        $classes = User::studentClassLookupKeys($student->student_class);
        $branches = User::branchLookupKeys($student->branch);
        return $query->whereHas('classroom', function ($classroom) use ($student, $classes, $branches): void {
            $classroom->where('program_type', User::normalizeProgramType($student->program_type))
                ->where(fn ($q) => collect($classes)->each(fn ($key) => $q->orWhereRaw('LOWER(TRIM(title)) = ?', [$key])))
                ->where(fn ($q) => collect($branches)->each(fn ($key) => $q->orWhereRaw('LOWER(TRIM(branch)) = ?', [$key])));
        });
    }

    private function studentsForClassroom(Classroom $classroom)
    {
        return User::where('role', 'student')->get()->filter(fn (User $student) => $this->studentCanAccess($classroom, $student));
    }
}
