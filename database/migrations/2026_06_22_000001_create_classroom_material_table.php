<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('classroom_material')) {
            Schema::create('classroom_material', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
                $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['classroom_id', 'material_id']);
            });
        }

        DB::table('materials')
            ->whereNotNull('classroom_id')
            ->orderBy('id')
            ->get(['id', 'classroom_id'])
            ->each(function ($material) {
                DB::table('classroom_material')->updateOrInsert(
                    [
                        'classroom_id' => $material->classroom_id,
                        'material_id' => $material->id,
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
        Schema::dropIfExists('classroom_material');
    }
};
