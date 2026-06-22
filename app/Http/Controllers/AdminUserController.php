<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $admins = User::where('role', 'admin')
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
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'program_type' => 'gambar',
            'video_accesses' => ['gambar'],
            'email_verified_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return redirect()
            ->route('admin-users.index')
            ->with('success', 'Akun admin berhasil dibuat dan menunggu persetujuan super admin.');
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
