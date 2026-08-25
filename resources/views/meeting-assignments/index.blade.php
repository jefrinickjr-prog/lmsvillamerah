@extends('layouts.app')
@section('title', 'Tugas Pertemuan')
@section('content')
<div class="mb-6"><p class="text-sm font-black uppercase tracking-wider text-indigo-500">Karya Setiap Pertemuan</p><h2 class="mt-1 text-3xl font-black">Tugas Pertemuan</h2><p class="mt-2 text-slate-500">Pengumpulan karya sekaligus menjadi konfirmasi kehadiran siswa pada minggu pertemuan.</p></div>

@if($manager)
  <form method="post" action="{{ route('meeting-assignments.store') }}" class="mb-7 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
    <h3 class="text-lg font-black">Buat Tugas Pertemuan</h3>
    <p class="mt-1 text-sm text-slate-500">Sistem menyiapkan absensi “belum hadir”; unggahan siswa akan mengubahnya menjadi hadir.</p>
    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <label class="font-bold">Kelas<select name="classroom_id" required class="mt-2 w-full rounded-xl border-slate-300"><option value="">Pilih kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->title }} · {{ $classroom->branch }}</option>@endforeach</select></label>
      <label class="font-bold">Judul Pertemuan<input name="title" required maxlength="255" class="mt-2 w-full rounded-xl border-slate-300" placeholder="Contoh: Sketsa Perspektif 1 Titik"></label>
      <label class="font-bold">Tanggal Pertemuan<input type="date" name="meeting_date" required value="{{ now()->toDateString() }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
      <label class="font-bold">Batas Pengumpulan<input type="datetime-local" name="due_at" required class="mt-2 w-full rounded-xl border-slate-300"></label>
      <label class="font-bold">Nilai Maksimal<input type="number" name="max_score" value="100" min="1" max="1000" required class="mt-2 w-full rounded-xl border-slate-300"></label>
      <label class="font-bold md:col-span-2 xl:col-span-3">Instruksi<textarea name="instructions" rows="4" class="mt-2 w-full rounded-xl border-slate-300" placeholder="Jelaskan karya yang harus dikumpulkan..."></textarea></label>
    </div>
    <button class="btn-action btn-primary-solid mt-5 rounded-2xl px-6">Buat Tugas Pertemuan</button>
  </form>
@endif

<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
  @forelse($assignments as $assignment)
    @php $mine=$assignment->submissions->first(); $late=now()->gt($assignment->due_at); @endphp
    <article class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-start justify-between gap-3"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $assignment->meeting_date->format('d M Y') }}</span><span class="rounded-full px-3 py-1 text-xs font-black {{ $late ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700' }}">{{ $late ? 'Ditutup' : 'Dibuka' }}</span></div>
      <h3 class="mt-4 text-xl font-black">{{ $assignment->title }}</h3><p class="mt-1 text-sm font-bold text-indigo-600">{{ $assignment->classroom->title }} · {{ $assignment->classroom->branch }}</p>
      <p class="mt-3 flex-1 text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($assignment->instructions ?: 'Tidak ada instruksi tambahan.', 130) }}</p>
      <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm"><b>Batas:</b> {{ $assignment->due_at->format('d M Y H:i') }}</div>
      @if(auth()->user()->role === 'student')<div class="mt-3 text-sm font-black {{ $mine ? 'text-emerald-600' : 'text-amber-600' }}">{{ $mine ? ($mine->score === null ? 'Sudah dikumpulkan · belum dinilai' : 'Nilai: '.$mine->score.'/'.$assignment->max_score) : 'Belum dikumpulkan' }}</div>@else<div class="mt-3 text-sm font-black text-slate-600">{{ $assignment->submissions_count }} karya terkumpul</div>@endif
      <a href="{{ route('meeting-assignments.show', $assignment) }}" class="mt-4 rounded-xl bg-indigo-600 px-5 py-3 text-center font-black text-white">{{ auth()->user()->role === 'student' ? 'Buka & Kumpulkan' : 'Lihat Pengumpulan' }}</a>
    </article>
  @empty
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">Belum ada tugas pertemuan.</div>
  @endforelse
</div>
<div class="mt-5">{{ $assignments->links() }}</div>
@endsection
