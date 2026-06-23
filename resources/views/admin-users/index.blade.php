@extends('layouts.app')

@section('title', 'Kelola Admin')

@section('content')
  <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Super Admin</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Kelola Admin</h2>
      <p class="mt-2 text-slate-500">Daftarkan admin baru, lalu setujui agar akun mendapatkan akses penuh ke dashboard admin.</p>
    </div>

    <a href="{{ route('admin-users.create') }}" class="btn-action btn-primary-solid rounded-2xl px-5 py-3 text-sm">
      <i class="fa-solid fa-user-plus"></i>
      Tambah Admin
    </a>
  </div>

  <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs font-black uppercase tracking-wider text-slate-400">
          <tr>
            <th class="px-5 py-4">Admin</th>
            <th class="px-5 py-4">Status</th>
            <th class="px-5 py-4">Disetujui Oleh</th>
            <th class="px-5 py-4 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($admins as $admin)
            <tr>
              <td class="px-5 py-4">
                <div class="font-black text-slate-900">{{ $admin->name }}</div>
                <div class="mt-1 text-xs font-semibold text-slate-400">{{ $admin->email }}</div>
              </td>
              <td class="px-5 py-4">
                @if($admin->approved_at)
                  <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-600">Disetujui</span>
                  <div class="mt-1 text-xs font-semibold text-slate-400">{{ $admin->approved_at->format('d M Y H:i') }}</div>
                @else
                  <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-600">Menunggu Persetujuan</span>
                @endif
              </td>
              <td class="px-5 py-4 text-sm font-semibold text-slate-500">
                {{ $admin->approver?->name ?? '-' }}
              </td>
              <td class="px-5 py-4 text-right">
                @if($admin->approved_at)
                  <span class="text-xs font-bold text-slate-400">Akses penuh aktif</span>
                @else
                  <form method="POST" action="{{ route('admin-users.approve', $admin) }}">
                    @csrf
                    @method('PUT')
                    <button class="btn-action btn-approve-solid rounded-2xl px-4 py-2 text-xs" type="submit">
                      <i class="fa-solid fa-check"></i>
                      Setujui
                    </button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-5 py-10 text-center text-sm font-semibold text-slate-500">
                Belum ada akun admin tambahan.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
