<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Attempt;
use App\Models\Classroom;
use App\Models\Material;
use App\Models\MeetingAssignment;
use App\Models\MeetingSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function dashboard()
    {
        $student=Auth::user();
        $classrooms=$student->activeClassrooms()->with(['program.category','branchMaster','academicPeriod','schedules'])->get();
        if ($classrooms->isEmpty()) {
            $classKeys=User::studentClassLookupKeys($student->student_class);
            $branchKeys=User::branchLookupKeys($student->branch);
            if ($classKeys !== [] && $branchKeys !== []) {
                $classrooms=Classroom::with(['program.category','branchMaster','academicPeriod','schedules'])
                    ->where('program_type',User::normalizeProgramType($student->program_type))
                    ->where(fn($query)=>collect($classKeys)->each(fn($key)=>$query->orWhereRaw('LOWER(TRIM(title)) = ?',[$key])))
                    ->where(fn($query)=>collect($branchKeys)->each(fn($key)=>$query->orWhereRaw('LOWER(TRIM(branch)) = ?',[$key])))
                    ->get();
            }
        }
        $classroomIds=$classrooms->pluck('id');
        $assignments=MeetingAssignment::whereIn('classroom_id',$classroomIds)->with('classroom')->latest('meeting_date')->get();
        $submissions=MeetingSubmission::where('student_id',$student->id)->with('assignment.classroom')->latest('submitted_at')->get();
        $submissionByAssignment=$submissions->keyBy('meeting_assignment_id');
        $attempts=Attempt::where('student_id',$student->id)->with('exam')->whereIn('status',['submitted','graded'])->latest('submitted_at')->get();
        $attendances=Attendance::where('student_id',$student->id)->latest('date')->get();
        $presentCount=$attendances->where('present',true)->count();
        $attendanceRate=$attendances->count()?(int)round($presentCount/$attendances->count()*100):0;
        $completedAssignments=$assignments->whereIn('id',$submissions->pluck('meeting_assignment_id'))->count();
        $assignmentRate=$assignments->count()?(int)round($completedAssignments/$assignments->count()*100):0;
        $averageScore=$submissions->whereNotNull('score')->merge($attempts->whereNotNull('score'))->avg('score');
        $materialCount=Material::where(fn($query)=>$query->whereIn('classroom_id',$classroomIds)->orWhereHas('classrooms',fn($classroom)=>$classroom->whereIn('classrooms.id',$classroomIds)))->count();
        $activities=collect()
            ->merge($submissions->take(5)->map(fn($item)=>['type'=>'Karya','title'=>$item->assignment?->title?:'Tugas pertemuan','detail'=>$item->score!==null?'Nilai '.$item->score:'Menunggu penilaian','date'=>$item->submitted_at?:$item->created_at,'icon'=>'fa-solid fa-images','tone'=>'violet']))
            ->merge($attempts->take(5)->map(fn($item)=>['type'=>'Ujian','title'=>$item->exam?->title?:'Ujian','detail'=>$item->score!==null?'Skor '.$item->score:'Telah diselesaikan','date'=>$item->submitted_at?:$item->created_at,'icon'=>'fa-solid fa-file-circle-check','tone'=>'indigo']))
            ->merge($attendances->take(5)->map(fn($item)=>['type'=>'Absensi','title'=>$item->present?'Hadir di kelas':'Tidak hadir','detail'=>optional($item->date)->format('d M Y'),'date'=>$item->updated_at,'icon'=>'fa-solid fa-calendar-check','tone'=>$item->present?'emerald':'rose']))
            ->sortByDesc('date')->take(8)->values();
        return view('dashboard.student',compact('student','classrooms','assignments','submissionByAssignment','attendanceRate','assignmentRate','completedAssignments','averageScore','materialCount','activities'));
    }
}
