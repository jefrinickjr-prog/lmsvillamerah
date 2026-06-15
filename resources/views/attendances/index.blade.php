@extends('layouts.app')

@section('title', 'Absensi Siswa')

@section('content')
  <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Manajemen Absensi</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Absensi Siswa</h2>
      <p class="mt-2 text-slate-500">Pantau kehadiran siswa berdasarkan kelas dan minggu pertemuan.</p>
    </div>

    <form method="GET" action="{{ route('attendances.index') }}" class="grid gap-3 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm md:grid-cols-[1fr_1fr_auto]">
      <select name="classroom_id" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
        @foreach($classrooms as $classroom)
          <option value="{{ $classroom->id }}" @selected($selectedClassroom?->id === $classroom->id)>{{ $classroom->title }} - {{ $classroom->branch ?? 'Cabang' }} - {{ $classroom->teacher->name ?? 'Pengajar' }}</option>
        @endforeach
      </select>
      <input type="date" name="week" value="{{ $weekStart->toDateString() }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
      <button class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700" type="submit">Tampilkan</button>
    </form>
  </div>

  <div class="mb-6 grid gap-5 md:grid-cols-3">
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Minggu Pertemuan</div>
      <div class="mt-3 text-2xl font-black text-slate-950">{{ $weekStart->format('d M') }} - {{ $weekEnd->format('d M Y') }}</div>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Hadir</div>
      <div class="mt-3 text-4xl font-black text-emerald-600">{{ $presentCount }}/{{ $totalStudents }}</div>
    </div>
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="text-sm font-bold text-slate-500">Persentase</div>
      <div class="mt-3 text-4xl font-black text-indigo-600">{{ $attendanceRate }}%</div>
    </div>
  </div>

  <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
    <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
      <div>
        <h3 class="text-lg font-black text-slate-950">{{ $selectedClassroom?->title ?? 'Belum ada kelas' }} {{ $selectedClassroom?->branch ? '- '.$selectedClassroom->branch : '' }}</h3>
        <p class="mt-1 text-sm font-semibold text-slate-500">Checklist siswa yang hadir untuk minggu yang dipilih.</p>
      </div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelas Sabtu/Minggu</div>
    </div>

    @if($selectedClassroom)
      <form method="POST" action="{{ route('attendances.store') }}">
        @csrf
        <input type="hidden" name="classroom_id" value="{{ $selectedClassroom->id }}">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

        <div class="overflow-hidden rounded-2xl border border-slate-100">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-4 py-3">Hadir</th>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Siswa</th>
                <th class="px-4 py-3">Tahun Ajaran</th>
                <th class="px-4 py-3">Tanggal Catat</th>
                <th class="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse($students as $student)
                @php($attendance = $attendanceByStudent->get($student->id))
                <tr>
                  <td class="px-4 py-4">
                    <input type="checkbox" name="present_students[]" value="{{ $student->id }}" @checked($attendance?->present) class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                  </td>
                  <td class="px-4 py-4 font-black text-indigo-700">{{ $student->student_code ?? '-' }}</td>
                  <td class="px-4 py-4">
                    <div class="font-black text-slate-900">{{ $student->name }}</div>
                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $student->email }}</div>
                  </td>
                  <td class="px-4 py-4 font-semibold text-slate-500">{{ $student->academic_year ?? '-' }}</td>
                  <td class="px-4 py-4 font-semibold text-slate-500">{{ $attendance?->date ? $attendance->date->format('d M Y') : '-' }}</td>
                  <td class="px-4 py-4">
                    @if($attendance?->present)
                      <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Hadir</span>
                    @else
                      <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700">Belum hadir</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="px-4 py-8 text-center font-semibold text-slate-500">Belum ada siswa pada kelas dan cabang ini.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($students->isNotEmpty())
          <button class="mt-5 inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700" type="submit">
            <i class="fa-solid fa-floppy-disk"></i>
            Simpan Checklist Absensi
          </button>
        @endif
      </form>
    @else
      <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada kelas untuk absensi.</div>
    @endif
  </section>
@endsection
