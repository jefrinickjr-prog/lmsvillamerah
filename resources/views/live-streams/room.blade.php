@extends('layouts.app')

@section('title', $liveStream->title)

@section('content')
  <style>
    .meeting-page { margin: -1.5rem -1rem; min-height: calc(100vh - 5rem); padding: 1rem; }
    .meeting-shell { background: #0b0f19; border: 1px solid #202938; border-radius: 24px; box-shadow: 0 24px 70px rgb(15 23 42 / .24); margin: 0 auto; max-width: 1500px; overflow: hidden; }
    .meeting-header { align-items: center; background: #111827; border-bottom: 1px solid #263244; display: flex; gap: 16px; justify-content: space-between; padding: 16px 20px; }
    .meeting-eyebrow { color: #a5b4fc; font-size: 11px; font-weight: 900; letter-spacing: .12em; margin: 0 0 3px; text-transform: uppercase; }
    .meeting-title { color: #fff; font-size: clamp(20px, 3vw, 28px); font-weight: 900; line-height: 1.1; margin: 0; }
    .meeting-subtitle { color: #94a3b8; font-size: 13px; font-weight: 700; margin: 5px 0 0; }
    .meeting-exit { align-items: center; background: #dc2626; border-radius: 12px; box-shadow: 0 8px 20px rgb(220 38 38 / .3); color: #fff !important; display: inline-flex; font-size: 13px; font-weight: 900; gap: 8px; min-height: 44px; padding: 0 16px; text-decoration: none; }
    .meeting-body { display: grid; grid-template-columns: minmax(0, 1fr) 280px; min-height: 680px; }
    .meeting-main { background: #030712; min-height: 680px; position: relative; }
    #jitsiMeeting { height: 100%; inset: 0; min-height: 680px; position: absolute; width: 100%; }
    #jitsiMeeting iframe { border: 0 !important; height: 100% !important; width: 100% !important; }
    .meeting-loading { align-items: center; background: radial-gradient(circle at center, #1f2937, #0b0f19 65%); color: #fff; display: flex; inset: 0; justify-content: center; padding: 30px; position: absolute; text-align: center; z-index: 3; }
    .meeting-loading.is-hidden { display: none; }
    .meeting-loading i { color: #818cf8; font-size: 38px; }
    .meeting-loading h3 { color: #fff; font-size: 19px; font-weight: 900; margin: 16px 0 0; }
    .meeting-loading p { color: #cbd5e1; font-size: 14px; line-height: 1.6; margin: 8px auto 0; max-width: 560px; }
    .meeting-sidebar { background: #f8fafc; border-left: 1px solid #263244; color: #0f172a; padding: 20px; }
    .meeting-sidebar h3 { font-size: 15px; font-weight: 900; margin: 0 0 16px; }
    .meeting-info { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 14px; padding: 15px; }
    .meeting-label { color: #64748b; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .meeting-value { color: #0f172a; font-size: 13px; font-weight: 900; margin-top: 5px; overflow-wrap: anywhere; }
    .meeting-tip { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 16px; color: #3730a3; font-size: 12px; font-weight: 700; line-height: 1.55; padding: 14px; }
    .meeting-fallback { background: #4f46e5; border-radius: 12px; color: #fff !important; display: none; font-size: 13px; font-weight: 900; margin-top: 16px; padding: 12px 16px; text-decoration: none; }
    .meeting-fallback.is-visible { display: inline-flex; }
    @media (max-width: 900px) {
      .meeting-body { grid-template-columns: 1fr; min-height: 0; }
      .meeting-main, #jitsiMeeting { min-height: 72vh; }
      .meeting-sidebar { border-left: 0; border-top: 1px solid #dbe2ea; display: grid; gap: 10px; grid-template-columns: 1fr 1fr; }
      .meeting-sidebar h3 { grid-column: 1 / -1; margin-bottom: 0; }
    }
    @media (max-width: 640px) {
      .meeting-page { margin: -1rem; padding: 0; }
      .meeting-shell { border: 0; border-radius: 0; }
      .meeting-header { align-items: flex-start; padding: 14px; }
      .meeting-exit { font-size: 0; min-width: 44px; padding: 0 13px; }
      .meeting-exit i { font-size: 15px; }
      .meeting-main, #jitsiMeeting { min-height: 68vh; }
      .meeting-sidebar { grid-template-columns: 1fr; }
    }
  </style>

  <div class="meeting-page">
    <section class="meeting-shell">
      <header class="meeting-header">
        <div>
          <p class="meeting-eyebrow">Video Meeting Villa Merah</p>
          <h2 class="meeting-title">{{ $liveStream->title }}</h2>
          <p class="meeting-subtitle">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</p>
        </div>
        <a href="{{ route('live-streams.index') }}" class="meeting-exit"><i class="fa-solid fa-phone-slash"></i><span>Keluar dari Ruang</span></a>
      </header>

      <div class="meeting-body">
        <main class="meeting-main">
          <div id="jitsiMeeting"></div>
          <div id="meetingLoading" class="meeting-loading">
            <div>
              <i class="fa-solid fa-spinner fa-spin"></i>
              <h3>Memuat ruang video…</h3>
              <p id="meetingStatus">Menghubungkan ke layanan meeting. Kamera dan mikrofon dimulai dalam keadaan mati.</p>
              <a id="meetingFallback" class="meeting-fallback" href="https://meet.jit.si/{{ $meetingRoom }}" target="_blank" rel="noopener">Buka Meeting di Tab Baru</a>
            </div>
          </div>
        </main>

        <aside class="meeting-sidebar">
          <h3>Detail Pertemuan</h3>
          <div class="meeting-info"><div class="meeting-label">Status Anda</div><div class="meeting-value">{{ $isHost ? 'Host/Penyelenggara' : 'Peserta' }}</div></div>
          <div class="meeting-info"><div class="meeting-label">Jadwal</div><div class="meeting-value">{{ $liveStream->starts_at->format('d M Y, H:i') }}–{{ $liveStream->ends_at->format('H:i') }} WIB</div></div>
          <div class="meeting-info"><div class="meeting-label">Kelas</div><div class="meeting-value">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</div></div>
          <div class="meeting-tip"><i class="fa-solid fa-shield-halved mr-2"></i>Kontrol kamera, mikrofon, berbagi layar, peserta, chat, dan tampilan tersedia langsung pada toolbar meeting.</div>
        </aside>
      </div>
    </section>
  </div>

  <script src="https://meet.jit.si/external_api.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const loading = document.getElementById('meetingLoading');
      const status = document.getElementById('meetingStatus');
      const fallback = document.getElementById('meetingFallback');

      const showFailure = (message) => {
        status.textContent = message;
        fallback.classList.add('is-visible');
        loading.classList.remove('is-hidden');
        loading.querySelector('i').className = 'fa-solid fa-triangle-exclamation';
      };

      if (typeof JitsiMeetExternalAPI === 'undefined') {
        showFailure('Layanan meeting gagal dimuat. Buka meeting pada tab baru atau periksa koneksi internet.');
        return;
      }

      try {
        const api = new JitsiMeetExternalAPI('meet.jit.si', {
          roomName: @json($meetingRoom),
          parentNode: document.getElementById('jitsiMeeting'),
          width: '100%',
          height: '100%',
          lang: 'id',
          userInfo: {
            displayName: @json(auth()->user()->name),
          },
          configOverwrite: {
            startWithAudioMuted: true,
            startWithVideoMuted: true,
            prejoinPageEnabled: false,
            disableDeepLinking: true,
          },
          interfaceConfigOverwrite: {
            MOBILE_APP_PROMO: false,
            SHOW_JITSI_WATERMARK: false,
            SHOW_WATERMARK_FOR_GUESTS: false,
            TILE_VIEW_MAX_COLUMNS: 4,
          },
        });

        api.addEventListener('videoConferenceJoined', () => {
          loading.classList.add('is-hidden');
        });
        api.addEventListener('readyToClose', () => {
          window.location.assign(@json(route('live-streams.index')));
        });
        api.addEventListener('errorOccurred', event => {
          showFailure(event?.message || 'Ruang meeting mengalami gangguan. Silakan buka pada tab baru.');
        });

        window.setTimeout(() => {
          if (!loading.classList.contains('is-hidden')) {
            status.textContent = 'Proses memuat lebih lama dari biasanya. Anda dapat membuka meeting pada tab baru.';
            fallback.classList.add('is-visible');
          }
        }, 15000);
      } catch (error) {
        showFailure(error?.message || 'Ruang meeting tidak dapat dibuat.');
      }
    });
  </script>
@endsection
