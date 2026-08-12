<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->timestamps(); });
        Schema::create('topics', function (Blueprint $table) { $table->id(); $table->foreignId('subject_id')->constrained()->cascadeOnDelete(); $table->foreignId('parent_id')->nullable()->constrained('topics')->nullOnDelete(); $table->string('name'); $table->timestamps(); });
        Schema::create('questions', function (Blueprint $table) {
            $table->id(); $table->foreignId('subject_id')->constrained()->cascadeOnDelete(); $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('class_level')->nullable(); $table->enum('type', ['multiple_choice','essay']); $table->longText('question'); $table->enum('difficulty', ['easy','medium','hard'])->default('medium');
            $table->decimal('score', 8, 2)->default(10); $table->longText('explanation')->nullable(); $table->longText('answer_key')->nullable(); $table->longText('instructions')->nullable(); $table->longText('rubric')->nullable();
            $table->string('status')->default('active'); $table->unsignedSmallInteger('year')->nullable(); $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('question_options', function (Blueprint $table) { $table->id(); $table->foreignId('question_id')->constrained()->cascadeOnDelete(); $table->char('option_label'); $table->longText('option_text'); $table->boolean('is_correct')->default(false); $table->timestamps(); $table->unique(['question_id','option_label']); });
        Schema::create('exams', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->text('description')->nullable(); $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedInteger('duration')->default(60); $table->decimal('passing_grade', 5, 2)->default(75); $table->enum('status', ['draft','published','closed'])->default('draft');
            $table->boolean('randomize_questions')->default(false); $table->boolean('randomize_options')->default(false); $table->enum('show_result', ['immediately','after_exam','manual'])->default('immediately'); $table->boolean('allow_retake')->default(false); $table->boolean('allow_resume')->default(true); $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->timestamps();
        });
        Schema::create('exam_questions', function (Blueprint $table) { $table->foreignId('exam_id')->constrained()->cascadeOnDelete(); $table->foreignId('question_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('order')->default(0); $table->primary(['exam_id','question_id']); });
        Schema::create('attempts', function (Blueprint $table) { $table->id(); $table->foreignId('exam_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_id')->constrained('users')->cascadeOnDelete(); $table->timestamp('started_at'); $table->timestamp('submitted_at')->nullable(); $table->decimal('score', 8, 2)->nullable(); $table->enum('status', ['in_progress','submitted','graded'])->default('in_progress'); $table->timestamps(); $table->index(['exam_id','student_id']); });
        Schema::create('answers', function (Blueprint $table) { $table->id(); $table->foreignId('attempt_id')->constrained()->cascadeOnDelete(); $table->foreignId('question_id')->constrained()->cascadeOnDelete(); $table->longText('answer')->nullable(); $table->decimal('score', 8, 2)->nullable(); $table->text('feedback')->nullable(); $table->timestamps(); $table->unique(['attempt_id','question_id']); });
    }
    public function down(): void { foreach (['answers','attempts','exam_questions','exams','question_options','questions','topics','subjects'] as $table) Schema::dropIfExists($table); }
};
