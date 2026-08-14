<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_teacher_account(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->post(route('admin-users.store'), [
                'name' => 'Mentor Baru',
                'email' => 'mentor@example.com',
                'role' => 'teacher',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin-users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Mentor Baru',
            'email' => 'mentor@example.com',
            'role' => 'teacher',
        ]);
        $this->assertNotNull(User::where('email', 'mentor@example.com')->first()?->approved_at);
    }
}
