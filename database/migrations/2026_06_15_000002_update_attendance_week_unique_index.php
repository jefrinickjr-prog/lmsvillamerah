<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropUnique(['student_id', 'week_start']);
            $table->unique(['classroom_id', 'student_id', 'week_start']);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropUnique(['classroom_id', 'student_id', 'week_start']);
            $table->unique(['student_id', 'week_start']);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
