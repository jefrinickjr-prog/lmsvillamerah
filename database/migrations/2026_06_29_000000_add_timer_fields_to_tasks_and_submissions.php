<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'duration_minutes')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedSmallInteger('duration_minutes')->nullable()->after('due_at');
            });
        }

        if (Schema::hasTable('submissions') && ! Schema::hasColumn('submissions', 'started_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->timestamp('started_at')->nullable()->after('answers');
            });
        }

        if (Schema::hasTable('submissions') && ! Schema::hasColumn('submissions', 'submitted_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->timestamp('submitted_at')->nullable()->after('started_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'submitted_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('submitted_at');
            });
        }

        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'started_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('started_at');
            });
        }

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'duration_minutes')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('duration_minutes');
            });
        }
    }
};
