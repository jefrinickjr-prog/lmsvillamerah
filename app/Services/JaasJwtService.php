<?php

namespace App\Services;

use App\Models\LiveStreamSession;
use App\Models\User;
use RuntimeException;

class JaasJwtService
{
    public function configurationFor(LiveStreamSession $session, User $user, bool $moderator): array
    {
        $domain = trim((string) config('services.jaas.domain', '8x8.vc'));
        $appId = trim((string) config('services.jaas.app_id'));
        $keyId = trim((string) config('services.jaas.key_id'));
        $privateKey = $this->privateKey();

        if ($domain === '' || $appId === '' || $keyId === '' || $privateKey === '') {
            throw new RuntimeException('JaaS belum dikonfigurasi. Lengkapi JAAS_APP_ID, JAAS_KEY_ID, dan private key pada environment server.');
        }

        $room = 'villa-merah-lms-'.$session->id;
        $now = now()->timestamp;
        $expiresAt = max($now + 300, $session->ends_at->copy()->addMinutes(15)->timestamp);
        $payload = [
            'aud' => 'jitsi',
            'iss' => 'chat',
            'sub' => $appId,
            'room' => $room,
            'nbf' => $now - 10,
            'exp' => $expiresAt,
            'context' => [
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => '',
                    'moderator' => $moderator ? 'true' : 'false',
                ],
                'features' => [
                    'livestreaming' => false,
                    'recording' => false,
                    'transcription' => false,
                    'outbound-call' => false,
                ],
                'room' => ['regex' => false],
            ],
        ];

        return [
            'domain' => $domain,
            'appId' => $appId,
            'room' => $room,
            'roomName' => $appId.'/'.$room,
            'jwt' => $this->encode($payload, $keyId, $privateKey),
            'scriptUrl' => 'https://'.$domain.'/'.$appId.'/external_api.js',
        ];
    }

    private function privateKey(): string
    {
        $encoded = trim((string) config('services.jaas.private_key'));
        if ($encoded !== '') {
            $decoded = base64_decode($encoded, true);
            if ($decoded === false) {
                throw new RuntimeException('JAAS_PRIVATE_KEY bukan base64 yang valid.');
            }

            return $decoded;
        }

        $path = trim((string) config('services.jaas.private_key_path'));
        if ($path === '') {
            return '';
        }

        if (! is_readable($path)) {
            throw new RuntimeException('File JAAS_PRIVATE_KEY_PATH tidak dapat dibaca oleh aplikasi.');
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Private key JaaS gagal dibaca. Periksa path dan izin file.');
        }

        return $this->normalizePrivateKey($contents);
    }

    private function encode(array $payload, string $keyId, string $privateKey): string
    {
        $key = @openssl_pkey_get_private($this->normalizePrivateKey($privateKey));
        if ($key === false) {
            throw new RuntimeException(
                'Private key JaaS tidak valid. Gunakan file private key PEM yang diawali BEGIN PRIVATE KEY atau BEGIN RSA PRIVATE KEY, bukan file .pub.'
            );
        }

        $header = ['alg' => 'RS256', 'kid' => $keyId, 'typ' => 'JWT'];
        $segments = [
            $this->base64Url(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64Url(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);

        if (! @openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Private key JaaS tidak valid atau gagal digunakan untuk menandatangani JWT.');
        }

        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function normalizePrivateKey(string $privateKey): string
    {
        $privateKey = preg_replace('/^\xEF\xBB\xBF/', '', trim($privateKey)) ?? trim($privateKey);

        // Mendukung nilai environment yang menyimpan line break sebagai "\n".
        if (! str_contains($privateKey, "\n") && str_contains($privateKey, '\\n')) {
            $privateKey = str_replace('\\n', "\n", $privateKey);
        }

        return $privateKey;
    }
}
