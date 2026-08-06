<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'attachment_path')) {
            return;
        }

        DB::table('tasks')
            ->whereNotNull('attachment_path')
            ->orderBy('id')
            ->pluck('attachment_path')
            ->filter()
            ->unique()
            ->each(function (string $path): void {
                if (Storage::disk('local')->exists($path) || ! Storage::disk('public')->exists($path)) {
                    return;
                }

                if (! Storage::disk('local')->put($path, Storage::disk('public')->get($path))) {
                    throw new RuntimeException('Gagal memindahkan lampiran tugas ke storage privat: '.$path);
                }

                Storage::disk('public')->delete($path);
            });
    }

    public function down(): void
    {
        // Lampiran yang sudah diprivatkan tidak boleh dipublikasikan kembali.
    }
};
