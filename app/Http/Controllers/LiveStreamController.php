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
        $query = LiveStreamSession::with(['classroom.teacher'])
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

    public function edit(LiveStreamSession $liveStream)
    {
        abort_unless($this->canManage($liveStream->classroom), 403);

        $classrooms = Classroom::where('delivery_mode', 'online')
            ->when(Auth::user()?->role === 'teacher', fn ($query) => $query->where('teacher_id', Auth::id()))
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
                ->update(['entered_at' => now(), 'updated_at' => now()]);

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

    private function validateData(Request $request): array
    {
        return $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('delivery_mode', 'online')],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
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

        return $classroom->delivery_mode === 'online'
            && ($user->delivery_mode ?? 'offline') === 'online'
            && $classroom->program_type === User::normalizeProgramType($user->program_type)
            && in_array(User::normalizeStudentClass($classroom->title), User::studentClassLookupKeys($user->student_class), true)
            && in_array(User::normalizeBranch($classroom->branch), User::branchLookupKeys($user->branch), true);
    }
}
