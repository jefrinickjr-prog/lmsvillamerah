@extends('layouts.app')

@section('title', $liveStream->title)

@section('content')
<style>
  .meeting-page{margin:-1.5rem -1rem;min-height:calc(100vh - 5rem);padding:1rem}.meeting-shell{background:#0b0f19;border:1px solid #202938;border-radius:24px;box-shadow:0 24px 70px rgb(15 23 42/.24);margin:auto;max-width:1500px;overflow:hidden}.meeting-header{align-items:center;background:#111827;border-bottom:1px solid #263244;display:flex;gap:16px;justify-content:space-between;padding:16px 20px}.meeting-eyebrow{color:#a5b4fc;font-size:11px;font-weight:900;letter-spacing:.12em;margin:0 0 3px;text-transform:uppercase}.meeting-title{color:#fff;font-size:clamp(20px,3vw,28px);font-weight:900;line-height:1.1;margin:0}.meeting-subtitle{color:#94a3b8;font-size:13px;font-weight:700;margin:5px 0 0}.meeting-exit{align-items:center;background:#dc2626;border-radius:12px;color:#fff!important;display:inline-flex;font-size:13px;font-weight:900;gap:8px;min-height:44px;padding:0 16px;text-decoration:none}.meeting-body{display:grid;grid-template-columns:minmax(0,1fr) 280px;min-height:680px}.meeting-main{background:#030712;min-height:680px;position:relative}.jitsi-container{height:100%;inset:0;min-height:680px;position:absolute;width:100%}.jitsi-container iframe{border:0!important;height:100%!important;width:100%!important}.meeting-loading{align-items:center;background:radial-gradient(circle at center,#1f2937,#0b0f19 65%);color:#fff;display:flex;inset:0;justify-content:center;padding:30px;position:absolute;text-align:center;z-index:3}.meeting-loading.is-hidden{display:none}.meeting-loading i{color:#818cf8;font-size:38px}.meeting-loading h3{font-size:19px;font-weight:900;margin:16px 0 0}.meeting-loading p{color:#cbd5e1;font-size:14px;line-height:1.6;margin:8px auto 0;max-width:560px}.meeting-sidebar{background:#f8fafc;border-left:1px solid #263244;color:#0f172a;padding:20px}.meeting-sidebar h3{font-size:15px;font-weight:900;margin:0 0 16px}.meeting-info{background:#fff;border:1px solid #e2e8f0;border-radius:16px;margin-bottom:14px;padding:15px}.meeting-label{color:#64748b;font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.meeting-value{font-size:13px;font-weight:900;margin-top:5px}.meeting-tip{background:#eef2ff;border:1px solid #c7d2fe;border-radius:16px;color:#3730a3;font-size:12px;font-weight:700;line-height:1.55;padding:14px}.meeting-retry{background:#4f46e5;border:0;border-radius:12px;color:#fff;display:none;font-size:13px;font-weight:900;margin-top:16px;padding:12px 16px}.meeting-retry.is-visible{display:inline-flex}@media(max-width:900px){.meeting-body{grid-template-columns:1fr}.meeting-main,.jitsi-container{min-height:72vh}.meeting-sidebar{border-left:0;border-top:1px solid #dbe2ea}}@media(max-width:640px){.meeting-page{margin:-1rem;padding:0}.meeting-shell{border:0;border-radius:0}.meeting-header{padding:14px}.meeting-exit{font-size:0;min-width:44px;padding:0 13px}.meeting-main,.jitsi-container{min-height:68vh}}
</style>

<div class="meeting-page">
  <section class="meeting-shell">
    <header class="meeting-header">
      <div>
        <p class="meeting-eyebrow">Pembelajaran Online Bimbel Gambar Villa Merah</p>
        <h2 class="meeting-title">{{ $liveStream->title }}</h2>
        <p class="meeting-subtitle">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</p>
      </div>
      @if($isHost)
        <form id="endLiveForm" method="POST" action="{{ route('live-streams.end', $liveStream) }}">
          @csrf
          <button type="submit" class="meeting-exit border-0"><i class="fa-solid fa-phone-slash"></i><span>Akhiri Live untuk Semua</span></button>
        </form>
      @else
        <a href="{{ route('live-streams.index') }}" class="meeting-exit"><i class="fa-solid fa-right-from-bracket"></i><span>Keluar dari Ruang</span></a>
      @endif
    </header>

    <div class="meeting-body">
      <main class="meeting-main">
        <div id="jitsiMeeting" class="jitsi-container"></div>
        <div id="meetingLoading" class="meeting-loading">
          <div>
            <i class="fa-solid fa-spinner fa-spin"></i>
            <h3>Memuat ruang meeting…</h3>
            <p id="meetingStatus">Menghubungkan ke Jitsi Meet. Kamera dan mikrofon dimulai dalam keadaan mati.</p>
            <button id="meetingRetry" class="meeting-retry" type="button" onclick="window.location.reload()">Muat Ulang Meeting</button>
          </div>
        </div>
      </main>

      <aside class="meeting-sidebar">
        <h3>Detail Pertemuan</h3>
        <div class="meeting-info"><div class="meeting-label">Status Anda</div><div class="meeting-value">{{ $isHost ? 'Host/Penyelenggara' : 'Peserta' }}</div></div>
        <div class="meeting-info"><div class="meeting-label">Jadwal</div><div class="meeting-value">{{ $liveStream->starts_at->format('d M Y, H:i') }}–{{ $liveStream->ends_at->format('H:i') }} WIB</div></div>
        <div class="meeting-info"><div class="meeting-label">Akses</div><div class="meeting-value">Khusus akun LMS pada kelas ini</div></div>
        <div class="meeting-tip"><i class="fa-solid fa-shield-halved mr-2"></i>{{ $usingJaas ? 'Meeting menggunakan Jitsi as a Service dengan akses akun LMS dan token sementara.' : 'Mode uji publik aktif. Konfigurasikan kredensial 8x8/JaaS agar durasi meeting tidak dibatasi lima menit.' }}</div>
      </aside>
    </div>
  </section>
</div>

<script src="{{ $jitsiScriptUrl }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const loading = document.getElementById('meetingLoading');
  const status = document.getElementById('meetingStatus');
  const retry = document.getElementById('meetingRetry');
  const endLiveForm = document.getElementById('endLiveForm');
  const statusUrl = @json(route('live-streams.status', $liveStream));
  const indexUrl = @json(route('live-streams.index'));
  const isHost = @json($isHost);
  let api;
  let endingForEveryone = false;
  const fail = message => {
    status.textContent = message;
    retry.classList.add('is-visible');
    loading.classList.remove('is-hidden');
    loading.querySelector('i').className = 'fa-solid fa-triangle-exclamation';
  };

  if (typeof JitsiMeetExternalAPI === 'undefined') {
    fail('Jitsi Meet tidak dapat dimuat. Periksa koneksi internet atau pemblokir konten browser.');
    return;
  }

  try {
    api = new JitsiMeetExternalAPI(@json($jitsiDomain), {
      roomName: @json($jitsiRoomName),
      jwt: @json($jitsiJwt),
      parentNode: document.getElementById('jitsiMeeting'),
      width: '100%',
      height: '100%',
      onload: () => loading.classList.add('is-hidden'),
      lang: 'id',
      userInfo: {
        displayName: @json(auth()->user()->name),
      },
      configOverwrite: {
        prejoinConfig: {enabled: false},
        startWithAudioMuted: true,
        startWithVideoMuted: true,
        disableInviteFunctions: true,
        enableWelcomePage: false,
        hideConferenceSubject: true,
        subject: @json($liveStream->title),
      },
      interfaceConfigOverwrite: {
        MOBILE_APP_PROMO: false,
        SHOW_JITSI_WATERMARK: false,
        TILE_VIEW_MAX_COLUMNS: 4,
      },
    });

    api.addListener('videoConferenceJoined', () => loading.classList.add('is-hidden'));
    const endSessionOnServer = () => fetch(@json(route('live-streams.end', $liveStream)), {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'},
      keepalive: true,
    });

    endLiveForm?.addEventListener('submit', async event => {
      event.preventDefault();
      if (!window.confirm('Akhiri live streaming untuk seluruh peserta?')) return;
      endingForEveryone = true;
      await endSessionOnServer();
      api.executeCommand('hangup');
      window.location.assign(indexUrl);
    });

    api.addListener('readyToClose', async () => {
      if (isHost && !endingForEveryone) await endSessionOnServer();
      window.location.assign(indexUrl);
    });
    api.addListener('cameraError', () => {
      status.textContent = 'Browser tidak dapat mengakses kamera. Periksa izin kamera jika ingin menyalakan video.';
    });

    window.setTimeout(() => {
      if (!loading.classList.contains('is-hidden')) {
        status.textContent = 'Proses memuat lebih lama dari biasanya. Periksa koneksi lalu muat ulang meeting.';
        retry.classList.add('is-visible');
      }
    }, 20000);

    window.setInterval(async () => {
      try {
        const response = await fetch(statusUrl, {headers:{'Accept':'application/json'}, cache:'no-store'});
        if (!response.ok) return;
        const session = await response.json();
        if (session.ended) {
          api.executeCommand('hangup');
          window.location.assign(indexUrl);
        }
      } catch (error) {
        // Gangguan jaringan sementara tidak langsung mengeluarkan peserta.
      }
    }, 4000);
  } catch (error) {
    fail(error?.message || 'Ruang Jitsi Meet tidak dapat dibuka.');
  }
});
</script>
@endsection
