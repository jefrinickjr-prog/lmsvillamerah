<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('program_type')->default('gambar')->after('role');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('program_type')->default('gambar')->after('id');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->string('program_type')->default('gambar')->after('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });
    }
};
