<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $studentIds = DB::table('classroom_enrollments')
            ->where('status', 'active')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('student_id');

        foreach ($studentIds as $studentId) {
            $activeEnrollments = DB::table('classroom_enrollments')
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->orderByDesc('joined_at')
                ->orderByDesc('id')
                ->get(['id']);

            $oldIds = $activeEnrollments->skip(1)->pluck('id');
            if ($oldIds->isNotEmpty()) {
                DB::table('classroom_enrollments')
                    ->whereIn('id', $oldIds)
                    ->update([
                        'status' => 'transferred',
                        'left_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Riwayat kelas lama tetap tersimpan; status aktif ganda tidak dipulihkan.
    }
};
