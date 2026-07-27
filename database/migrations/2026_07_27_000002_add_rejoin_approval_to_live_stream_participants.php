<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_participants', function (Blueprint $table) {
            $table->string('rejoin_status', 20)->nullable()->after('entered_at');
            $table->dateTime('rejoin_requested_at')->nullable()->after('rejoin_status');
            $table->dateTime('rejoin_approved_at')->nullable()->after('rejoin_requested_at');
            $table->foreignId('rejoin_approved_by')->nullable()->after('rejoin_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('live_stream_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejoin_approved_by');
            $table->dropColumn(['rejoin_status', 'rejoin_requested_at', 'rejoin_approved_at']);
        });
    }
};
