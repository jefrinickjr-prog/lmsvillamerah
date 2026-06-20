<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lms:repair-production {--seed-defaults : Create default admin and base classrooms when missing}', function () {
    $this->info('Repairing LMS production schema and data...');

    if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'program_type')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('program_type')->default('gambar')->after('role');
        });
        $this->line('Added users.program_type');
    }

    if (Schema::hasTable('classrooms') && ! Schema::hasColumn('classrooms', 'program_type')) {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('program_type')->default('gambar')->after('id');
        });
        $this->line('Added classrooms.program_type');
    }

    if (Schema::hasTable('materials') && ! Schema::hasColumn('materials', 'program_type')) {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('program_type')->default('gambar')->after('classroom_id');
        });
        $this->line('Added materials.program_type');
    }

    if (Schema::hasTable('users') && Schema::hasColumn('users', 'program_type')) {
        DB::table('users')->whereNull('program_type')->orWhere('program_type', '')->update(['program_type' => 'gambar']);
    }

    if (Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'program_type')) {
        DB::table('classrooms')->whereNull('program_type')->orWhere('program_type', '')->update(['program_type' => 'gambar']);
    }

    if (
        Schema::hasTable('materials')
        && Schema::hasTable('classrooms')
        && Schema::hasColumn('materials', 'program_type')
        && Schema::hasColumn('classrooms', 'program_type')
    ) {
        DB::table('materials')
            ->leftJoin('classrooms', 'materials.classroom_id', '=', 'classrooms.id')
            ->whereNotNull('classrooms.program_type')
            ->update(['materials.program_type' => DB::raw('classrooms.program_type')]);

        DB::table('materials')->whereNull('program_type')->orWhere('program_type', '')->update(['program_type' => 'gambar']);
    }

    if ($this->option('seed-defaults') && Schema::hasTable('users')) {
        $admin = User::firstOrCreate(
            ['email' => 'admin@lmsvillamerah.sivmi.id'],
            [
                'name' => 'Admin LMS',
                'password' => Hash::make('Admin12345'),
                'role' => 'admin',
                'program_type' => 'gambar',
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->line('Created default admin: admin@lmsvillamerah.sivmi.id / Admin12345');
        }

        if (Schema::hasTable('classrooms')) {
            foreach (User::programTypeOptions() as $programType => $label) {
                foreach (User::studentClassOptions($programType) as $classTitle) {
                    DB::table('classrooms')->updateOrInsert(
                        [
                            'program_type' => $programType,
                            'title' => $classTitle,
                            'branch' => 'Bandung',
                        ],
                        [
                            'description' => 'Kelas default '.$label,
                            'teacher_id' => $admin->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
            $this->line('Ensured default Bandung classrooms for Gambar and Skolastik.');
        }
    }

    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');

    $this->info('Repair complete. Open /materials again.');
})->purpose('Repair production schema/data after LMS deployments');
