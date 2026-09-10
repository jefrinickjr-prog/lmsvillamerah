@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
<style>
  .report-hero{background:linear-gradient(135deg,#312e81 0%,#4f46e5 55%,#7c3aed 100%);box-shadow:0 24px 55px rgba(79,70,229,.2)}.report-card{background:#fff;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 10px 30px rgba(15,23,42,.05)}.report-ring{align-items:center;background:conic-gradient(#22c55e calc(var(--progress)*1%),#e2e8f0 0);border-radius:999px;display:flex;height:112px;justify-content:center;position:relative;width:112px}.report-ring:after{background:#fff;border-radius:999px;content:"";height:84px;position:absolute;width:84px}.report-ring span{color:#0f172a;font-size:22px;font-weight:900;position:relative;z-index:1}.score-chart{align-items:end;display:grid;gap:12px;grid-auto-columns:minmax(42px,1fr);grid-auto-flow:column;height:210px;overflow-x:auto;padding:14px 4px 0}.score-column{align-items:center;display:flex;flex-direction:column;height:100%;justify-content:end;min-width:42px}.score-bar{background:linear-gradient(180deg,#818cf8,#4f46e5);border-radius:10px 10px 4px 4px;min-height:5px;width:28px}.score-column:nth-child(even) .score-bar{background:linear-gradient(180deg,#c084fc,#7c3aed)}.score-label{color:#64748b;font-size:10px;font-weight:800;margin-top:8px;max-width:70px;overflow:hidden;text-align:center;text-overflow:ellipsis;white-space:nowrap}@media(max-width:640px){.report-ring{height:96px;width:96px}.report-ring:after{height:72px;width:72px}.score-chart{height:180px}}
</style>

<section class="report-hero overflow-hidden rounded-3xl p-6 text-white sm:p-8">
  <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
    <div><p class="text-xs font-black uppercase tracking-[.2em] text-indigo-200">Ringkasan Pembelajaran</p><h2 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Laporan Belajar Anda</h2><p class="mt-3 max-w-2xl text-sm font-semibold leading-6 text-indigo-100">Pantau tugas pertemuan, perkembangan nilai, materi, dan konsistensi kehadiran dalam satu tampilan.</p></div>
    <div class="flex items-center gap-4 rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-indigo-700"><i class="fa-solid fa-chart-line text-xl"></i></div><div><div class="text-xs font-black uppercase tracking-wider text-indigo-200">Rata-rata keseluruhan</div><div class="text-3xl font-black">{{ $averageScore !== null ? number_format($averageScore, 1) : '—' }}</div></div></div>
  </div>
</section>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  @foreach([
    ['label'=>'Video tersedia','value'=>$materialsCount,'note'=>'Materi sesuai kelas','icon'=>'fa-book-open','bg'=>'#eef2ff','color'=>'#4338ca'],
    ['label'=>'Tugas dikumpulkan','value'=>$submittedCount.'/'.$tasksCount,'note'=>$pendingTasksCount.' tugas belum dikumpulkan','icon'=>'fa-file-circle-check','bg'=>'#ecfeff','color'=>'#0e7490'],
    ['label'=>'Rata-rata nilai','value'=>$averageScore !== null ? number_format($averageScore,1) : '—','note'=>$gradedCount.' penilaian tercatat','icon'=>'fa-star','bg'=>'#faf5ff','color'=>'#7e22ce'],
    ['label'=>'Kehadiran','value'=>$attendanceRate.'%','note'=>$presentCount.' dari '.$attendances->count().' catatan hadir','icon'=>'fa-calendar-check','bg'=>'#ecfdf5','color'=>'#047857'],
  ] as $metric)
    <article class="report-card p-5"><div class="flex items-start justify-between gap-4"><div class="grid h-12 w-12 place-items-center rounded-2xl" style="background:{{ $metric['bg'] }};color:{{ $metric['color'] }}"><i class="fa-solid {{ $metric['icon'] }}"></i></div><span class="rounded-full bg-slate-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500">Aktual</span></div><div class="mt-5 text-3xl font-black text-slate-950">{{ $metric['value'] }}</div><div class="mt-1 font-black text-slate-800">{{ $metric['label'] }}</div><p class="mt-1 text-xs font-semibold text-slate-500">{{ $metric['note'] }}</p></article>
  @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_.75fr]">
  <section class="report-card p-5 sm:p-6">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-wider text-indigo-500">Perkembangan</p><h3 class="mt-1 text-xl font-black text-slate-950">Tren Nilai Terbaru</h3></div><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Skala 0–100</span></div>
    @if($scoreTrend->isNotEmpty())
      <div class="mt-5 border-b border-slate-200"><div class="score-chart">@foreach($scoreTrend as $score)<div class="score-column" title="{{ $score['label'] }}: {{ number_format($score['score'], 1) }}"><strong class="mb-2 text-xs text-slate-700">{{ number_format($score['score'], 0) }}</strong><div class="score-bar" style="height:{{ max(5, min(100, $score['score'])) }}%"></div><span class="score-label">{{ $score['type'] }}</span></div>@endforeach</div></div>
      <p class="mt-4 text-xs font-semibold text-slate-500">Nilai karya dan ujian dinormalisasi berdasarkan nilai maksimal masing-masing.</p>
    @else
      <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"><i class="fa-solid fa-chart-column text-3xl text-slate-300"></i><p class="mt-3 text-sm font-bold text-slate-500">Tren akan tampil setelah karya atau ujian selesai dinilai.</p></div>
    @endif
  </section>
  <section class="report-card p-5 sm:p-6"><p class="text-xs font-black uppercase tracking-wider text-emerald-600">Progres Tugas</p><h3 class="mt-1 text-xl font-black text-slate-950">Penyelesaian Pertemuan</h3><div class="mt-7 flex items-center justify-center"><div class="report-ring" style="--progress:{{ $assignmentRate }}"><span>{{ $assignmentRate }}%</span></div></div><div class="mt-7 grid grid-cols-2 gap-3 text-center"><div class="rounded-2xl bg-emerald-50 p-4"><strong class="block text-2xl font-black text-emerald-700">{{ $submittedCount }}</strong><span class="text-xs font-bold text-emerald-800">Dikumpulkan</span></div><div class="rounded-2xl bg-amber-50 p-4"><strong class="block text-2xl font-black text-amber-700">{{ $pendingTasksCount }}</strong><span class="text-xs font-bold text-amber-800">Belum selesai</span></div></div></section>
</div>

<section class="report-card mt-6 overflow-hidden">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5 sm:p-6"><div><p class="text-xs font-black uppercase tracking-wider text-violet-500">Aktivitas Pertemuan</p><h3 class="mt-1 text-xl font-black text-slate-950">Tugas Terbaru</h3></div><a href="{{ route('meeting-assignments.index') }}" class="rounded-xl bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700">Lihat semua</a></div>
  <div class="divide-y divide-slate-100">
    @forelse($recentAssignments as $item)
      @php($submission = $item['submission'])
      <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div class="flex min-w-0 items-center gap-4"><div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $submission ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}"><i class="fa-solid {{ $submission ? 'fa-check' : 'fa-hourglass-half' }}"></i></div><div class="min-w-0"><h4 class="truncate font-black text-slate-900">{{ $item['assignment']->title }}</h4><p class="mt-1 text-xs font-semibold text-slate-500">{{ $item['assignment']->classroom?->title }} · {{ $item['assignment']->meeting_date->format('d M Y') }}</p></div></div><div class="flex items-center justify-between gap-3 sm:justify-end">@if($submission?->score !== null)<span class="rounded-xl bg-violet-50 px-3 py-2 text-xs font-black text-violet-700">Nilai {{ $submission->score }}/{{ $item['assignment']->max_score }}</span>@endif<span class="rounded-xl px-3 py-2 text-xs font-black {{ $submission ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $submission ? ($submission->score === null ? 'Menunggu nilai' : 'Sudah dinilai') : 'Belum dikumpulkan' }}</span></div></article>
    @empty
      <div class="p-10 text-center text-sm font-bold text-slate-500">Belum ada tugas pertemuan untuk kelas Anda.</div>
    @endforelse
  </div>
</section>

<div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50 px-5 py-4 text-sm font-semibold leading-6 text-indigo-800"><i class="fa-solid fa-circle-info mr-2"></i>Laporan memakai data tugas pertemuan, hasil ujian, dan absensi yang tercatat di sistem. Nilai rata-rata ditampilkan dalam skala 100.</div>
@endsection
