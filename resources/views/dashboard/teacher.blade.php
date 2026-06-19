@extends('layouts.app')

@section('title', 'Dashboard Pengajar')

@section('content')
  @php
    $classes = \App\Models\Classroom::where('teacher_id', auth()->id())->count();
    $videos = \App\Models\Material::whereHas('classroom', function($query) { $query->where('teacher_id', auth()->id()); })->count();
    $tasks = \App\Models\Task::whereHas('material.classroom', function($query) { $query->where('teacher_id', auth()->id()); })->count();
    $latestVideos = \App\Models\Material::whereHas('classroom', function($query) { $query->where('teacher_id', auth()->id()); })->latest()->limit(6)->get();
  @endphp

  <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Dashboard Pengajar</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Halo, {{ auth()->user()->name }}</h2>
      <p class="mt-2 text-slate-500">Kelola video pembelajaran dan tugas untuk kelas program Anda.</p>
    </div>
    <a href="{{ route('materials.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700">
      <i class="fa-solid fa-plus"></i>
      Tambah Video
    </a>
  </div>

  <div class="grid gap-5 md:grid-cols-3">
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Kelas</div>
      <div class="mt-3 text-4xl font-black text-slate-950">{{ $classes }}</div>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Video</div>
      <div class="mt-3 text-4xl font-black text-slate-950">{{ $videos }}</div>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Tugas</div>
      <div class="mt-3 text-4xl font-black text-slate-950">{{ $tasks }}</div>
    </div>
  </div>

  <section class="mt-8">
    <div class="mb-5 flex items-center justify-between">
      <h3 class="text-lg font-black text-slate-950">Video Terbaru</h3>
      <a href="{{ route('materials.index') }}" class="text-sm font-black text-indigo-600">Lihat semua</a>
    </div>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      @forelse($latestVideos as $material)
        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
          <div class="grid h-11 w-11 place-items-center rounded-2xl bg-indigo-100 text-indigo-700">
            <i class="fa-solid fa-circle-play"></i>
          </div>
          <h4 class="mt-5 font-black text-slate-950">{{ $material->title }}</h4>
          <div class="mt-2 inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-black text-violet-700">{{ \App\Models\User::programTypeLabel($material->program_type ?? 'gambar') }}</div>
          <p class="mt-2 text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($material->content, 90) }}</p>
        </div>
      @empty
        <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm font-semibold text-slate-500 md:col-span-2 xl:col-span-3">Belum ada video pembelajaran untuk kelas Anda.</div>
      @endforelse
    </div>
  </section>
@endsection
