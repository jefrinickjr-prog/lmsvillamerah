<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('branch')->nullable()->after('student_class');
            $table->string('academic_year')->nullable()->after('branch');
            $table->string('student_code')->nullable()->unique()->after('academic_year');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['student_code']);
            $table->dropColumn(['branch', 'academic_year', 'student_code']);
        });
    }
};
