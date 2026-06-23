<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $data = [
            'name' => 'Super Admin',
            'email' => 'spadmin@vilmer.com',
            'password' => Hash::make('spadmin123'),
            'role' => 'super_admin',
            'program_type' => 'gambar',
            'email_verified_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('users', 'video_accesses')) {
            $data['video_accesses'] = json_encode(['gambar', 'skolastik']);
        }

        if (Schema::hasColumn('users', 'approved_at')) {
            $data['approved_at'] = now();
        }

        $existing = DB::table('users')->where('email', 'spadmin@vilmer.com')->first();

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($data);

            return;
        }

        DB::table('users')->insert($data + ['created_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('email', 'spadmin@vilmer.com')->delete();
        }
    }
};
