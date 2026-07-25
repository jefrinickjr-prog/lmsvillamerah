<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WherebyMeetingService
{
    public function create(CarbonInterface $endsAt, int $sessionId): array
    {
        $apiKey = trim((string) config('services.whereby.api_key'));
        $apiUrl = rtrim((string) config('services.whereby.api_url'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('Whereby belum dikonfigurasi. Isi WHEREBY_API_KEY pada environment server.');
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout(20)
                ->retry(2, 300)
                ->post($apiUrl.'/meetings', [
                    'endDate' => $endsAt->copy()->utc()->toIso8601String(),
                    'roomMode' => 'group',
                    'roomNamePrefix' => 'villa-merah-'.$sessionId.'-',
                    'roomNamePattern' => 'human-short',
                    'fields' => ['hostRoomUrl'],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);
            throw new RuntimeException('Whereby gagal membuat ruang meeting. Periksa API key dan koneksi server.', previous: $exception);
        }

        $meetingId = trim((string) $response->json('meetingId'));
        $roomUrl = trim((string) $response->json('roomUrl'));
        $hostRoomUrl = trim((string) $response->json('hostRoomUrl'));

        if ($meetingId === '' || $roomUrl === '' || $hostRoomUrl === '') {
            throw new RuntimeException('Respons Whereby tidak lengkap. Pastikan paket Embedded mendukung hostRoomUrl.');
        }

        return [
            'whereby_meeting_id' => $meetingId,
            'meeting_url' => $roomUrl,
            'whereby_host_url' => $hostRoomUrl,
        ];
    }
}
