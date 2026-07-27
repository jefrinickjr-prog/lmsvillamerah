<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_sessions', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'whereby_meeting_id', 'whereby_host_url']);
        });
    }

    public function down(): void
    {
        Schema::table('live_stream_sessions', function (Blueprint $table) {
            $table->text('meeting_url')->nullable();
            $table->string('whereby_meeting_id')->nullable();
            $table->text('whereby_host_url')->nullable();
        });
    }
};
