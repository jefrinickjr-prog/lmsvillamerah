@extends('layouts.app')

@section('title', 'Tambah Admin')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Super Admin</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Tambah Admin</h2>
      <p class="mt-2 text-slate-500">Akun admin baru dibuat dengan status menunggu. Akses penuh baru aktif setelah disetujui super admin.</p>
    </div>

    <form method="POST" action="{{ route('admin-users.store') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif

      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Nama Admin</label>
          <input name="name" value="{{ old('name') }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required autofocus>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Email Admin</label>
          <input name="email" value="{{ old('email') }}" type="email" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Password Awal</label>
            <input name="password" type="password" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Konfirmasi Password</label>
            <input name="password_confirmation" type="password" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
          Setelah dibuat, admin belum bisa login sampai super admin menekan tombol Setujui di halaman Kelola Admin.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <button class="btn-action btn-primary-solid rounded-2xl px-5 py-3 text-sm" type="submit">
            <i class="fa-solid fa-user-shield"></i>
            Buat Admin Pending
          </button>
          <a href="{{ route('admin-users.index') }}" class="btn-action rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">
            Kembali
          </a>
        </div>
      </div>
    </form>
  </div>
@endsection
