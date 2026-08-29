<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\ClassroomSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassroomRosterController extends Controller
{
    public function show(Classroom $classroom)
    {
        $this->authorizeClassroom($classroom);
        $classroom->load(['program.category','branchMaster','academicPeriod','teacher','schedules','activeEnrollments.student']);
        $enrolledIds = $classroom->activeEnrollments->pluck('student_id');
        $availableStudents = User::where('role','student')->whereNotIn('id',$enrolledIds)
            ->when($classroom->program_type, fn($q)=>$q->where('program_type',$classroom->program_type))
            ->when($classroom->branch, fn($q)=>$q->whereRaw('LOWER(TRIM(branch)) = ?', [User::normalizeBranch($classroom->branch)]))
            ->orderBy('name')->get();
        return view('classrooms.roster', compact('classroom','availableStudents'));
    }

    public function enroll(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($classroom);
        $data = $request->validate(['student_id'=>'required|exists:users,id','notes'=>'nullable|string|max:1000']);
        $student = User::where('role','student')->findOrFail($data['student_id']);
        abort_if($classroom->activeEnrollments()->count() >= $classroom->capacity, 422, 'Kapasitas kelas sudah penuh.');

        DB::transaction(function() use ($classroom,$student,$data): void {
            $otherQuery = ClassroomEnrollment::where('student_id',$student->id)->where('status','active')->where('classroom_id','!=',$classroom->id);
            if ($classroom->class_program_id) $otherQuery->whereHas('classroom', fn($q)=>$q->where('class_program_id',$classroom->class_program_id));
            else $otherQuery->whereHas('classroom', fn($q)=>$q->whereRaw('LOWER(TRIM(title)) = ?', [User::normalizeStudentClass($classroom->title)]));
            $otherQuery->update(['status'=>'transferred','left_at'=>now(),'updated_at'=>now()]);
            ClassroomEnrollment::updateOrCreate(['classroom_id'=>$classroom->id,'student_id'=>$student->id],[
                'status'=>'active','joined_at'=>now(),'left_at'=>null,'assigned_by'=>Auth::id(),'notes'=>$data['notes']??null,
            ]);
            $student->update([
                'student_class'=>$classroom->program?->name ?: $classroom->title,
                'branch'=>$classroom->branchMaster?->name ?: $classroom->branch,
                'academic_year'=>$classroom->academicPeriod?->code ?: $student->academic_year,
                'program_type'=>$classroom->program?->category?->code ?: $classroom->program_type,
                'delivery_mode'=>$classroom->delivery_mode,
            ]);
        });
        return back()->with('success', 'Siswa berhasil ditempatkan ke '.$classroom->display_name.'.');
    }

    public function remove(Request $request, Classroom $classroom, User $student)
    {
        $this->authorizeClassroom($classroom);
        ClassroomEnrollment::where('classroom_id',$classroom->id)->where('student_id',$student->id)->where('status','active')->update(['status'=>'inactive','left_at'=>now(),'notes'=>$request->input('notes'),'updated_at'=>now()]);
        return back()->with('success', 'Siswa dikeluarkan dari kelas pertemuan tanpa menghapus riwayat.');
    }

    public function storeSchedule(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($classroom);
        $data = $request->validate(['day_of_week'=>['required','integer',Rule::in(array_keys(ClassroomSchedule::DAYS))],'starts_at'=>'required|date_format:H:i','ends_at'=>'required|date_format:H:i|after:starts_at','room'=>'nullable|string|max:255']);
        $conflict = ClassroomSchedule::where('day_of_week',$data['day_of_week'])->where('id','!=',0)
            ->whereHas('classroom', fn($q)=>$q->where('teacher_id',$classroom->teacher_id)->where('is_active',true))
            ->where('starts_at','<',$data['ends_at'])->where('ends_at','>',$data['starts_at'])->exists();
        if ($conflict) return back()->withErrors(['starts_at'=>'Jadwal mentor bertabrakan dengan kelas lain.'])->withInput();
        $classroom->schedules()->create($data);
        return back()->with('success', 'Jadwal kelas berhasil ditambahkan.');
    }

    public function destroySchedule(Classroom $classroom, ClassroomSchedule $schedule)
    {
        $this->authorizeClassroom($classroom);
        abort_unless($schedule->classroom_id === $classroom->id, 404);
        $schedule->delete();
        return back()->with('success', 'Jadwal kelas dihapus.');
    }

    private function authorizeClassroom(Classroom $classroom): void
    {
        $allowed = in_array(Auth::user()?->role,['admin','super_admin'],true) || (Auth::user()?->role==='teacher' && $classroom->teacher_id===Auth::id());
        abort_unless($allowed,403);
    }
}
