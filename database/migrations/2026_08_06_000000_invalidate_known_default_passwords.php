<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $accounts = DB::table('users')
            ->whereIn('email', ['spadmin@vilmer.com', 'admin@lmsvillamerah.sivmi.id'])
            ->get(['id', 'email', 'password']);

        foreach ($accounts as $account) {
            $knownPassword = $account->email === 'spadmin@vilmer.com' ? 'spadmin123' : 'Admin12345';

            if (Hash::check($knownPassword, $account->password)) {
                DB::table('users')->where('id', $account->id)->update([
                    'password' => Hash::make(Str::random(64)),
                    'remember_token' => null,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Password rotations must never be reversed.
    }
};
