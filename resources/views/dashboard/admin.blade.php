@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
  @php
    $stats = [
      ['label' => 'Pengguna', 'value' => \App\Models\User::count(), 'icon' => 'fa-solid fa-users', 'tone' => 'bg-indigo-100 text-indigo-700'],
      ['label' => 'Kelas', 'value' => \App\Models\Classroom::count(), 'icon' => 'fa-solid fa-chalkboard-user', 'tone' => 'bg-cyan-100 text-cyan-700'],
      ['label' => 'Video', 'value' => \App\Models\Material::count(), 'icon' => 'fa-solid fa-circle-play', 'tone' => 'bg-violet-100 text-violet-700'],
      ['label' => 'Tugas', 'value' => \App\Models\Task::count(), 'icon' => 'fa-solid fa-clipboard-check', 'tone' => 'bg-rose-100 text-rose-700'],
    ];
  @endphp

  <div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Dashboard Admin</p>
    <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Ringkasan platform</h2>
    <p class="mt-2 text-slate-500">Pantau pengguna, kelas program, video pembelajaran, dan tugas dari satu tempat.</p>
  </div>

  <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($stats as $stat)
      <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div class="grid h-12 w-12 place-items-center rounded-2xl {{ $stat['tone'] }}">
            <i class="{{ $stat['icon'] }}"></i>
          </div>
          <span class="text-xs font-black uppercase tracking-wider text-slate-300">Total</span>
        </div>
        <div class="mt-6 text-4xl font-black tracking-tight text-slate-950">{{ $stat['value'] }}</div>
        <div class="mt-1 text-sm font-bold text-slate-500">{{ $stat['label'] }}</div>
      </div>
    @endforeach
  </div>

  <section class="mt-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <div class="mb-5 flex items-center justify-between">
      <h3 class="text-lg font-black text-slate-950">Aktivitas Terbaru</h3>
      <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Tugas</span>
    </div>
    <div class="divide-y divide-slate-100">
      @forelse(\App\Models\Task::latest()->limit(5)->get() as $task)
        <div class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
          <div>
            <div class="font-black text-slate-900">{{ $task->title }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ $task->material->title ?? 'Tanpa video' }}</div>
          </div>
          <div class="text-sm font-bold text-slate-400">{{ $task->created_at->diffForHumans() }}</div>
        </div>
      @empty
        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">Belum ada aktivitas.</div>
      @endforelse
    </div>
  </section>
@endsection
