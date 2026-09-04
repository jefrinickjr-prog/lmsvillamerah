<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\LiveStreamSession;
use App\Models\User;
use App\Services\JitsiJwtService;
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
        $query = LiveStreamSession::with(['classroom.teacher', 'participants'])
            ->withCount('participants')
            ->withExists(['participants as current_user_joined' => fn ($query) => $query->whereKey(Auth::id())])
            ->orderBy('starts_at');

        if ($user->role === 'teacher') {
            $query->whereHas('classroom', fn ($q) => $q->where('teacher_id', $user->id));
        } elseif ($this->isStudentRole($user->role)) {
            if (($user->delivery_mode ?? 'offline') !== 'online') {
                $query->whereRaw('1 = 0');
            }
            $classKeys = User::studentClassLookupKeys($user->student_class);
            $branchKeys = User::branchLookupKeys($user->branch);
            $query->whereHas('classroom', function ($q) use ($user, $classKeys, $branchKeys) {
                $q->where('delivery_mode', 'online')
                    ->where('program_type', User::normalizeProgramType($user->program_type))
                    ->where(function ($title) use ($classKeys) {
                        if ($classKeys === []) {
                            return $title->whereRaw('1 = 0');
                        }
                        foreach ($classKeys as $key) {
                            $title->orWhereRaw('LOWER(TRIM(title)) = ?', [$key]);
                        }
                    })
                    ->where(function ($branch) use ($branchKeys) {
                        if ($branchKeys === []) {
                            return $branch->whereRaw('1 = 0');
                        }
                        foreach ($branchKeys as $key) {
                            $branch->orWhereRaw('LOWER(TRIM(branch)) = ?', [$key]);
                        }
                    });
            });
        }

        $sessions = $query->get();
        $classrooms = in_array($user->role, ['teacher', 'admin', 'super_admin'], true)
            ? Classroom::when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
                ->orderByRaw("CASE WHEN delivery_mode = 'online' THEN 0 ELSE 1 END")
                ->orderBy('title')
                ->orderBy('branch')
                ->get()
            : collect();

        return view('live-streams.index', compact('sessions', 'classrooms'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isManager(), 403);
        $data = $this->validateData($request);
        $classroom = Classroom::findOrFail($data['classroom_id']);
        abort_unless($this->canManage($classroom), 403);
        $this->activateClassroomForLive($classroom);
        LiveStreamSession::create($data);

        return back()->with('success', 'Jadwal live streaming berhasil dibuat dan kelas sudah diaktifkan sebagai kelas online.');
    }

    public function edit(LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);

        $classrooms = Classroom::when(Auth::user()?->role === 'teacher', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->orderByRaw("CASE WHEN delivery_mode = 'online' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();

        return view('live-streams.edit', compact('liveStream', 'classrooms'));
    }

    public function update(Request $request, LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);
        $data = $this->validateData($request);
        $classroom = Classroom::findOrFail($data['classroom_id']);
        abort_unless($this->canManage($classroom), 403);
        $this->activateClassroomForLive($classroom);
        $liveStream->update($data + [
            'started_at' => null,
            'started_by' => null,
        ]);

        return redirect()->route('live-streams.index')->with('success', 'Jadwal live streaming berhasil diperbarui.');
    }

    public function start(LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);

        $restarting = now()->gt($liveStream->ends_at);
        $endsAt = $restarting ? now()->addHour() : $liveStream->ends_at;
        DB::transaction(function () use ($liveStream, $restarting, $endsAt) {
            $session = LiveStreamSession::whereKey($liveStream->id)
                ->lockForUpdate()
                ->firstOrFail();

            $session->update([
                'started_at' => $restarting ? now() : ($session->started_at ?? now()),
                'started_by' => Auth::id(),
                'ends_at' => $endsAt,
            ]);

            if ($restarting) {
                $session->participants()->detach();
            }
        });

        return redirect()->route('live-streams.room', $liveStream);
    }

    public function end(LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);

        DB::transaction(function () use ($liveStream): void {
            $session = LiveStreamSession::whereKey($liveStream->id)
                ->lockForUpdate()
                ->firstOrFail();

            $session->update(['ends_at' => now()]);
            $session->participants()->detach();
        });

        return redirect()->route('live-streams.index')->with('success', 'Live streaming telah diakhiri untuk seluruh peserta.');
    }

    public function status(LiveStreamSession $liveStream)
    {
        $isManager = $this->canManage($liveStream->classroom);
        $canAccess = $isManager
            || ($this->isStudentRole(Auth::user()?->role) && $this->studentCanAccess($liveStream->classroom));
        abort_unless($canAccess, 403);

        $pendingRejoins = $isManager
            ? $liveStream->participants()
                ->wherePivot('rejoin_status', 'pending')
                ->orderBy('live_stream_participants.rejoin_requested_at')
                ->get()
                ->map(fn (User $student) => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_code' => $student->student_code,
                    'requested_at' => $student->pivot->rejoin_requested_at,
                    'approve_url' => route('live-streams.rejoin.approve', [$liveStream, $student]),
                ])
                ->values()
            : collect();

        return response()->json([
            'ended' => ! $liveStream->started_at || now()->gte($liveStream->ends_at),
            'ends_at' => $liveStream->ends_at?->toIso8601String(),
            'pending_rejoins' => $pendingRejoins,
            'pending_rejoin_count' => $pendingRejoins->count(),
        ]);
    }

    public function destroy(LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);
        $liveStream->delete();

        return back()->with('success', 'Jadwal live streaming berhasil dihapus.');
    }

    public function join(LiveStreamSession $liveStream)
    {
        abort_unless($this->isStudentRole(Auth::user()?->role), 403);
        abort_unless($this->studentCanAccess($liveStream->classroom), 403);
        abort_unless($liveStream->started_at, 403, 'Live streaming belum dimulai oleh pengajar.');
        abort_if(now()->gt($liveStream->ends_at), 410, 'Sesi live streaming sudah selesai.');

        DB::transaction(function () use ($liveStream) {
            $session = LiveStreamSession::whereKey($liveStream->id)->lockForUpdate()->firstOrFail();
            if ($session->participants()->whereKey(Auth::id())->exists()) {
                throw ValidationException::withMessages([
                    'live_stream' => 'Akun Anda sudah menggunakan kesempatan masuk untuk sesi ini.',
                ]);
            }
            if ($session->participants()->count() >= LiveStreamSession::MAX_PARTICIPANTS) {
                throw ValidationException::withMessages(['live_stream' => 'Ruang sudah penuh (maksimal 20 peserta).']);
            }
            $session->participants()->attach(Auth::id());
        });

        return redirect()->route('live-streams.room', $liveStream);
    }

    public function room(LiveStreamSession $liveStream, JitsiJwtService $jitsi)
    {
        $isHost = (int) $liveStream->started_by === Auth::id();
        $isManager = $this->canManage($liveStream->classroom);
        $isParticipant = $this->isStudentRole(Auth::user()?->role)
            && $this->studentCanAccess($liveStream->classroom)
            && $liveStream->participants()->whereKey(Auth::id())->exists();
        abort_unless($isManager || $isParticipant, 403);
        if (now()->gt($liveStream->ends_at)) {
            return redirect()
                ->route('live-streams.index')
                ->withErrors(['live_stream' => 'Sesi live streaming sudah selesai. Host dapat menekan Mulai Ulang untuk membuka sesi selama 60 menit.']);
        }

        abort_unless($liveStream->started_at && $liveStream->started_by, 403, 'Pengajar belum memulai sesi.');

        if ($isParticipant) {
            $claimed = DB::table('live_stream_participants')
                ->where('live_stream_session_id', $liveStream->id)
                ->where('user_id', Auth::id())
                ->whereNull('entered_at')
                ->update([
                    'entered_at' => now(),
                    'rejoin_status' => 'used',
                    'updated_at' => now(),
                ]);

            abort_unless($claimed === 1, 429, 'Akun siswa hanya dapat membuka ruang satu kali untuk setiap sesi.');
        }

        $roomAlias = sprintf(
            '%s-%d-%s',
            config('jitsi.room_prefix'),
            $liveStream->id,
            substr(hash_hmac('sha256', 'live-stream-'.$liveStream->id, config('app.key')), 0, 24)
        );
        $usingJaas = $jitsi->configured();
        $appId = trim((string) config('jitsi.app_id'));

        return view('live-streams.room', [
            'liveStream' => $liveStream,
            'isHost' => $isHost,
            'isManager' => $isManager,
            'jitsiDomain' => $usingJaas ? '8x8.vc' : 'meet.jit.si',
            'jitsiRoomName' => $usingJaas ? $appId.'/'.$roomAlias : $roomAlias,
            'jitsiScriptUrl' => $usingJaas
                ? 'https://8x8.vc/'.$appId.'/external_api.js'
                : 'https://meet.jit.si/external_api.js',
            'jitsiJwt' => $usingJaas
                ? $jitsi->create(Auth::user(), $liveStream, $roomAlias, $isHost)
                : null,
            'usingJaas' => $usingJaas,
        ]);
    }

    public function requestRejoin(LiveStreamSession $liveStream)
    {
        abort_unless($this->isStudentRole(Auth::user()?->role), 403);
        abort_unless($this->studentCanAccess($liveStream->classroom), 403);
        abort_if(now()->gt($liveStream->ends_at), 410, 'Sesi live streaming sudah selesai.');

        $participant = DB::table('live_stream_participants')
            ->where('live_stream_session_id', $liveStream->id)
            ->where('user_id', Auth::id())
            ->first();

        abort_unless($participant && $participant->entered_at, 422, 'Akun belum pernah memasuki sesi ini.');

        if ($participant->rejoin_status === 'pending') {
            return back()->withErrors(['live_stream' => 'Permintaan masuk kembali masih menunggu persetujuan.']);
        }

        DB::table('live_stream_participants')
            ->where('id', $participant->id)
            ->update([
                'rejoin_status' => 'pending',
                'rejoin_requested_at' => now(),
                'rejoin_approved_at' => null,
                'rejoin_approved_by' => null,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Permintaan masuk kembali dikirim. Tunggu persetujuan admin atau pengajar.');
    }

    public function approveRejoin(LiveStreamSession $liveStream, User $student)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);
        abort_if(now()->gt($liveStream->ends_at), 410, 'Sesi live streaming sudah selesai.');

        $updated = DB::table('live_stream_participants')
            ->where('live_stream_session_id', $liveStream->id)
            ->where('user_id', $student->id)
            ->where('rejoin_status', 'pending')
            ->update([
                'entered_at' => null,
                'rejoin_status' => 'approved',
                'rejoin_approved_at' => now(),
                'rejoin_approved_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        abort_unless($updated === 1, 422, 'Permintaan masuk kembali tidak ditemukan atau sudah diproses.');

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Permintaan masuk kembali '.$student->name.' telah disetujui.']);
        }

        return back()->with('success', 'Permintaan masuk kembali '.$student->name.' telah disetujui.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
    }

    private function activateClassroomForLive(Classroom $classroom): void
    {
        DB::transaction(function () use ($classroom): void {
            if ($classroom->delivery_mode !== 'online') {
                $classroom->update(['delivery_mode' => 'online']);
            }

            $classKeys = User::studentClassLookupKeys($classroom->title);
            $branchKeys = User::branchLookupKeys($classroom->branch);

            if ($classKeys === [] || $branchKeys === []) {
                return;
            }

            User::where('role', 'student')
                ->where('program_type', $classroom->program_type)
                ->where(function ($query) use ($classKeys): void {
                    foreach ($classKeys as $key) {
                        $query->orWhereRaw('LOWER(TRIM(student_class)) = ?', [$key]);
                    }
                })
                ->where(function ($query) use ($branchKeys): void {
                    foreach ($branchKeys as $key) {
                        $query->orWhereRaw('LOWER(TRIM(branch)) = ?', [$key]);
                    }
                })
                ->update([
                    'delivery_mode' => 'online',
                    'updated_at' => now(),
                ]);
        });
    }

    private function isManager(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function isStudentRole(?string $role): bool
    {
        return in_array(strtolower(trim((string) $role)), ['student', 'siswa'], true);
    }

    private function canManage(Classroom $classroom): bool
    {
        return $this->isManager() && (in_array(Auth::user()?->role, ['admin', 'super_admin'], true) || $classroom->teacher_id === Auth::id());
    }

    private function studentCanAccess(Classroom $classroom): bool
    {
        $user = Auth::user();

        if ($classroom->class_program_id) {
            return $classroom->delivery_mode === 'online'
                && ($user->delivery_mode ?? 'offline') === 'online'
                && $user->activeClassrooms()->whereKey($classroom->id)->exists();
        }

        return $classroom->delivery_mode === 'online'
            && ($user->delivery_mode ?? 'offline') === 'online'
            && $classroom->program_type === User::normalizeProgramType($user->program_type)
            && in_array(User::normalizeStudentClass($classroom->title), User::studentClassLookupKeys($user->student_class), true)
            && in_array(User::normalizeBranch($classroom->branch), User::branchLookupKeys($user->branch), true);
    }
}
