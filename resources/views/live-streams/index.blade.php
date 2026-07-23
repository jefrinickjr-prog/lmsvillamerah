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
        <p class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">Belum ada kelas online. Ubah kategori kelas menjadi Online terlebih dahulu.</p>
      @else
        <div class="grid gap-4 md:grid-cols-2">
          <div><label class="mb-2 block text-sm font-bold">Kelas Online</label><select name="classroom_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"><option value="">Pilih kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->title }} · {{ $classroom->branch }}</option>@endforeach</select></div>
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
      @php $ended = now()->gt($session->ends_at); $notOpen = ! $session->started_at; $full = $session->participants_count >= 20 && ! $session->current_user_joined; @endphp
      <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-100 text-rose-600"><i class="fa-solid fa-video"></i></div><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $session->participants_count }}/20 peserta</span></div>
        <h3 class="mt-5 text-lg font-black">{{ $session->title }}</h3>
        <p class="mt-1 text-sm font-bold text-indigo-600">{{ $session->classroom->title }} · {{ $session->classroom->branch }}</p>
        <div class="mt-4 space-y-1 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600"><div><i class="fa-regular fa-calendar mr-2"></i>{{ $session->starts_at->format('d M Y') }}</div><div><i class="fa-regular fa-clock mr-2"></i>{{ $session->starts_at->format('H:i') }}–{{ $session->ends_at->format('H:i') }} WIB</div></div>
        @if(in_array(strtolower(trim((string) auth()->user()->role)), ['student', 'siswa'], true))
          <form method="POST" action="{{ route('live-streams.join', $session) }}" class="mt-4">
            @csrf
            <button
              type="submit"
              @disabled($ended || $notOpen || $full)
              class="btn-action btn-download-solid min-h-12 w-full rounded-2xl px-4 py-3 text-sm {{ $ended || $notOpen || $full ? 'cursor-not-allowed opacity-50' : '' }}"
            >
              <i class="fa-solid fa-{{ $session->current_user_joined ? 'right-to-bracket' : 'video' }}"></i>
              <span>{{ $ended ? 'Sesi Selesai' : ($notOpen ? 'Menunggu Pengajar Memulai' : ($full ? 'Ruang Penuh' : ($session->current_user_joined ? 'Masuk Kembali ke Live' : 'Join Live Streaming'))) }}</span>
            </button>
          </form>
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
          <form method="POST" action="{{ route('live-streams.destroy', $session) }}" class="mt-2" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button class="w-full rounded-2xl bg-rose-50 px-4 py-3 text-sm font-black text-rose-600">Hapus Jadwal</button></form>
        @endif
      </article>
    @empty
      <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center md:col-span-2 xl:col-span-3"><i class="fa-solid fa-video-slash text-3xl text-slate-300"></i><p class="mt-3 font-black">Belum ada jadwal live streaming.</p></div>
    @endforelse
  </div>
@endsection
