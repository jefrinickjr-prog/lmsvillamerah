<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('delivery_mode', 10)->default('offline')->after('program_type');
        });

        DB::table('users')
            ->where('role', 'student')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('classrooms')
                    ->where('classrooms.delivery_mode', 'online')
                    ->whereColumn('classrooms.program_type', 'users.program_type')
                    ->whereRaw('LOWER(TRIM(classrooms.title)) = LOWER(TRIM(users.student_class))')
                    ->whereRaw('LOWER(TRIM(classrooms.branch)) = LOWER(TRIM(users.branch))');
            })
            ->update(['delivery_mode' => 'online']);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('delivery_mode'));
    }
};
