@extends('layouts.app')

@section('title', $attempt->exam->title)

@section('content')
@php
  $questionCount = $attempt->exam->questions->count();
  $answeredIds = $attempt->answers->filter(fn ($answer) => filled($answer->answer))->pluck('question_id')->all();
@endphp

<div class="mx-auto max-w-7xl">
  <header class="sticky top-3 z-20 mb-6 rounded-3xl border border-slate-700 px-5 py-4 shadow-xl shadow-slate-300/30 sm:px-7" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:#fff">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <div class="text-xs font-black uppercase tracking-[0.18em]" style="color:#a5b4fc">Ujian Berlangsung</div>
        <h1 class="mt-1 text-xl font-black sm:text-2xl">{{ $attempt->exam->title }}</h1>
        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold" style="color:#cbd5e1">
          <span>{{ $questionCount }} soal</span>
          <span>Durasi {{ $attempt->exam->duration }} menit</span>
          <span>Jawaban tersimpan otomatis</span>
        </div>
      </div>

      <div id="timerBox" class="min-w-40 rounded-2xl border px-5 py-3 text-center" style="background:#020617;border-color:#475569;color:#fff">
        <div class="text-[11px] font-black uppercase tracking-widest" style="color:#cbd5e1">Sisa Waktu</div>
        <div id="timer" class="mt-1 font-mono text-3xl font-black tabular-nums" style="color:#fff">--:--</div>
      </div>
    </div>
  </header>

  <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_290px]">
    <main>
      @foreach($attempt->exam->questions as $index => $question)
        @php
          $savedAnswer = $attempt->answers->firstWhere('question_id', $question->id)?->answer;
          $options = $attempt->exam->randomize_options ? $question->options->shuffle() : $question->options;
        @endphp

        <section class="question {{ $index ? 'hidden' : '' }} overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" data-index="{{ $index }}" data-id="{{ $question->id }}">
          <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-4 sm:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-black text-white">Soal {{ $index + 1 }}</span>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">{{ $question->subject?->name ?? 'Subtest Umum' }}</span>
                @if($question->topic)
                  <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-600">{{ $question->topic->name }}</span>
                @endif
              </div>
              <span class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $question->type === 'essay' ? 'Esai' : 'Pilihan Ganda' }}</span>
            </div>
          </div>

          <div class="p-6 sm:p-8">
            @if($question->story)
              <div class="mb-7 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-5 sm:p-6">
                <div class="mb-3 text-xs font-black uppercase tracking-widest text-indigo-600">Bacaan / Informasi Soal</div>
                <div class="math-content whitespace-pre-wrap leading-8 text-slate-700">{!! strip_tags($question->story, '<p><br><strong><em>') !!}</div>
              </div>
            @endif

            <div class="math-content text-lg font-semibold leading-8 text-slate-900">{!! strip_tags($question->question, '<p><br><strong><em>') !!}</div>

            @if($question->instructions)
              <p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">{{ $question->instructions }}</p>
            @endif

            @if($question->type === 'multiple_choice')
              <div class="mt-7 space-y-3">
                @foreach($options as $option)
                  <label class="option-label flex cursor-pointer items-start gap-4 rounded-2xl border-2 border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40">
                    <input class="answer mt-1 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" type="radio" name="q{{ $question->id }}" value="{{ $option->option_label }}" @checked($savedAnswer === $option->option_label)>
                    <span class="flex min-w-0 gap-2 leading-7 text-slate-700"><b>{{ $option->option_label }}.</b> <span class="math-content">{{ $option->option_text }}</span></span>
                  </label>
                @endforeach
              </div>
            @else
              <textarea class="answer mt-7 w-full rounded-2xl border-slate-300 p-4 leading-7 focus:border-indigo-500 focus:ring-indigo-500" rows="10" placeholder="Tulis jawaban Anda secara lengkap...">{{ $savedAnswer }}</textarea>
            @endif

            <div class="save-status mt-4 min-h-5 text-sm font-bold text-emerald-600">{{ filled($savedAnswer) ? 'Jawaban sudah tersimpan' : '' }}</div>
          </div>
        </section>
      @endforeach

      <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <button id="prev" type="button" class="rounded-2xl border border-slate-300 bg-white px-5 py-3 font-black text-slate-700 shadow-sm hover:bg-slate-50">← Sebelumnya</button>
        <button id="next" type="button" class="rounded-2xl bg-indigo-600 px-6 py-3 font-black text-white shadow-sm hover:bg-indigo-700">Berikutnya →</button>
      </div>

      <div id="completionPanel" class="mt-5 rounded-3xl border p-5 shadow-sm sm:p-6" style="display:{{ count($answeredIds) === $questionCount && $questionCount > 0 ? 'block' : 'none' }};background:#ecfdf5;border-color:#6ee7b7">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="font-black" style="color:#065f46">Semua soal sudah terjawab</div>
            <p class="mt-1 text-sm" style="color:#047857">Anda tidak perlu menunggu waktu habis. Periksa kembali atau langsung simpan hasil ujian.</p>
          </div>
          <form id="submitForm" method="post" action="{{ route('attempts.submit', $attempt) }}" onsubmit="return confirm('Semua jawaban sudah terisi. Simpan dan akhiri ujian?')">
            @csrf
            <button class="rounded-2xl px-6 py-3 font-black shadow-sm" style="background:#059669;color:#fff">Simpan dan Akhiri Ujian</button>
          </form>
        </div>
      </div>
    </main>

    <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-40">
      <div class="flex items-center justify-between">
        <h2 class="font-black text-slate-900">Daftar Soal</h2>
        <span id="progressText" class="text-xs font-black text-indigo-600">{{ count($answeredIds) }}/{{ $questionCount }} dijawab</span>
      </div>
      <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div id="progressBar" class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $questionCount ? count($answeredIds) / $questionCount * 100 : 0 }}%"></div></div>

      <div class="mt-5 grid grid-cols-5 gap-2">
        @foreach($attempt->exam->questions as $index => $question)
          <button type="button" class="nav h-10 rounded-xl border font-black transition {{ in_array($question->id, $answeredIds) ? 'answered border-emerald-200 bg-emerald-100 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-600' }}" data-go="{{ $index }}" data-id="{{ $question->id }}">{{ $index + 1 }}</button>
        @endforeach
      </div>

      <div class="mt-5 space-y-2 text-xs font-semibold text-slate-500">
        <div><span class="mr-2 inline-block h-3 w-3 rounded bg-indigo-600"></span>Soal aktif</div>
        <div><span class="mr-2 inline-block h-3 w-3 rounded bg-emerald-200"></span>Sudah dijawab</div>
        <div><span class="mr-2 inline-block h-3 w-3 rounded bg-slate-100 ring-1 ring-slate-200"></span>Belum dijawab</div>
      </div>

      <div id="incompleteNotice" class="mt-6 rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">
        Lengkapi seluruh jawaban untuk mengirim hasil ujian.
      </div>

    </aside>
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
  const timers = new Map();
  const answered = new Set(@json($answeredIds));
  const pendingSaves = new Set();
  const nextButton = document.querySelector('#next');
  const previousButton = document.querySelector('#prev');
  const submitForm = document.querySelector('#submitForm');
  const incompleteNotice = document.querySelector('#incompleteNotice');
  const completionPanel = document.querySelector('#completionPanel');

  const isAnswered = (element) => element.type === 'radio'
    ? Boolean(element.closest('.question').querySelector('.answer:checked'))
    : element.value.trim() !== '';

  const updateProgress = () => {
    const complete = sections.length > 0 && answered.size === sections.length && pendingSaves.size === 0;
    document.querySelector('#progressText').textContent = `${answered.size}/${sections.length} dijawab`;
    document.querySelector('#progressBar').style.width = `${sections.length ? answered.size / sections.length * 100 : 0}%`;
    navButtons.forEach((button, index) => {
      const isComplete = answered.has(Number(button.dataset.id));
      button.classList.toggle('answered', isComplete);
      button.classList.toggle('bg-emerald-100', isComplete && index !== current);
      button.classList.toggle('border-emerald-200', isComplete && index !== current);
      button.classList.toggle('text-emerald-800', isComplete && index !== current);
      button.classList.toggle('bg-slate-50', !isComplete && index !== current);
      button.classList.toggle('border-slate-200', !isComplete && index !== current);
      button.classList.toggle('text-slate-600', !isComplete && index !== current);
    });
    completionPanel.style.display = complete ? 'block' : 'none';
    incompleteNotice.style.display = complete ? 'none' : 'block';
    nextButton.style.display = complete || current === sections.length - 1 ? 'none' : 'inline-flex';
  };

  const show = (index) => {
    current = Math.max(0, Math.min(index, sections.length - 1));
    sections.forEach((section, sectionIndex) => section.classList.toggle('hidden', sectionIndex !== current));
    navButtons.forEach((button, buttonIndex) => {
      button.classList.toggle('bg-indigo-600', buttonIndex === current);
      button.classList.toggle('border-indigo-600', buttonIndex === current);
      button.classList.toggle('text-white', buttonIndex === current);
      if (buttonIndex !== current && button.classList.contains('answered')) {
        button.classList.add('bg-emerald-100', 'border-emerald-200', 'text-emerald-800');
      }
    });
    previousButton.disabled = current === 0;
    previousButton.classList.toggle('opacity-40', current === 0);
    updateProgress();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  previousButton.addEventListener('click', () => show(current - 1));
  nextButton.addEventListener('click', () => show(current + 1));
  navButtons.forEach((button) => button.addEventListener('click', () => show(Number(button.dataset.go))));

  document.querySelectorAll('.answer').forEach((element) => {
    element.addEventListener('input', () => {
      const section = element.closest('.question');
      const questionId = Number(section.dataset.id);
      const answeredNow = isAnswered(element);
      answeredNow ? answered.add(questionId) : answered.delete(questionId);
      pendingSaves.add(questionId);

      const nav = navButtons[Number(section.dataset.index)];
      nav.classList.toggle('answered', answeredNow);
      nav.classList.toggle('bg-emerald-100', answeredNow && Number(section.dataset.index) !== current);
      nav.classList.toggle('border-emerald-200', answeredNow && Number(section.dataset.index) !== current);
      nav.classList.toggle('text-emerald-800', answeredNow && Number(section.dataset.index) !== current);
      updateProgress();

      clearTimeout(timers.get(section));
      section.querySelector('.save-status').textContent = 'Menyimpan...';
      timers.set(section, setTimeout(async () => {
        try {
          const response = await fetch('{{ route('attempts.autosave', $attempt) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: JSON.stringify({question_id: questionId, answer: element.type === 'radio' ? element.value : element.value})
          });
          section.querySelector('.save-status').textContent = response.ok ? 'Jawaban tersimpan otomatis' : 'Jawaban gagal tersimpan. Coba lagi.';
          section.querySelector('.save-status').classList.toggle('text-rose-600', !response.ok);
          if (!response.ok) answered.delete(questionId);
        } catch (error) {
          section.querySelector('.save-status').textContent = 'Koneksi bermasalah. Jawaban belum tersimpan.';
          section.querySelector('.save-status').classList.add('text-rose-600');
          answered.delete(questionId);
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
    const remainingSeconds = seconds % 60;
    document.querySelector('#timer').textContent = hours > 0
      ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
      : `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    document.querySelector('#timerBox').classList.toggle('border-rose-500', seconds <= 300);
    document.querySelector('#timer').classList.toggle('text-rose-400', seconds <= 300);
    document.querySelector('#timerBox').style.borderColor = seconds <= 300 ? '#fb7185' : '#475569';
    document.querySelector('#timer').style.color = seconds <= 300 ? '#fda4af' : '#ffffff';
    if (seconds === 0) submitForm.submit();
  };

  updateTimer();
  setInterval(updateTimer, 1000);
  show(0);
});
</script>
@endsection
