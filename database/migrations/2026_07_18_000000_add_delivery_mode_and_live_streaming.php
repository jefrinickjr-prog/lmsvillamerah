<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('delivery_mode', 10)->default('offline')->after('program_type');
        });

        Schema::create('live_stream_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('meeting_url');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });

        Schema::create('live_stream_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['live_stream_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_participants');
        Schema::dropIfExists('live_stream_sessions');
        Schema::table('classrooms', fn (Blueprint $table) => $table->dropColumn('delivery_mode'));
    }
};
