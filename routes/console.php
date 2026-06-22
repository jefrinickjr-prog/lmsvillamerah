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

    if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'video_accesses')) {
        Schema::table('users', function (Blueprint $table) {
            $table->json('video_accesses')->nullable()->after('program_type');
        });
        $this->line('Added users.video_accesses');
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

    if (
        Schema::hasTable('classrooms')
        && Schema::hasTable('materials')
        && ! Schema::hasTable('classroom_material')
    ) {
        Schema::create('classroom_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'material_id']);
        });
        $this->line('Added classroom_material pivot table');
    }

    if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'task_type')) {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('task_type')->default('assignment')->after('material_id');
        });
        $this->line('Added tasks.task_type');
    }

    if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'attachment_path')) {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('description');
        });
        $this->line('Added tasks.attachment_path');
    }

    if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'questions')) {
        Schema::table('tasks', function (Blueprint $table) {
            $table->json('questions')->nullable()->after('attachment_path');
        });
        $this->line('Added tasks.questions');
    }

    if (Schema::hasTable('users') && Schema::hasColumn('users', 'program_type')) {
        DB::table('users')->whereNull('program_type')->orWhere('program_type', '')->update(['program_type' => 'gambar']);
    }

    if (Schema::hasTable('users') && Schema::hasColumn('users', 'video_accesses')) {
        DB::table('users')
            ->where('role', 'student')
            ->where(function ($query) {
                $query->whereNull('video_accesses')->orWhere('video_accesses', '');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($student) {
                DB::table('users')->where('id', $student->id)->update([
                    'video_accesses' => json_encode(User::defaultVideoAccesses($student->program_type ?? 'gambar', $student->student_class ?? null)),
                ]);
            });

        DB::table('users')
            ->where('role', 'student')
            ->whereRaw('LOWER(TRIM(student_class)) = ?', ['sr gold'])
            ->update([
                'video_accesses' => json_encode(['gambar', 'skolastik']),
                'updated_at' => now(),
            ]);
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

    if (
        Schema::hasTable('classroom_material')
        && Schema::hasTable('materials')
        && Schema::hasColumn('materials', 'classroom_id')
    ) {
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
