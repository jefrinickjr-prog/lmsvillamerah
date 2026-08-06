<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('materials', 'sort_order')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('program_type');
            });

            DB::table('materials')->orderBy('id')->get(['id'])->each(function ($material) {
                DB::table('materials')->where('id', $material->id)->update(['sort_order' => $material->id]);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('materials', 'sort_order')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
