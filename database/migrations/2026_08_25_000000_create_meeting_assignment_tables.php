<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meeting_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->date('meeting_date');
            $table->dateTime('due_at');
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->timestamps();
        });

        Schema::create('meeting_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('work_path');
            $table->text('note')->nullable();
            $table->dateTime('submitted_at');
            $table->unsignedSmallInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['meeting_assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_submissions');
        Schema::dropIfExists('meeting_assignments');
    }
};
