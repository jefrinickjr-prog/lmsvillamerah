@extends('layouts.app')

@section('title', 'Video Pembelajaran')

@section('content')
  @php
    $canManageMaterials = in_array(auth()->user()?->role, ['teacher', 'admin', 'super_admin'], true);
    $studentClass = auth()->user()?->role === 'student' ? auth()->user()?->student_class : null;
    $selectedProgramType = $selectedProgramType ?? 'gambar';
    $selectedProgramLabel = $programTypes[$selectedProgramType] ?? ($programTypes['gambar'] ?? 'Video Tutorial Gambar');
  @endphp

  <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div class="min-w-0">
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Video Pembelajaran</p>
      <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $selectedProgramLabel }}</h2>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base">Pilih grup video agar tutorial gambar dan pengerjaan skolastik tidak tercampur.</p>
      <?php if ($studentClass): ?>
        <p class="mt-2 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-black text-indigo-700">{{ $studentClass }}</p>
      <?php endif; ?>
    </div>

    <?php if ($canManageMaterials): ?>
      <a href="{{ route('materials.create') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 sm:w-auto">
        <i class="fa-solid fa-plus"></i>
        Tambah Video
      </a>
    <?php endif; ?>
  </div>

  <div class="mb-6 grid gap-2 sm:inline-flex sm:flex-wrap">
    <?php foreach ($programTypes as $value => $label): ?>
      <?php $isActive = $selectedProgramType === $value; ?>
      <?php if ($canManageMaterials): ?>
        <a href="{{ route('materials.index', ['program_type' => $value]) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl px-4 py-2 text-center text-sm font-black {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
          {{ $label }}
        </a>
      <?php endif; ?>
      <?php if (! $canManageMaterials && $isActive): ?>
        <span class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2 text-center text-sm font-black text-white shadow-lg shadow-indigo-100">
          {{ $label }}
        </span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <?php if ($materials->isNotEmpty()): ?>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($materials as $material): ?>
        <?php
          $canManageThisMaterial = in_array(auth()->user()?->role, ['admin', 'super_admin'], true)
            || (auth()->user()?->role === 'teacher' && $material->classroom?->teacher_id === auth()->id());
          $materialGroup = $programTypes[$material->program_type ?? 'gambar'] ?? ($programTypes['gambar'] ?? 'Video Tutorial Gambar');
        ?>

        <article class="min-w-0 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200 sm:p-6">
          <div class="flex items-start justify-between gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-indigo-100 text-indigo-700">
              <i class="fa-solid fa-circle-play"></i>
            </div>
            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">{{ $material->created_at->format('Y-m-d') }}</span>
          </div>

          <div class="mt-5 flex flex-wrap items-center gap-2">
            <h3 class="min-w-0 break-words text-lg font-black text-slate-950">{{ $material->title }}</h3>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $material->classroom->title ?? 'Kelas' }}</span>
            <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-black text-violet-700">{{ $materialGroup }}</span>
          </div>

          <p class="mt-2 text-sm leading-6 text-slate-500 sm:min-h-12">{{ \Illuminate\Support\Str::limit($material->content, 120) }}</p>

          <?php if ($material->youtube_embed_url): ?>
            <div class="video-frame mt-5 overflow-hidden rounded-2xl bg-slate-100">
              <iframe class="h-full w-full" src="{{ $material->youtube_embed_url }}" title="Video pembelajaran {{ $material->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            </div>
          <?php endif; ?>

          <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm font-black text-indigo-600">Video pembelajaran</div>
            <?php if ($canManageThisMaterial): ?>
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('materials.edit', $material) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-2xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                  <i class="fa-solid fa-pen-to-square"></i>
                  Edit
                </a>
                <form method="POST" action="{{ route('materials.destroy', $material) }}" onsubmit="return confirm('Hapus video pembelajaran ini? Tugas yang terkait video ini juga akan terhapus.');">
                  @csrf
                  @method('DELETE')
                  <button class="inline-flex min-h-10 items-center justify-center gap-2 rounded-2xl bg-rose-50 px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-100" type="submit">
                    <i class="fa-solid fa-trash"></i>
                    Hapus
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($materials->isEmpty()): ?>
    <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-6 text-center sm:p-10">
      <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
        <i class="fa-regular fa-folder-open"></i>
      </div>
      <h3 class="mt-4 font-black text-slate-900">Belum ada video pembelajaran</h3>
      <p class="mt-2 text-sm text-slate-500">
        <?php if (auth()->user()?->role === 'student' && ! $studentClass): ?>
          Akun siswa ini belum memiliki kelas program. Minta admin atau pengajar mengisi kelas siswa terlebih dahulu.
        <?php endif; ?>
        <?php if (auth()->user()?->role !== 'student' || $studentClass): ?>
          Video yang dibuat untuk kelas ini akan tampil di halaman ini.
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
@endsection
