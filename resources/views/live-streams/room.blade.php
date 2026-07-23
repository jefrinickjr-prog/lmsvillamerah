@extends('layouts.app')

@section('title', $liveStream->title)

@section('content')
  <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-rose-500">Live Streaming Internal</p>
      <h2 class="mt-1 text-3xl font-black">{{ $liveStream->title }}</h2>
      <p class="mt-1 text-sm font-bold text-indigo-600">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</p>
    </div>
    <a href="{{ route('live-streams.index') }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm font-black text-slate-700">Keluar dari Ruang</a>
  </div>

  <div class="overflow-hidden rounded-3xl bg-slate-950 shadow-2xl">
    <div class="relative aspect-video min-h-64">
      <video id="liveVideo" class="h-full w-full bg-black object-contain" autoplay playsinline {{ $isHost ? 'muted' : '' }}></video>
      <div id="waiting" class="absolute inset-0 grid place-items-center bg-slate-950 text-center text-white">
        <div><i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-400"></i><p class="mt-4 font-black">{{ $isHost ? 'Menyiapkan kamera dan mikrofon…' : 'Menghubungkan ke siaran…' }}</p></div>
      </div>
      <div class="absolute left-4 top-4 rounded-full bg-rose-600 px-3 py-1 text-xs font-black text-white"><i class="fa-solid fa-circle mr-1 text-[8px]"></i> LIVE</div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 p-4 text-white">
      <div id="status" class="text-sm font-bold text-slate-300">Menghubungkan…</div>
      @if($isHost)
        <div class="flex flex-wrap gap-2">
          <button id="shareScreen" type="button" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-black"><i class="fa-solid fa-display mr-2"></i>Bagikan Layar</button>
          <button id="toggleMic" type="button" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-black"><i class="fa-solid fa-microphone mr-2"></i>Mikrofon</button>
          <button id="toggleCamera" type="button" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-black"><i class="fa-solid fa-video mr-2"></i>Kamera</button>
        </div>
      @endif
    </div>
  </div>
  <p class="mt-4 rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700">Izinkan akses kamera dan mikrofon pada browser. Jika perangkat media tidak tersedia, host tetap dapat memulai melalui Bagikan Layar.</p>

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
          waiting.classList.add('hidden');
          status.textContent = 'Terhubung ke live streaming';
        };
        peer.onconnectionstatechange = () => {
          status.textContent = peer.connectionState === 'connected' ? 'Live terhubung' : `Status: ${peer.connectionState}`;
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

      const renegotiate = async (userId, peer) => {
        const offer = await peer.createOffer({ offerToReceiveVideo: true, offerToReceiveAudio: true });
        await peer.setLocalDescription(offer);
        await send(userId, 'offer', offer);
      };

      const publishTrack = async (track, stream) => {
        await Promise.all([...peers.entries()].map(async ([userId, peer]) => {
          const sender = peer.getSenders().find(item => item.track?.kind === track.kind);
          if (sender) return sender.replaceTrack(track);
          peer.addTrack(track, stream);
          await renegotiate(userId, peer);
        }));
      };

      let startCamera;
      const showMediaFallback = (error) => {
        const detail = mediaErrorText(error);
        waiting.classList.remove('hidden');
        waiting.innerHTML = '<div class="px-6"><i class="fa-solid fa-triangle-exclamation text-3xl text-amber-400"></i><p class="mt-4 font-black">Kamera/mikrofon belum aktif.</p><p id="mediaErrorDetail" class="mt-2 text-sm text-slate-300"></p><button id="retryMedia" type="button" class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-black text-white"><i class="fa-solid fa-rotate-right mr-2"></i>Coba Kamera Lagi</button><p class="mt-3 text-xs text-slate-400">Host tetap dapat memulai dengan tombol Bagikan Layar.</p></div>';
        document.getElementById('mediaErrorDetail').textContent = detail;
        document.getElementById('retryMedia')?.addEventListener('click', () => startCamera());
        status.textContent = detail;
      };

      startCamera = async () => {
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
          cameraStream?.getTracks().forEach(track => track.stop());
          cameraStream = stream;
          if (!sharingScreen) {
            localStream = stream;
            video.srcObject = stream;
            for (const track of stream.getTracks()) await publishTrack(track, stream);
          }
          waiting.classList.add('hidden');
          status.textContent = 'Siaran aktif · menunggu siswa';
        } catch (error) {
          showMediaFallback(error);
        }
      };

      const restoreCamera = async () => {
        if (!sharingScreen) return;
        sharingScreen = false;
        const cameraTrack = cameraStream?.getVideoTracks()[0];
        if (cameraTrack) await publishTrack(cameraTrack, cameraStream);
        localStream = cameraStream || new MediaStream();
        video.srcObject = cameraStream || null;
        shareButton.innerHTML = '<i class="fa-solid fa-display mr-2"></i>Bagikan Layar';
        shareButton.classList.remove('bg-rose-600');
        shareButton.classList.add('bg-indigo-600');
        if (cameraTrack) {
          waiting.classList.add('hidden');
          status.textContent = 'Siaran kamera aktif';
        } else {
          showMediaFallback(new DOMException('Kamera belum tersedia.', 'NotFoundError'));
        }
      };

      setInterval(async () => {
        try {
          const response = await fetch(`${signalsUrl}?after=${lastSignalId}`, { headers: { 'Accept': 'application/json' } });
          if (!response.ok) throw new Error(`Polling signaling gagal (${response.status})`);
          for (const signal of await response.json()) {
            lastSignalId = Math.max(lastSignalId, signal.id);
            const peer = makePeer(signal.from_user_id);
            if (signal.type === 'offer') {
              await peer.setRemoteDescription(signal.payload);
              const answer = await peer.createAnswer();
              await peer.setLocalDescription(answer);
              await send(signal.from_user_id, 'answer', answer);
            } else if (signal.type === 'answer') {
              await peer.setRemoteDescription(signal.payload);
            } else if (signal.type === 'ice') {
              try { await peer.addIceCandidate(signal.payload); } catch (_) {}
            }
          }
        } catch (_) {
          status.textContent = 'Mencoba menyambungkan kembali…';
        }
      }, 1500);

      try {
        if (!window.isSecureContext || !navigator.mediaDevices) {
          throw new Error('Live streaming memerlukan HTTPS dan browser modern.');
        }

        if (isHost) {
          document.getElementById('toggleMic')?.addEventListener('click', () => cameraStream?.getAudioTracks().forEach(track => track.enabled = !track.enabled));
          document.getElementById('toggleCamera')?.addEventListener('click', () => cameraStream?.getVideoTracks().forEach(track => track.enabled = !track.enabled));
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
              waiting.classList.add('hidden');
              sharingScreen = true;
              shareButton.innerHTML = '<i class="fa-solid fa-stop mr-2"></i>Hentikan Berbagi';
              shareButton.classList.remove('bg-indigo-600');
              shareButton.classList.add('bg-rose-600');
              status.textContent = 'Layar sedang dibagikan';
              screenTrack.addEventListener('ended', restoreCamera, { once: true });
            } catch (error) {
              status.textContent = mediaErrorText(error);
            }
          });
          await startCamera();
        } else {
          await renegotiate(hostUserId, makePeer(hostUserId));
        }
      } catch (error) {
        if (isHost) {
          showMediaFallback(error);
        } else {
          waiting.innerHTML = '<div><i class="fa-solid fa-triangle-exclamation text-3xl text-amber-400"></i><p class="mt-4 font-black">Tidak dapat terhubung ke live streaming.</p></div>';
          status.textContent = error?.message || 'Browser tidak mendukung live streaming';
        }
      }
    });
  </script>
@endsection
