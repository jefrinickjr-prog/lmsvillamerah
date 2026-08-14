@extends('layouts.app')
@section('title', 'Hasil '.$exam->title)
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
  <div><p class="text-sm font-black uppercase tracking-wider text-indigo-500">Hasil Peserta</p><h2 class="mt-1 text-3xl font-black">{{ $exam->title }}</h2><p class="mt-2 text-slate-500">{{ $exam->questions_count }} soal · KKM {{ number_format($exam->passing_grade, 0) }}</p></div>
  <a href="{{ route('exams.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-bold text-slate-700">Kembali</a>
</div>
<div class="mt-6 overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
  <table class="w-full min-w-[760px] text-left text-sm">
    <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-400"><tr><th class="p-4">Siswa</th><th class="p-4">Mulai</th><th class="p-4">Selesai</th><th class="p-4">Status</th><th class="p-4">Skor</th><th class="p-4 text-right">Detail</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
      @forelse($attempts as $attempt)
        @php $max=(float)$exam->questions()->sum('score'); $score=$max>0?round((float)$attempt->score/$max*100):0; @endphp
        <tr><td class="p-4"><div class="font-black text-slate-900">{{ $attempt->student?->name ?? 'Siswa terhapus' }}</div><div class="text-xs text-slate-400">{{ $attempt->student?->email }}</div></td><td class="p-4">{{ $attempt->started_at?->format('d M Y H:i') }}</td><td class="p-4">{{ $attempt->submitted_at?->format('d M Y H:i') ?? '-' }}</td><td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-black {{ $attempt->status === 'in_progress' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $attempt->status === 'in_progress' ? 'Berlangsung' : ($attempt->status === 'submitted' ? 'Menunggu Esai' : 'Selesai') }}</span></td><td class="p-4 text-xl font-black text-indigo-700">{{ $attempt->status === 'in_progress' ? '-' : $score }}</td><td class="p-4 text-right">@if($attempt->status !== 'in_progress')<a href="{{ route('attempts.result', $attempt) }}" class="font-black text-indigo-600">Lihat Jawaban</a>@else<span class="text-xs text-slate-400">Belum selesai</span>@endif</td></tr>
      @empty
        <tr><td colspan="6" class="p-10 text-center text-slate-500">Belum ada siswa yang mengerjakan ujian ini.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-5">{{ $attempts->links() }}</div>
@endsection
