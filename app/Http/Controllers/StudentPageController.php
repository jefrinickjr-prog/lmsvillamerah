<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Attempt;
use App\Models\Classroom;
use App\Models\Material;
use App\Models\MeetingAssignment;
use App\Models\MeetingSubmission;
use App\Models\Submission;
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

        $meetingSubmissions = MeetingSubmission::with(['assignment.classroom', 'grader'])
            ->where('student_id', Auth::id())
            ->latest('submitted_at')
            ->get();
        $meetingAverage = $meetingSubmissions->whereNotNull('score')->map(
            fn ($submission) => $submission->assignment->max_score > 0
                ? ($submission->score / $submission->assignment->max_score) * 100
                : null
        )->filter(fn ($score) => $score !== null)->avg();

        return view('student.grades', compact('submissions', 'averageScore', 'meetingSubmissions', 'meetingAverage'));
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

        $student = Auth::user();
        $classrooms = $student->activeClassrooms()
            ->where('classrooms.is_active', true)
            ->orderByDesc('classroom_enrollments.joined_at')
            ->orderByDesc('classroom_enrollments.id')
            ->limit(1)
            ->get();
        if ($classrooms->isEmpty() && ($fallbackClassroom = $this->studentClassroom($student))) {
            $classrooms = collect([$fallbackClassroom]);
        }

        $classroomIds = $classrooms->pluck('id');
        $assignments = MeetingAssignment::with('classroom')
            ->whereIn('classroom_id', $classroomIds)
            ->latest('meeting_date')
            ->get();
        $meetingSubmissions = MeetingSubmission::with(['assignment.classroom', 'grader'])
            ->where('student_id', $student->id)
            ->whereHas('assignment', fn ($query) => $query->whereIn('classroom_id', $classroomIds))
            ->latest('submitted_at')
            ->get();
        $submissionByAssignment = $meetingSubmissions->keyBy('meeting_assignment_id');
        $attempts = Attempt::with(['exam.questions'])
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->latest('submitted_at')
            ->get();
        $attendances = Attendance::where('student_id', $student->id)->latest('date')->get();

        $tasksCount = $assignments->count();
        $submittedCount = $assignments->whereIn('id', $meetingSubmissions->pluck('meeting_assignment_id'))->count();
        $pendingTasksCount = max(0, $tasksCount - $submittedCount);
        $assignmentRate = $tasksCount > 0 ? (int) round(($submittedCount / $tasksCount) * 100) : 0;
        $materialsCount = Material::where(fn ($query) => $query
            ->whereIn('classroom_id', $classroomIds)
            ->orWhereHas('classrooms', fn ($classroom) => $classroom->whereIn('classrooms.id', $classroomIds)))
            ->count();

        $scoreItems = $meetingSubmissions->whereNotNull('score')->map(function ($submission) {
            $maximum = max(1, (float) $submission->assignment->max_score);
            return [
                'label' => $submission->assignment->title,
                'type' => 'Karya',
                'date' => $submission->graded_at ?? $submission->submitted_at,
                'score' => round(((float) $submission->score / $maximum) * 100, 1),
                'display' => $submission->score.'/'.$submission->assignment->max_score,
            ];
        })->merge($attempts->whereNotNull('score')->map(function ($attempt) {
            $maximum = max(1, (float) $attempt->exam->questions->sum('score'));
            return [
                'label' => $attempt->exam->title,
                'type' => 'Ujian',
                'date' => $attempt->submitted_at ?? $attempt->updated_at,
                'score' => round(((float) $attempt->score / $maximum) * 100, 1),
                'display' => number_format(((float) $attempt->score / $maximum) * 100, 1),
            ];
        }))->sortByDesc('date')->values();
        $averageScore = $scoreItems->avg('score');
        $attendanceRate = $attendances->count() > 0
            ? round(($attendances->where('present', true)->count() / $attendances->count()) * 100)
            : 0;
        $presentCount = $attendances->where('present', true)->count();
        $gradedCount = $scoreItems->count();
        $scoreTrend = $scoreItems->take(8)->reverse()->values();
        $recentAssignments = $assignments->take(6)->map(fn ($assignment) => [
            'assignment' => $assignment,
            'submission' => $submissionByAssignment->get($assignment->id),
        ]);

        return view('student.reports', compact(
            'tasksCount',
            'materialsCount',
            'submittedCount',
            'averageScore',
            'attendanceRate',
            'pendingTasksCount',
            'assignmentRate',
            'presentCount',
            'gradedCount',
            'scoreTrend',
            'recentAssignments',
            'attendances'
        ));
    }

    private function authorizeStudentPage(): void
    {
        abort_unless(Auth::user()?->role === 'student', 403);
    }

    private function studentClassroom(User $student): ?Classroom
    {
        $enrolledClassroom = $student->activeClassrooms()
            ->where('classrooms.is_active', true)
            ->orderByDesc('classroom_enrollments.joined_at')
            ->orderByDesc('classroom_enrollments.id')
            ->first();
        if ($enrolledClassroom) {
            return $enrolledClassroom;
        }

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

}
