<?php
namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_mutation_is_recorded_and_visible_to_admin(): void
    {
        $admin=User::factory()->create(['role'=>'admin','approved_at'=>now()]);
        $this->actingAs($admin)->put(route('profile.update'), ['name'=>'Admin Baru','email'=>$admin->email])->assertRedirect();
        $this->assertDatabaseHas('activity_logs',['user_id'=>$admin->id,'module'=>'Profil','event'=>'memperbarui','status_code'=>302]);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Admin Baru memperbarui data Profil.')->assertSee('Aktivitas Sistem Terbaru');
    }

    public function test_student_dashboard_displays_visual_learning_summary(): void
    {
        $student=User::factory()->create(['role'=>'student','student_code'=>'TEST-0001']);
        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('Ruang Belajar Saya')->assertSee('Materi Tersedia')->assertSee('Aktivitas Belajar')->assertSee('TEST-0001');
    }
}
