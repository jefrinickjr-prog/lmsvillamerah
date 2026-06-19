@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
  @php
    $studentClass = auth()->user()->student_class;
    $branch = auth()->user()->branch;
    $programType = \App\Models\User::normalizeProgramType(auth()->user()->program_type);
    $studentClassKeys = \App\Models\User::studentClassLookupKeys($studentClass);
    $latestTasks = \App\Models\Task::with('material.classroom')
      ->whereHas('material', fn ($materialQuery) => $materialQuery->where('program_type', $programType))
      ->when($studentClassKeys === [], fn ($query) => $query->whereRaw('1 = 0'))
      ->when($studentClassKeys !== [], function ($query) use ($studentClassKeys) {
        $query->whereHas('material.classroom', function ($classroomQuery) use ($studentClassKeys) {
          $classroomQuery->where(function ($titleQuery) use ($studentClassKeys) {
            foreach ($studentClassKeys as $studentClassKey) {
              $titleQuery->orWhereRaw('LOWER(TRIM(title)) = ?', [$studentClassKey]);
            }
          });
        });
      })
      ->latest()
      ->limit(5)
      ->get();
  @endphp

  <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Dashboard Siswa</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Selamat belajar, {{ auth()->user()->name }}</h2>
      <div class="mt-2 flex flex-wrap gap-2">
        <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-black text-indigo-700">{{ $studentClass ?? 'Belum punya kelas program' }}</span>
        <span class="inline-flex rounded-full bg-violet-50 px-3 py-1 text-sm font-black text-violet-700">{{ \App\Models\User::programTypeLabel($programType) }}</span>
        <span class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-sm font-black text-cyan-700">{{ $branch ?? 'Cabang belum diisi' }}</span>
        @if(auth()->user()->student_code)
          <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-black text-slate-700">{{ auth()->user()->student_code }}</span>
        @endif
      </div>
    </div>
    <a href="{{ route('materials.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700">
      <i class="fa-solid fa-book-open"></i>
      Lihat Video
    </a>
  </div>

  <div class="grid gap-5 md:grid-cols-2">
    <a href="{{ route('materials.index') }}" class="group rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-700">
        <i class="fa-solid fa-layer-group"></i>
      </div>
      <h3 class="mt-5 text-xl font-black text-slate-950">Video Pembelajaran</h3>
      <p class="mt-2 text-sm leading-6 text-slate-500">Akses video pembelajaran sesuai kelas program Anda.</p>
    </a>

    <a href="{{ route('tasks.index') }}" class="group rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-100 text-cyan-700">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>
      <h3 class="mt-5 text-xl font-black text-slate-950">Tugas Terbaru</h3>
      <p class="mt-2 text-sm leading-6 text-slate-500">Lihat instruksi tugas, deadline, dan kumpulkan pekerjaan Anda.</p>
    </a>
  </div>

  <div class="mt-5 grid gap-5 md:grid-cols-3">
    <a href="{{ route('student.grades') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-violet-100 text-violet-700">
        <i class="fa-solid fa-star-half-stroke"></i>
      </div>
      <h3 class="mt-5 text-lg font-black text-slate-950">Penilaian</h3>
      <p class="mt-2 text-sm leading-6 text-slate-500">Pantau nilai tugas yang sudah diperiksa.</p>
    </a>

    <a href="{{ route('student.attendance') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
        <i class="fa-solid fa-calendar-check"></i>
      </div>
      <h3 class="mt-5 text-lg font-black text-slate-950">Rekap Absensi</h3>
      <p class="mt-2 text-sm leading-6 text-slate-500">Lihat ringkasan kehadiran di kelas.</p>
    </a>

    <a href="{{ route('student.reports') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-100 text-rose-700">
        <i class="fa-solid fa-chart-line"></i>
      </div>
      <h3 class="mt-5 text-lg font-black text-slate-950">Laporan</h3>
      <p class="mt-2 text-sm leading-6 text-slate-500">Baca ringkasan progres belajar Anda.</p>
    </a>
  </div>

  <section class="mt-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <div class="mb-5 flex items-center justify-between">
      <h3 class="text-lg font-black text-slate-950">Daftar Tugas Terbaru</h3>
      <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">5 terbaru</span>
    </div>
    <div class="divide-y divide-slate-100">
      @forelse($latestTasks as $task)
        <div class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
          <div>
            <div class="font-black text-slate-900">{{ $task->title }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ $task->material->title ?? 'Tanpa video' }}</div>
          </div>
          <div class="text-sm font-bold text-slate-400">{{ optional($task->due_at)->format('Y-m-d') ?? 'Tanpa deadline' }}</div>
        </div>
      @empty
        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">Belum ada tugas terbaru.</div>
      @endforelse
    </div>
  </section>
@endsection
