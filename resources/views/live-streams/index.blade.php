@extends('layouts.app')

@section('title', 'Live Streaming')

@section('content')
  <div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Kelas Online</p>
    <h2 class="mt-1 text-3xl font-black text-slate-950">Live Streaming</h2>
    <p class="mt-2 text-slate-500">Live berlangsung langsung di aplikasi, dibatasi maksimal 20 peserta, dan hanya tersedia bagi siswa kelas online yang sesuai.</p>
  </div>

  @if($errors->any())
    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
  @endif

  @if(in_array(auth()->user()->role, ['teacher', 'admin', 'super_admin'], true))
    <form method="POST" action="{{ route('live-streams.store') }}" class="mb-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      @csrf
      <h3 class="mb-5 text-lg font-black">Buat Jadwal Live</h3>
      @if($classrooms->isEmpty())
        <div class="rounded-2xl bg-amber-50 px-4 py-4 text-sm font-bold text-amber-700">
          <p>Belum ada kelas. Buat kelas terlebih dahulu agar jadwal live dapat dibuat.</p>
          <a href="{{ route('classrooms.create') }}" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-white">
            <i class="fa-solid fa-plus"></i> Buat Kelas Online
          </a>
        </div>
      @else
        <p class="mb-4 rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700">Kelas Offline akan otomatis diaktifkan menjadi Online saat jadwal disimpan. Siswa pada kelas dan cabang yang sesuai juga akan memperoleh akses live.</p>
        <div class="grid gap-4 md:grid-cols-2">
          <div><label class="mb-2 block text-sm font-bold">Kelas</label><select name="classroom_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"><option value="">Pilih kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->title }} · {{ $classroom->branch }} ({{ $classroom->delivery_mode === 'online' ? 'Online' : 'Offline — akan diaktifkan' }})</option>@endforeach</select></div>
          <div><label class="mb-2 block text-sm font-bold">Judul Sesi</label><input name="title" value="{{ old('title') }}" required maxlength="255" class="w-full rounded-2xl border border-slate-200 px-4 py-3" placeholder="Contoh: Pembahasan Perspektif"></div>
          <div><label class="mb-2 block text-sm font-bold">Mulai</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"></div>
          <div><label class="mb-2 block text-sm font-bold">Selesai</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"></div>
        </div>
        <button class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white" type="submit"><i class="fa-solid fa-calendar-plus"></i> Buat Jadwal</button>
      @endif
    </form>
  @endif

  <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($sessions as $session)
      @php
        $ended = now()->gt($session->ends_at);
        $notOpen = ! $session->started_at;
        $full = $session->participants_count >= 20 && ! $session->current_user_joined;
        $currentParticipant = $session->participants->firstWhere('id', auth()->id());
        $pendingRejoins = $session->participants->filter(fn ($participant) => $participant->pivot->rejoin_status === 'pending');
      @endphp
      <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-100 text-rose-600"><i class="fa-solid fa-video"></i></div><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $session->participants_count }}/20 peserta</span></div>
        <h3 class="mt-5 text-lg font-black">{{ $session->title }}</h3>
        <p class="mt-1 text-sm font-bold text-indigo-600">{{ $session->classroom->title }} · {{ $session->classroom->branch }}</p>
        <div class="mt-4 space-y-1 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600"><div><i class="fa-regular fa-calendar mr-2"></i>{{ $session->starts_at->format('d M Y') }}</div><div><i class="fa-regular fa-clock mr-2"></i>{{ $session->starts_at->format('H:i') }}–{{ $session->ends_at->format('H:i') }} WIB</div></div>
        @if(in_array(strtolower(trim((string) auth()->user()->role)), ['student', 'siswa'], true))
          @if($currentParticipant?->pivot->rejoin_status === 'approved' && ! $currentParticipant?->pivot->entered_at)
            <a href="{{ route('live-streams.room', $session) }}" class="btn-action btn-approve-solid mt-4 min-h-12 w-full rounded-2xl px-4 py-3 text-sm">
              <i class="fa-solid fa-right-to-bracket"></i><span>Masuk Kembali — Disetujui</span>
            </a>
          @elseif($currentParticipant?->pivot->rejoin_status === 'pending')
            <button disabled
              class="btn-action mt-4 min-h-12 w-full cursor-not-allowed rounded-2xl bg-amber-100 px-4 py-3 text-sm text-amber-700 opacity-80"
              data-rejoin-waiting
              data-status-url="{{ route('live-streams.status', $session) }}"
              data-room-url="{{ route('live-streams.room', $session) }}">
              <i class="fa-solid fa-clock"></i><span>Pending Persetujuan Admin</span>
            </button>
          @elseif($session->current_user_joined)
            <form method="POST" action="{{ route('live-streams.rejoin.request', $session) }}" class="mt-4">
              @csrf
              <button type="submit" @disabled($ended) class="btn-action btn-download-solid min-h-12 w-full rounded-2xl px-4 py-3 text-sm {{ $ended ? 'cursor-not-allowed opacity-50' : '' }}">
                <i class="fa-solid fa-paper-plane"></i><span>{{ $ended ? 'Sesi Selesai' : 'Ajukan Masuk Kembali' }}</span>
              </button>
            </form>
          @else
            <form method="POST" action="{{ route('live-streams.join', $session) }}" class="mt-4">
              @csrf
              <button type="submit" @disabled($ended || $notOpen || $full) class="btn-action btn-download-solid min-h-12 w-full rounded-2xl px-4 py-3 text-sm {{ $ended || $notOpen || $full ? 'cursor-not-allowed opacity-50' : '' }}">
                <i class="fa-solid fa-video"></i>
                <span>{{ $ended ? 'Sesi Selesai' : ($notOpen ? 'Menunggu Pengajar Memulai' : ($full ? 'Ruang Penuh' : 'Join Live Streaming')) }}</span>
              </button>
            </form>
          @endif
        @else
          <div class="mt-4 grid gap-2 sm:grid-cols-2">
            <form method="POST" action="{{ route('live-streams.start', $session) }}">
              @csrf
              <button
                type="submit"
                class="btn-action btn-approve-solid min-h-12 w-full rounded-2xl px-4 py-3 text-sm"
              >
                <i class="fa-solid fa-{{ $ended ? 'rotate-right' : ($session->started_at ? 'video' : 'play') }}"></i>
                <span>{{ $ended ? 'Mulai Ulang 60 Menit' : ($session->started_at ? 'Masuk sebagai Host' : 'Mulai Live sebagai Host') }}</span>
              </button>
            </form>
            <a href="{{ route('live-streams.edit', $session) }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700"><i class="fa-solid fa-pen-to-square mr-2"></i>Edit</a>
          </div>
          @if($session->started_at && ! $ended)
            <form method="POST" action="{{ route('live-streams.end', $session) }}" class="mt-2" onsubmit="return confirm('Akhiri live streaming untuk seluruh peserta?')">@csrf<button class="w-full rounded-2xl bg-amber-50 px-4 py-3 text-sm font-black text-amber-700"><i class="fa-solid fa-phone-slash mr-2"></i>Akhiri Live untuk Semua</button></form>
          @endif
          <form method="POST" action="{{ route('live-streams.destroy', $session) }}" class="mt-2" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button class="w-full rounded-2xl bg-rose-50 px-4 py-3 text-sm font-black text-rose-600">Hapus Jadwal</button></form>
          @if($pendingRejoins->isNotEmpty())
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-3">
              <p class="text-xs font-black uppercase tracking-wider text-amber-700">Permintaan Masuk Kembali</p>
              <div class="mt-2 space-y-2">
                @foreach($pendingRejoins as $participant)
                  <form method="POST" action="{{ route('live-streams.rejoin.approve', [$session, $participant]) }}" class="flex items-center justify-between gap-2 rounded-xl bg-white p-2">
                    @csrf
                    @method('PUT')
                    <span class="min-w-0 truncate text-xs font-black text-slate-700">{{ $participant->name }}</span>
                    <button type="submit" class="shrink-0 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white">Setujui</button>
                  </form>
                @endforeach
              </div>
            </div>
          @endif
        @endif
      </article>
    @empty
      <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center md:col-span-2 xl:col-span-3"><i class="fa-solid fa-video-slash text-3xl text-slate-300"></i><p class="mt-3 font-black">Belum ada jadwal live streaming.</p></div>
    @endforelse
  </div>

  @if(in_array(strtolower(trim((string) auth()->user()->role)), ['student', 'siswa'], true))
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const waitingButtons = [...document.querySelectorAll('[data-rejoin-waiting]')];
        if (!waitingButtons.length) return;

        let redirecting = false;
        const checkApproval = async button => {
          try {
            const response = await fetch(button.dataset.statusUrl, {
              headers: {'Accept': 'application/json'},
              cache: 'no-store',
            });
            if (!response.ok) return;

            const session = await response.json();
            if (session.ended) {
              button.innerHTML = '<i class="fa-solid fa-circle-xmark"></i><span>Sesi Sudah Selesai</span>';
              return;
            }
            if (!session.can_rejoin || redirecting) return;

            redirecting = true;
            const roomUrl = session.room_url || button.dataset.roomUrl;
            const link = document.createElement('a');
            link.href = roomUrl;
            link.className = 'btn-action btn-approve-solid mt-4 min-h-12 w-full rounded-2xl px-4 py-3 text-sm';
            link.innerHTML = '<i class="fa-solid fa-circle-check"></i><span>Disetujui — Masuk Sekarang</span>';
            button.replaceWith(link);

            window.setTimeout(() => window.location.assign(roomUrl), 500);
          } catch (error) {
            // Kegagalan jaringan sementara akan dicoba kembali pada polling berikutnya.
          }
        };

        waitingButtons.forEach(checkApproval);
        window.setInterval(() => waitingButtons.forEach(button => {
          if (button.isConnected) checkApproval(button);
        }), 3000);
      });
    </script>
  @endif
@endsection
