@extends('layouts.app')

@section('title', $liveStream->title)

@section('content')
  <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div><p class="text-sm font-bold uppercase tracking-wider text-rose-500">Live Streaming Internal</p><h2 class="mt-1 text-3xl font-black">{{ $liveStream->title }}</h2><p class="mt-1 text-sm font-bold text-indigo-600">{{ $liveStream->classroom->title }} · {{ $liveStream->classroom->branch }}</p></div>
    <a href="{{ route('live-streams.index') }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm font-black text-slate-700">Keluar dari Ruang</a>
  </div>

  <div class="overflow-hidden rounded-3xl bg-slate-950 shadow-2xl">
    <div class="relative aspect-video min-h-64">
      <video id="liveVideo" class="h-full w-full bg-black object-contain" autoplay playsinline {{ $isHost ? 'muted' : '' }}></video>
      <div id="waiting" class="absolute inset-0 grid place-items-center bg-slate-950 text-center text-white"><div><i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-400"></i><p class="mt-4 font-black">{{ $isHost ? 'Menyiapkan kamera dan mikrofon…' : 'Menghubungkan ke siaran…' }}</p></div></div>
      <div class="absolute left-4 top-4 rounded-full bg-rose-600 px-3 py-1 text-xs font-black text-white"><i class="fa-solid fa-circle mr-1 text-[8px]"></i> LIVE</div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 p-4 text-white">
      <div id="status" class="text-sm font-bold text-slate-300">Menghubungkan…</div>
      @if($isHost)<div class="flex flex-wrap gap-2"><button id="shareScreen" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-black"><i class="fa-solid fa-display mr-2"></i>Bagikan Layar</button><button id="toggleMic" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-black"><i class="fa-solid fa-microphone mr-2"></i>Mikrofon</button><button id="toggleCamera" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-black"><i class="fa-solid fa-video mr-2"></i>Kamera</button></div>@endif
    </div>
  </div>
  <p class="mt-4 rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700">Izinkan akses kamera dan mikrofon pada browser. Live internal memerlukan koneksi HTTPS yang stabil.</p>

  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      const isHost = @json($isHost);
      const currentUserId = {{ auth()->id() }};
      const hostUserId = {{ (int) $liveStream->started_by }};
      const signalUrl = @json(route('live-streams.signal', $liveStream));
      const signalsUrl = @json(route('live-streams.signals', $liveStream));
      const csrf = @json(csrf_token());
      const video = document.getElementById('liveVideo');
      const waiting = document.getElementById('waiting');
      const status = document.getElementById('status');
      const peers = new Map();
      let localStream = null;
      let cameraStream = null;
      let sharingScreen = false;
      let lastSignalId = 0;
      const rtcConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

      const send = async (to, type, payload) => fetch(signalUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ to_user_id: to, type, payload }) });
      const makePeer = (userId) => {
        if (peers.has(userId)) return peers.get(userId);
        const peer = new RTCPeerConnection(rtcConfig);
        if (isHost && localStream) localStream.getTracks().forEach(track => peer.addTrack(track, localStream));
        peer.onicecandidate = event => event.candidate && send(userId, 'ice', event.candidate.toJSON());
        peer.ontrack = event => { video.srcObject = event.streams[0]; waiting.classList.add('hidden'); status.textContent = 'Terhubung ke live streaming'; };
        peer.onconnectionstatechange = () => { status.textContent = peer.connectionState === 'connected' ? 'Live terhubung' : `Status: ${peer.connectionState}`; };
        peers.set(userId, peer);
        return peer;
      };

      try {
        if (isHost) {
          localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
          cameraStream = localStream;
          video.srcObject = localStream; waiting.classList.add('hidden'); status.textContent = 'Siaran aktif · menunggu siswa';
          document.getElementById('toggleMic')?.addEventListener('click', () => localStream.getAudioTracks().forEach(t => t.enabled = !t.enabled));
          document.getElementById('toggleCamera')?.addEventListener('click', () => localStream.getVideoTracks().forEach(t => t.enabled = !t.enabled));
          const shareButton = document.getElementById('shareScreen');
          const replaceVideoTrack = async (track) => {
            await Promise.all([...peers.values()].map(peer => {
              const sender = peer.getSenders().find(item => item.track?.kind === 'video');
              return sender ? sender.replaceTrack(track) : Promise.resolve();
            }));
          };
          const restoreCamera = async () => {
            if (!sharingScreen) return;
            sharingScreen = false;
            const cameraTrack = cameraStream.getVideoTracks()[0];
            await replaceVideoTrack(cameraTrack);
            localStream = cameraStream;
            video.srcObject = cameraStream;
            shareButton.innerHTML = '<i class="fa-solid fa-display mr-2"></i>Bagikan Layar';
            shareButton.classList.remove('bg-rose-600'); shareButton.classList.add('bg-indigo-600');
            status.textContent = 'Siaran kamera aktif';
          };
          shareButton?.addEventListener('click', async () => {
            if (sharingScreen) return restoreCamera();
            try {
              const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
              const screenTrack = screenStream.getVideoTracks()[0];
              await replaceVideoTrack(screenTrack);
              localStream = new MediaStream([screenTrack, ...cameraStream.getAudioTracks()]);
              video.srcObject = localStream;
              sharingScreen = true;
              shareButton.innerHTML = '<i class="fa-solid fa-stop mr-2"></i>Hentikan Berbagi';
              shareButton.classList.remove('bg-indigo-600'); shareButton.classList.add('bg-rose-600');
              status.textContent = 'Layar sedang dibagikan';
              screenTrack.addEventListener('ended', restoreCamera, { once: true });
            } catch (_) { status.textContent = 'Berbagi layar dibatalkan atau tidak diizinkan'; }
          });
        } else {
          const peer = makePeer(hostUserId);
          const offer = await peer.createOffer({ offerToReceiveVideo: true, offerToReceiveAudio: true });
          await peer.setLocalDescription(offer);
          await send(hostUserId, 'offer', offer);
        }

        setInterval(async () => {
          try {
            const response = await fetch(`${signalsUrl}?after=${lastSignalId}`, { headers: { 'Accept': 'application/json' } });
            for (const signal of await response.json()) {
              lastSignalId = Math.max(lastSignalId, signal.id);
              const peer = makePeer(signal.from_user_id);
              if (signal.type === 'offer' && isHost) { await peer.setRemoteDescription(signal.payload); const answer = await peer.createAnswer(); await peer.setLocalDescription(answer); await send(signal.from_user_id, 'answer', answer); }
              if (signal.type === 'answer' && !isHost) await peer.setRemoteDescription(signal.payload);
              if (signal.type === 'ice') { try { await peer.addIceCandidate(signal.payload); } catch (_) {} }
            }
          } catch (_) { status.textContent = 'Mencoba menyambungkan kembali…'; }
        }, 1500);
      } catch (error) { waiting.classList.remove('hidden'); waiting.innerHTML = '<div><i class="fa-solid fa-triangle-exclamation text-3xl text-amber-400"></i><p class="mt-4 font-black">Kamera/mikrofon tidak dapat diakses.</p><p class="mt-2 text-sm text-slate-300">Periksa izin browser lalu muat ulang halaman.</p></div>'; status.textContent = 'Perangkat media tidak tersedia'; }
    });
  </script>
@endsection
