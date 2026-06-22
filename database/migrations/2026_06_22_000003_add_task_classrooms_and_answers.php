<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable()->change();
        });

        if (! Schema::hasTable('classroom_task')) {
            Schema::create('classroom_task', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['classroom_id', 'task_id']);
            });
        }

        if (! Schema::hasColumn('submissions', 'answers')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->json('answers')->nullable()->after('content');
            });
        }

        DB::table('tasks')
            ->join('materials', 'tasks.material_id', '=', 'materials.id')
            ->whereNotNull('materials.classroom_id')
            ->orderBy('tasks.id')
            ->get(['tasks.id as task_id', 'materials.classroom_id'])
            ->each(function ($task) {
                DB::table('classroom_task')->updateOrInsert(
                    [
                        'classroom_id' => $task->classroom_id,
                        'task_id' => $task->task_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('submissions', 'answers')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('answers');
            });
        }

        Schema::dropIfExists('classroom_task');

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable(false)->change();
        });
    }
};
