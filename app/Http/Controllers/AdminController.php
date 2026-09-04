<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\MeetingSubmission;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            ['label'=>'Siswa Aktif','value'=>User::where('role','student')->count(),'icon'=>'fa-solid fa-user-graduate','tone'=>'indigo'],
            ['label'=>'Kelas Aktif','value'=>Classroom::where('is_active',true)->count(),'icon'=>'fa-solid fa-chalkboard-user','tone'=>'cyan'],
            ['label'=>'Karya Terkumpul','value'=>MeetingSubmission::count(),'icon'=>'fa-solid fa-images','tone'=>'violet'],
            ['label'=>'Karya Dinilai','value'=>MeetingSubmission::whereNotNull('score')->count(),'icon'=>'fa-solid fa-star','tone'=>'emerald'],
        ];
        $activities = ActivityLog::with('user')->latest()->limit(10)->get();
        $classroomLoad = Classroom::where('is_active',true)->withCount('activeEnrollments')->orderByDesc('active_enrollments_count')->limit(6)->get();
        $roleMix = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total','role');
        return view('dashboard.admin', compact('stats','activities','classroomLoad','roleMix'));
    }
}
