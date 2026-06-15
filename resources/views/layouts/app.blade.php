<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'E-Learning Gambar')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @php
      $manifestPath = public_path('build/manifest.json');
      $useVite = false;
      if (file_exists($manifestPath)) {
        $contents = json_decode(file_get_contents($manifestPath), true);
        if (is_array($contents) && array_key_exists('resources/css/app.css', $contents)) {
          $useVite = true;
        }
      }
    @endphp
    @if($useVite)
      @vite(['resources/css/app.css','resources/js/app.js'])
    @else
      <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @endif
    <style>
      body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
      html, body { max-width: 100%; overflow-x: hidden; }
      img, iframe, video { max-width: 100%; }
      @media (max-width: 640px) {
        h1, h2 { overflow-wrap: anywhere; }
        main { min-width: 0; }
        .rounded-3xl { border-radius: 1rem; }
        .p-6 { padding: 1rem; }
        .p-8, .p-10 { padding: 1.25rem; }
        .text-3xl { font-size: 1.5rem; line-height: 2rem; }
        .text-4xl, .text-5xl { font-size: 2rem; line-height: 2.35rem; }
        .shadow-lg, .shadow-xl, .shadow-2xl { box-shadow: 0 8px 18px rgb(15 23 42 / 0.08); }
        table { min-width: 680px; }
        input, select, textarea, button, a { max-width: 100%; }
        main .inline-flex.rounded-2xl,
        main form .inline-flex.rounded-2xl,
        main form button.rounded-2xl { width: 100%; justify-content: center; }
      }
    </style>
  </head>
  <body class="bg-slate-50 text-slate-900">
    @guest
      @yield('content')
    @else
      @php
        $user = auth()->user();
        $roleLabel = [
          'admin' => 'Administrator',
          'super_admin' => 'Super Admin',
          'teacher' => 'Pengajar',
          'student' => 'Siswa',
        ][$user->role ?? 'student'] ?? 'Siswa';
        $photoUrl = $user->photo_path ? asset('storage/'.$user->photo_path) : null;

        $navItems = [
          ['label' => 'Dashboard', 'icon' => 'fa-solid fa-table-columns', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
          ['label' => 'Video Pembelajaran', 'icon' => 'fa-solid fa-circle-play', 'route' => 'materials.index', 'active' => request()->routeIs('materials.*')],
          ['label' => 'Tugas', 'icon' => 'fa-solid fa-clipboard-check', 'route' => 'tasks.index', 'active' => request()->routeIs('tasks.*')],
        ];

        if (($user->role ?? 'student') === 'student') {
          $navItems = array_merge($navItems, [
            ['label' => 'Penilaian', 'icon' => 'fa-solid fa-star-half-stroke', 'route' => 'student.grades', 'active' => request()->routeIs('student.grades')],
            ['label' => 'Rekap Absensi', 'icon' => 'fa-solid fa-calendar-check', 'route' => 'student.attendance', 'active' => request()->routeIs('student.attendance')],
            ['label' => 'Laporan', 'icon' => 'fa-solid fa-chart-line', 'route' => 'student.reports', 'active' => request()->routeIs('student.reports')],
          ]);
        } else {
          array_splice($navItems, 1, 0, [[
            'label' => 'Kelas',
            'icon' => 'fa-solid fa-chalkboard-user',
            'route' => 'classrooms.index',
            'active' => request()->routeIs('classrooms.*'),
          ]]);
          $navItems[] = [
            'label' => 'Siswa',
            'icon' => 'fa-solid fa-users',
            'route' => 'students.index',
            'active' => request()->routeIs('students.*'),
          ];
          $navItems[] = [
            'label' => 'Absensi Siswa',
            'icon' => 'fa-solid fa-calendar-check',
            'route' => 'attendances.index',
            'active' => request()->routeIs('attendances.*'),
          ];
          $navItems[] = [
            'label' => 'Daftarkan Siswa',
            'icon' => 'fa-solid fa-user-plus',
            'route' => 'register',
            'active' => request()->routeIs('register'),
          ];
        }

        $unread = \App\Models\UserNotification::where('user_id', auth()->id())->where('read', false)->count();
        $notifications = \App\Models\UserNotification::where('user_id', auth()->id())->latest()->limit(5)->get();
      @endphp

      <div class="min-h-screen">
        <aside id="appSidebar" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none">
          <div class="flex h-full flex-col">
            <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
              <div class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-sky-400 via-indigo-500 to-violet-700 text-sm font-black text-white shadow-lg shadow-indigo-200">EL</div>
              <div>
                <div class="text-base font-extrabold tracking-tight">E-Learning</div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Bimbingan Gambar</div>
              </div>
            </div>

            <nav class="flex-1 space-y-2 px-4 py-6">
              @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition {{ $item['active'] ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                  <i class="{{ $item['icon'] }} w-5 text-center"></i>
                  <span>{{ $item['label'] }}</span>
                </a>
              @endforeach
            </nav>

            <div class="m-4 rounded-3xl bg-gradient-to-br from-indigo-50 to-cyan-50 p-4">
              <div class="text-xs font-bold uppercase tracking-wider text-indigo-500">Masuk sebagai</div>
              <div class="mt-2 font-extrabold text-slate-900">{{ $roleLabel }}</div>
              @if(($user->role ?? null) === 'student' && $user->student_class)
                <div class="mt-1 text-sm font-bold text-indigo-600">{{ $user->student_class }}</div>
                <div class="mt-1 text-xs font-bold text-slate-500">{{ $user->branch ?? 'Cabang belum diisi' }} @if($user->academic_year) · {{ $user->academic_year }} @endif</div>
                @if($user->student_code)
                  <div class="mt-2 rounded-xl bg-white/70 px-3 py-2 text-xs font-black text-slate-700">{{ $user->student_code }}</div>
                @endif
              @endif
              <div class="mt-1 truncate text-sm text-slate-500">{{ $user->email }}</div>
            </div>
          </div>
        </aside>

        <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 lg:hidden"></div>

        <div class="lg:pl-72">
          <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
              <div class="flex items-center gap-3">
                <button id="sidebarOpen" type="button" class="grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700 lg:hidden" aria-label="Buka sidebar">
                  <i class="fa-solid fa-bars"></i>
                </button>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-slate-500">Selamat datang,</div>
                  <h1 class="truncate text-lg font-extrabold tracking-tight sm:text-2xl">{{ $user->name }}</h1>
                </div>
              </div>

              <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <div class="relative">
                  <button id="notifToggle" type="button" class="relative grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" aria-label="Buka notifikasi">
                    <i class="fa-regular fa-bell"></i>
                    @if($unread > 0)
                      <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">{{ $unread }}</span>
                    @endif
                  </button>
                  <div id="notifMenu" class="absolute right-0 mt-3 hidden w-80 overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-2xl shadow-slate-200" style="max-width: calc(100vw - 2rem);">
                    <div class="border-b border-slate-100 px-4 py-3 font-extrabold">Notifikasi</div>
                    <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                      @forelse($notifications as $notification)
                        <div class="px-4 py-3 text-sm {{ $notification->read ? 'text-slate-400' : 'text-slate-700' }}">
                          <div class="font-bold">{{ $notification->title }}</div>
                          <div class="mt-1 text-xs">{{ \Illuminate\Support\Str::limit($notification->body, 90) }}</div>
                        </div>
                      @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-500">Belum ada notifikasi.</div>
                      @endforelse
                    </div>
                  </div>
                </div>

                <div class="relative">
                  <button id="userToggle" type="button" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white py-2 pl-2 pr-3 hover:bg-slate-50">
                    @if($photoUrl)
                      <img src="{{ $photoUrl }}" alt="Foto {{ $user->name }}" class="h-9 w-9 rounded-xl object-cover">
                    @else
                      <div class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-100 text-sm font-black text-indigo-700">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    @endif
                    <div class="hidden text-left sm:block">
                      <div class="text-sm font-extrabold leading-4">{{ $user->name }}</div>
                      <div class="text-xs font-semibold text-slate-400">{{ $roleLabel }}</div>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                  </button>
                  <div id="userMenu" class="absolute right-0 mt-3 hidden w-52 overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-2xl shadow-slate-200" style="max-width: calc(100vw - 2rem);">
                    <a class="block px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50" href="{{ route('profile.show') }}">Profil</a>
                    <form method="POST" action="{{ route('logout') }}">
                      @csrf
                      <button class="w-full px-4 py-3 text-left text-sm font-bold text-rose-600 hover:bg-rose-50">Logout</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </header>

          <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
              @if(session('success'))
                <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
              @endif
              @yield('content')
            </div>
          </main>
        </div>
      </div>

      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const sidebar = document.getElementById('appSidebar');
          const backdrop = document.getElementById('sidebarBackdrop');
          const openButton = document.getElementById('sidebarOpen');
          const notifToggle = document.getElementById('notifToggle');
          const notifMenu = document.getElementById('notifMenu');
          const userToggle = document.getElementById('userToggle');
          const userMenu = document.getElementById('userMenu');

          const closeSidebar = () => {
            sidebar?.classList.add('-translate-x-full');
            backdrop?.classList.add('hidden');
          };

          openButton?.addEventListener('click', () => {
            sidebar?.classList.remove('-translate-x-full');
            backdrop?.classList.remove('hidden');
          });

          backdrop?.addEventListener('click', closeSidebar);
          notifToggle?.addEventListener('click', () => {
            notifMenu?.classList.toggle('hidden');
            userMenu?.classList.add('hidden');
          });
          userToggle?.addEventListener('click', () => {
            userMenu?.classList.toggle('hidden');
            notifMenu?.classList.add('hidden');
          });
        });
      </script>
    @endguest
  </body>
</html>
