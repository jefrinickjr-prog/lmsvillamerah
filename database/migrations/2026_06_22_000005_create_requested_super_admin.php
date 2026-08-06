<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Privileged accounts must be created explicitly with
        // `php artisan lms:create-super-admin`, never with a known default.
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('email', 'spadmin@vilmer.com')->delete();
        }
    }
};
