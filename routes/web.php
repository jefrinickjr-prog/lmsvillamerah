<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentManagementController;
use App\Http\Controllers\StudentPageController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : view('auth.login');
});

// Simple auth (minimal, without Breeze)
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.post');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $role = Auth::user()->role ?? 'student';

        if ($role === 'admin' || $role === 'super_admin') {
            return app(AdminController::class)->dashboard();
        }

        if ($role === 'teacher') {
            return app(TeacherController::class)->dashboard();
        }

        return app(StudentController::class)->dashboard();
    })->name('dashboard');

    Route::resource('classrooms', ClassroomController::class)->only(['index','create','store','edit','update','destroy']);
    Route::resource('students', StudentManagementController::class)->only(['index','edit','update']);
    Route::resource('materials', MaterialController::class)->only(['index','create','store','edit','update','destroy']);
    Route::resource('tasks', TaskController::class)->only(['index','show','create','store']);

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register'])->name('register.post');

    Route::get('/absensi-siswa', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::post('/absensi-siswa', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::get('/penilaian', [StudentPageController::class, 'grades'])->name('student.grades');
    Route::get('/rekap-absensi', [StudentPageController::class, 'attendance'])->name('student.attendance');
    Route::get('/laporan', [StudentPageController::class, 'reports'])->name('student.reports');
});

// Public helper route
Route::get('/health', function () { return 'ok'; });
