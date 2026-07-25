<?php

namespace Tests\Unit;

use App\Models\LiveStreamSession;
use App\Models\User;
use App\Services\JaasJwtService;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class JaasJwtServiceTest extends TestCase
{
    public function test_invalid_private_key_becomes_a_clear_configuration_error(): void
    {
        Config::set('services.jaas', [
            'domain' => '8x8.vc',
            'app_id' => 'test-app',
            'key_id' => 'test-app/test-key',
            'private_key' => base64_encode('ssh-rsa this-is-a-public-key'),
            'private_key_path' => null,
        ]);

        $session = new LiveStreamSession([
            'ends_at' => now()->addHour(),
        ]);
        $session->id = 10;
        $user = new User([
            'name' => 'Pengajar',
            'email' => 'teacher@example.test',
        ]);
        $user->id = 5;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bukan file .pub');

        app(JaasJwtService::class)->configurationFor($session, $user, true);
    }
}
