<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\LiveStreamSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LiveStreamController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = LiveStreamSession::with(['classroom.teacher'])->withCount('participants')->orderBy('starts_at');

        if ($user->role === 'teacher') {
            $query->whereHas('classroom', fn ($q) => $q->where('teacher_id', $user->id));
        } elseif ($user->role === 'student') {
            $classKeys = User::studentClassLookupKeys($user->student_class);
            $branchKeys = User::branchLookupKeys($user->branch);
            $query->whereHas('classroom', function ($q) use ($user, $classKeys, $branchKeys) {
                $q->where('delivery_mode', 'online')
                    ->where('program_type', User::normalizeProgramType($user->program_type))
                    ->where(function ($title) use ($classKeys) {
                        if ($classKeys === []) return $title->whereRaw('1 = 0');
                        foreach ($classKeys as $key) $title->orWhereRaw('LOWER(TRIM(title)) = ?', [$key]);
                    })
                    ->where(function ($branch) use ($branchKeys) {
                        if ($branchKeys === []) return $branch->whereRaw('1 = 0');
                        foreach ($branchKeys as $key) $branch->orWhereRaw('LOWER(TRIM(branch)) = ?', [$key]);
                    });
            });
        }

        $sessions = $query->get();
        $classrooms = in_array($user->role, ['teacher', 'admin', 'super_admin'], true)
            ? Classroom::where('delivery_mode', 'online')
                ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
                ->orderBy('title')->get()
            : collect();

        return view('live-streams.index', compact('sessions', 'classrooms'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isManager(), 403);
        $data = $this->validateData($request);
        $classroom = Classroom::findOrFail($data['classroom_id']);
        abort_unless($this->canManage($classroom), 403);
        abort_unless($classroom->delivery_mode === 'online', 422, 'Live streaming hanya dapat dibuat untuk kelas online.');
        LiveStreamSession::create($data);

        return back()->with('success', 'Jadwal live streaming berhasil dibuat.');
    }

    public function destroy(LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);
        $liveStream->delete();
        return back()->with('success', 'Jadwal live streaming berhasil dihapus.');
    }

    public function join(LiveStreamSession $liveStream)
    {
        abort_unless(Auth::user()?->role === 'student', 403);
        abort_unless($this->studentCanAccess($liveStream->classroom), 403);
        abort_if(now()->lt($liveStream->starts_at->copy()->subMinutes(15)), 403, 'Ruang belum dibuka. Silakan masuk 15 menit sebelum mulai.');
        abort_if(now()->gt($liveStream->ends_at), 410, 'Sesi live streaming sudah selesai.');

        DB::transaction(function () use ($liveStream) {
            $session = LiveStreamSession::whereKey($liveStream->id)->lockForUpdate()->firstOrFail();
            if ($session->participants()->whereKey(Auth::id())->exists()) return;
            if ($session->participants()->count() >= LiveStreamSession::MAX_PARTICIPANTS) {
                throw ValidationException::withMessages(['live_stream' => 'Ruang sudah penuh (maksimal 20 peserta).']);
            }
            $session->participants()->attach(Auth::id());
        });

        return redirect()->away($liveStream->meeting_url);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('delivery_mode', 'online')],
            'title' => ['required', 'string', 'max:255'],
            'meeting_url' => ['required', 'url:http,https', 'max:2048'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
    }

    private function isManager(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function canManage(Classroom $classroom): bool
    {
        return $this->isManager() && (in_array(Auth::user()?->role, ['admin', 'super_admin'], true) || $classroom->teacher_id === Auth::id());
    }

    private function studentCanAccess(Classroom $classroom): bool
    {
        $user = Auth::user();
        return $classroom->delivery_mode === 'online'
            && $classroom->program_type === User::normalizeProgramType($user->program_type)
            && in_array(User::normalizeStudentClass($classroom->title), User::studentClassLookupKeys($user->student_class), true)
            && in_array(User::normalizeBranch($classroom->branch), User::branchLookupKeys($user->branch), true);
    }
}
