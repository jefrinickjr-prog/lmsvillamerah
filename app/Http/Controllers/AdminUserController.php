<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $admins = User::whereIn('role', ['admin', 'teacher'])
            ->with('approver')
            ->latest()
            ->get();

        return view('admin-users.index', compact('admins'));
    }

    public function create()
    {
        $this->authorizeSuperAdmin();

        return view('admin-users.create');
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:admin,teacher'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'program_type' => 'gambar',
            'video_accesses' => ['gambar'],
            'email_verified_at' => now(),
            'approved_at' => $data['role'] === 'teacher' ? now() : null,
            'approved_by' => null,
        ]);

        return redirect()
            ->route('admin-users.index')
            ->with('success', $data['role'] === 'teacher' ? 'Akun guru/mentor berhasil dibuat dan langsung aktif.' : 'Akun admin berhasil dibuat dan menunggu persetujuan super admin.');
    }

    public function approve(User $user)
    {
        $this->authorizeSuperAdmin();

        abort_unless($user->role === 'admin', 404);

        if ($user->approved_at === null) {
            $user->approveAsAdmin(Auth::user());
        }

        return redirect()
            ->route('admin-users.index')
            ->with('success', 'Akun admin '.$user->name.' sudah disetujui dan bisa login.');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(Auth::user()?->role === 'super_admin', 403);
    }
}
