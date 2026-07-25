<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Mock Whereby Meeting Service untuk Development/Testing
 * Gunakan ini saat WHEREBY_API_KEY belum dikonfigurasi
 */
class MockWherebyMeetingService
{
    /**
     * Generate mock meeting data untuk development
     */
    public function create(CarbonInterface $endsAt, int $sessionId): array
    {
        $meetingId = 'mock-meeting-' . $sessionId . '-' . random_int(10000, 99999);
        $roomKey = 'room-' . $sessionId . '-' . substr(md5(uniqid()), 0, 8);
        
        return [
            'whereby_meeting_id' => $meetingId,
            'meeting_url' => 'https://demo.whereby.com/' . $roomKey,
            'whereby_host_url' => 'https://demo.whereby.com/' . $roomKey . '?role=host&token=mock-' . bin2hex(random_bytes(16)),
        ];
    }
}
