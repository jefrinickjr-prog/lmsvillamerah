<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_sessions', function (Blueprint $table) {
            $table->string('whereby_meeting_id')->nullable()->after('meeting_url');
            $table->text('whereby_host_url')->nullable()->after('whereby_meeting_id');
        });
    }

    public function down(): void
    {
        Schema::table('live_stream_sessions', function (Blueprint $table) {
            $table->dropColumn(['whereby_meeting_id', 'whereby_host_url']);
        });
    }
};
