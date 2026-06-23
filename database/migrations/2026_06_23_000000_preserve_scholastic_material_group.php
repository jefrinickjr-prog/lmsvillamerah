<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materials') || ! Schema::hasColumn('materials', 'program_type')) {
            return;
        }

        DB::table('materials')
            ->where(function ($query) {
                $query
                    ->whereRaw('LOWER(title) LIKE ?', ['%skolastik%'])
                    ->orWhereRaw('LOWER(content) LIKE ?', ['%skolastik%']);
            })
            ->update([
                'program_type' => 'skolastik',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
