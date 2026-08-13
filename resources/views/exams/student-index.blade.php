@extends('layouts.app')

@section('title', 'Ujian Saya')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h2 class="text-3xl font-black text-slate-900">Ujian Saya</h2>
    <p class="mt-1 text-slate-500">Pilih ujian yang tersedia atau lanjutkan ujian yang waktunya masih berjalan.</p>
  </div>
</div>

<div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
  @forelse($exams as $exam)
    @php
      $activeAttempt = $exam->attempts->firstWhere('status', 'in_progress');
      $completedAttempt = $exam->attempts->first(fn ($attempt) => in_array($attempt->status, ['submitted', 'graded']));
      $deadline = $activeAttempt?->started_at?->copy()->addMinutes($exam->duration);
      $canContinue = $activeAttempt && $deadline && now()->lt($deadline);
      $timeExpired = $activeAttempt && !$canContinue;
      $isFinished = ($completedAttempt || $timeExpired) && !$exam->allow_retake;
    @endphp

    <article class="flex min-h-72 flex-col rounded-3xl border {{ $isFinished ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-white' }} p-6 shadow-sm">
      <div class="flex items-start justify-between gap-3">
        @if($canContinue)
          <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">Sedang dikerjakan</span>
        @elseif($isFinished)
          <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Ujian selesai</span>
        @elseif($completedAttempt && $exam->allow_retake)
          <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">Dapat diulang</span>
        @else
          <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">Tersedia</span>
        @endif
      </div>

      <h3 class="mt-4 text-xl font-black text-slate-900">{{ $exam->title }}</h3>
      <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">{{ $exam->description ?: 'Tidak ada deskripsi ujian.' }}</p>

      <div class="mt-5 flex flex-wrap gap-3 text-sm font-bold text-slate-600">
        <span class="rounded-xl bg-slate-100 px-3 py-2">{{ $exam->duration }} menit</span>
        <span class="rounded-xl bg-slate-100 px-3 py-2">{{ $exam->questions_count }} soal</span>
      </div>

      @if($canContinue)
        <div class="mt-4 rounded-2xl bg-amber-50 p-3 text-sm font-semibold text-amber-800">
          Waktu masih berjalan sampai {{ $deadline->format('H:i') }}. Silakan lanjutkan pengerjaan.
        </div>
        <form method="post" action="{{ route('exams.start', $exam) }}" class="mt-4">
          @csrf
          <button class="btn-action w-full rounded-2xl bg-amber-500 px-6 font-black text-white hover:bg-amber-600">Lanjutkan Ujian</button>
        </form>
      @elseif($isFinished)
        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
          Anda sudah mengikuti ujian ini. Ujian tidak dapat dimasuki kembali.
        </div>
        @if($completedAttempt)
          <a href="{{ route('attempts.result', $completedAttempt) }}" class="mt-4 block rounded-2xl border border-emerald-300 bg-white px-6 py-3 text-center font-black text-emerald-700 hover:bg-emerald-50">Lihat Hasil Ujian</a>
        @endif
      @else
        <form method="post" action="{{ route('exams.start', $exam) }}" class="mt-4">
          @csrf
          <button class="btn-action btn-primary-solid w-full rounded-2xl px-6">{{ $completedAttempt ? 'Ulangi Ujian' : 'Mulai Ujian' }}</button>
        </form>
      @endif
    </article>
  @empty
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">Belum ada ujian aktif.</div>
  @endforelse
</div>
@endsection
