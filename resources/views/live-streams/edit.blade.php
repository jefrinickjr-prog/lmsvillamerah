@extends('layouts.app')

@section('title', 'Edit Live Streaming')

@section('content')
  <div class="mx-auto max-w-3xl">
    <a href="{{ route('live-streams.index') }}" class="text-sm font-black text-indigo-600"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali ke Live Streaming</a>
    <h2 class="mt-4 text-3xl font-black text-slate-950">Edit Jadwal Live</h2>
    <p class="mt-2 text-slate-500">Perbarui kelas, waktu, atau tautan Zoom/Google Meet.</p>

    <form method="POST" action="{{ route('live-streams.update', $liveStream) }}" class="mt-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @method('PUT')
      @if($errors->any())<div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
      <div class="space-y-5">
        <div><label class="mb-2 block text-sm font-bold">Kelas Online</label><select name="classroom_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3">@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected(old('classroom_id', $liveStream->classroom_id) == $classroom->id)>{{ $classroom->title }} · {{ $classroom->branch }}</option>@endforeach</select></div>
        <div><label class="mb-2 block text-sm font-bold">Judul Sesi</label><input name="title" value="{{ old('title', $liveStream->title) }}" required maxlength="255" class="w-full rounded-2xl border border-slate-200 px-4 py-3"></div>
        <div><label class="mb-2 block text-sm font-bold">Link Zoom / Google Meet</label><input type="url" name="meeting_url" value="{{ old('meeting_url', $liveStream->meeting_url) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"></div>
        <div class="grid gap-5 sm:grid-cols-2">
          <div><label class="mb-2 block text-sm font-bold">Mulai</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $liveStream->starts_at->format('Y-m-d\\TH:i')) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"></div>
          <div><label class="mb-2 block text-sm font-bold">Selesai</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $liveStream->ends_at->format('Y-m-d\\TH:i')) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3"></div>
        </div>
        <button class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white" type="submit"><i class="fa-solid fa-save"></i>Simpan Perubahan</button>
      </div>
    </form>
  </div>
@endsection
