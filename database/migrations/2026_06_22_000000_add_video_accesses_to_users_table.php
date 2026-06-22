<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('video_accesses')->nullable()->after('program_type');
        });

        DB::table('users')
            ->where('role', 'student')
            ->orderBy('id')
            ->get()
            ->each(function ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'video_accesses' => json_encode(User::defaultVideoAccesses($user->program_type ?? 'gambar', $user->student_class ?? null)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('video_accesses');
        });
    }
};
