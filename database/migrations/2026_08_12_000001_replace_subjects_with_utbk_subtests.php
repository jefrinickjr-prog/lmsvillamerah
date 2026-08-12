<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SUBTESTS = [
        'Penalaran Matematika', 'Penalaran Umum', 'Pengetahuan dan Pemahaman Umum',
        'Pemahaman Bacaan dan Menulis', 'Pengetahuan Kuantitatif',
        'Literasi dalam Bahasa Indonesia', 'Literasi dalam Bahasa Inggris',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $mathematics = DB::table('subjects')->where('name', 'Matematika')->first();
            $primary = DB::table('subjects')->where('name', self::SUBTESTS[0])->first();

            if (! $primary && $mathematics) {
                DB::table('subjects')->where('id', $mathematics->id)->update(['name' => self::SUBTESTS[0], 'updated_at' => $now]);
                $primaryId = $mathematics->id;
            } else {
                $primaryId = $primary?->id ?? DB::table('subjects')->insertGetId(['name' => self::SUBTESTS[0], 'created_at' => $now, 'updated_at' => $now]);
            }

            foreach (array_slice(self::SUBTESTS, 1) as $name) {
                DB::table('subjects')->updateOrInsert(['name' => $name], ['updated_at' => $now, 'created_at' => $now]);
            }

            $obsoleteIds = DB::table('subjects')->whereNotIn('name', self::SUBTESTS)->pluck('id');
            if ($obsoleteIds->isNotEmpty()) {
                DB::table('questions')->whereIn('subject_id', $obsoleteIds)->update(['subject_id' => $primaryId]);
                DB::table('exams')->whereIn('subject_id', $obsoleteIds)->update(['subject_id' => $primaryId]);
                DB::table('topics')->whereIn('subject_id', $obsoleteIds)->update(['subject_id' => $primaryId]);
                DB::table('subjects')->whereIn('id', $obsoleteIds)->delete();
            }
        });
    }

    public function down(): void
    {
        DB::table('subjects')->where('name', self::SUBTESTS[0])->update(['name' => 'Matematika', 'updated_at' => now()]);
        DB::table('subjects')->whereIn('name', array_slice(self::SUBTESTS, 1))->delete();
    }
};
