<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('class_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_category_id')->constrained()->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_capacity')->default(20);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['program_category_id', 'name']);
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('classrooms', function (Blueprint $table): void {
            $table->foreignId('class_program_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('branch')->constrained()->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->string('section_name')->default('Umum')->after('title');
            $table->unsignedSmallInteger('capacity')->default(20)->after('section_name');
            $table->boolean('is_active')->default(true)->after('capacity');
        });

        Schema::create('classroom_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->timestamps();
            $table->index(['classroom_id', 'day_of_week']);
        });

        Schema::create('classroom_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['classroom_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        $now = now();
        foreach (User::PROGRAM_TYPES as $code => $name) {
            DB::table('program_categories')->insert([
                'code' => $code,
                'name' => $name,
                'is_active' => true,
                'sort_order' => $code === 'gambar' ? 10 : 20,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (User::STUDENT_CLASSES as $categoryCode => $programs) {
            $categoryId = DB::table('program_categories')->where('code', $categoryCode)->value('id');
            foreach ($programs as $index => $name) {
                DB::table('class_programs')->insert([
                    'program_category_id' => $categoryId,
                    'code' => Str::upper(Str::slug($name, '-')),
                    'name' => $name,
                    'default_capacity' => 20,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (User::BRANCHES as $name) {
            DB::table('branches')->insert([
                'code' => collect(explode(' ', $name))->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode(''),
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $periods = DB::table('users')->whereNotNull('academic_year')->distinct()->pluck('academic_year')->filter();
        if ($periods->isEmpty()) $periods = collect([User::currentAcademicYear()]);
        foreach ($periods as $period) {
            DB::table('academic_periods')->insert([
                'code' => $period,
                'name' => $period,
                'is_active' => true,
                'is_default' => $period === User::currentAcademicYear(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('classrooms')->orderBy('id')->get()->each(function ($classroom) use ($now): void {
            $programId = DB::table('class_programs')->whereRaw('LOWER(name) = ?', [strtolower(trim($classroom->title))])->value('id');
            $branchId = DB::table('branches')->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $classroom->branch))])->value('id');
            $periodId = DB::table('academic_periods')->where('is_default', true)->value('id') ?: DB::table('academic_periods')->value('id');
            DB::table('classrooms')->where('id', $classroom->id)->update([
                'class_program_id' => $programId,
                'branch_id' => $branchId,
                'academic_period_id' => $periodId,
                'section_name' => 'Umum',
                'capacity' => 20,
                'is_active' => true,
                'updated_at' => $now,
            ]);
        });

        DB::table('users')->where('role', 'student')->orderBy('id')->get()->each(function ($student) use ($now): void {
            $matches = DB::table('classrooms')
                ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim((string) $student->student_class))])
                ->whereRaw('LOWER(TRIM(branch)) = ?', [strtolower(trim((string) $student->branch))])
                ->whereNull('deleted_at')
                ->pluck('id');
            if ($matches->count() === 1) {
                DB::table('classroom_enrollments')->insert([
                    'classroom_id' => $matches->first(),
                    'student_id' => $student->id,
                    'status' => 'active',
                    'joined_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_enrollments');
        Schema::dropIfExists('classroom_schedules');
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('academic_period_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('class_program_id');
            $table->dropColumn(['section_name', 'capacity', 'is_active']);
        });
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('class_programs');
        Schema::dropIfExists('program_categories');
    }
};
