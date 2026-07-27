<?php

namespace App\Services;

use App\Models\LiveStreamSession;
use App\Models\User;
use RuntimeException;

class JitsiJwtService
{
    public function configured(): bool
    {
        return filled(config('jitsi.app_id'))
            && filled(config('jitsi.key_id'))
            && ($this->privateKeyPath() !== null || filled(config('jitsi.private_key_base64')));
    }

    public function create(User $user, LiveStreamSession $session, string $room, bool $isModerator): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Kredensial Jitsi as a Service belum lengkap.');
        }

        $now = now()->timestamp;
        $expiresAt = max($now + 300, $session->ends_at->copy()->addMinutes(30)->timestamp);
        $expiresAt = min($expiresAt, $now + ((int) config('jitsi.max_token_hours', 6) * 3600));

        return $this->encode([
            'aud' => 'jitsi',
            'iss' => 'chat',
            'sub' => config('jitsi.app_id'),
            'room' => $room,
            'nbf' => $now - 10,
            'exp' => $expiresAt,
            'context' => [
                'features' => [
                    'livestreaming' => false,
                    'recording' => false,
                    'transcription' => false,
                    'outbound-call' => false,
                ],
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'moderator' => $isModerator,
                ],
            ],
        ]);
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => 'RS256', 'kid' => config('jitsi.key_id'), 'typ' => 'JWT'];
        $unsigned = $this->base64Url(json_encode($header, JSON_UNESCAPED_SLASHES))
            .'.'.$this->base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $privateKey = openssl_pkey_get_private($this->privateKey());

        if ($privateKey === false || ! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Private key JaaS tidak valid atau JWT gagal ditandatangani.');
        }

        return $unsigned.'.'.$this->base64Url($signature);
    }

    private function privateKey(): string
    {
        if ($path = $this->privateKeyPath()) {
            $key = file_get_contents($path);
            if ($key === false) {
                throw new RuntimeException('Private key JaaS tidak dapat dibaca.');
            }

            return $key;
        }

        $decoded = base64_decode((string) config('jitsi.private_key_base64'), true);
        if ($decoded === false) {
            throw new RuntimeException('JITSI_PRIVATE_KEY_BASE64 bukan Base64 yang valid.');
        }

        return $decoded;
    }

    private function privateKeyPath(): ?string
    {
        $path = trim((string) config('jitsi.private_key_path'));

        return $path !== '' && is_file($path) ? $path : null;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
