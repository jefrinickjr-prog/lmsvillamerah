<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_access_profile_page(): void
    {
        foreach (['student', 'teacher', 'admin', 'super_admin'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'student_class' => $role === 'student' ? 'SR Gold' : null,
            ]);

            $this->actingAs($user)
                ->get(route('profile.show'))
                ->assertOk()
                ->assertSee('Profil')
                ->assertSee($user->name);
        }
    }

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
        ]);
    }

    public function test_user_can_update_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'photo' => UploadedFile::fake()->image('avatar.png'),
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->photo_path);
        Storage::disk('public')->assertExists($user->photo_path);
    }

    public function test_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password-lama'),
        ]);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password-lama',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }
}
