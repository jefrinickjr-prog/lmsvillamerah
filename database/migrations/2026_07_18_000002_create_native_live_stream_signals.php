<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_sessions', function (Blueprint $table) {
            $table->text('meeting_url')->nullable()->change();
            $table->dateTime('started_at')->nullable()->after('ends_at');
            $table->foreignId('started_by')->nullable()->after('started_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('live_stream_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->json('payload');
            $table->timestamps();
            $table->index(['live_stream_session_id', 'to_user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_signals');
        Schema::table('live_stream_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('started_by');
            $table->dropColumn('started_at');
            $table->text('meeting_url')->nullable(false)->change();
        });
    }
};
