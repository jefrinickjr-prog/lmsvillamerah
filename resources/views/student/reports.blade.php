@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
  <div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Halaman Siswa</p>
    <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Laporan Belajar</h2>
    <p class="mt-2 text-slate-500">Ringkasan progres belajar, tugas, nilai, dan absensi Anda.</p>
  </div>

  <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-700">
        <i class="fa-solid fa-book-open"></i>
      </div>
      <div class="mt-6 text-4xl font-black text-slate-950">{{ $materialsCount }}</div>
      <div class="mt-1 text-sm font-bold text-slate-500">Video tersedia</div>
    </div>

    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-100 text-cyan-700">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>
      <div class="mt-6 text-4xl font-black text-slate-950">{{ $submittedCount }}/{{ $tasksCount }}</div>
      <div class="mt-1 text-sm font-bold text-slate-500">Tugas dikumpulkan</div>
    </div>

    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-violet-100 text-violet-700">
        <i class="fa-solid fa-star"></i>
      </div>
      <div class="mt-6 text-4xl font-black text-slate-950">{{ $averageScore ? number_format($averageScore, 1) : '-' }}</div>
      <div class="mt-1 text-sm font-bold text-slate-500">Rata-rata nilai</div>
    </div>

    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
        <i class="fa-solid fa-calendar-check"></i>
      </div>
      <div class="mt-6 text-4xl font-black text-slate-950">{{ $attendanceRate }}%</div>
      <div class="mt-1 text-sm font-bold text-slate-500">Kehadiran</div>
    </div>
  </div>

  <section class="mt-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-black text-slate-950">Catatan Laporan</h3>
    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
      Laporan ini dihitung dari video pembelajaran kelas Anda, tugas yang sudah dikumpulkan, nilai yang sudah diberikan pengajar, dan data absensi yang tercatat di sistem.
    </p>
  </section>
@endsection
