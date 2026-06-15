@extends('layouts.app')

@section('title', 'Daftar Siswa')

@section('content')
  <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Manajemen Siswa</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Daftar Siswa</h2>
      <p class="mt-2 text-slate-500">Lihat siswa yang sudah terdaftar dan perbarui data akun siswa.</p>
    </div>
    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700">
      <i class="fa-solid fa-user-plus"></i>
      Daftarkan Siswa
    </a>
  </div>

  <form method="GET" action="{{ route('students.index') }}" class="mb-5 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm">
    <div class="grid gap-3 md:grid-cols-4">
      <div class="md:col-span-2">
        <label class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-400">Cari</label>
        <input name="search" value="{{ request('search') }}" placeholder="Nama, email, atau kode siswa" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
      </div>
      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-400">Kelas</label>
        <select name="student_class" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          <option value="">Semua kelas</option>
          @foreach($studentClasses as $studentClass)
            <option value="{{ $studentClass }}" @selected(request('student_class') === $studentClass)>{{ $studentClass }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-400">Cabang</label>
        <select name="branch" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          <option value="">Semua cabang</option>
          @foreach($branches as $branch)
            <option value="{{ $branch }}" @selected(request('branch') === $branch)>{{ $branch }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-3">
      <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white hover:bg-slate-800" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
        Filter
      </button>
      <a href="{{ route('students.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">
        Reset
      </a>
    </div>
  </form>

  <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50">
          <tr class="text-left text-xs font-black uppercase tracking-wider text-slate-400">
            <th class="px-5 py-4">Kode</th>
            <th class="px-5 py-4">Siswa</th>
            <th class="px-5 py-4">Kelas</th>
            <th class="px-5 py-4">Cabang</th>
            <th class="px-5 py-4">Tahun Ajaran</th>
            <th class="px-5 py-4 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($students as $student)
            <tr>
              <td class="whitespace-nowrap px-5 py-4 text-sm font-black text-indigo-700">{{ $student->student_code ?? '-' }}</td>
              <td class="px-5 py-4">
                <div class="font-black text-slate-900">{{ $student->name }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-400">{{ $student->email }}</div>
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-600">{{ $student->student_class ?? '-' }}</td>
              <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-600">{{ $student->branch ?? '-' }}</td>
              <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-600">{{ $student->academic_year ?? '-' }}</td>
              <td class="whitespace-nowrap px-5 py-4 text-right">
                <a href="{{ route('students.edit', $student) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700 hover:bg-indigo-100">
                  <i class="fa-solid fa-pen-to-square"></i>
                  Edit
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-5 py-10 text-center">
                <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                  <i class="fa-solid fa-users"></i>
                </div>
                <h3 class="mt-4 font-black text-slate-900">Belum ada siswa</h3>
                <p class="mt-2 text-sm text-slate-500">Siswa yang sudah didaftarkan akan muncul di halaman ini.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-5">
    {{ $students->links() }}
  </div>
@endsection
