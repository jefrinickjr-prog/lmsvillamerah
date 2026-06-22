<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Material;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentPageController extends Controller
{
    public function grades()
    {
        $this->authorizeStudentPage();

        $submissions = Submission::with('task.material')
            ->where('student_id', Auth::id())
            ->latest()
            ->get();

        $averageScore = $submissions
            ->whereNotNull('score')
            ->avg('score');

        return view('student.grades', compact('submissions', 'averageScore'));
    }

    public function attendance()
    {
        $this->authorizeStudentPage();

        $today = Carbon::now(config('app.timezone'))->startOfDay();
        $weekStart = $today->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $weekEnd = $today->copy()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay();
        $isClassDay = in_array($today->dayOfWeek, [CarbonInterface::SATURDAY, CarbonInterface::SUNDAY], true);
        $classroom = $this->studentClassroom(Auth::user());
        $currentWeekAttendance = Attendance::where('student_id', Auth::id())
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->whereDate('week_start', $weekStart->toDateString())
                    ->orWhere(function ($legacyQuery) use ($weekStart, $weekEnd) {
                        $legacyQuery->whereNull('week_start')
                            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()]);
                    });
            })
            ->first();
        $canSubmitAttendance = $classroom && $isClassDay && ! $currentWeekAttendance;

        $attendances = Attendance::with('classroom')
            ->where('student_id', Auth::id())
            ->latest('date')
            ->get();

        $presentCount = $attendances->where('present', true)->count();
        $totalCount = $attendances->count();
        $attendanceRate = $totalCount > 0 ? round(($presentCount / $totalCount) * 100) : 0;

        return view('student.attendance', compact(
            'attendances',
            'presentCount',
            'totalCount',
            'attendanceRate',
            'today',
            'weekStart',
            'weekEnd',
            'isClassDay',
            'classroom',
            'currentWeekAttendance',
            'canSubmitAttendance'
        ));
    }

    public function reports()
    {
        $this->authorizeStudentPage();

        $studentClass = Auth::user()?->student_class;
        $studentClassKeys = User::studentClassLookupKeys($studentClass);
        $videoAccesses = Auth::user()?->videoAccesses() ?? [User::normalizeProgramType(Auth::user()?->program_type)];
        $submissions = Submission::where('student_id', Auth::id())->get();
        $attendances = Attendance::where('student_id', Auth::id())->get();
        $tasksCount = Task::where(function ($query) use ($studentClassKeys) {
                $query
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
            })
            ->count();
        $materialsCount = Material::whereIn('program_type', $videoAccesses)
            ->where(function ($query) use ($studentClassKeys) {
                $query
                    ->whereHas('classrooms', fn ($classroomQuery) => $this->classroomTitleQuery($classroomQuery, $studentClassKeys))
                    ->orWhere(function ($fallbackQuery) use ($studentClassKeys) {
                        $fallbackQuery
                            ->doesntHave('classrooms')
                            ->whereHas('classroom', fn ($classroomQuery) => $this->classroomTitleQuery($classroomQuery, $studentClassKeys));
                    });
            })
            ->count();
        $submittedCount = $submissions->count();
        $averageScore = $submissions->whereNotNull('score')->avg('score');
        $attendanceRate = $attendances->count() > 0
            ? round(($attendances->where('present', true)->count() / $attendances->count()) * 100)
            : 0;

        return view('student.reports', compact(
            'tasksCount',
            'materialsCount',
            'submittedCount',
            'averageScore',
            'attendanceRate'
        ));
    }

    private function authorizeStudentPage(): void
    {
        abort_unless(Auth::user()?->role === 'student', 403);
    }

    private function studentClassroom(User $student): ?Classroom
    {
        $studentClassKeys = User::studentClassLookupKeys($student->student_class);
        $branchKeys = User::branchLookupKeys($student->branch);

        if ($studentClassKeys === [] || $branchKeys === []) {
            return null;
        }

        return Classroom::where(function ($query) use ($studentClassKeys) {
            foreach ($studentClassKeys as $studentClassKey) {
                $query->orWhereRaw('LOWER(TRIM(title)) = ?', [$studentClassKey]);
            }
        })->where('program_type', User::normalizeProgramType($student->program_type))
        ->where(function ($query) use ($branchKeys) {
            foreach ($branchKeys as $branchKey) {
                $query->orWhereRaw('LOWER(TRIM(branch)) = ?', [$branchKey]);
            }
        })->first();
    }

    private function classroomTitleQuery($query, array $studentClassKeys): void
    {
        if ($studentClassKeys === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($titleQuery) use ($studentClassKeys) {
            foreach ($studentClassKeys as $studentClassKey) {
                $titleQuery->orWhereRaw('LOWER(TRIM(title)) = ?', [$studentClassKey]);
            }
        });
    }
}
