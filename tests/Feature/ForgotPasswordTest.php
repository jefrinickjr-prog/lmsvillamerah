<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendingNotifications;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Lupa kata sandi');
    }

    public function test_user_can_request_password_reset_link(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'role' => 'student',
        ]);

        Notification::fake();

        $response = $this->from('/login')->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_view_reset_password_page_with_token(): void
    {
        $response = $this->get('/reset-password/test-token?email=student@example.com');

        $response->assertStatus(200);
        $response->assertSee('Atur ulang kata sandi');
    }
}
