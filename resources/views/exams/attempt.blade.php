@extends('layouts.app')

@section('title', $attempt->exam->title)

@section('content')
@php
  $questionCount = $attempt->exam->questions->count();
  $answeredIds = $attempt->answers->filter(fn ($answer) => filled($answer->answer))->pluck('question_id')->all();
@endphp

<style>
  .exam-shell { --exam-indigo:#4f46e5; --exam-indigo-dark:#312e81; --exam-green:#059669; --exam-slate:#0f172a; }
  .exam-topbar { background:linear-gradient(135deg,#4338ca 0%,#4f46e5 52%,#6366f1 100%); color:#fff; }
  .exam-nav { background:#fff; border:1px solid #cbd5e1; color:#475569; }
  .exam-nav:hover { border-color:#818cf8; background:#eef2ff; color:#3730a3; }
  .exam-nav.is-answered { background:#d1fae5; border-color:#6ee7b7; color:#065f46; }
  .exam-nav.is-active { background:#4f46e5 !important; border-color:#4f46e5 !important; color:#fff !important; box-shadow:0 7px 16px rgba(79,70,229,.25); }
  .exam-option:has(input:checked) { background:#eef2ff; border-color:#6366f1; box-shadow:0 0 0 1px #6366f1; }
  .exam-option:has(input:checked) .option-letter { background:#4f46e5; border-color:#4f46e5; color:#fff; }
  .exam-option { transition:border-color .18s,background .18s,box-shadow .18s,transform .18s; }
  .exam-option:hover { border-color:#a5b4fc; background:#f8faff; transform:translateY(-1px); }
  @media (min-width:1024px) { .exam-workspace { grid-template-columns:280px minmax(0,1fr); } }
</style>

<div class="exam-shell mx-auto max-w-[1500px]">
  <header class="exam-topbar sticky top-2 z-20 mb-5 overflow-hidden rounded-3xl border shadow-lg" style="border-color:#818cf8;box-shadow:0 14px 32px rgba(79,70,229,.18)">
    <div class="grid items-center gap-4 px-5 py-4 sm:grid-cols-[1fr_auto_auto] sm:px-7">
      <div class="min-w-0 sm:max-w-md">
        <div class="text-[11px] font-black uppercase tracking-[0.2em]" style="color:#e0e7ff">Ujian Berlangsung</div>
        <h1 class="mt-1 truncate text-xl font-black">{{ $attempt->exam->title }}</h1>
        <div class="mt-1 truncate text-xs font-semibold" style="color:#e0e7ff">{{ $attempt->exam->description ?: 'Jawaban tersimpan otomatis' }}</div>
      </div>
      <div class="hidden min-w-28 text-center sm:block">
        <div class="text-[11px] font-bold uppercase tracking-widest" style="color:#e0e7ff">Nomor Soal</div>
        <div id="currentNumber" class="mt-1 text-2xl font-black">1 / {{ $questionCount }}</div>
      </div>
      <div id="timerBox" class="min-w-40 rounded-2xl border px-5 py-2 text-center" style="background:rgba(49,46,129,.58);border-color:#a5b4fc">
        <div class="text-[11px] font-black uppercase tracking-widest" style="color:#e0e7ff">Sisa Waktu</div>
        <div id="timer" class="font-mono text-2xl font-black tabular-nums" style="color:#fff">--:--</div>
      </div>
    </div>
  </header>

  <div class="exam-workspace grid gap-5">
    <aside class="order-2 h-fit rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:order-1 lg:sticky lg:top-32">
      <div class="rounded-2xl p-4" style="background:#eef2ff">
        <div class="text-[11px] font-black uppercase tracking-widest" style="color:#6366f1">Peserta</div>
        <div class="mt-1 truncate font-black text-slate-900">{{ auth()->user()->name }}</div>
      </div>
      <div class="mt-3 rounded-2xl border border-slate-200 p-4">
        <div class="text-[11px] font-black uppercase tracking-widest text-slate-400">Subtest Aktif</div>
        <div id="activeSubject" class="mt-1 font-black text-slate-800">{{ $attempt->exam->questions->first()?->subject?->name ?? 'Subtest Umum' }}</div>
      </div>

      <div class="mt-5 flex items-end justify-between gap-3">
        <div><span id="answeredCount" class="text-3xl font-black" style="color:#059669">{{ count($answeredIds) }}</span><span class="text-sm font-bold text-slate-500"> dari {{ $questionCount }}</span></div>
        <span class="text-xs font-bold text-slate-400">soal dijawab</span>
      </div>
      <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100"><div id="progressBar" class="h-full rounded-full transition-all" style="width:{{ $questionCount ? count($answeredIds) / $questionCount * 100 : 0 }}%;background:#059669"></div></div>

      <div class="mt-5 grid grid-cols-5 gap-2 lg:grid-cols-5">
        @foreach($attempt->exam->questions as $index => $question)
          <button type="button" class="exam-nav nav h-11 rounded-xl font-black transition {{ in_array($question->id, $answeredIds) ? 'is-answered' : '' }}" data-go="{{ $index }}" data-id="{{ $question->id }}" aria-label="Buka soal {{ $index + 1 }}">{{ $index + 1 }}</button>
        @endforeach
      </div>

      <div class="mt-5 grid gap-2 text-xs font-semibold text-slate-500">
        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded" style="background:#4f46e5"></span>Nomor aktif</div>
        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded" style="background:#6ee7b7"></span>Sudah dijawab</div>
        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded border border-slate-300 bg-white"></span>Belum dijawab</div>
      </div>

      <div id="incompleteNotice" class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-semibold leading-5 text-amber-800">Jawab seluruh soal agar tombol penyelesaian ujian tersedia.</div>
      <form id="submitForm" method="post" action="{{ route('attempts.submit', $attempt) }}" class="mt-5" style="display:{{ count($answeredIds) === $questionCount && $questionCount > 0 ? 'block' : 'none' }}" onsubmit="return confirm('Semua jawaban sudah terisi. Simpan dan akhiri ujian?')">
        @csrf
        <button class="w-full rounded-2xl px-5 py-3 font-black shadow-sm" style="background:#059669;color:#fff"><i class="fa-solid fa-circle-check mr-2"></i>Selesai dan Kirim</button>
      </form>
    </aside>

    <main class="order-1 min-w-0 lg:order-2">
      @foreach($attempt->exam->questions as $index => $question)
        @php
          $savedAnswer = $attempt->answers->firstWhere('question_id', $question->id)?->answer;
          $options = $attempt->exam->randomize_options ? $question->options->shuffle() : $question->options;
        @endphp
        <section class="question {{ $index ? 'hidden' : '' }} overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" data-index="{{ $index }}" data-id="{{ $question->id }}" data-subject="{{ $question->subject?->name ?? 'Subtest Umum' }}">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-7">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full px-3 py-1 text-xs font-black" style="background:#312e81;color:#fff">No. {{ $index + 1 }}</span>
              <span class="rounded-full px-3 py-1 text-xs font-black" style="background:#e0e7ff;color:#3730a3">{{ $question->subject?->name ?? 'Subtest Umum' }}</span>
              @if($question->topic)<span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-600">{{ $question->topic->name }}</span>@endif
            </div>
            <span class="text-xs font-black uppercase tracking-wide text-slate-400">{{ $question->type === 'essay' ? 'Esai' : 'Pilihan Ganda' }}</span>
          </div>

          <div class="p-5 sm:p-8">
            @if($question->story)
              <div class="mb-7 rounded-2xl border border-indigo-100 p-5 sm:p-6" style="background:#f5f7ff">
                <div class="mb-3 text-xs font-black uppercase tracking-widest" style="color:#4f46e5">Bacaan / Informasi Soal</div>
                <div class="math-content whitespace-pre-wrap leading-8 text-slate-700">{!! strip_tags($question->story, '<p><br><strong><em>') !!}</div>
              </div>
            @endif

            <div class="math-content text-lg font-bold leading-8 text-slate-900">{!! strip_tags($question->question, '<p><br><strong><em>') !!}</div>
            @if($question->instructions)<p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">{{ $question->instructions }}</p>@endif

            @if($question->type === 'multiple_choice')
              <div class="mt-7 space-y-3">
                @foreach($options as $option)
                  <label class="exam-option flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-slate-200 p-4 sm:p-5">
                    <input class="answer sr-only" type="radio" name="q{{ $question->id }}" value="{{ $option->option_label }}" @checked($savedAnswer === $option->option_label)>
                    <span class="option-letter grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 border-slate-300 bg-white text-sm font-black text-slate-600">{{ $option->option_label }}</span>
                    <span class="math-content min-w-0 leading-7 text-slate-700">{{ $option->option_text }}</span>
                  </label>
                @endforeach
              </div>
            @else
              <textarea class="answer mt-7 w-full rounded-2xl border-slate-300 p-5 leading-7 focus:border-indigo-500 focus:ring-indigo-500" rows="10" placeholder="Tulis jawaban Anda secara lengkap...">{{ $savedAnswer }}</textarea>
            @endif
            <div class="save-status mt-4 min-h-5 text-sm font-bold text-emerald-600">{{ filled($savedAnswer) ? 'Jawaban tersimpan' : '' }}</div>
          </div>
        </section>
      @endforeach

      <div class="sticky bottom-3 mt-5 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
        <button id="prev" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-black text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-2"></i>Sebelumnya</button>
        <div id="mobileNumber" class="text-sm font-black text-slate-500 sm:hidden">1 / {{ $questionCount }}</div>
        <button id="next" type="button" class="rounded-xl px-6 py-3 font-black shadow-sm" style="background:#4f46e5;color:#fff">Selanjutnya<i class="fa-solid fa-arrow-right ml-2"></i></button>
      </div>
    </main>
  </div>
</div>

@include('questions.math')
<script>
document.addEventListener('DOMContentLoaded', () => {
  let current = 0;
  const sections = [...document.querySelectorAll('.question')];
  const navButtons = [...document.querySelectorAll('.nav')];
  const deadline = {{ $deadline->timestamp }} * 1000;
  const csrf = '{{ csrf_token() }}';
  const saveTimers = new Map();
  const answered = new Set(@json($answeredIds));
  const pendingSaves = new Set();
  const nextButton = document.querySelector('#next');
  const previousButton = document.querySelector('#prev');
  const submitForm = document.querySelector('#submitForm');
  const incompleteNotice = document.querySelector('#incompleteNotice');

  const updateProgress = () => {
    const complete = sections.length > 0 && answered.size === sections.length && pendingSaves.size === 0;
    document.querySelector('#answeredCount').textContent = answered.size;
    document.querySelector('#progressBar').style.width = `${sections.length ? answered.size / sections.length * 100 : 0}%`;
    navButtons.forEach((button, index) => {
      button.classList.toggle('is-answered', answered.has(Number(button.dataset.id)));
      button.classList.toggle('is-active', index === current);
    });
    submitForm.style.display = complete ? 'block' : 'none';
    incompleteNotice.style.display = complete ? 'none' : 'block';
    nextButton.style.visibility = current === sections.length - 1 ? 'hidden' : 'visible';
  };

  const show = (index) => {
    current = Math.max(0, Math.min(index, sections.length - 1));
    sections.forEach((section, sectionIndex) => section.classList.toggle('hidden', sectionIndex !== current));
    const numberText = `${current + 1} / ${sections.length}`;
    document.querySelector('#currentNumber').textContent = numberText;
    document.querySelector('#mobileNumber').textContent = numberText;
    document.querySelector('#activeSubject').textContent = sections[current]?.dataset.subject || 'Subtest Umum';
    previousButton.disabled = current === 0;
    previousButton.style.opacity = current === 0 ? '.4' : '1';
    updateProgress();
    window.scrollTo({top:0,behavior:'smooth'});
  };

  previousButton.addEventListener('click', () => show(current - 1));
  nextButton.addEventListener('click', () => show(current + 1));
  navButtons.forEach(button => button.addEventListener('click', () => show(Number(button.dataset.go))));

  document.querySelectorAll('.answer').forEach(element => {
    element.addEventListener('input', () => {
      const section = element.closest('.question');
      const questionId = Number(section.dataset.id);
      const answeredNow = element.type === 'radio' ? Boolean(section.querySelector('.answer:checked')) : element.value.trim() !== '';
      answeredNow ? answered.add(questionId) : answered.delete(questionId);
      pendingSaves.add(questionId);
      updateProgress();
      clearTimeout(saveTimers.get(section));
      section.querySelector('.save-status').textContent = 'Menyimpan jawaban...';
      saveTimers.set(section, setTimeout(async () => {
        try {
          const response = await fetch('{{ route('attempts.autosave', $attempt) }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body:JSON.stringify({question_id:questionId,answer:element.value})
          });
          if (!response.ok) throw new Error('save_failed');
          section.querySelector('.save-status').textContent = 'Jawaban tersimpan otomatis';
          section.querySelector('.save-status').style.color = '#059669';
        } catch (error) {
          answered.delete(questionId);
          section.querySelector('.save-status').textContent = 'Jawaban gagal tersimpan. Periksa koneksi lalu coba lagi.';
          section.querySelector('.save-status').style.color = '#e11d48';
        } finally {
          pendingSaves.delete(questionId);
          updateProgress();
        }
      }, 500));
    });
  });

  const updateTimer = () => {
    const seconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remaining = seconds % 60;
    document.querySelector('#timer').textContent = hours > 0
      ? `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(remaining).padStart(2,'0')}`
      : `${String(minutes).padStart(2,'0')}:${String(remaining).padStart(2,'0')}`;
    document.querySelector('#timerBox').style.borderColor = seconds <= 300 ? '#fecdd3' : '#a5b4fc';
    document.querySelector('#timer').style.color = seconds <= 300 ? '#fda4af' : '#fff';
    if (seconds === 0) submitForm.submit();
  };

  updateTimer();
  setInterval(updateTimer, 1000);
  show(0);
});
</script>
@endsection
