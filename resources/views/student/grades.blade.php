@extends('layouts.app')

@section('title', 'Penilaian')

@section('content')
  <div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Halaman Siswa</p>
    <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Penilaian</h2>
    <p class="mt-2 text-slate-500">Lihat nilai tugas yang sudah diperiksa oleh pengajar.</p>
  </div>

  <div class="mb-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <div class="text-sm font-bold text-slate-500">Rata-rata Nilai</div>
    <div class="mt-3 text-5xl font-black text-indigo-600">{{ $averageScore ? number_format($averageScore, 1) : '-' }}</div>
  </div>

  <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <h3 class="mb-5 text-lg font-black text-slate-950">Riwayat Nilai</h3>
    <div class="divide-y divide-slate-100">
      @forelse($submissions as $submission)
        <div class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
          <div>
            <div class="font-black text-slate-900">{{ $submission->task->title ?? 'Tugas' }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ $submission->task->material->title ?? 'Tanpa video' }}</div>
          </div>
          <div class="rounded-2xl bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700">
            {{ is_null($submission->score) ? 'Belum dinilai' : $submission->score }}
          </div>
        </div>
      @empty
        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada nilai yang tersedia.</div>
      @endforelse
    </div>
  </section>
@endsection
