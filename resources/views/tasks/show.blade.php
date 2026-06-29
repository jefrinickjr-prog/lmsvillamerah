@extends('layouts.app')

@section('title', $task->title)

@section('content')
  @php
    $questions = $task->questions ?? [];
    $typeLabels = [
      'essay' => 'Esai',
      'multiple_choice' => 'Pilihan Ganda',
      'questionnaire' => 'Kuesioner',
    ];
    $isStudent = auth()->user()?->role === 'student';
    $canManageTasks = in_array(auth()->user()?->role, ['teacher', 'admin', 'super_admin'], true);
    $savedAnswers = $submission?->answers ?? [];
    $isSubmitted = (bool) $submission?->submitted_at;
    $endsAt = ($isStudent && $task->duration_minutes && $submission?->started_at)
      ? $submission->started_at->copy()->addMinutes($task->duration_minutes)
      : null;
    $remainingSeconds = $endsAt ? max(0, now()->diffInSeconds($endsAt, false)) : null;
    $canAnswer = $isStudent && ! $isSubmitted && $remainingSeconds !== 0;
  @endphp

  <div class="mx-auto max-w-5xl">
    <div class="mb-6">
      <a href="{{ route('tasks.index') }}" class="btn-link-strong inline-flex items-center gap-2 text-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke tugas
      </a>
      <div class="mt-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div>
          <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Detail Tugas</p>
          <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">{{ $task->title }}</h2>
          <div class="mt-3 flex flex-wrap gap-2">
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ \App\Models\Task::typeLabel($task->task_type ?? 'assignment') }}</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ optional($task->due_at)->format('Y-m-d H:i') ?? 'Tanpa deadline' }}</span>
            @if($task->duration_minutes)
              <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">{{ $task->duration_minutes }} menit</span>
            @endif
          </div>
        </div>
        @if($canManageTasks)
          <div class="flex flex-wrap gap-2">
            <a href="{{ route('tasks.edit', $task) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 transition hover:-translate-y-0.5 hover:bg-indigo-50 hover:text-indigo-700 hover:shadow-lg">
              <i class="fa-solid fa-pen"></i>
              Edit
            </a>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Hapus tugas ini? Jawaban siswa untuk tugas ini juga akan terhapus.');">
              @csrf
              @method('DELETE')
              <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-lg shadow-rose-100 transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-xl" type="submit">
                <i class="fa-solid fa-trash"></i>
                Hapus
              </button>
            </form>
          </div>
        @endif
      </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
      <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm lg:col-span-2">
        <h3 class="font-black text-slate-900">Instruksi</h3>
        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $task->description ?: 'Belum ada instruksi tambahan.' }}</p>

        @if($task->attachment_path)
          <div class="mt-5 rounded-2xl border border-rose-100 bg-rose-50 p-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
              <div class="flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-rose-600">
                  <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div>
                  <div class="font-black text-slate-900">Lampiran PDF</div>
                  <div class="text-xs font-semibold text-slate-500">Buka modul atau lembar soal.</div>
                </div>
              </div>
              <a href="{{ route('tasks.attachment', $task) }}" class="btn-action btn-download-solid rounded-2xl px-4 py-3 text-sm">
                Unduh PDF
                <i class="fa-solid fa-download text-xs"></i>
              </a>
            </div>
          </div>
        @endif

        <form id="taskAnswerForm" method="POST" action="{{ route('tasks.submit', $task) }}" class="mt-6">
          @csrf
          <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <h3 class="font-black text-slate-900">Soal</h3>
            @if($isStudent && $task->duration_minutes && ! empty($questions))
              <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-black text-amber-700">
                <i class="fa-solid fa-stopwatch mr-2"></i>
                <span id="taskTimer" data-remaining="{{ $remainingSeconds ?? 0 }}">
                  {{ $isSubmitted ? 'Sudah dikirim' : 'Memuat timer...' }}
                </span>
              </div>
            @endif
          </div>
          @if(session('success'))
            <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
          @endif
          @if($errors->any())
            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
          @endif
          @if($isSubmitted)
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">Jawaban sudah dikirim pada {{ $submission->submitted_at->format('d M Y H:i') }}.</div>
          @elseif($remainingSeconds === 0)
            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">Waktu pengerjaan sudah habis.</div>
          @endif
          <div class="mt-4 space-y-4">
            @forelse($questions as $index => $question)
              <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-indigo-600 px-3 py-1 text-xs font-black text-white">Soal {{ $index + 1 }}</span>
                  <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600">{{ $typeLabels[$question['type'] ?? 'essay'] ?? 'Esai' }}</span>
                </div>
                <p class="font-bold leading-7 text-slate-900">{{ $question['prompt'] ?? '' }}</p>
                @if(($question['type'] ?? null) === 'multiple_choice' && ! empty($question['options']))
                  <div class="mt-3 grid gap-2">
                    @foreach($question['options'] as $option)
                      <label class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                        <input type="radio" name="answers[{{ $index }}]" value="{{ $option }}" class="h-4 w-4" @checked(($savedAnswers[$index] ?? null) === $option) @disabled(! $canAnswer)>
                        <span>{{ $option }}</span>
                      </label>
                    @endforeach
                  </div>
                @else
                  <textarea name="answers[{{ $index }}]" class="mt-3 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 disabled:text-slate-400" rows="4" placeholder="Tulis jawaban..." @disabled(! $canAnswer)>{{ $savedAnswers[$index] ?? '' }}</textarea>
                @endif
              </div>
            @empty
              <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm font-semibold text-slate-500">Belum ada soal pada tugas ini.</div>
            @endforelse
          </div>
          @if($canAnswer && ! empty($questions))
            <button class="btn-action btn-primary-solid mt-5 rounded-2xl px-5 py-3 text-sm" type="submit">
              <i class="fa-solid fa-paper-plane"></i>
              Kirim Jawaban
            </button>
          @endif
        </form>
      </section>

      <aside class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <h3 class="font-black text-slate-900">Video Terkait</h3>
        @if($task->material)
          <div class="mt-4 rounded-2xl bg-slate-50 p-4">
            <div class="font-black text-slate-900">{{ $task->material->title ?? 'Video pembelajaran' }}</div>
            <div class="mt-2 text-sm font-semibold text-slate-500">{{ \App\Models\User::videoAccessLabel($task->material->program_type ?? 'gambar') }}</div>
          </div>
        @else
          <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">Tugas ini tidak dikaitkan dengan video pembelajaran.</div>
        @endif
        @if($task->material?->youtube_embed_url)
          <div class="video-frame mt-4 overflow-hidden rounded-2xl bg-slate-100">
            <iframe class="h-full w-full" src="{{ $task->material->youtube_embed_url }}" title="Video {{ $task->material->title }}" allowfullscreen></iframe>
          </div>
        @endif
      </aside>
    </div>
  </div>

  @if($isStudent && $task->duration_minutes && ! empty($questions) && ! $isSubmitted)
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const timer = document.getElementById('taskTimer');
        const form = document.getElementById('taskAnswerForm');
        let remaining = Number(timer?.dataset.remaining || 0);
        let submitted = false;

        const formatTime = (seconds) => {
          const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
          const rest = Math.floor(seconds % 60).toString().padStart(2, '0');
          return `${minutes}:${rest}`;
        };

        const tick = () => {
          if (!timer) {
            return;
          }

          if (remaining <= 0) {
            timer.textContent = 'Waktu habis';
            if (form && !submitted) {
              submitted = true;
              form.submit();
            }
            return;
          }

          timer.textContent = formatTime(remaining);
          remaining -= 1;
          window.setTimeout(tick, 1000);
        };

        tick();
      });
    </script>
  @endif
@endsection
