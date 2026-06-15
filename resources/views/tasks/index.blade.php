@extends('layouts.app')

@section('title', 'Tugas')

@section('content')
  @php
    $canManageTasks = in_array(auth()->user()?->role, ['teacher', 'admin', 'super_admin'], true);
    $studentClass = auth()->user()?->role === 'student' ? auth()->user()?->student_class : null;
    $branch = auth()->user()?->role === 'student' ? auth()->user()?->branch : null;
  @endphp

  <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Tugas</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Daftar Tugas</h2>
      <p class="mt-2 text-slate-500">Pantau instruksi dan deadline tugas belajar.</p>
      @if($studentClass)
        <p class="mt-2 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-black text-indigo-700">{{ $studentClass }} - {{ $branch ?? 'Cabang belum diisi' }}</p>
      @endif
    </div>
    @if($canManageTasks)
      <a href="{{ route('tasks.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700">
        <i class="fa-solid fa-plus"></i>
        Buat Tugas
      </a>
    @endif
  </div>

  <div class="grid gap-5 lg:grid-cols-2">
    @forelse($tasks as $task)
      <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-100 text-cyan-700">
              <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h3 class="mt-5 text-lg font-black text-slate-950">{{ $task->title }}</h3>
          </div>
          <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">{{ optional($task->due_at)->format('Y-m-d') ?? 'Tanpa deadline' }}</span>
        </div>
        <p class="mt-3 text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($task->description, 120) }}</p>
        <a href="#" class="mt-5 inline-flex items-center gap-2 text-sm font-black text-indigo-600">
          Lihat tugas
          <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
      </article>
    @empty
      <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center lg:col-span-2">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
          <i class="fa-regular fa-clipboard"></i>
        </div>
        <h3 class="mt-4 font-black text-slate-900">Belum ada tugas</h3>
        <p class="mt-2 text-sm text-slate-500">Tugas yang dibuat akan tampil di halaman ini.</p>
      </div>
    @endforelse
  </div>
@endsection
