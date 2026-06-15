<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private const CLASS_DAYS = [
        CarbonInterface::SATURDAY,
        CarbonInterface::SUNDAY,
    ];

    public function index(Request $request): View
    {
        abort_unless($this->canViewAttendanceReport(), 403);

        $classrooms = $this->availableClassrooms();
        $selectedClassroom = $this->selectedClassroom($request, $classrooms);
        $weekStart = $this->selectedWeekStart($request);
        $weekEnd = $weekStart->copy()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay();

        $students = collect();
        $attendanceByStudent = collect();

        if ($selectedClassroom) {
            $classKeys = User::studentClassLookupKeys($selectedClassroom->title);
            $branchKeys = User::branchLookupKeys($selectedClassroom->branch);
            $students = User::where('role', 'student')
                ->where(function ($query) use ($classKeys) {
                    foreach ($classKeys as $classKey) {
                        $query->orWhereRaw('LOWER(TRIM(student_class)) = ?', [$classKey]);
                    }
                })
                ->where(function ($query) use ($branchKeys) {
                    foreach ($branchKeys as $branchKey) {
                        $query->orWhereRaw('LOWER(TRIM(branch)) = ?', [$branchKey]);
                    }
                })
                ->orderBy('name')
                ->get();

            $attendanceByStudent = Attendance::with('student')
                ->where('classroom_id', $selectedClassroom->id)
                ->where(function ($query) use ($weekStart, $weekEnd) {
                    $query->whereDate('week_start', $weekStart->toDateString())
                        ->orWhere(function ($legacyQuery) use ($weekStart, $weekEnd) {
                            $legacyQuery->whereNull('week_start')
                                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()]);
                        });
                })
                ->get()
                ->keyBy('student_id');
        }

        $presentCount = $attendanceByStudent->where('present', true)->count();
        $totalStudents = $students->count();
        $attendanceRate = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100) : 0;

        return view('attendances.index', compact(
            'classrooms',
            'selectedClassroom',
            'weekStart',
            'weekEnd',
            'students',
            'attendanceByStudent',
            'presentCount',
            'totalStudents',
            'attendanceRate'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canViewAttendanceReport(), 403);

        $classrooms = $this->availableClassrooms();
        $classroom = $classrooms->firstWhere('id', (int) $request->input('classroom_id'));

        if (! $classroom) {
            abort(403);
        }

        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'week' => ['required', 'date'],
            'present_students' => ['nullable', 'array'],
            'present_students.*' => ['integer', 'exists:users,id'],
        ]);

        $weekStart = Carbon::parse($data['week'], config('app.timezone'))->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $attendanceDate = $this->attendanceDateForWeek($weekStart);
        $presentStudentIds = collect($data['present_students'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $allowedStudentIds = $this->studentsForClassroom($classroom)->pluck('id');

        if ($presentStudentIds->diff($allowedStudentIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'present_students' => 'Daftar siswa tidak sesuai dengan kelas dan cabang yang dipilih.',
            ]);
        }

        foreach ($allowedStudentIds as $studentId) {
            Attendance::updateOrCreate(
                [
                    'classroom_id' => $classroom->id,
                    'student_id' => $studentId,
                    'week_start' => $weekStart->toDateString(),
                ],
                [
                    'date' => $attendanceDate->toDateString(),
                    'present' => $presentStudentIds->contains($studentId),
                ]
            );
        }

        return redirect()
            ->route('attendances.index', ['classroom_id' => $classroom->id, 'week' => $weekStart->toDateString()])
            ->with('success', 'Absensi siswa berhasil diperbarui.');
    }

    private function canViewAttendanceReport(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function availableClassrooms()
    {
        $query = Classroom::with('teacher')->latest();

        if (Auth::user()?->role === 'teacher') {
            $query->where('teacher_id', Auth::id());
        }

        return $query->get();
    }

    private function selectedClassroom(Request $request, $classrooms): ?Classroom
    {
        $requestedClassroomId = (int) $request->query('classroom_id');

        if ($classrooms->isEmpty()) {
            if ($requestedClassroomId) {
                abort(403);
            }

            return null;
        }

        if (! $requestedClassroomId) {
            return $classrooms->first();
        }

        return $classrooms->firstWhere('id', $requestedClassroomId) ?? abort(403);
    }

    private function selectedWeekStart(Request $request): Carbon
    {
        $date = $request->query('week');

        try {
            $selected = $date ? Carbon::parse($date, config('app.timezone')) : $this->today();
        } catch (\Throwable) {
            $selected = $this->today();
        }

        return $selected->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
    }

    private function studentsForClassroom(Classroom $classroom)
    {
        $classKeys = User::studentClassLookupKeys($classroom->title);
        $branchKeys = User::branchLookupKeys($classroom->branch);

        return User::where('role', 'student')
            ->where(function ($query) use ($classKeys) {
                foreach ($classKeys as $classKey) {
                    $query->orWhereRaw('LOWER(TRIM(student_class)) = ?', [$classKey]);
                }
            })
            ->where(function ($query) use ($branchKeys) {
                foreach ($branchKeys as $branchKey) {
                    $query->orWhereRaw('LOWER(TRIM(branch)) = ?', [$branchKey]);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function attendanceDateForWeek(Carbon $weekStart): Carbon
    {
        $today = $this->today();
        $weekEnd = $weekStart->copy()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay();

        if ($today->betweenIncluded($weekStart, $weekEnd) && $this->isClassDay($today)) {
            return $today;
        }

        return $weekStart->copy()->next(CarbonInterface::SATURDAY)->startOfDay();
    }

    private function today(): Carbon
    {
        return Carbon::now(config('app.timezone'))->startOfDay();
    }

    private function isClassDay(Carbon $date): bool
    {
        return in_array($date->dayOfWeek, self::CLASS_DAYS, true);
    }
}
