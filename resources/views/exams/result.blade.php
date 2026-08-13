@extends('layouts.app')

@section('title', 'Hasil Ujian')

@section('content')
@php
  $questions = $attempt->exam->questions;
  $maxScore = (float) $questions->sum('score');
  $earnedScore = (float) ($attempt->score ?? 0);
  $finalScore = $maxScore > 0 ? round($earnedScore / $maxScore * 100) : 0;
  $answered = $attempt->answers->filter(fn ($answer) => filled($answer->answer))->count();
  $multipleChoice = $questions->where('type', 'multiple_choice');
  $correct = $multipleChoice->filter(function ($question) use ($attempt) {
      $answer = $attempt->answers->firstWhere('question_id', $question->id);
      return $answer && $answer->answer === $question->options->firstWhere('is_correct', true)?->option_label;
  })->count();
  $incorrect = $multipleChoice->count() - $correct;
  $durationSeconds = $attempt->submitted_at
      ? abs($attempt->started_at->diffInSeconds($attempt->submitted_at))
      : 0;
  $durationText = $durationSeconds >= 60
      ? floor($durationSeconds / 60).' menit '.($durationSeconds % 60).' detik'
      : $durationSeconds.' detik';
@endphp

<div class="mx-auto max-w-5xl">
  <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="px-6 py-9 text-center sm:px-10" style="background:linear-gradient(135deg,#0f172a 0%,#312e81 100%);color:#fff">
      <div class="text-xs font-black uppercase tracking-[0.2em]" style="color:#c7d2fe">Hasil Akhir Ujian</div>
      <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $attempt->exam->title }}</h1>
      <div class="mx-auto mt-7 flex h-40 w-40 flex-col items-center justify-center rounded-full border-8" style="background:#fff;border-color:#818cf8;color:#312e81">
        <div class="text-6xl font-black">{{ $finalScore }}</div>
        <div class="text-xs font-black uppercase tracking-widest text-slate-500">Skor / 100</div>
      </div>
      <p class="mt-5 font-bold" style="color:{{ $attempt->status === 'submitted' ? '#fde68a' : '#a7f3d0' }}">{{ $attempt->status === 'submitted' ? 'Nilai sementara — menunggu penilaian esai' : ($finalScore >= $attempt->exam->passing_grade ? 'Lulus' : 'Belum mencapai nilai kelulusan') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4">
      @foreach([['Benar', $correct, '#047857'], ['Salah', $incorrect, '#be123c'], ['Terjawab', $answered.'/'.$questions->count(), '#4338ca'], ['Durasi', $durationText, '#334155']] as [$label, $value, $color])
        <div class="bg-white p-5 text-center"><div class="text-xl font-black" style="color:{{ $color }}">{{ $value }}</div><div class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</div></div>
      @endforeach
    </div>
  </section>

  <div class="mt-8 flex flex-wrap items-end justify-between gap-3">
    <div><h2 class="text-2xl font-black text-slate-900">Pembahasan Jawaban</h2><p class="mt-1 text-slate-500">Pelajari jawaban yang benar dan bagian yang masih perlu diperbaiki.</p></div>
    <a href="{{ route('exams.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-bold text-slate-700 hover:bg-slate-50">Kembali ke Ujian Saya</a>
  </div>

  <div class="mt-5 space-y-5">
    @foreach($questions as $index => $question)
      @php
        $answer = $attempt->answers->firstWhere('question_id', $question->id);
        $correctOption = $question->options->firstWhere('is_correct', true);
        $isCorrect = $question->type === 'multiple_choice' && $answer && $answer->answer === $correctOption?->option_label;
        $isEssay = $question->type === 'essay';
      @endphp
      <article class="overflow-hidden rounded-3xl border {{ $isEssay ? 'border-amber-200' : ($isCorrect ? 'border-emerald-200' : 'border-rose-200') }} bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:px-7">
          <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-black text-white">Soal {{ $index + 1 }}</span><span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">{{ $question->subject?->name }}</span>@if($question->topic)<span class="text-xs font-bold text-slate-500">{{ $question->topic->name }}</span>@endif</div>
          <span class="rounded-full px-3 py-1 text-xs font-black" style="background:{{ $isEssay ? '#fef3c7' : ($isCorrect ? '#d1fae5' : '#ffe4e6') }};color:{{ $isEssay ? '#92400e' : ($isCorrect ? '#065f46' : '#9f1239') }}">{{ $isEssay ? 'Menunggu penilaian' : ($isCorrect ? 'Benar' : 'Salah') }}</span>
        </div>
        <div class="p-5 sm:p-7">
          @if($question->story)<div class="math-content mb-5 whitespace-pre-wrap rounded-2xl bg-indigo-50 p-4 leading-7 text-slate-700">{!! strip_tags($question->story, '<p><br><strong><em>') !!}</div>@endif
          <div class="math-content font-bold leading-7 text-slate-900">{!! strip_tags($question->question, '<p><br><strong><em>') !!}</div>
          <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4"><div class="text-xs font-black uppercase tracking-wide text-slate-400">Jawaban Anda</div><div class="math-content mt-2 font-bold text-slate-800">{{ filled($answer?->answer) ? $answer->answer : 'Tidak dijawab' }}</div></div>
            <div class="rounded-2xl bg-emerald-50 p-4"><div class="text-xs font-black uppercase tracking-wide text-emerald-600">Jawaban Benar / Referensi</div><div class="math-content mt-2 font-bold text-emerald-900">{{ $isEssay ? ($question->answer_key ?: 'Menunggu penilaian pengajar') : (($correctOption?->option_label ? $correctOption->option_label.'. ' : '').($correctOption?->option_text ?? 'Belum tersedia')) }}</div></div>
          </div>
          <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4"><div class="text-xs font-black uppercase tracking-wide text-indigo-600">Pembahasan</div><div class="math-content mt-2 whitespace-pre-wrap leading-7 text-slate-700">{{ $question->explanation ?: 'Pembahasan belum ditambahkan oleh pengajar.' }}</div></div>
        </div>
      </article>
    @endforeach
  </div>
</div>

@include('questions.math')
@endsection
