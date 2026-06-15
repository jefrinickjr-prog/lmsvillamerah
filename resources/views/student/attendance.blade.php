@extends('layouts.app')

@section('title', 'Rekap Absensi')

@section('content')
  <div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Halaman Siswa</p>
    <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Rekap Absensi</h2>
    <p class="mt-2 text-slate-500">Pantau riwayat kehadiran Anda. Checklist absensi dicatat oleh admin atau pengajar.</p>
  </div>

  <section class="mb-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
      <div>
        <div class="text-sm font-bold uppercase tracking-wider text-indigo-500">Absensi Minggu Ini</div>
        <h3 class="mt-2 text-2xl font-black text-slate-950">{{ $weekStart->format('d M Y') }} - {{ $weekEnd->format('d M Y') }}</h3>
        <p class="mt-2 text-sm font-semibold text-slate-500">
          Hari ini: {{ $today->format('d M Y') }}. Absensi dicatat oleh admin/pengajar sesuai kelas dan cabang.
        </p>
        @if($classroom)
          <div class="mt-3 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-indigo-700">{{ $classroom->title }}</div>
        @endif
      </div>

      <div class="min-w-64">
        @if($errors->has('attendance'))
          <div class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first('attendance') }}</div>
        @endif

        @if($currentWeekAttendance)
          <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700">
            Anda sudah absen minggu ini pada {{ $currentWeekAttendance->date->format('d M Y') }}.
          </div>
        @elseif(! $classroom)
          <div class="rounded-2xl bg-amber-50 px-5 py-4 text-sm font-bold text-amber-700">
            Akun Anda belum terhubung ke kelas program yang tersedia.
          </div>
        @else
          <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm font-bold text-slate-600">
            Belum ada catatan absensi untuk minggu ini.
          </div>
        @endif
      </div>
    </div>
  </section>

  <div class="mb-6 grid gap-5 md:grid-cols-3">
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Total Pertemuan</div>
      <div class="mt-3 text-4xl font-black text-slate-950">{{ $totalCount }}</div>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Hadir</div>
      <div class="mt-3 text-4xl font-black text-emerald-600">{{ $presentCount }}</div>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Persentase</div>
      <div class="mt-3 text-4xl font-black text-indigo-600">{{ $attendanceRate }}%</div>
    </div>
  </div>

  <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <h3 class="mb-5 text-lg font-black text-slate-950">Detail Absensi</h3>
    <div class="divide-y divide-slate-100">
      @forelse($attendances as $attendance)
        <div class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
          <div>
            <div class="font-black text-slate-900">{{ $attendance->classroom->title ?? 'Kelas' }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($attendance->date)->format('Y-m-d') }}</div>
          </div>
          <div class="rounded-2xl px-4 py-2 text-sm font-black {{ $attendance->present ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
            {{ $attendance->present ? 'Hadir' : 'Tidak hadir' }}
          </div>
        </div>
      @empty
        <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada data absensi.</div>
      @endforelse
    </div>
  </section>
@endsection
