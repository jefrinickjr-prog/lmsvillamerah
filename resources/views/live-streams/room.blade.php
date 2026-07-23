@extends('layouts.app')

@section('title', $liveStream->title)

@section('content')
  <style>
    .meet-page { color: #e5e7eb; margin: -1.5rem -1rem; min-height: calc(100vh - 5rem); padding: 1rem; }
    .meet-shell { background: #0b0f19; border: 1px solid #202938; border-radius: 24px; box-shadow: 0 24px 70px rgb(15 23 42 / .24); margin: 0 auto; max-width: 1500px; overflow: hidden; }
    .meet-header { align-items: center; background: #111827; border-bottom: 1px solid #263244; display: flex; gap: 16px; justify-content: space-between; padding: 16px 20px; }
    .meet-eyebrow { color: #a5b4fc; font-size: 11px; font-weight: 900; letter-spacing: .12em; margin: 0 0 3px; text-transform: uppercase; }
    .meet-title { color: #fff; font-size: clamp(20px, 3vw, 28px); font-weight: 900; line-height: 1.1; margin: 0; }
    .meet-subtitle { color: #94a3b8; font-size: 13px; font-weight: 700; margin: 5px 0 0; }
    .meet-exit { align-items: center; background: #dc2626; border: 0; border-radius: 12px; box-shadow: 0 8px 20px rgb(220 38 38 / .3); color: #fff !important; display: inline-flex; font-size: 13px; font-weight: 900; gap: 8px; min-height: 44px; padding: 0 16px; text-decoration: none; }
    .meet-exit:hover { background: #b91c1c; transform: translateY(-1px); }
    .meet-body { display: grid; grid-template-columns: minmax(0, 1fr) 280px; min-height: 620px; }
    .meet-main { display: flex; flex-direction: column; min-width: 0; }
    .meet-stage { background: #030712; flex: 1; min-height: 500px; position: relative; }
    .meet-video { height: 100%; inset: 0; object-fit: contain; position: absolute; width: 100%; }
    .meet-waiting { align-items: center; background: radial-gradient(circle at center, #1f2937 0, #0b0f19 60%); color: #fff; display: flex; inset: 0; justify-content: center; padding: 30px; position: absolute; text-align: center; z-index: 2; }
    .meet-waiting.is-hidden { display: none; }
    .meet-waiting-icon { color: #818cf8; font-size: 36px; }
    .meet-waiting-title { color: #fff; font-size: 18px; font-weight: 900; margin: 16px 0 0; }
    .meet-waiting-copy { color: #cbd5e1; font-size: 14px; line-height: 1.6; margin: 8px auto 0; max-width: 560px; }
    .meet-live-badge { align-items: center; background: #dc2626; border-radius: 999px; box-shadow: 0 6px 18px rgb(220 38 38 / .35); color: #fff; display: flex; font-size: 11px; font-weight: 900; gap: 7px; left: 18px; padding: 7px 11px; position: absolute; top: 18px; z-index: 4; }
    .meet-live-dot { background: #fff; border-radius: 999px; height: 7px; width: 7px; }
    .meet-toolbar { align-items: center; background: #111827; border-top: 1px solid #263244; display: flex; gap: 14px; justify-content: space-between; min-height: 84px; padding: 14px 18px; }
    .meet-status { color: #cbd5e1; font-size: 13px; font-weight: 800; line-height: 1.4; max-width: 330px; }
    .meet-controls { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
    .meet-control, .meet-primary { align-items: center; border: 0; cursor: pointer; display: inline-flex; font-family: inherit; font-size: 13px; font-weight: 900; gap: 8px; justify-content: center; min-height: 46px; padding: 0 16px; transition: .18s ease; }
    .meet-control { background: #334155; border: 1px solid #475569; border-radius: 14px; color: #fff !important; }
    .meet-control:hover { background: #475569; transform: translateY(-1px); }
    .meet-control.is-off { background: #991b1b; border-color: #dc2626; }
    .meet-primary { background: #4f46e5; border-radius: 14px; box-shadow: 0 8px 20px rgb(79 70 229 / .35); color: #fff !important; }
    .meet-primary:hover { background: #4338ca; transform: translateY(-1px); }
    .meet-primary.is-sharing { background: #dc2626; box-shadow: 0 8px 20px rgb(220 38 38 / .3); }
    .meet-sidebar { background: #f8fafc; border-left: 1px solid #263244; color: #0f172a; padding: 20px; }
    .meet-sidebar-title { font-size: 15px; font-weight: 900; margin: 0 0 16px; }
    .meet-info-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 14px; padding: 15px; }
    .meet-info-label { color: #64748b; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .meet-info-value { color: #0f172a; font-size: 13px; font-weight: 900; margin-top: 5px; overflow-wrap: anywhere; }
    .meet-tip { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 16px; color: #3730a3; font-size: 12px; font-weight: 700; line-height: 1.55; padding: 14px; }
    .meet-retry { align-items: center; background: #4f46e5; border: 0; border-radius: 12px; box-shadow: 0 8px 20px rgb(79 70 229 / .35); color: #fff !important; cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 900; gap: 8px; justify-content: center; margin-top: 16px; min-height: 44px; padding: 0 18px; }
    .meet-fallback-action { color: #a5b4fc; font-size: 12px; font-weight: 800; margin-top: 12px; }
    @media (max-width: 900px) {
      .meet-body { grid-template-columns: 1fr; min-height: 0; }
      .meet-sidebar { border-left: 0; border-top: 1px solid #dbe2ea; display: grid; gap: 10px; grid-template-columns: 1fr 1fr; }
      .meet-sidebar-title { grid-column: 1 / -1; margin-bottom: 0; }
      .meet-stage { min-height: min(62vh, 520px); }
    }
    @media (max-width: 640px) {
      .meet-page { margin: -1rem; padding: 0; }
      .meet-shell { border: 0; border-radius: 0; }
      .meet-header { align-items: flex-start; padding: 14px; }
      .meet-exit { font-size: 0; min-width: 44px; padding: 0 13px; }
      .meet-exit i { font-size: 15px; }
      .meet-stage { min-height: 54vh; }
      .meet-toolbar { align-items: stretch; flex-direction: column; padding: 12px; }
      .meet-status { max-width: none; text-align: center; }
      .meet-controls { display: grid; grid-template-columns: repeat(3, 1fr); width: 100%; }
      .meet-control, .meet-primary { font-size: 11px; min-height: 50px; padding: 0 8px; }
      .meet-sidebar { grid-template-columns: 1fr; }
    }
  </style>

  <div class="meet-page">
    <section class="meet-shell">
      <header class="meet-header">
        <div>
          <p class="meet-eyebrow">Live Streaming Internal</p>
          <h2 class="meet-title">{{ $liveStream->title }}</h2>
          <p class="meet-subtitle">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</p>
        </div>
        <a href="{{ route('live-streams.index') }}" class="meet-exit"><i class="fa-solid fa-phone-slash"></i><span>Keluar dari Ruang</span></a>
      </header>

      <div class="meet-body">
        <div class="meet-main">
          <div class="meet-stage">
            <video id="liveVideo" class="meet-video" autoplay playsinline {{ $isHost ? 'muted' : '' }}></video>
            <div id="waiting" class="meet-waiting">
              <div>
                <i class="meet-waiting-icon fa-solid fa-spinner fa-spin"></i>
                <p class="meet-waiting-title">{{ $isHost ? 'Anda sudah masuk sebagai host' : 'Menghubungkan ke siaran…' }}</p>
                <p class="meet-waiting-copy">{{ $isHost ? 'Kamera dan mikrofon bersifat opsional. Aktifkan melalui toolbar saat diperlukan.' : 'Mohon tunggu hingga koneksi dengan host tersedia.' }}</p>
              </div>
            </div>
            <div class="meet-live-badge"><span class="meet-live-dot"></span>LIVE</div>
          </div>

          <footer class="meet-toolbar">
            <div id="status" class="meet-status">Menghubungkan…</div>
            @if($isHost)
              <div class="meet-controls">
                <button id="toggleMic" type="button" class="meet-control is-off"><i class="fa-solid fa-microphone-slash"></i><span>Mic Mati</span></button>
                <button id="toggleCamera" type="button" class="meet-control is-off"><i class="fa-solid fa-video-slash"></i><span>Kamera Mati</span></button>
                <button id="shareScreen" type="button" class="meet-primary"><i class="fa-solid fa-display"></i><span>Bagikan Layar</span></button>
              </div>
            @endif
          </footer>
        </div>

        <aside class="meet-sidebar">
          <h3 class="meet-sidebar-title">Detail Pertemuan</h3>
          <div class="meet-info-card"><div class="meet-info-label">Status Anda</div><div class="meet-info-value">{{ $isHost ? 'Host/Penyelenggara' : 'Peserta' }}</div></div>
          <div class="meet-info-card"><div class="meet-info-label">Jadwal</div><div class="meet-info-value">{{ $liveStream->starts_at->format('d M Y, H:i') }}–{{ $liveStream->ends_at->format('H:i') }} WIB</div></div>
          <div class="meet-info-card"><div class="meet-info-label">Kelas</div><div class="meet-info-value">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</div></div>
          <div class="meet-tip"><i class="fa-solid fa-shield-halved mr-2"></i>Gunakan Chrome atau Edge terbaru melalui HTTPS. Jika kamera tidak tersedia, host tetap dapat menayangkan layar.</div>
        </aside>
      </div>
    </section>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      const isHost = @json($isHost);
      const hostUserId = {{ (int) $liveStream->started_by }};
      const signalUrl = @json(route('live-streams.signal', $liveStream));
      const signalsUrl = @json(route('live-streams.signals', $liveStream));
      const csrf = @json(csrf_token());
      const video = document.getElementById('liveVideo');
      const waiting = document.getElementById('waiting');
      const status = document.getElementById('status');
      const shareButton = document.getElementById('shareScreen');
      const peers = new Map();
      const pendingIce = new Map();
      let localStream = null;
      let cameraStream = null;
      let sharingScreen = false;
      let lastSignalId = 0;
      const rtcConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

      const send = async (to, type, payload) => {
        const response = await fetch(signalUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({ to_user_id: to, type, payload }),
        });
        if (!response.ok) throw new Error(`Signaling gagal (${response.status})`);
      };

      const makePeer = (userId) => {
        if (peers.has(userId)) return peers.get(userId);
        const peer = new RTCPeerConnection(rtcConfig);
        if (isHost && localStream) localStream.getTracks().forEach(track => peer.addTrack(track, localStream));
        peer.onicecandidate = event => event.candidate && send(userId, 'ice', event.candidate.toJSON()).catch(() => {});
        peer.ontrack = event => {
          video.srcObject = event.streams[0];
          waiting.classList.add('is-hidden');
          status.textContent = 'Terhubung ke live streaming';
        };
        peer.onconnectionstatechange = () => {
          if (peer.connectionState === 'connected') {
            if (!isHost && !video.srcObject) {
              waiting.classList.remove('is-hidden');
              waiting.innerHTML = '<div><i class="meet-waiting-icon fa-solid fa-signal"></i><p class="meet-waiting-title">Sudah terhubung ke host.</p><p class="meet-waiting-copy">Menunggu host menyalakan kamera atau membagikan layar.</p></div>';
              status.textContent = 'Terhubung · menunggu siaran host';
            } else {
              status.textContent = isHost ? 'Siswa terhubung' : 'Live terhubung';
            }
          } else if (peer.connectionState === 'failed') {
            status.textContent = 'Koneksi gagal · mencoba menyambungkan ulang';
            peer.restartIce();
            if (!isHost) renegotiate(userId, peer).catch(() => {});
          } else {
            status.textContent = `Status: ${peer.connectionState}`;
          }
        };
        peers.set(userId, peer);
        return peer;
      };

      const mediaErrorText = (error) => {
        if (error?.name === 'NotAllowedError') return 'Izin kamera/mikrofon diblokir. Klik ikon gembok di address bar, pilih Izinkan, lalu coba lagi.';
        if (error?.name === 'NotFoundError') return 'Kamera atau mikrofon tidak ditemukan pada perangkat ini.';
        if (error?.name === 'NotReadableError') return 'Kamera atau mikrofon sedang digunakan aplikasi lain.';
        return error?.message || 'Kamera dan mikrofon tidak dapat diakses.';
      };

      const showHostReady = (message = 'Kamera dan mikrofon nonaktif. Aktifkan melalui toolbar saat diperlukan.') => {
        waiting.classList.remove('is-hidden');
        waiting.innerHTML = '<div><i class="meet-waiting-icon fa-solid fa-user-shield"></i><p class="meet-waiting-title">Anda sudah masuk sebagai host</p><p id="hostReadyDetail" class="meet-waiting-copy"></p><p class="meet-fallback-action">Siswa dapat masuk sekarang. Kamera, mikrofon, dan berbagi layar bersifat opsional.</p></div>';
        document.getElementById('hostReadyDetail').textContent = message;
      };

      const renegotiate = async (userId, peer) => {
        if (peer.signalingState !== 'stable') return;
        const offer = await peer.createOffer({ offerToReceiveVideo: true, offerToReceiveAudio: true });
        await peer.setLocalDescription(offer);
        await send(userId, 'offer', offer);
      };

      const setRemoteDescription = async (userId, peer, description) => {
        const normalizeSdp = (sdp) => {
          if (typeof sdp !== 'string') return sdp;
          const sourceLines = sdp.split(/\r?\n/);
          const redPayloads = new Set(
            sourceLines
              .map(line => line.match(/^a=rtpmap:(\d+)\s+red\//i)?.[1])
              .filter(Boolean)
          );
          const unsupported = [
            /^a=max-message-size:/i,
            /^a=extmap-allow-mixed$/i,
            /^a=extmap:/i,
            /^a=rtcp-fb:/i,
            /^a=rtcp-rsize$/i,
            /^a=rid:/i,
            /^a=simulcast:/i,
          ];
          return sourceLines
            .map(line => {
              if (!/^m=(audio|video)\s/i.test(line)) return line;
              const parts = line.trim().split(/\s+/);
              return parts.filter((part, index) => index < 3 || !redPayloads.has(part)).join(' ');
            })
            .filter(line => {
              const trimmed = line.trim();
              if (unsupported.some(pattern => pattern.test(trimmed))) return false;
              const payload = trimmed.match(/^a=(?:rtpmap|fmtp|rtcp-fb):(\d+)/i)?.[1];
              return !payload || !redPayloads.has(payload);
            })
            .join('\r\n');
        };
        const normalized = {
          ...description,
          // Browser lama/embedded WebView dapat menolak beberapa atribut
          // opsional yang dikirim browser WebRTC versi lebih baru.
          sdp: normalizeSdp(description?.sdp),
        };
        if (description.type === 'offer' && peer.signalingState !== 'stable') {
          try { await peer.setLocalDescription({ type: 'rollback' }); } catch (_) {}
        }
        await peer.setRemoteDescription(normalized);
        const queued = pendingIce.get(userId) || [];
        pendingIce.delete(userId);
        for (const candidate of queued) {
          try { await peer.addIceCandidate(candidate); } catch (_) {}
        }
      };

      const publishTrack = async (track, stream) => {
        await Promise.all([...peers.entries()].map(async ([userId, peer]) => {
          const sender = peer.getSenders().find(item => item.track?.kind === track.kind);
          if (sender) return sender.replaceTrack(track);
          peer.addTrack(track, stream);
          await renegotiate(userId, peer);
        }));
      };

      const showMediaFallback = (error) => {
        const detail = mediaErrorText(error);
        showHostReady(detail);
        status.textContent = detail;
      };

      const setControlState = (button, kind, enabled) => {
        button.classList.toggle('is-off', !enabled);
        const icon = kind === 'audio'
          ? `fa-microphone${enabled ? '' : '-slash'}`
          : `fa-video${enabled ? '' : '-slash'}`;
        const label = kind === 'audio'
          ? (enabled ? 'Mikrofon' : 'Mic Mati')
          : (enabled ? 'Kamera' : 'Kamera Mati');
        button.innerHTML = `<i class="fa-solid ${icon}"></i><span>${label}</span>`;
      };

      const enableMediaKind = async (kind) => {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: kind === 'video',
          audio: kind === 'audio',
        });
        cameraStream ||= new MediaStream();
        const track = kind === 'video' ? stream.getVideoTracks()[0] : stream.getAudioTracks()[0];
        cameraStream.getTracks().filter(item => item.kind === kind).forEach(item => {
          cameraStream.removeTrack(item);
          item.stop();
        });
        cameraStream.addTrack(track);
        if (!sharingScreen) {
          localStream = cameraStream;
          await publishTrack(track, cameraStream);
        }
        if (kind === 'video' && !sharingScreen) {
          video.srcObject = cameraStream;
          waiting.classList.add('is-hidden');
        }
        status.textContent = `${kind === 'video' ? 'Kamera' : 'Mikrofon'} aktif · ruang live siap`;
        return track;
      };

      const restoreCamera = async () => {
        if (!sharingScreen) return;
        sharingScreen = false;
        const cameraTrack = cameraStream?.getVideoTracks()[0];
        if (cameraTrack) await publishTrack(cameraTrack, cameraStream);
        localStream = cameraStream || new MediaStream();
        video.srcObject = cameraStream || null;
        shareButton.innerHTML = '<i class="fa-solid fa-display"></i><span>Bagikan Layar</span>';
        shareButton.classList.remove('is-sharing');
        if (cameraTrack) {
          waiting.classList.add('is-hidden');
          status.textContent = 'Siaran kamera aktif';
        } else {
          showHostReady();
          status.textContent = 'Berbagi layar dihentikan · host tetap terhubung';
        }
      };

      setInterval(async () => {
        try {
          const response = await fetch(`${signalsUrl}?after=${lastSignalId}`, { headers: { 'Accept': 'application/json' } });
          if (!response.ok) throw new Error(`Polling signaling gagal (${response.status})`);
          const signals = await response.json();
          for (const signal of signals) {
            lastSignalId = Math.max(lastSignalId, signal.id);
            const peer = makePeer(signal.from_user_id);
            const payload = typeof signal.payload === 'string'
              ? JSON.parse(signal.payload)
              : signal.payload;
            if (signal.type === 'offer') {
              await setRemoteDescription(signal.from_user_id, peer, payload);
              const answer = await peer.createAnswer();
              await peer.setLocalDescription(answer);
              await send(signal.from_user_id, 'answer', answer);
            } else if (signal.type === 'answer') {
              if (peer.signalingState === 'have-local-offer') {
                await setRemoteDescription(signal.from_user_id, peer, payload);
              }
            } else if (signal.type === 'ice') {
              if (peer.remoteDescription) {
                try { await peer.addIceCandidate(payload); } catch (_) {}
              } else {
                const queued = pendingIce.get(signal.from_user_id) || [];
                queued.push(payload);
                pendingIce.set(signal.from_user_id, queued);
              }
            }
          }
          if (signals.length === 0 && peers.size === 0) {
            status.textContent = isHost
              ? 'Host siap · menunggu siswa masuk'
              : 'Mencari koneksi host…';
          }
        } catch (error) {
          status.textContent = `Gangguan signaling: ${error?.message || 'mencoba menyambungkan ulang'}`;
        }
      }, 1500);

      try {
        if (!window.isSecureContext || !window.RTCPeerConnection) {
          throw new Error('Live streaming memerlukan HTTPS dan browser yang mendukung WebRTC.');
        }

        if (isHost) {
          document.getElementById('toggleMic')?.addEventListener('click', async event => {
            const button = event.currentTarget;
            let track = cameraStream?.getAudioTracks()[0];
            try {
              if (!track) track = await enableMediaKind('audio');
              else track.enabled = !track.enabled;
              setControlState(button, 'audio', track.enabled);
              status.textContent = track.enabled ? 'Mikrofon aktif' : 'Mikrofon dimatikan · host tetap terhubung';
            } catch (error) {
              setControlState(button, 'audio', false);
              showMediaFallback(error);
            }
          });
          document.getElementById('toggleCamera')?.addEventListener('click', async event => {
            const button = event.currentTarget;
            let track = cameraStream?.getVideoTracks()[0];
            try {
              if (!track) track = await enableMediaKind('video');
              else track.enabled = !track.enabled;
              setControlState(button, 'video', track.enabled);
              if (track.enabled && !sharingScreen) {
                video.srcObject = cameraStream;
                waiting.classList.add('is-hidden');
                status.textContent = 'Kamera aktif';
              } else if (!sharingScreen) {
                showHostReady('Kamera dimatikan. Host tetap berada di dalam ruang dan siswa tetap dapat terhubung.');
                status.textContent = 'Kamera dimatikan · host tetap terhubung';
              }
            } catch (error) {
              setControlState(button, 'video', false);
              showMediaFallback(error);
            }
          });
          shareButton?.addEventListener('click', async () => {
            if (sharingScreen) return restoreCamera();
            try {
              const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
              const screenTrack = screenStream.getVideoTracks()[0];
              const audioTracks = screenStream.getAudioTracks().length ? screenStream.getAudioTracks() : (cameraStream?.getAudioTracks() || []);
              localStream = new MediaStream([screenTrack, ...audioTracks]);
              await publishTrack(screenTrack, localStream);
              for (const audioTrack of audioTracks) await publishTrack(audioTrack, localStream);
              video.srcObject = localStream;
              waiting.classList.add('is-hidden');
              sharingScreen = true;
              shareButton.innerHTML = '<i class="fa-solid fa-stop"></i><span>Hentikan Berbagi</span>';
              shareButton.classList.add('is-sharing');
              status.textContent = 'Layar sedang dibagikan';
              screenTrack.addEventListener('ended', restoreCamera, { once: true });
            } catch (error) {
              status.textContent = mediaErrorText(error);
            }
          });
          localStream = new MediaStream();
          showHostReady();
          status.textContent = 'Host sudah masuk · kamera dan mikrofon nonaktif';
        } else {
          await renegotiate(hostUserId, makePeer(hostUserId));
        }
      } catch (error) {
        if (isHost) {
          showMediaFallback(error);
        } else {
          waiting.innerHTML = '<div><i class="meet-waiting-icon fa-solid fa-triangle-exclamation"></i><p class="meet-waiting-title">Tidak dapat terhubung ke live streaming.</p><p class="meet-waiting-copy">Periksa koneksi internet lalu muat ulang halaman.</p></div>';
          status.textContent = error?.message || 'Browser tidak mendukung live streaming';
        }
      }
    });
  </script>
@endsection
