@extends('layouts.app')

@section('title', 'Video Pembelajaran')

@section('content')
  @php
    $canManageMaterials = in_array(auth()->user()?->role, ['teacher', 'admin', 'super_admin'], true);
    $studentClass = auth()->user()?->role === 'student' ? auth()->user()?->student_class : null;
    $selectedProgramType = $selectedProgramType ?? 'gambar';
    $visibleProgramTypes = $visibleProgramTypes ?? $programTypes;
    $selectedProgramLabel = $programTypes[$selectedProgramType] ?? ($programTypes['gambar'] ?? 'Video Tutorial Gambar');
  @endphp

  <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div class="min-w-0">
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Video Pembelajaran</p>
      <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $selectedProgramLabel }}</h2>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base">Pilih grup video agar tutorial gambar dan pembahasan skolastik tidak tercampur.</p>
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
    <?php foreach ($visibleProgramTypes as $value => $label): ?>
      <?php $isActive = $selectedProgramType === $value; ?>
      <?php if ($canManageMaterials): ?>
        <a href="{{ route('materials.index', ['program_type' => $value]) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl px-4 py-2 text-center text-sm font-black {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
          {{ $label }}
        </a>
      <?php endif; ?>
      <?php if (! $canManageMaterials): ?>
        <a href="{{ route('materials.index', ['program_type' => $value]) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl px-4 py-2 text-center text-sm font-black {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
          {{ $label }}
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <?php if ($materials->isNotEmpty() && auth()->user()?->role === 'student'): ?>
    <?php $firstMaterial = $materials->first(); ?>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" data-course-player>
      <div class="grid lg:grid-cols-[minmax(0,1fr)_21rem]">
        <div class="min-w-0 bg-slate-950 p-3 sm:p-5">
          <div class="video-frame overflow-hidden rounded-2xl bg-black">
            <iframe data-course-frame class="h-full w-full" src="{{ $firstMaterial->youtube_embed_url }}" title="Video pembelajaran {{ $firstMaterial->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
          </div>
          <div class="px-1 pb-1 pt-5 sm:px-2">
            <p class="text-xs font-black uppercase tracking-widest text-indigo-300">Sedang diputar</p>
            <h3 data-course-title class="mt-1 text-xl font-black text-white">{{ $firstMaterial->title }}</h3>
            <p data-course-content class="mt-2 text-sm leading-6 text-slate-300">{{ $firstMaterial->content }}</p>
          </div>
        </div>
        <aside class="border-t border-slate-200 bg-white lg:border-l lg:border-t-0">
          <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-black text-slate-950">Daftar pembelajaran</h3><p class="mt-1 text-xs font-semibold text-slate-500">Pilih satu video untuk mulai belajar.</p></div>
          <div class="max-h-[32rem] overflow-y-auto p-3">
            <?php foreach ($materials as $index => $material): ?>
              <button type="button" data-course-item data-video-url="{{ $material->youtube_embed_url }}" data-video-title="{{ $material->title }}" data-video-content="{{ $material->content }}" class="flex w-full items-start gap-3 rounded-2xl p-3 text-left transition {{ $index === 0 ? 'bg-indigo-50 text-indigo-900' : 'text-slate-700 hover:bg-slate-50' }}">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl {{ $index === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500' }} text-xs font-black">{{ $index + 1 }}</span>
                <span class="min-w-0"><span class="block text-sm font-black">{{ $material->title }}</span><span class="mt-1 block text-xs font-semibold text-slate-500">{{ \Illuminate\Support\Str::limit($material->content, 65) }}</span></span>
              </button>
            <?php endforeach; ?>
          </div>
        </aside>
      </div>
    </section>
    <script>
      document.querySelectorAll('[data-course-player]').forEach((player) => {
        const frame = player.querySelector('[data-course-frame]');
        const title = player.querySelector('[data-course-title]');
        const content = player.querySelector('[data-course-content]');
        player.querySelectorAll('[data-course-item]').forEach((item) => item.addEventListener('click', () => {
          frame.src = item.dataset.videoUrl;
          title.textContent = item.dataset.videoTitle;
          content.textContent = item.dataset.videoContent || '';
          player.querySelectorAll('[data-course-item]').forEach((other) => other.classList.remove('bg-indigo-50', 'text-indigo-900'));
          item.classList.add('bg-indigo-50', 'text-indigo-900');
        }));
      });
    </script>
  <?php endif; ?>

  <?php if ($materials->isNotEmpty() && auth()->user()?->role !== 'student'): ?>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($materials as $material): ?>
        <?php
          $canManageThisMaterial = in_array(auth()->user()?->role, ['admin', 'super_admin'], true)
            || (auth()->user()?->role === 'teacher' && (
              $material->classrooms->contains('teacher_id', auth()->id())
              || $material->classroom?->teacher_id === auth()->id()
            ));
          $materialGroup = $programTypes[$material->program_type ?? 'gambar'] ?? ($programTypes['gambar'] ?? 'Video Tutorial Gambar');
          $materialClassrooms = $material->classrooms->isNotEmpty()
            ? $material->classrooms
            : collect([$material->classroom])->filter();
        ?>

        <article class="min-w-0 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200 sm:p-6">
          <div class="flex items-start justify-end gap-4">
            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">{{ $material->created_at->format('Y-m-d') }}</span>
          </div>

          <div class="mt-5 flex flex-wrap items-center gap-2">
            <h3 class="min-w-0 break-words text-lg font-black text-slate-950">{{ $material->title }}</h3>
            <?php foreach ($materialClassrooms as $classroom): ?>
              <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $classroom->title ?? 'Kelas' }}</span>
            <?php endforeach; ?>
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
